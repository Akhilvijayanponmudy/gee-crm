<?php
/**
 * Plugin Name: Gee Woo CRM
 * Plugin URI:  https://example.com
 * Description: A minimal CRM for WooCommerce with layout similar to FluentCRM.
 * Version:     1.0.0
 * Author:      Antigravity
 * Author URI:  https://google.com
 * Text Domain: gee-woo-crm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GEE_WOO_CRM_VERSION', '1.0.0' );
define( 'GEE_WOO_CRM_PATH', plugin_dir_path( __FILE__ ) );
define( 'GEE_WOO_CRM_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_gee_woo_crm() {
	require_once GEE_WOO_CRM_PATH . 'includes/class-gee-woo-crm-activator.php';
	Gee_Woo_CRM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_gee_woo_crm() {
    // Optional: cleanup tasks
}

register_activation_hook( __FILE__, 'activate_gee_woo_crm' );
register_deactivation_hook( __FILE__, 'deactivate_gee_woo_crm' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once GEE_WOO_CRM_PATH . 'includes/class-gee-woo-crm-admin.php';
require_once GEE_WOO_CRM_PATH . 'includes/class-gee-woo-crm-ajax.php';

function run_gee_woo_crm() {
	$plugin_admin = new Gee_Woo_CRM_Admin();
	$plugin_admin->run();
	
	$plugin_ajax = new Gee_Woo_CRM_Ajax();
	$plugin_ajax->init();
}
run_gee_woo_crm();
