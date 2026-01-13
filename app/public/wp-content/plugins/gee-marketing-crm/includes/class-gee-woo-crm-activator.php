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
			unsubscribe_token varchar(64) NULL,
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
	<title>Marketing Email</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5;padding: 20px 0">
		<tr>
			<td align="center">
				<!-- Main Container -->
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff;border-radius: 8px;overflow: hidden;max-width: 600px">
					
					<!-- Header with Gradient -->
					<tr>
						<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding: 50px 30px;text-align: center">
							<h1 style="margin: 0 0 15px 0;color: #ffffff;font-size: 36px;font-weight: 700;line-height: 1.2">🎉 Exclusive Offer for {first_name}!</h1>
							<p style="margin: 0;color: #ffffff;font-size: 20px;opacity: 0.95;font-weight: 300">Don\'t miss out on this limited-time deal</p>
						</td>
					</tr>
					
					<!-- Hero Image Section -->
					<tr>
						<td style="padding: 0;background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);text-align: center;height: 250px;position: relative">
							<div style="padding: 40px 30px;color: #ffffff">
								<h2 style="margin: 0 0 15px 0;font-size: 32px;font-weight: 700">Up to 50% OFF</h2>
								<p style="margin: 0;font-size: 18px;opacity: 0.95">On Selected Items</p>
								<p style="margin: 15px 0 0 0;font-size: 14px;opacity: 0.9">Valid until {current_date}</p>
							</div>
						</td>
					</tr>
					
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px">
							<h2 style="margin: 0 0 20px 0;color: #333333;font-size: 28px;font-weight: 600">Hi {full_name},</h2>
							<p style="margin: 0 0 20px 0;color: #555555;font-size: 16px;line-height: 1.8">
								We\'re excited to offer you an exclusive discount as one of our valued customers! This special promotion is available for a limited time only.
							</p>
							<p style="margin: 0 0 30px 0;color: #555555;font-size: 16px;line-height: 1.8">
								As someone who has been with us since {created_date}, we wanted to make sure you get first access to this amazing deal.
							</p>
							
							<!-- Primary CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0">
								<tr>
									<td align="center">
										<a href="{site_url}" style="padding: 18px 50px;background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);color: #ffffff;text-decoration: none;border-radius: 50px;font-size: 18px;font-weight: 600">Shop Now & Save</a>
									</td>
								</tr>
							</table>
							
							<!-- Urgency Message -->
							<div style="background-color: #fff3cd;border-left: 4px solid #ffc107;padding: 15px 20px;margin: 30px 0;border-radius: 4px">
								<p style="margin: 0;color: #856404;font-size: 14px;font-weight: 600">
									⏰ This offer expires soon! Don\'t wait - claim your discount today.
								</p>
							</div>
							
							<!-- Value Proposition -->
							<div style="margin: 40px 0">
								<h3 style="margin: 0 0 20px 0;color: #333333;font-size: 22px;font-weight: 600">Why Shop With Us?</h3>
								<table width="100%" cellpadding="0" cellspacing="0">
									<tr>
										<td width="50%" style="padding: 15px 10px;vertical-align: top">
											<div style="text-align: center">
												<div style="font-size: 40px;margin-bottom: 10px">🚚</div>
												<p style="margin: 0;color: #333333;font-size: 15px;font-weight: 600">Free Shipping</p>
												<p style="margin: 5px 0 0 0;color: #666666;font-size: 13px">On orders over $50</p>
											</div>
										</td>
										<td width="50%" style="padding: 15px 10px;vertical-align: top">
											<div style="text-align: center">
												<div style="font-size: 40px;margin-bottom: 10px">↩️</div>
												<p style="margin: 0;color: #333333;font-size: 15px;font-weight: 600">Easy Returns</p>
												<p style="margin: 5px 0 0 0;color: #666666;font-size: 13px">30-day return policy</p>
											</div>
										</td>
									</tr>
									<tr>
										<td width="50%" style="padding: 15px 10px;vertical-align: top">
											<div style="text-align: center">
												<div style="font-size: 40px;margin-bottom: 10px">💳</div>
												<p style="margin: 0;color: #333333;font-size: 15px;font-weight: 600">Secure Payment</p>
												<p style="margin: 5px 0 0 0;color: #666666;font-size: 13px">100% secure checkout</p>
											</div>
										</td>
										<td width="50%" style="padding: 15px 10px;vertical-align: top">
											<div style="text-align: center">
												<div style="font-size: 40px;margin-bottom: 10px">⭐</div>
												<p style="margin: 0;color: #333333;font-size: 15px;font-weight: 600">Top Rated</p>
												<p style="margin: 5px 0 0 0;color: #666666;font-size: 13px">5-star customer service</p>
											</div>
										</td>
									</tr>
								</table>
							</div>
							
							<!-- Social Proof -->
							<div style="background-color: #f8f9fa;padding: 25px;border-radius: 8px;margin: 30px 0;text-align: center">
								<p style="margin: 0 0 10px 0;color: #333333;font-size: 16px;font-weight: 600">Join {order_count}+ Happy Customers!</p>
								<p style="margin: 0;color: #666666;font-size: 14px">Our customers have spent over {total_spent} with us. Be part of our growing community!</p>
							</div>
							
							<!-- Secondary CTA -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0 20px 0">
								<tr>
									<td align="center">
										<a href="{site_url}" style="padding: 14px 40px;background-color: #ffffff;color: #667eea;text-decoration: none;border: 2px solid #667eea;border-radius: 50px;font-size: 16px;font-weight: 600">Browse All Products</a>
									</td>
								</tr>
							</table>
							
							<!-- Closing -->
							<p style="margin: 40px 0 0 0;color: #555555;font-size: 16px;line-height: 1.8">
								Thank you for being an amazing customer! We look forward to serving you.
							</p>
							<p style="margin: 20px 0 0 0;color: #555555;font-size: 16px;line-height: 1.8">
								Best regards,<br>
								<strong style="color: #333333">The {site_name} Team</strong>
							</p>
						</td>
					</tr>
					
					<!-- Features Bar -->
					<tr>
						<td style="background-color: #f8f9fa;padding: 30px 40px;border-top: 1px solid #e9ecef">
							<table width="100%" cellpadding="0" cellspacing="0">
								<tr>
									<td width="25%" style="padding: 10px;text-align: center;vertical-align: top">
										<div style="font-size: 32px;margin-bottom: 8px">✓</div>
										<p style="margin: 0;color: #333333;font-size: 13px;font-weight: 600">Free Shipping</p>
									</td>
									<td width="25%" style="padding: 10px;text-align: center;vertical-align: top">
										<div style="font-size: 32px;margin-bottom: 8px">✓</div>
										<p style="margin: 0;color: #333333;font-size: 13px;font-weight: 600">Easy Returns</p>
									</td>
									<td width="25%" style="padding: 10px;text-align: center;vertical-align: top">
										<div style="font-size: 32px;margin-bottom: 8px">✓</div>
										<p style="margin: 0;color: #333333;font-size: 13px;font-weight: 600">24/7 Support</p>
									</td>
									<td width="25%" style="padding: 10px;text-align: center;vertical-align: top">
										<div style="font-size: 32px;margin-bottom: 8px">✓</div>
										<p style="margin: 0;color: #333333;font-size: 13px;font-weight: 600">Secure Checkout</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					
					<!-- Footer -->
					<tr>
						<td style="background-color: #2c3e50;padding: 40px 30px;text-align: center">
							<p style="margin: 0 0 15px 0;color: #ffffff;font-size: 18px;font-weight: 600">{site_name}</p>
							<p style="margin: 0 0 20px 0;color: #bdc3c7;font-size: 14px;line-height: 1.6">
								This email was sent to {email}<br>
								You received this because you are subscribed to our newsletter.
							</p>
							<div style="margin: 25px 0;padding-top: 25px;border-top: 1px solid #34495e">
								<a href="{site_url}" style="color: #3498db;text-decoration: none;margin: 0 15px;font-size: 14px">Visit Website</a>
								<span style="color: #7f8c8d">|</span>
								<a href="{unsubscribe_link}" style="color: #3498db;text-decoration: none;margin: 0 15px;font-size: 14px">Unsubscribe</a>
								<span style="color: #7f8c8d">|</span>
								<a href="{site_url}/contact" style="color: #3498db;text-decoration: none;margin: 0 15px;font-size: 14px">Contact Us</a>
							</div>
							<p style="margin: 20px 0 0 0;color: #95a5a6;font-size: 12px">
								© {current_date} {site_name}. All rights reserved.
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
					'name' => 'Marketing Template',
					'subject' => 'Exclusive Offer for {first_name}!',
					'content_html' => $default_template,
					'description' => 'Default marketing email template with promotional content, CTAs, and product highlights',
					'is_default' => 1
				)
			);
		}

		// Create thank you email template
		$thank_you_exists = $wpdb->get_var( "SELECT id FROM $table_email_templates WHERE name LIKE '%Thank%' OR name LIKE '%thank%'" );
		if ( ! $thank_you_exists ) {
			$thank_you_template = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Thank You for Subscribing</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 30px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 36px; font-weight: 700;">Thank You, {first_name}! 🎉</h1>
							<p style="margin: 15px 0 0 0; color: #ffffff; font-size: 18px; opacity: 0.95;">Welcome to our community</p>
						</td>
					</tr>
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px;">
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Hi {full_name},
							</p>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Thank you for subscribing to our marketing emails! We\'re thrilled to have you join our community.
							</p>
							<p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								You\'ll now receive our latest updates, exclusive offers, special promotions, and valuable content directly in your inbox. We promise to only send you emails that matter and respect your inbox.
							</p>
							<!-- CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">Visit Our Website</a>
									</td>
								</tr>
							</table>
							<!-- Closing -->
							<p style="margin: 40px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								We look forward to sharing amazing content with you!
							</p>
							<p style="margin: 20px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Best regards,<br>
								<strong style="color: #333333;">The {site_name} Team</strong>
							</p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
							<p style="margin: 0 0 15px 0; color: #666666; font-size: 14px; line-height: 1.6;">
								You can <a href="{unsubscribe_link}" style="color: #667eea; text-decoration: underline;">unsubscribe</a> from marketing emails at any time.
							</p>
							<p style="margin: 0; color: #999999; font-size: 12px;">
								This email was sent to {email}
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
					'name' => 'Thank You Template',
					'subject' => 'Thank You for Subscribing!',
					'content_html' => $thank_you_template,
					'description' => 'Default thank you email template sent when contacts subscribe to marketing emails',
					'is_default' => 0
				)
			);
		}
		
		// Create default tag if it doesn't exist
		self::ensure_default_tag();
	}
	
	/**
	 * Ensure default tag exists
	 */
	private static function ensure_default_tag() {
		global $wpdb;
		$table_tags = $wpdb->prefix . 'gee_crm_tags';
		
		// Check if default tag already exists
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is derived from $wpdb->prefix, not user input.
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_tags WHERE slug = %s", 'form-submission' ) );
		
		if ( ! $existing ) {
			// Create default tag
			$wpdb->insert(
				$table_tags,
				array(
					'name' => 'Form Submission',
					'slug' => 'form-submission'
				)
			);
			
			// Set as default form tag in settings
			$settings = get_option( 'gee_woo_crm_settings', array() );
			if ( ! isset( $settings['default_form_tag'] ) || $settings['default_form_tag'] == 0 ) {
				$default_tag_id = $wpdb->insert_id;
				$settings['default_form_tag'] = $default_tag_id;
				update_option( 'gee_woo_crm_settings', $settings );
			}
		}
	}
}
