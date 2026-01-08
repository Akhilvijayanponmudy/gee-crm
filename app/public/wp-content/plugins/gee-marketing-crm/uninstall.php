<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Gee_Woo_CRM
 * @author    Akhil Vijayan
 * @license   GPL-2.0+
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load WordPress database functions
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

global $wpdb;

// Ensure we have database access
if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
	return;
}

// Disable foreign key checks temporarily to avoid constraint issues
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

// Drop tables in correct order (child tables first, then parent tables)
// This order ensures foreign key constraints don't prevent deletion
$tables = array(
	// Child tables first (have foreign keys)
	$wpdb->prefix . 'gee_crm_contact_tags',    // References contacts and tags
	$wpdb->prefix . 'gee_crm_campaign_logs',   // References campaigns and contacts
	// Parent tables
	$wpdb->prefix . 'gee_crm_contacts',
	$wpdb->prefix . 'gee_crm_tags',
	$wpdb->prefix . 'gee_crm_segments',
	$wpdb->prefix . 'gee_crm_campaigns',
	$wpdb->prefix . 'gee_crm_email_templates',
);

// Drop each table
foreach ( $tables as $table ) {
	// Use DROP TABLE IF EXISTS to avoid errors if table doesn't exist
	$sql = "DROP TABLE IF EXISTS `{$table}`";
	$wpdb->query( $sql );
}

// Re-enable foreign key checks
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

// Delete plugin options
delete_option( 'gee_woo_crm_settings' );

// Delete any other plugin options that might exist
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gee_woo_crm_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gee_crm_%'" );

// Clear any cached data
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

