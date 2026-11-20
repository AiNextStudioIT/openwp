<?php
/**
 * Adapter for fluXtore: lets the AI list templates and funnels, create a funnel from a template, link a product and read the month's numbers.
 * Only active when fluXtore is. Everything created stays a draft: nothing goes live by itself.
 */
defined( 'ABSPATH' ) || exit;

class OpenWP_Fluxtore {

	public static function active() {
		return class_exists( 'fluXtore\\Core\\Templates\\fluXtore_Template_Packs' ) || class_exists( 'fluXtore_Template_Packs' ) || defined( 'FLUXTORE_PREFIX' );
	}

	private static function packs_class() {
		foreach ( array( 'fluXtore\\Core\\Templates\\fluXtore_Template_Packs', 'fluXtore_Template_Packs' ) as $c ) {
			if ( class_exists( $c ) ) {
				return $c;
			}
		}

		return '';
	}

	public static function tools() {
		$none = (object) array( 'type' => 'object', 'properties' => new stdClass() );

		return array(
			(object) array( 'name' => 'fluxtore_templates', 'description' => 'fluXtore: list the funnel templates (slug, name, builders it works with, and whether it needs Pro). Use a slug and a builder with fluxtore_create_funnel.', 'inputSchema' => $none ),
			(object) array( 'name' => 'fluxtore_funnels', 'description' => 'fluXtore: list the funnels with their steps (id, title, type, address) and the product linked to each step.', 'inputSchema' => $none ),
			(object) array(
				'name'        => 'fluxtore_create_funnel',
				'description' => 'fluXtore: create a funnel from a template. Pages are made with the chosen builder. Nothing is published to customers until the owner links a product and shares the checkout.',
				'inputSchema' => (object) array(
					'type'       => 'object',
					'properties' => (object) array(
						'template' => (object) array( 'type' => 'string', 'description' => 'Template slug from fluxtore_templates' ),
						'builder'  => (object) array( 'type' => 'string', 'description' => 'Gutenberg, Elementor, Bricks or Divi (must be one the template supports)' ),
						'name'     => (object) array( 'type' => 'string', 'description' => 'Name of the funnel' ),
					),
					'required'   => array( 'template', 'builder', 'name' ),
				),
			),
			(object) array(
				'name'        => 'fluxtore_set_product',
				'description' => 'fluXtore: link a WooCommerce product to a funnel step (checkout, order bump, upsell or downsell). Use the step id from fluxtore_funnels and a product id.',
				'inputSchema' => (object) array(
					'type'       => 'object',
					'properties' => (object) array(
						'step_id'    => (object) array( 'type' => 'integer', 'description' => 'Step id' ),
						'product_id' => (object) array( 'type' => 'integer', 'description' => 'WooCommerce product id' ),
						'price'      => (object) array( 'type' => 'number', 'description' => 'Optional special price for this step; leave out to keep the product price' ),
					),
					'required'   => array( 'step_id', 'product_id' ),
				),
			),
			(object) array( 'name' => 'fluxtore_stats', 'description' => 'fluXtore: the numbers of this month (net sales, best funnel, funnels online, orders waiting).', 'inputSchema' => $none ),
		);
	}

	public static function has( $name ) {
		return 0 === strpos( $name, 'fluxtore_' ) && in_array( $name, wp_list_pluck( self::tools(), 'name' ), true );
	}

	public static function call( $name, array $args ) {
		if ( ! self::active() ) {
			return new WP_Error( 'openwp_fluxtore', 'fluXtore is not active on this site.' );
		}

		try {
			switch ( $name ) {
				case 'fluxtore_templates':
					$c = self::packs_class();
					$out = array();

					foreach ( $c::all() as $p ) {
						$out[] = array( 'slug' => $p['slug'], 'name' => isset( $p['name'] ) ? $p['name'] : $p['slug'], 'builders' => array_values( (array) $p['builders'] ), 'needs_pro' => ! empty( $p['locked'] ) );
					}

					return $out;

				case 'fluxtore_funnels':
					$out = array();

					foreach ( get_posts( array( 'post_type' => 'fluxtore_flow', 'numberposts' => 100, 'post_status' => 'any' ) ) as $f ) {
						$steps = array();

						foreach ( (array) get_post_meta( $f->ID, FLUXTORE_PREFIX . 'steps', true ) as $sid ) {
							$steps[] = array(
								'step_id' => (int) $sid,
								'title'   => get_the_title( $sid ),
								'type'    => (string) get_post_meta( $sid, FLUXTORE_PREFIX . 'tag', true ),
								'url'     => get_permalink( $sid ),
								'product' => (int) get_post_meta( $sid, FLUXTORE_PREFIX . 'product', true ),
							);
						}

						$out[] = array( 'funnel_id' => $f->ID, 'name' => $f->post_title, 'status' => $f->post_status, 'builder' => (string) get_post_meta( $f->ID, FLUXTORE_PREFIX . 'editor', true ), 'steps' => $steps );
					}

					return $out;

				case 'fluxtore_create_funnel':
					$c       = self::packs_class();
					$flow_id = $c::import( sanitize_key( isset( $args['template'] ) ? $args['template'] : '' ), sanitize_text_field( isset( $args['builder'] ) ? $args['builder'] : '' ), sanitize_text_field( isset( $args['name'] ) ? $args['name'] : '' ) );
					$res     = self::call( 'fluxtore_funnels', array() );

					foreach ( $res as $f ) {
						if ( (int) $f['funnel_id'] === (int) $flow_id ) {
							return array( 'created' => true, 'funnel' => $f, 'next' => 'Link a product to the checkout step with fluxtore_set_product.' );
						}
					}

					return array( 'created' => true, 'funnel_id' => $flow_id );

				case 'fluxtore_set_product':
					$sid = isset( $args['step_id'] ) ? (int) $args['step_id'] : 0;
					$pid = isset( $args['product_id'] ) ? (int) $args['product_id'] : 0;

					if ( 'fluxtore_step' !== get_post_type( $sid ) || ! $pid || 'product' !== get_post_type( $pid ) ) {
						return new WP_Error( 'openwp_fluxtore', 'Step or product not found.' );
					}

					$model = new \fluXtore\Core\Models\fluXtore_Step();
					$price = isset( $args['price'] ) && '' !== $args['price'] ? (float) $args['price'] : 0;
					$model->update_product( $sid, $pid, $price ? 'custom' : 'original', $price, '' );

					return array( 'ok' => true, 'step_id' => $sid, 'product_id' => $pid, 'price' => $price ? $price : 'product price' );

				case 'fluxtore_stats':
					return \fluXtore\Core\Admin\fluXtore_Dashboard::data();
			}
		} catch ( \Throwable $e ) {
			return new WP_Error( 'openwp_fluxtore', $e->getMessage() );
		}

		return new WP_Error( 'openwp_fluxtore', 'Unknown fluXtore tool.' );
	}
}
