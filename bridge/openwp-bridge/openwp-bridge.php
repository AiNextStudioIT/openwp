<?php
/**
 * Plugin Name:       OpenWP Bridge
 * Description:       Optional companion of the OpenWP MCP server. Lets your AI agent read and edit page builder layouts (Elementor, Bricks, Divi, Gutenberg) and SEO fields, with a backup before every change. Includes a Developer mode (off by default) for staging sites.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AI Next Studio
 * License:           MIT
 * Text Domain:       openwp-bridge
 *
 * Not meant for the WordPress.org directory: Developer mode can run code, by design.
 */

defined( 'ABSPATH' ) || exit;

define( 'OPENWP_BRIDGE_VERSION', '0.1.0' );
define( 'OPENWP_BRIDGE_DIR', plugin_dir_path( __FILE__ ) );

require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-audit.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-backup.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-builders.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-meta.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-dev.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-rest.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-presence.php';
require_once OPENWP_BRIDGE_DIR . 'includes/class-openwp-settings.php';

OpenWP_Rest::init();
OpenWP_Presence::init();
OpenWP_Settings::init();
