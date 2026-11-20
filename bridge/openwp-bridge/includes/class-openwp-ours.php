<?php
/**
 * "Our plugins": products by the makers of OpenWP, one module per product.
 * Shows only what is not installed yet, installs it from WordPress.org in one click, and tells the AI the same list.
 * Nothing is sent to us: the check of what is installed stays on the site.
 */
defined( 'ABSPATH' ) || exit;

class OpenWP_Ours {

	const HIDDEN = 'openwp_bridge_ours_hidden';

	/** One entry per product. 'slug' = WordPress.org slug (empty = not there yet). 'files' = plugin files that mean "installed". */
	public static function catalog() {
		return array(
			'fluxtore'      => array(
				'name'  => 'fluXtore Funnel Builder',
				'what'  => 'Checkout, order bump, one-click upsell and downsell for WooCommerce, with a funnel you can create with AI.',
				'slug'  => 'fluxtore',
				'files' => array( 'fluxtore/fluxtore.php', 'fluxtore-pro/fluxtore-pro.php' ),
				'url'   => 'https://fluxtore.com',
				'when'  => 'woocommerce',
			),
			'funnelzi'      => array(
				'name'  => 'funnelzi',
				'what'  => 'Sales funnels for WordPress with or without WooCommerce: checkout, upsells, and a visual canvas.',
				'slug'  => 'funnelzi',
				'files' => array( 'funnelzi/funnelzi.php', 'funnelzi-pro/funnelzi-pro.php' ),
				'url'   => 'https://funnelzi.com',
				'when'  => '',
			),
			'fluxtore-cart' => array(
				'name'  => 'fluXtore Cart Recovery',
				'what'  => 'Brings back abandoned WooCommerce carts with automatic emails.',
				'slug'  => 'fluxtore-cart-recovery',
				'files' => array( 'fluxtore-cart-recovery/fluxtore-cart-recovery.php' ),
				'url'   => 'https://fluxtore.com',
				'when'  => 'woocommerce',
			),
			'rankaizi'      => array(
				'name'  => 'RankAIZI',
				'what'  => 'SEO, GEO and AEO with an AI agent that fixes the pages for you.',
				'slug'  => '',
				'files' => array( 'rankaizi/rankaizi.php' ),
				'url'   => 'https://rankaizi.com',
				'when'  => '',
			),
		);
	}

	public static function installed( $item ) {
		foreach ( $item['files'] as $f ) {
			if ( file_exists( WP_PLUGIN_DIR . '/' . $f ) ) {
				return true;
			}
		}

		return false;
	}

	public static function active( $item ) {
		foreach ( $item['files'] as $f ) {
			if ( is_plugin_active( $f ) ) {
				return true;
			}
		}

		return false;
	}

	public static function hidden() {
		$h = get_option( self::HIDDEN, array() );

		return is_array( $h ) ? $h : array();
	}

	/** Rows for the page: every product with its state. */
	public static function rows() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$rows = array();

		foreach ( self::catalog() as $key => $item ) {
			$item['key']       = $key;
			$item['installed'] = self::installed( $item );
			$item['active']    = $item['installed'] && self::active( $item );
			$item['fits']      = '' === $item['when'] || ( 'woocommerce' === $item['when'] && class_exists( 'WooCommerce' ) );
			$item['hidden']    = in_array( $key, self::hidden(), true );
			$rows[]            = $item;
		}

		return $rows;
	}

	/** For the AI: only what is NOT installed and was not hidden, with a note on whether it fits this site. */
	public static function for_ai() {
		$out = array();

		foreach ( self::rows() as $r ) {
			if ( $r['installed'] || $r['hidden'] ) {
				continue;
			}

			$out[] = array(
				'name'            => $r['name'],
				'what'            => $r['what'],
				'by'              => 'the makers of OpenWP (AI Next Studio)',
				'fits_this_site'  => $r['fits'],
				'on_wordpress_org' => '' !== $r['slug'],
				'more'            => $r['url'],
			);
		}

		return array(
			'note'    => 'These are products by the makers of OpenWP. Mention one only if it really helps the request, say that it is theirs, and never push it.',
			'plugins' => $out,
		);
	}

	public static function init() {
		add_action( 'admin_post_openwp_bridge_ours_install', array( __CLASS__, 'install' ) );
		add_action( 'admin_post_openwp_bridge_ours_hide', array( __CLASS__, 'hide' ) );
	}

	public static function install() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( 'No permission.' );
		}

		check_admin_referer( 'openwp_bridge_ours_install' );
		$key     = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$catalog = self::catalog();
		$msg     = 'error';

		if ( isset( $catalog[ $key ] ) && '' !== $catalog[ $key ]['slug'] ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$api = plugins_api( 'plugin_information', array( 'slug' => $catalog[ $key ]['slug'], 'fields' => array( 'sections' => false ) ) );

			if ( ! is_wp_error( $api ) ) {
				$up  = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
				$res = $up->install( $api->download_link );

				if ( true === $res || ( ! is_wp_error( $res ) && $res ) ) {
					$file = $up->plugin_info();

					if ( $file && ! is_wp_error( activate_plugin( $file ) ) ) {
						$msg = 'done';
					} else {
						$msg = 'installed';
					}
				}
			}
		}

		wp_safe_redirect( OpenWP_Settings::url( 'ours', array( 'ours' => $msg, 'p' => $key ) ) );
		exit;
	}

	public static function hide() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permission.' );
		}

		check_admin_referer( 'openwp_bridge_ours_hide' );
		$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$h   = self::hidden();

		if ( 'all' === $key ) {
			$h = array_keys( self::catalog() );
		} elseif ( $key && ! in_array( $key, $h, true ) ) {
			$h[] = $key;
		}

		if ( isset( $_POST['show'] ) ) {
			$h = array();
		}

		update_option( self::HIDDEN, $h, false );
		wp_safe_redirect( OpenWP_Settings::url( 'ours' ) );
		exit;
	}
}
