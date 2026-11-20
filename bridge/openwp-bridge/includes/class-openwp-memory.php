<?php
/**
 * Notes of the agent about this site, kept on the site itself (one option), so any computer sees the same notes.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Memory {

	const OPTION = 'openwp_bridge_memory';

	private static function load() {
		$d = get_option( self::OPTION, array() );

		return is_array( $d ) ? $d : array();
	}

	public static function write( $key, $note ) {
		if ( '' === (string) $key || '' === (string) $note ) {
			return new WP_Error( 'openwp_bad_payload', 'key and note are required.' );
		}

		$d         = self::load();
		$d[ $key ] = array( 'note' => (string) $note, 'updated' => gmdate( 'c' ) );
		update_option( self::OPTION, $d, false );

		return array( 'saved' => $key, 'updated' => $d[ $key ]['updated'] );
	}

	public static function read( $key = '' ) {
		$d = self::load();

		if ( '' !== (string) $key ) {
			return isset( $d[ $key ] ) ? array_merge( array( 'key' => $key ), $d[ $key ] ) : new WP_Error( 'openwp_no_memory', 'No memory with key "' . $key . '".' );
		}

		$out = array();

		foreach ( $d as $k => $v ) {
			$out[] = array( 'key' => $k, 'updated' => $v['updated'], 'preview' => mb_substr( $v['note'], 0, 120 ) );
		}

		return $out;
	}

	public static function search( $q ) {
		$q   = strtolower( (string) $q );
		$out = array();

		foreach ( self::load() as $k => $v ) {
			if ( false !== strpos( strtolower( $k ), $q ) || false !== strpos( strtolower( $v['note'] ), $q ) ) {
				$out[] = array( 'key' => $k, 'updated' => $v['updated'], 'note' => $v['note'] );
			}
		}

		return $out;
	}

	public static function delete( $key ) {
		$d = self::load();

		if ( ! isset( $d[ $key ] ) ) {
			return new WP_Error( 'openwp_no_memory', 'No memory with key "' . $key . '".' );
		}

		unset( $d[ $key ] );
		update_option( self::OPTION, $d, false );

		return array( 'deleted' => $key );
	}
}
