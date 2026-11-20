<?php
/**
 * Skills shipped with the plugin (the same Markdown files as the OpenWP server): list, read, and match to the active plugins and theme.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Skills {

	private static function dir() {
		return OPENWP_BRIDGE_DIR . 'skills/';
	}

	private static function parse( $file ) {
		$raw  = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$meta = array();
		$body = $raw;

		if ( preg_match( '/^---\R(.*?)\R---\R?(.*)$/s', $raw, $m ) ) {
			foreach ( preg_split( '/\R/', $m[1] ) as $line ) {
				$i = strpos( $line, ':' );

				if ( $i > 0 ) {
					$meta[ trim( substr( $line, 0, $i ) ) ] = trim( substr( $line, $i + 1 ) );
				}
			}

			$body = trim( $m[2] );
		}

		return array( $meta, $body );
	}

	public static function all() {
		$out = array();

		foreach ( (array) glob( self::dir() . '*.md' ) as $file ) {
			list( $meta ) = self::parse( $file );
			$out[]        = array(
				'name'    => isset( $meta['name'] ) ? $meta['name'] : basename( $file, '.md' ),
				'title'   => isset( $meta['title'] ) ? $meta['title'] : '',
				'status'  => isset( $meta['status'] ) ? $meta['status'] : 'unverified',
				'detects' => array_filter( array_map( 'trim', explode( ',', isset( $meta['detects'] ) ? $meta['detects'] : '' ) ) ),
			);
		}

		return $out;
	}

	public static function get( $name ) {
		$file = self::dir() . sanitize_file_name( $name ) . '.md';

		if ( ! is_file( $file ) ) {
			return new WP_Error( 'openwp_no_skill', 'Skill not found: "' . $name . '". Call get_skill without a name to list them.' );
		}

		list( $meta, $body ) = self::parse( $file );

		return array(
			'name'    => $name,
			'title'   => isset( $meta['title'] ) ? $meta['title'] : '',
			'status'  => isset( $meta['status'] ) ? $meta['status'] : 'unverified',
			'tested'  => isset( $meta['tested'] ) ? $meta['tested'] : '',
			'content' => $body,
		);
	}

	/** Skills that match the active plugins and theme of this site. */
	public static function detect() {
		$plugins = array();

		foreach ( (array) get_option( 'active_plugins', array() ) as $p ) {
			$plugins[] = strtolower( preg_replace( '/\.php$/', '', explode( '/', $p )[0] ) );
		}

		$theme  = wp_get_theme();
		$themes = array_filter( array( strtolower( $theme->get_stylesheet() ), strtolower( $theme->get_template() ) ) );
		$match  = array();

		foreach ( self::all() as $s ) {
			foreach ( $s['detects'] as $d ) {
				$parts = explode( ':', $d, 2 );
				$hit   = 'core' === $d
					|| ( 'plugin' === $parts[0] && isset( $parts[1] ) && in_array( strtolower( $parts[1] ), $plugins, true ) )
					|| ( 'theme' === $parts[0] && isset( $parts[1] ) && in_array( strtolower( $parts[1] ), $themes, true ) );

				if ( $hit ) {
					$match[] = array( 'name' => $s['name'], 'title' => $s['title'], 'status' => $s['status'] );
					break;
				}
			}
		}

		return array(
			'active_theme'    => $theme->get_stylesheet(),
			'matching_skills' => $match,
			'next'            => 'Read the matching skills with get_skill before changing layouts or SEO data.',
		);
	}
}
