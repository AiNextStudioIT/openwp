<?php
/**
 * Tools > OpenWP Bridge: what the bridge is, the Developer mode switch and the log.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Settings {

	const PAGE = 'openwp-bridge';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_openwp_bridge_dev', array( __CLASS__, 'save' ) );
		add_action( 'admin_notices', array( __CLASS__, 'banner' ) );
	}

	public static function menu() {
		add_management_page( 'OpenWP Bridge', 'OpenWP Bridge', 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	/** A warning on every admin page while Developer mode is on. */
	public static function banner() {
		if ( ! current_user_can( 'manage_options' ) || ! OpenWP_Dev::enabled() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>OpenWP Bridge: Developer mode is ON.</strong> Your AI agent can run code on this site. ';
		echo '<a href="' . esc_url( admin_url( 'tools.php?page=' . self::PAGE ) ) . '">Turn it off</a>.</p></div>';
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		check_admin_referer( 'openwp_bridge_dev' );

		$choice = isset( $_POST['dev'] ) ? sanitize_key( wp_unslash( $_POST['dev'] ) ) : 'off';

		if ( 'off' === $choice ) {
			OpenWP_Dev::turn_off();
		} elseif ( ! OpenWP_Dev::forbidden_for_good() ) {
			$hours = isset( $_POST['hours'] ) ? (int) $_POST['hours'] : 1;
			OpenWP_Dev::turn_on( in_array( $hours, array( 0, 1, 8, 24 ), true ) ? $hours : 1 );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE . '&done=1' ) );
		exit;
	}

	public static function assets( $hook ) {
		if ( 'tools_page_' . self::PAGE !== $hook ) {
			return;
		}

		$url = plugins_url( 'assets/', dirname( __FILE__ ) );
		wp_enqueue_style( 'openwp-admin', $url . 'openwp-admin.css', array(), OPENWP_BRIDGE_VERSION );
		wp_enqueue_script( 'openwp-admin', $url . 'openwp-admin.js', array(), OPENWP_BRIDGE_VERSION, true );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$on        = OpenWP_Dev::enabled();
		$left      = OpenWP_Dev::seconds_left();
		$last      = OpenWP_Presence::last();
		$user      = $last ? get_userdata( (int) $last['user'] ) : null;
		$status    = OpenWP_Rest::status();
		$connected = OpenWP_Presence::connected();

		require OPENWP_BRIDGE_DIR . 'views/page.php';
	}
}
