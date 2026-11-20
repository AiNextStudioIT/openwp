<?php
/**
 * Reads and writes the layout of a page for Elementor, Bricks, Divi and Gutenberg.
 * Every write keeps a copy of the previous content first (see OpenWP_Backup).
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Builders {

	const BRICKS_AREAS = array(
		'content' => '_bricks_page_content_2',
		'header'  => '_bricks_page_header_2',
		'footer'  => '_bricks_page_footer_2',
	);

	/** elementor | bricks | divi | gutenberg | classic */
	public static function detect( $post_id ) {
		if ( 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return 'elementor';
		}

		if ( 'bricks' === get_post_meta( $post_id, '_bricks_editor_mode', true ) ) {
			return 'bricks';
		}

		if ( 'on' === get_post_meta( $post_id, '_et_pb_use_builder', true ) ) {
			return 'divi';
		}

		$content = (string) get_post_field( 'post_content', $post_id, 'raw' );

		return false !== strpos( $content, '<!-- wp:' ) ? 'gutenberg' : 'classic';
	}

	public static function read( $post_id, $area = 'content', $force = '' ) {
		$builder = $force ? $force : self::detect( $post_id );

		switch ( $builder ) {
			case 'elementor':
				$raw  = get_post_meta( $post_id, '_elementor_data', true );
				$data = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : array();

				return array(
					'builder' => 'elementor',
					'version' => get_post_meta( $post_id, '_elementor_version', true ),
					'data'    => is_array( $data ) ? $data : array(),
				);

			case 'bricks':
				$key  = isset( self::BRICKS_AREAS[ $area ] ) ? self::BRICKS_AREAS[ $area ] : self::BRICKS_AREAS['content'];
				$data = get_post_meta( $post_id, $key, true );

				return array(
					'builder' => 'bricks',
					'area'    => $area,
					'data'    => is_array( $data ) ? $data : array(),
				);

			default:
				return array(
					'builder' => $builder,
					'content' => (string) get_post_field( 'post_content', $post_id, 'raw' ),
				);
		}
	}

	/**
	 * @param array $payload builder (optional), area (Bricks), data (Elementor/Bricks: list of elements) or content (Divi, Gutenberg).
	 * @return array|WP_Error
	 */
	public static function write( $post_id, array $payload, $note = '' ) {
		$builder = ! empty( $payload['builder'] ) ? sanitize_key( $payload['builder'] ) : self::detect( $post_id );
		$area    = ! empty( $payload['area'] ) ? sanitize_key( $payload['area'] ) : 'content';

		if ( 'elementor' === $builder || 'bricks' === $builder ) {
			$data = isset( $payload['data'] ) ? $payload['data'] : null;

			if ( is_string( $data ) ) {
				$data = json_decode( $data, true );
			}

			$check = self::validate_elements( $builder, $data );

			if ( is_wp_error( $check ) ) {
				return $check;
			}

			$before = self::read( $post_id, $area, $builder );
			$id     = OpenWP_Backup::add( $post_id, $builder, $area, 'elementor' === $builder ? wp_json_encode( $before['data'] ) : $before['data'], $note );

			self::apply( $post_id, $builder, $area, $data );
		} elseif ( 'divi' === $builder || 'gutenberg' === $builder || 'classic' === $builder ) {
			if ( ! isset( $payload['content'] ) || ! is_string( $payload['content'] ) ) {
				return new WP_Error( 'openwp_bad_payload', 'Send "content" as a string.', array( 'status' => 400 ) );
			}

			$id = OpenWP_Backup::add( $post_id, $builder, 'content', (string) get_post_field( 'post_content', $post_id, 'raw' ), $note );

			self::apply( $post_id, $builder, 'content', $payload['content'] );
		} else {
			return new WP_Error( 'openwp_unknown_builder', 'Unknown builder: ' . $builder, array( 'status' => 400 ) );
		}

		OpenWP_Audit::add( 'write_builder', array( 'post' => $post_id, 'builder' => $builder, 'backup' => $id ) );

		return array(
			'ok'      => true,
			'builder' => $builder,
			'backup'  => $id,
			'link'    => get_permalink( $post_id ),
		);
	}

	public static function restore( $post_id, $backup_id ) {
		$backup = OpenWP_Backup::get( $post_id, $backup_id );

		if ( ! $backup ) {
			return new WP_Error( 'openwp_no_backup', 'Backup not found.', array( 'status' => 404 ) );
		}

		$value = $backup['value'];

		// The restore itself keeps a copy of what is there now, so it can be undone.
		if ( 'elementor' === $backup['builder'] || 'bricks' === $backup['builder'] ) {
			$data = 'elementor' === $backup['builder'] ? json_decode( (string) $value, true ) : $value;
			$res  = self::write( $post_id, array( 'builder' => $backup['builder'], 'area' => $backup['area'], 'data' => $data ), 'before restoring backup ' . $backup_id );
		} else {
			$res = self::write( $post_id, array( 'builder' => $backup['builder'], 'content' => (string) $value ), 'before restoring backup ' . $backup_id );
		}

		if ( ! is_wp_error( $res ) ) {
			$res['restored'] = (int) $backup_id;
		}

		return $res;
	}

	/** Writes the new value and clears the caches of the builder. */
	private static function apply( $post_id, $builder, $area, $value ) {
		switch ( $builder ) {
			case 'elementor':
				// Elementor keeps the JSON as a slashed string: encode once, slash once.
				update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $value ) ) );
				update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
				delete_post_meta( $post_id, '_elementor_css' );
				delete_post_meta( $post_id, '_elementor_element_cache' );

				if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
					try {
						\Elementor\Plugin::$instance->files_manager->clear_cache();
					} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
						// The page still works: Elementor rebuilds its files on the next visit.
					}
				}
				break;

			case 'bricks':
				$key = isset( self::BRICKS_AREAS[ $area ] ) ? self::BRICKS_AREAS[ $area ] : self::BRICKS_AREAS['content'];
				update_post_meta( $post_id, $key, $value );
				update_post_meta( $post_id, '_bricks_editor_mode', 'bricks' );
				break;

			default:
				wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_slash( $value ) ) );

				if ( 'divi' === $builder ) {
					update_post_meta( $post_id, '_et_pb_use_builder', 'on' );
				}
		}

		clean_post_cache( $post_id );
	}

	private static function validate_elements( $builder, $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'openwp_bad_payload', 'Send "data" as the list of elements (an array).', array( 'status' => 400 ) );
		}

		$need = 'elementor' === $builder ? array( 'id', 'elType' ) : array( 'id', 'name' );

		foreach ( $data as $i => $el ) {
			foreach ( $need as $k ) {
				if ( ! is_array( $el ) || ! isset( $el[ $k ] ) ) {
					return new WP_Error( 'openwp_bad_element', 'Element ' . $i . ' has no "' . $k . '". The layout was not saved.', array( 'status' => 400 ) );
				}
			}
		}

		return true;
	}
}
