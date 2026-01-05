<?php

class Gee_Woo_CRM_Activator {

	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sqls = array();

		// Contacts Table
		$table_contacts = $wpdb->prefix . 'gee_crm_contacts';
		$sqls[] = "CREATE TABLE $table_contacts (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) UNSIGNED NULL,
			email varchar(100) NOT NULL UNIQUE,
			first_name varchar(100) DEFAULT '',
			last_name varchar(100) DEFAULT '',
			phone varchar(20) DEFAULT '',
			status varchar(20) DEFAULT 'subscribed',
			source varchar(50) DEFAULT 'manual',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Tags Table
		$table_tags = $wpdb->prefix . 'gee_crm_tags';
		$sqls[] = "CREATE TABLE $table_tags (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(50) NOT NULL,
			slug varchar(50) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		// Contact Tags Pivot Table
		$table_contact_tags = $wpdb->prefix . 'gee_crm_contact_tags';
		$sqls[] = "CREATE TABLE $table_contact_tags (
			contact_id mediumint(9) NOT NULL,
			tag_id mediumint(9) NOT NULL,
			PRIMARY KEY  (contact_id, tag_id)
		) $charset_collate;";

		// Segments Table
		$table_segments = $wpdb->prefix . 'gee_crm_segments';
		$sqls[] = "CREATE TABLE $table_segments (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(50) NOT NULL,
			slug varchar(50) NOT NULL,
			rules_json longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		// Campaigns Table
		$table_campaigns = $wpdb->prefix . 'gee_crm_campaigns';
		$sqls[] = "CREATE TABLE $table_campaigns (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			subject varchar(255) NOT NULL,
			content_html longtext,
			status varchar(20) DEFAULT 'draft',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Campaign Logs Table
		$table_campaign_logs = $wpdb->prefix . 'gee_crm_campaign_logs';
		$sqls[] = "CREATE TABLE $table_campaign_logs (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			campaign_id mediumint(9) NOT NULL,
			contact_id mediumint(9) NOT NULL,
			email varchar(100) NOT NULL,
			sent_at datetime DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) DEFAULT 'sent',
			meta_json text,
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sqls as $sql ) {
			dbDelta( $sql );
		}
	}
}
