<?php
/**
 * Tools that other plugins register with the WordPress Abilities API (WooCommerce, Elementor, Bricks, SEO plugins...),
 * offered through the same MCP address. For some areas the site owner chooses who does the job:
 * OpenWP (our tools), the plugin's own tools, or both. The choice is made in Tools > OpenWP Bridge.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Abilities {

	const OPTION = 'openwp_bridge_prefer';
	const PREFIX = 'ability__';

	/** area => array( ability namespace, OpenWP tools that overlap, default choice ) */
	public static function areas() {
		return array(
			'elementor'   => array( 'label' => 'Elementor layouts', 'ns' => 'elementor', 'ours' => array( 'get_builder_data', 'set_builder_data' ), 'builder' => 'elementor', 'default' => 'openwp' ),
			'bricks'      => array( 'label' => 'Bricks layouts', 'ns' => 'bricks', 'ours' => array( 'get_builder_data', 'set_builder_data' ), 'builder' => 'bricks', 'default' => 'openwp' ),
			'woocommerce' => array( 'label' => 'WooCommerce', 'ns' => 'woocommerce', 'ours' => array( 'get_woo_products', 'create_woo_product', 'update_woo_product', 'get_woo_orders', 'update_woo_order', 'get_woo_customers' ), 'builder' => '', 'default' => 'both' ),
		);
	}

	public static function supported() {
		return function_exists( 'wp_get_abilities' );
	}

	/** openwp | vendor | both */
	public static function choice( $area ) {
		$areas = self::areas();
		$saved = get_option( self::OPTION, array() );
		$v     = is_array( $saved ) && isset( $saved[ $area ] ) ? $saved[ $area ] : $areas[ $area ]['default'];

		return in_array( $v, array( 'openwp', 'vendor', 'both' ), true ) ? $v : 'openwp';
	}

	public static function save( array $post ) {
		$out = array();

		foreach ( array_keys( self::areas() ) as $area ) {
			$v           = isset( $post[ $area ] ) ? sanitize_key( $post[ $area ] ) : '';
			$out[ $area ] = in_array( $v, array( 'openwp', 'vendor', 'both' ), true ) ? $v : self::areas()[ $area ]['default'];
		}

		update_option( self::OPTION, $out, false );
		OpenWP_Audit::add( 'prefer_saved', wp_json_encode( $out ) );
	}

	/** Namespace => number of abilities, without the ones we hide on purpose. */
	public static function namespaces() {
		$out = array();

		if ( ! self::supported() ) {
			return $out;
		}

		foreach ( wp_get_abilities() as $ab ) {
			$ns         = explode( '/', $ab->get_name() )[0];
			$out[ $ns ] = isset( $out[ $ns ] ) ? $out[ $ns ] + 1 : 1;
		}

		ksort( $out );

		return $out;
	}

	private static function area_of_ns( $ns ) {
		foreach ( self::areas() as $key => $a ) {
			if ( $a['ns'] === $ns ) {
				return $key;
			}
		}

		return '';
	}

	/** MCP tool definitions for the abilities that should be offered. */
	public static function tools() {
		$tools = array();

		if ( ! self::supported() ) {
			return $tools;
		}

		foreach ( wp_get_abilities() as $ab ) {
			$name = $ab->get_name();
			$ns   = explode( '/', $name )[0];

			if ( 'mcp-adapter' === $ns ) {
				continue; // The adapter's own helpers would only confuse the agent.
			}

			$area = self::area_of_ns( $ns );

			if ( $area && 'openwp' === self::choice( $area ) ) {
				continue;
			}

			$schema = $ab->get_input_schema();
			$schema = is_array( $schema ) && $schema ? $schema : array( 'type' => 'object' );

			if ( empty( $schema['properties'] ) ) {
				$schema['properties'] = new stdClass();
			}

			$tools[] = (object) array(
				'name'        => self::PREFIX . str_replace( '/', '__', $name ),
				'description' => '[' . $ns . '] ' . $ab->get_label() . '. ' . $ab->get_description(),
				'inputSchema' => (object) $schema,
			);
		}

		return $tools;
	}

	public static function is_ability_tool( $tool ) {
		return 0 === strpos( (string) $tool, self::PREFIX );
	}

	public static function call( $tool, array $args ) {
		$name = preg_replace( '/__/', '/', substr( $tool, strlen( self::PREFIX ) ), 1 );
		$ab   = function_exists( 'wp_get_ability' ) ? wp_get_ability( $name ) : null;

		if ( ! $ab ) {
			return new WP_Error( 'openwp_no_ability', 'Ability not found: ' . $name );
		}

		$ns   = explode( '/', $name )[0];
		$area = self::area_of_ns( $ns );

		if ( $area && 'openwp' === self::choice( $area ) ) {
			return new WP_Error( 'openwp_choice', 'The site owner chose OpenWP\'s own tools for this area. Change it in Tools > OpenWP Bridge.' );
		}

		$input = $args ? $args : null;
		$ok    = $ab->check_permissions( $input );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		if ( true !== $ok ) {
			return new WP_Error( 'openwp_forbidden', 'You are not allowed to run this ability.' );
		}

		OpenWP_Audit::add( 'ability', $name );

		return $ab->execute( $input );
	}

	/** Should one of OUR tools refuse because the owner chose the plugin's own tools? Returns the message or null. */
	public static function refuse_ours( $tool, array $args ) {
		foreach ( self::areas() as $key => $a ) {
			if ( 'vendor' !== self::choice( $key ) || ! in_array( $tool, $a['ours'], true ) ) {
				continue;
			}

			if ( $a['builder'] ) {
				$id = isset( $args['id'] ) ? (int) $args['id'] : 0;

				if ( ! $id || OpenWP_Builders::detect( $id ) !== $a['builder'] ) {
					continue;
				}
			}

			return 'The site owner chose the tools of the plugin itself for "' . $a['label'] . '". Use the tools named ' . self::PREFIX . $a['ns'] . '__... instead (see the tool list).';
		}

		return null;
	}

	/** Our tools to hide from the list because the owner chose the plugin's own (store tools only: builder tools stay, they work for the other builders too). */
	public static function hidden_ours() {
		$hide = array();

		foreach ( self::areas() as $key => $a ) {
			if ( 'vendor' === self::choice( $key ) && ! $a['builder'] ) {
				$hide = array_merge( $hide, $a['ours'] );
			}
		}

		return $hide;
	}
}
