<?php
/**
 * Writes the SEO and builder switch fields that the plain REST API does not expose.
 * Only the keys in the list below are accepted.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Meta {

	/** key => type: text | long | flag | list */
	public static function allowed() {
		$keys = array(
			// Yoast SEO.
			'_yoast_wpseo_title'               => 'text',
			'_yoast_wpseo_metadesc'            => 'long',
			'_yoast_wpseo_meta-robots-noindex' => 'flag',
			'_yoast_wpseo_focuskw'             => 'text',
			// Rank Math.
			'rank_math_title'                  => 'text',
			'rank_math_description'            => 'long',
			'rank_math_focus_keyword'          => 'text',
			'rank_math_robots'                 => 'list',
			// All in One SEO and SEOPress.
			'_aioseo_title'                    => 'text',
			'_aioseo_description'              => 'long',
			'_seopress_titles_title'           => 'text',
			'_seopress_titles_desc'            => 'long',
			// Divi, so a page created from scratch opens in the builder.
			'_et_pb_use_builder'               => 'text',
			'_et_pb_page_layout'               => 'text',
			'_et_pb_show_title'                => 'text',
		);

		return apply_filters( 'openwp_bridge_meta_whitelist', $keys );
	}

	public static function write( $post_id, array $meta ) {
		$allowed = self::allowed();
		$done    = array();

		foreach ( $meta as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				return new WP_Error( 'openwp_meta_not_allowed', 'The field "' . $key . '" is not in the allowed list.', array( 'status' => 403 ) );
			}
		}

		foreach ( $meta as $key => $value ) {
			switch ( $allowed[ $key ] ) {
				case 'long':
					$clean = sanitize_textarea_field( (string) $value );
					break;
				case 'flag':
					$clean = $value ? '1' : '';
					break;
				case 'list':
					$clean = array_values( array_map( 'sanitize_key', (array) $value ) );
					break;
				default:
					$clean = sanitize_text_field( (string) $value );
			}

			update_post_meta( $post_id, $key, $clean );
			$done[ $key ] = $clean;
		}

		OpenWP_Audit::add( 'write_meta', array( 'post' => $post_id, 'keys' => array_keys( $done ) ) );

		return array( 'ok' => true, 'saved' => $done );
	}
}
