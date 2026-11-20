<?php
/**
 * Log of what the agent did through the bridge: kept in one option, last 200 entries.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Audit {

	const OPTION = 'openwp_bridge_audit';
	const MAX    = 200;

	public static function add( $action, $detail = '' ) {
		$log   = get_option( self::OPTION, array() );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'time'   => gmdate( 'c' ),
			'user'   => get_current_user_id(),
			'action' => (string) $action,
			'detail' => is_string( $detail ) ? mb_substr( $detail, 0, 300 ) : wp_json_encode( $detail ),
		);

		update_option( self::OPTION, array_slice( $log, -self::MAX ), false );
	}

	public static function all() {
		$log = get_option( self::OPTION, array() );

		return is_array( $log ) ? array_reverse( $log ) : array();
	}
}
