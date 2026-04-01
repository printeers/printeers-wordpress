<?php
/**
 * Plugin Name: Printeers
 * Plugin URI: https://printeers.com
 * Description: Connect your WooCommerce store to Printeers for print-on-demand fulfillment.
 * Version: 1.0.0
 * Author: Printeers
 * Author URI: https://printeers.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: printeers
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRINTEERS_VERSION', '1.0.0' );
define( 'PRINTEERS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRINTEERS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// URLs — hardcoded for production. Override via wp-config.php defines for dev/test.
if ( ! defined( 'PRINTEERS_DASHBOARD_URL' ) ) {
	define( 'PRINTEERS_DASHBOARD_URL', 'https://dashboard.printeers.com' );
}
if ( ! defined( 'PRINTEERS_CALLBACK_URL' ) ) {
	define( 'PRINTEERS_CALLBACK_URL', 'https://woocommerce-callback-api.printeers.com' );
}

require_once PRINTEERS_PLUGIN_DIR . 'includes/Admin.php';
require_once PRINTEERS_PLUGIN_DIR . 'includes/Connect.php';
require_once PRINTEERS_PLUGIN_DIR . 'includes/Api.php';

function printeers_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			include PRINTEERS_PLUGIN_DIR . 'templates/notice-woocommerce-required.php';
		} );
		return;
	}

	Printeers\Admin::init();
	Printeers\Api::init();
}
add_action( 'plugins_loaded', 'printeers_init' );
