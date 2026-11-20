<?php
/**
 * Shows that OpenWP is connected: remembers the last request that came from OpenWP
 * (it sends a User-Agent "OpenWP/<version>") and shows it in the admin bar and in Tools > OpenWP Bridge.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Presence {

	const OPTION   = 'openwp_bridge_last_seen';
	const ACTIVE   = 600; // Seconds: "connected" if seen in the last 10 minutes.
	const THROTTLE = 20;

	public static function init() {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'seen' ), 10, 3 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 100 );
		add_action( 'admin_head', array( __CLASS__, 'css' ) );
		add_action( 'wp_head', array( __CLASS__, 'css' ) );
	}

	/** Every REST call from OpenWP, also the ones to the normal WordPress API. */
	public static function seen( $result, $server, $request ) {
		$ua = $request->get_header( 'user_agent' );

		if ( ! is_string( $ua ) || 0 !== strpos( $ua, 'OpenWP/' ) || ! get_current_user_id() ) {
			return $result;
		}

		$last = get_option( self::OPTION, array() );

		if ( is_array( $last ) && ! empty( $last['time'] ) && time() - (int) $last['time'] < self::THROTTLE && (int) $last['user'] === get_current_user_id() ) {
			return $result;
		}

		update_option(
			self::OPTION,
			array(
				'time'   => time(),
				'user'   => get_current_user_id(),
				'client' => mb_substr( $ua, 0, 60 ),
				'route'  => mb_substr( $request->get_route(), 0, 80 ),
			),
			false
		);

		return $result;
	}

	public static function last() {
		$last = get_option( self::OPTION, array() );

		return is_array( $last ) && ! empty( $last['time'] ) ? $last : null;
	}

	public static function connected() {
		$last = self::last();

		return $last && time() - (int) $last['time'] < self::ACTIVE;
	}

	public static function admin_bar( $bar ) {
		if ( ! current_user_can( 'manage_options' ) || ! self::last() ) {
			return;
		}

		$on = self::connected();

		$bar->add_node(
			array(
				'id'    => 'openwp-status',
				'title' => '<span class="openwp-dot ' . ( $on ? 'on' : 'off' ) . '"></span>OpenWP',
				'href'  => admin_url( 'tools.php?page=openwp-bridge' ),
				'meta'  => array( 'title' => $on ? 'OpenWP is connected' : 'OpenWP not seen in the last 10 minutes' ),
			)
		);
	}

	public static function css() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		echo '<style>#wpadminbar .openwp-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:#8c8f94}#wpadminbar .openwp-dot.on{background:#46b450;box-shadow:0 0 0 3px rgba(70,180,80,.25)}</style>';
	}
}
