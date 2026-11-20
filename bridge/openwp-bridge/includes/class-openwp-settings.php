<?php
/**
 * The OpenWP admin app: a menu with a sidebar and a few simple sections (views/tab-*.php).
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Settings {

	const PAGE = 'openwp-bridge';

	public static function tabs() {
		return array(
			'home'    => array( 'Dashboard', 'dashicons-chart-area' ),
			'connect' => array( 'Connect your AI', 'dashicons-admin-links' ),
			'tools'   => array( 'Plugin tools', 'dashicons-screenoptions' ),
			'copies'  => array( 'Safety copies', 'dashicons-backup' ),
			'memory'  => array( 'Memory', 'dashicons-lightbulb' ),
			'skills'  => array( 'Skills', 'dashicons-welcome-learn-more' ),
			'dev'     => array( 'Developer mode', 'dashicons-editor-code' ),
			'log'     => array( 'Activity', 'dashicons-list-view' ),
		);
	}

	public static function url( $tab = 'home', array $extra = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE, 'tab' => $tab ), $extra ), admin_url( 'admin.php' ) );
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_openwp_bridge_dev', array( __CLASS__, 'save_dev' ) );
		add_action( 'admin_post_openwp_bridge_prefer', array( __CLASS__, 'save_prefer' ) );
		add_action( 'admin_post_openwp_bridge_restore', array( __CLASS__, 'do_restore' ) );
		add_action( 'admin_post_openwp_bridge_forget', array( __CLASS__, 'do_forget' ) );
		add_action( 'wp_ajax_openwp_bridge_create_pw', array( __CLASS__, 'ajax_create_pw' ) );
		add_action( 'admin_notices', array( __CLASS__, 'banner' ) );
	}

	public static function menu() {
		add_menu_page( 'OpenWP', 'OpenWP', 'manage_options', self::PAGE, array( __CLASS__, 'render' ), 'dashicons-superhero-alt', 58 );
	}

	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}

		$url = plugins_url( 'assets/', dirname( __FILE__ ) );
		wp_enqueue_style( 'openwp-admin', $url . 'openwp-admin.css', array(), OPENWP_BRIDGE_VERSION );
		wp_enqueue_script( 'openwp-admin', $url . 'openwp-admin.js', array(), OPENWP_BRIDGE_VERSION, true );
		wp_localize_script( 'openwp-admin', 'openwpBridge', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'openwp_bridge_pw' ) ) );
	}

	/** A warning on every admin page while Developer mode is on. */
	public static function banner() {
		if ( ! current_user_can( 'manage_options' ) || ! OpenWP_Dev::enabled() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>OpenWP: Developer mode is ON.</strong> Your AI agent can run code on this site. ';
		echo '<a href="' . esc_url( self::url( 'dev' ) ) . '">Turn it off</a>.</p></div>';
	}

	private static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		check_admin_referer( $action );
	}

	private static function back( $tab, array $extra = array() ) {
		wp_safe_redirect( self::url( $tab, array_merge( array( 'done' => 1 ), $extra ) ) );
		exit;
	}

	/** Creates an Application Password for the current user and returns it once, so the page can fill it in. */
	public static function ajax_create_pw() {
		check_ajax_referer( 'openwp_bridge_pw', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed.' );
		}

		$uid = get_current_user_id();

		if ( ! class_exists( 'WP_Application_Passwords' ) || ! wp_is_application_passwords_available_for_user( $uid ) ) {
			wp_send_json_error( 'Application Passwords are not available for this user. They need HTTPS, and some security plugins turn them off.' );
		}

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$res  = WP_Application_Passwords::create_new_application_password( $uid, array( 'name' => '' !== $name ? $name : 'OpenWP' ) );

		if ( is_wp_error( $res ) ) {
			wp_send_json_error( $res->get_error_message() );
		}

		OpenWP_Audit::add( 'password_created', '' !== $name ? $name : 'OpenWP' );

		wp_send_json_success( array( 'password' => WP_Application_Passwords::chunk_password( $res[0] ), 'user' => wp_get_current_user()->user_login ) );
	}

	public static function save_dev() {
		self::guard( 'openwp_bridge_dev' );
		$choice = isset( $_POST['dev'] ) ? sanitize_key( wp_unslash( $_POST['dev'] ) ) : 'off';

		if ( 'off' === $choice ) {
			OpenWP_Dev::turn_off();
		} elseif ( ! OpenWP_Dev::forbidden_for_good() ) {
			$hours = isset( $_POST['hours'] ) ? (int) $_POST['hours'] : 1;
			OpenWP_Dev::turn_on( in_array( $hours, array( 0, 1, 8, 24 ), true ) ? $hours : 1 );
		}

		self::back( 'dev' );
	}

	public static function save_prefer() {
		self::guard( 'openwp_bridge_prefer' );
		OpenWP_Abilities::save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		self::back( 'tools' );
	}

	public static function do_restore() {
		self::guard( 'openwp_bridge_restore' );
		$post   = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
		$backup = isset( $_POST['backup'] ) ? (int) $_POST['backup'] : 0;
		$res    = OpenWP_Builders::restore( $post, $backup );

		self::back( 'copies', is_wp_error( $res ) ? array( 'error' => rawurlencode( $res->get_error_message() ) ) : array( 'restored' => $post ) );
	}

	public static function do_forget() {
		self::guard( 'openwp_bridge_forget' );
		OpenWP_Memory::delete( isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '' );
		self::back( 'memory' );
	}

	/** Posts that have safety copies: array of post id => list of backups (without the saved values). */
	public static function posts_with_copies() {
		global $wpdb;

		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", OpenWP_Backup::META ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$out = array();

		foreach ( array_slice( array_unique( array_map( 'intval', (array) $ids ) ), 0, 100 ) as $id ) {
			if ( get_post( $id ) ) {
				$out[ $id ] = OpenWP_Backup::summary( $id );
			}
		}

		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = self::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'home'; // phpcs:ignore WordPress.Security.NonceVerification
		$tab  = isset( $tabs[ $tab ] ) ? $tab : 'home';

		$ctx = array(
			'on'        => OpenWP_Dev::enabled(),
			'left'      => OpenWP_Dev::seconds_left(),
			'last'      => OpenWP_Presence::last(),
			'connected' => OpenWP_Presence::connected(),
			'status'    => OpenWP_Rest::status(),
			'mcp_url'   => rest_url( 'openwp/v1/mcp' ),
		);
		$ctx['user'] = $ctx['last'] ? get_userdata( (int) $ctx['last']['user'] ) : null;

		require OPENWP_BRIDGE_DIR . 'views/layout.php';
	}
}
