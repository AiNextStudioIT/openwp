<?php
/**
 * REST routes of the bridge: /wp-json/openwp/v1/...
 * Authentication is the one WordPress already has (Application Password), and every route checks the rights of that user.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Rest {

	const NS = 'openwp/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$id = array(
			'id' => array(
				'validate_callback' => function ( $value ) {
					return is_numeric( $value );
				},
			),
		);

		register_rest_route( self::NS, '/status', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'status' ), 'permission_callback' => array( __CLASS__, 'can_use' ) ) );

		register_rest_route( self::NS, '/post/(?P<id>\d+)/builder', array(
			array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'read_builder' ), 'permission_callback' => array( __CLASS__, 'can_edit_post' ), 'args' => $id ),
			array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'write_builder' ), 'permission_callback' => array( __CLASS__, 'can_edit_post' ), 'args' => $id ),
		) );
		register_rest_route( self::NS, '/post/(?P<id>\d+)/backups', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'backups' ), 'permission_callback' => array( __CLASS__, 'can_edit_post' ), 'args' => $id ) );
		register_rest_route( self::NS, '/post/(?P<id>\d+)/restore', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'restore' ), 'permission_callback' => array( __CLASS__, 'can_edit_post' ), 'args' => $id ) );
		register_rest_route( self::NS, '/post/(?P<id>\d+)/meta', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'meta' ), 'permission_callback' => array( __CLASS__, 'can_edit_post' ), 'args' => $id ) );

		foreach ( array( 'php', 'sql', 'file' ) as $what ) {
			register_rest_route( self::NS, '/dev/' . $what, array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'dev_' . $what ), 'permission_callback' => array( __CLASS__, 'can_dev' ) ) );
		}

		// MCP over HTTP: the AI client connects straight to this address.
		register_rest_route( self::NS, '/mcp', array( 'methods' => 'POST', 'callback' => array( 'OpenWP_Mcp', 'handle' ), 'permission_callback' => array( __CLASS__, 'can_use' ) ) );

		register_rest_route( self::NS, '/log', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'log' ), 'permission_callback' => array( __CLASS__, 'can_manage' ) ) );
	}

	/* Permissions */

	public static function can_use() {
		return current_user_can( 'edit_posts' );
	}

	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	public static function can_edit_post( WP_REST_Request $r ) {
		return current_user_can( 'edit_post', (int) $r['id'] );
	}

	public static function can_dev() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! OpenWP_Dev::enabled() ) {
			return new WP_Error( 'openwp_dev_off', 'Developer mode is off. The site owner turns it on in Tools > OpenWP Bridge.', array( 'status' => 403 ) );
		}

		return true;
	}

	/* Routes */

	public static function status() {
		return array(
			'bridge'         => OPENWP_BRIDGE_VERSION,
			'wordpress'      => get_bloginfo( 'version' ),
			'builders'       => array(
				'elementor' => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : false,
				'bricks'    => defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : false,
				'divi'      => defined( 'ET_BUILDER_VERSION' ) ? ET_BUILDER_VERSION : ( function_exists( 'et_setup_theme' ) ? true : false ),
			),
			'seo'            => array(
				'yoast'    => defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : false,
				'rankmath' => defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : false,
			),
			'dev_mode'       => OpenWP_Dev::enabled(),
			'dev_seconds_left' => OpenWP_Dev::enabled() ? min( OpenWP_Dev::seconds_left(), 31536000 ) : 0,
			'dev_forbidden'  => OpenWP_Dev::forbidden_for_good(),
		);
	}

	public static function read_builder( WP_REST_Request $r ) {
		$id = (int) $r['id'];

		if ( ! get_post( $id ) ) {
			return new WP_Error( 'openwp_no_post', 'Post not found.', array( 'status' => 404 ) );
		}

		return OpenWP_Builders::read( $id, sanitize_key( (string) $r->get_param( 'area' ) ?: 'content' ), sanitize_key( (string) $r->get_param( 'builder' ) ) );
	}

	public static function write_builder( WP_REST_Request $r ) {
		$id = (int) $r['id'];

		if ( ! get_post( $id ) ) {
			return new WP_Error( 'openwp_no_post', 'Post not found.', array( 'status' => 404 ) );
		}

		return OpenWP_Builders::write( $id, (array) $r->get_json_params(), (string) $r->get_param( 'note' ) );
	}

	public static function backups( WP_REST_Request $r ) {
		return array( 'backups' => OpenWP_Backup::summary( (int) $r['id'] ) );
	}

	public static function restore( WP_REST_Request $r ) {
		return OpenWP_Builders::restore( (int) $r['id'], (int) $r->get_param( 'backup' ) );
	}

	public static function meta( WP_REST_Request $r ) {
		return OpenWP_Meta::write( (int) $r['id'], (array) $r->get_param( 'meta' ) );
	}

	public static function dev_php( WP_REST_Request $r ) {
		return OpenWP_Dev::run_php( (string) $r->get_param( 'code' ) );
	}

	public static function dev_sql( WP_REST_Request $r ) {
		return OpenWP_Dev::run_sql( (string) $r->get_param( 'sql' ), (int) ( $r->get_param( 'limit' ) ?: 200 ) );
	}

	public static function dev_file( WP_REST_Request $r ) {
		return OpenWP_Dev::file( sanitize_key( (string) $r->get_param( 'action' ) ), (string) $r->get_param( 'path' ), $r->get_param( 'content' ) );
	}

	public static function log() {
		return array( 'log' => array_slice( OpenWP_Audit::all(), 0, 100 ) );
	}
}
