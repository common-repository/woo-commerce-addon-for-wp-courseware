<?php
/**
 * Plugin Name: Woo Commmerce Addon for WP Courseware
 * Version:     1.5.0
 * Plugin URI:  http://flyplugins.com
 * Description: The official extension for WP Courseware to add integration for WooCommmerce.
 * Author:      Fly Plugins
 * Author URI:  http://flyplugins.com
 * License:     GPL v2 or later
 * Text Domain: wpcw-wc-addon
 * Domain Path: /languages
 *
 * @package WPCW_WC_Addon
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Constants.
define( 'WPCW_WC_ADDON_VERSION', '1.5.0' );

/**
 * WP Courseware WooCommerce Addon.
 *
 * @since 1.3.0
 */
function _wpcw_wc_addon() {
	// Plugin Path.
	$plugin_path = plugin_dir_path( __FILE__ );

	// Required Files.
	require_once $plugin_path . 'includes/functions.php';
	require_once $plugin_path . 'includes/class-wpcw-wc-admin.php';
	require_once $plugin_path . 'includes/class-wpcw-wc-members.php';
	require_once $plugin_path . 'includes/class-wpcw-wc-membership.php';
	require_once $plugin_path . 'includes/class-wpcw-wc-menu-courses.php';
	require_once $plugin_path . 'includes/class-wpcw-wc-addon.php';
	require_once $plugin_path . 'includes/deprecated.php';

	// Load Plugin Textdomain.
	load_plugin_textdomain( 'wpcw-wc-addon', false, basename( dirname( __FILE__ ) ) . '/languages' );

	// Initalize Add-On.
	WPCW_WC_Addon::init();
}
add_action( 'plugins_loaded', '_wpcw_wc_addon' );
