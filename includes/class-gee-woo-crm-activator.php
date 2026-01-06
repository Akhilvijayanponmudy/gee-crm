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
			marketing_consent tinyint(1) DEFAULT 0,
			consent_date datetime NULL,
			source varchar(50) DEFAULT 'manual',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY marketing_consent (marketing_consent)
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
			template_id mediumint(9) NULL,
			targeting_json text,
			status varchar(20) DEFAULT 'draft',
			scheduled_at datetime NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime NULL,
			total_recipients int(11) DEFAULT 0,
			total_sent int(11) DEFAULT 0,
			total_failed int(11) DEFAULT 0,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
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

		// Email Templates Table
		$table_email_templates = $wpdb->prefix . 'gee_crm_email_templates';
		$sqls[] = "CREATE TABLE $table_email_templates (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			subject varchar(255) NOT NULL,
			content_html longtext,
			description text,
			is_default tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY is_default (is_default)
		) $charset_collate;";


		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sqls as $sql ) {
			dbDelta( $sql );
		}

		// Create default email templates
		$default_exists = $wpdb->get_var( "SELECT id FROM $table_email_templates WHERE is_default = 1" );
		if ( ! $default_exists ) {
			$default_template = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Email Template</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
					<!-- Header -->
					<tr>
						<td style="background-color: #4e28a5; padding: 30px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 28px;">Hello {first_name}!</h1>
						</td>
					</tr>
					<!-- Content -->
					<tr>
						<td style="padding: 40px 30px;">
							<p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
								Dear {full_name},
							</p>
							<p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
								Thank you for being part of our community! We wanted to reach out and share some exciting updates with you.
							</p>
							<p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
								We appreciate your continued support and look forward to serving you better.
							</p>
							<p style="margin: 30px 0 0 0; color: #333333; font-size: 16px; line-height: 1.6;">
								Best regards,<br>
								The Team
							</p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td style="background-color: #f9f9f9; padding: 20px 30px; text-align: center; border-top: 1px solid #eeeeee;">
							<p style="margin: 0; color: #666666; font-size: 12px;">
								This email was sent to {email}<br>
								You can update your preferences or unsubscribe at any time.
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
			
			$wpdb->insert(
				$table_email_templates,
				array(
					'name' => 'Default Template',
					'subject' => 'Hello {first_name}!',
					'content_html' => $default_template,
					'description' => 'Default email template - you can edit this template',
					'is_default' => 1
				)
			);
		}

		// Create marketing email template
		$marketing_exists = $wpdb->get_var( "SELECT id FROM $table_email_templates WHERE name = 'Marketing Template'" );
		if ( ! $marketing_exists ) {
			require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';
			$template_model = new Gee_Woo_CRM_Email_Template();
			$marketing_template = $template_model->get_marketing_template_content();
			
			$wpdb->insert(
				$table_email_templates,
				array(
					'name' => 'Marketing Template',
					'subject' => 'Special Offer for {first_name} - Limited Time!',
					'content_html' => $marketing_template,
					'description' => 'Professional marketing email template with CTA button, product showcase, and features section',
					'is_default' => 0
				)
			);
		}
	}
}
