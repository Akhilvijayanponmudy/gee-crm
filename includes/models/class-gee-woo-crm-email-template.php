<?php

class Gee_Woo_CRM_Email_Template {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_email_templates';
	}

	public function get_templates() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY name ASC" );
	}

	public function get_template( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) );
	}

	public function create_template( $data ) {
		global $wpdb;
		
		$is_default = isset( $data['is_default'] ) && $data['is_default'] ? 1 : 0;
		
		// If setting as default, unset other defaults
		if ( $is_default ) {
			$wpdb->update( $this->table_name, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
		}
		
		$wpdb->insert(
			$this->table_name,
			array(
				'name' => sanitize_text_field( $data['name'] ),
				'subject' => sanitize_text_field( $data['subject'] ),
				'content_html' => wp_kses_post( $data['content_html'] ),
				'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
				'is_default' => $is_default
			)
		);
		
		return $wpdb->insert_id;
	}

	public function update_template( $id, $data ) {
		global $wpdb;
		
		$is_default = isset( $data['is_default'] ) && $data['is_default'] ? 1 : 0;
		
		// If setting as default, unset other defaults
		if ( $is_default ) {
			$wpdb->update( $this->table_name, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
		}
		
		return $wpdb->update(
			$this->table_name,
			array(
				'name' => sanitize_text_field( $data['name'] ),
				'subject' => sanitize_text_field( $data['subject'] ),
				'content_html' => wp_kses_post( $data['content_html'] ),
				'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
				'is_default' => $is_default
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);
	}

	public function get_default_template() {
		global $wpdb;
		return $wpdb->get_row( "SELECT * FROM $this->table_name WHERE is_default = 1 LIMIT 1" );
	}

	public function set_default_template( $id ) {
		global $wpdb;
		// Unset all defaults
		$wpdb->update( $this->table_name, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
		// Set new default
		return $wpdb->update( $this->table_name, array( 'is_default' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	}

	public function delete_template( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Replace template variables with sample data for preview
	 * 
	 * @param string $content Template content with variables
	 * @param string $subject Template subject with variables
	 * @return array Array with 'content' and 'subject' keys
	 */
	public function preview_template( $content, $subject = '' ) {
		// Sample data for preview
		$sample_data = array(
			'{first_name}' => 'John',
			'{last_name}' => 'Doe',
			'{full_name}' => 'John Doe',
			'{email}' => 'john.doe@example.com',
			'{phone}' => '+1 (555) 123-4567',
			'{status}' => 'Subscribed',
			'{source}' => 'WooCommerce',
			'{created_date}' => date( 'F j, Y', strtotime( '-6 months' ) ),
			'{total_spent}' => '$1,250.00',
			'{order_count}' => '15',
			'{last_purchase_date}' => date( 'F j, Y', strtotime( '-2 weeks' ) ),
			'{last_purchase_value}' => '$89.99',
			'{site_name}' => get_bloginfo( 'name' ),
			'{site_url}' => home_url(),
			'{current_date}' => date( 'F j, Y' ),
			'{unsubscribe_link}' => home_url( '/unsubscribe?email=john.doe@example.com' ),
		);

		// Replace variables in content
		$preview_content = str_replace( array_keys( $sample_data ), array_values( $sample_data ), $content );
		
		// Replace variables in subject
		$preview_subject = $subject ? str_replace( array_keys( $sample_data ), array_values( $sample_data ), $subject ) : '';

		return array(
			'content' => $preview_content,
			'subject' => $preview_subject
		);
	}

	/**
	 * Create default templates if they don't exist
	 * This can be called to ensure templates are available
	 */
	public function ensure_default_templates() {
		global $wpdb;
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" );
		if ( ! $table_exists ) {
			return; // Table doesn't exist, activation will create it
		}
		
		// Check if default template exists
		$default_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE is_default = 1" );
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
					<tr>
						<td style="background-color: #4e28a5; padding: 30px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 28px;">Hello {first_name}!</h1>
						</td>
					</tr>
					<tr>
						<td style="padding: 40px 30px;">
							<p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">Dear {full_name},</p>
							<p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">Thank you for being part of our community!</p>
							<p style="margin: 30px 0 0 0; color: #333333; font-size: 16px; line-height: 1.6;">Best regards,<br>The Team</p>
						</td>
					</tr>
					<tr>
						<td style="background-color: #f9f9f9; padding: 20px 30px; text-align: center; border-top: 1px solid #eeeeee;">
							<p style="margin: 0; color: #666666; font-size: 12px;">This email was sent to {email}</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
			
			$wpdb->insert(
				$this->table_name,
				array(
					'name' => 'Default Template',
					'subject' => 'Hello {first_name}!',
					'content_html' => $default_template,
					'description' => 'Default email template - you can edit this template',
					'is_default' => 1
				)
			);
		}
		
		// Check if marketing template exists
		$marketing_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE name = 'Marketing Template'" );
		if ( ! $marketing_exists ) {
			$marketing_template = $this->get_marketing_template_content();
			
			$wpdb->insert(
				$this->table_name,
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

	/**
	 * Get marketing email template content
	 * A professional, conversion-optimized marketing email template
	 */
	public function get_marketing_template_content() {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Marketing Email</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<!-- Main Container -->
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					
					<!-- Header with Gradient -->
					<tr>
						<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 30px; text-align: center;">
							<h1 style="margin: 0 0 15px 0; color: #ffffff; font-size: 36px; font-weight: 700; line-height: 1.2;">🎉 Exclusive Offer for {first_name}!</h1>
							<p style="margin: 0; color: #ffffff; font-size: 20px; opacity: 0.95; font-weight: 300;">Don\'t miss out on this limited-time deal</p>
						</td>
					</tr>
					
					<!-- Hero Image Section -->
					<tr>
						<td style="padding: 0; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); text-align: center; height: 250px; position: relative;">
							<div style="padding: 40px 30px; color: #ffffff;">
								<h2 style="margin: 0 0 15px 0; font-size: 32px; font-weight: 700;">Up to 50% OFF</h2>
								<p style="margin: 0; font-size: 18px; opacity: 0.95;">On Selected Items</p>
								<p style="margin: 15px 0 0 0; font-size: 14px; opacity: 0.9;">Valid until {current_date}</p>
							</div>
						</td>
					</tr>
					
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px;">
							<h2 style="margin: 0 0 20px 0; color: #333333; font-size: 28px; font-weight: 600;">Hi {full_name},</h2>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								We\'re excited to offer you an exclusive discount as one of our valued customers! This special promotion is available for a limited time only.
							</p>
							<p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								As someone who has been with us since {created_date}, we wanted to make sure you get first access to this amazing deal.
							</p>
							
							<!-- Primary CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 18px 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">Shop Now & Save</a>
									</td>
								</tr>
							</table>
							
							<!-- Urgency Message -->
							<div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px 20px; margin: 30px 0; border-radius: 4px;">
								<p style="margin: 0; color: #856404; font-size: 14px; font-weight: 600;">
									⏰ This offer expires soon! Don\'t wait - claim your discount today.
								</p>
							</div>
							
							<!-- Value Proposition -->
							<div style="margin: 40px 0;">
								<h3 style="margin: 0 0 20px 0; color: #333333; font-size: 22px; font-weight: 600;">Why Shop With Us?</h3>
								<table width="100%" cellpadding="0" cellspacing="0">
									<tr>
										<td width="50%" style="padding: 15px 10px; vertical-align: top;">
											<div style="text-align: center;">
												<div style="font-size: 40px; margin-bottom: 10px;">🚚</div>
												<p style="margin: 0; color: #333333; font-size: 15px; font-weight: 600;">Free Shipping</p>
												<p style="margin: 5px 0 0 0; color: #666666; font-size: 13px;">On orders over $50</p>
											</div>
										</td>
										<td width="50%" style="padding: 15px 10px; vertical-align: top;">
											<div style="text-align: center;">
												<div style="font-size: 40px; margin-bottom: 10px;">↩️</div>
												<p style="margin: 0; color: #333333; font-size: 15px; font-weight: 600;">Easy Returns</p>
												<p style="margin: 5px 0 0 0; color: #666666; font-size: 13px;">30-day return policy</p>
											</div>
										</td>
									</tr>
									<tr>
										<td width="50%" style="padding: 15px 10px; vertical-align: top;">
											<div style="text-align: center;">
												<div style="font-size: 40px; margin-bottom: 10px;">💳</div>
												<p style="margin: 0; color: #333333; font-size: 15px; font-weight: 600;">Secure Payment</p>
												<p style="margin: 5px 0 0 0; color: #666666; font-size: 13px;">100% secure checkout</p>
											</div>
										</td>
										<td width="50%" style="padding: 15px 10px; vertical-align: top;">
											<div style="text-align: center;">
												<div style="font-size: 40px; margin-bottom: 10px;">⭐</div>
												<p style="margin: 0; color: #333333; font-size: 15px; font-weight: 600;">Top Rated</p>
												<p style="margin: 5px 0 0 0; color: #666666; font-size: 13px;">5-star customer service</p>
											</div>
										</td>
									</tr>
								</table>
							</div>
							
							<!-- Social Proof -->
							<div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0; text-align: center;">
								<p style="margin: 0 0 10px 0; color: #333333; font-size: 16px; font-weight: 600;">Join {order_count}+ Happy Customers!</p>
								<p style="margin: 0; color: #666666; font-size: 14px;">Our customers have spent over {total_spent} with us. Be part of our growing community!</p>
							</div>
							
							<!-- Secondary CTA -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0 20px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 14px 40px; background-color: #ffffff; color: #667eea; text-decoration: none; border: 2px solid #667eea; border-radius: 50px; font-size: 16px; font-weight: 600;">Browse All Products</a>
									</td>
								</tr>
							</table>
							
							<!-- Closing -->
							<p style="margin: 40px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Thank you for being an amazing customer! We look forward to serving you.
							</p>
							<p style="margin: 20px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Best regards,<br>
								<strong style="color: #333333;">The {site_name} Team</strong>
							</p>
						</td>
					</tr>
					
					<!-- Features Bar -->
					<tr>
						<td style="background-color: #f8f9fa; padding: 30px 40px; border-top: 1px solid #e9ecef;">
							<table width="100%" cellpadding="0" cellspacing="0">
								<tr>
									<td width="25%" style="padding: 10px; text-align: center; vertical-align: top;">
										<div style="font-size: 32px; margin-bottom: 8px;">✓</div>
										<p style="margin: 0; color: #333333; font-size: 13px; font-weight: 600;">Free Shipping</p>
									</td>
									<td width="25%" style="padding: 10px; text-align: center; vertical-align: top;">
										<div style="font-size: 32px; margin-bottom: 8px;">✓</div>
										<p style="margin: 0; color: #333333; font-size: 13px; font-weight: 600;">Easy Returns</p>
									</td>
									<td width="25%" style="padding: 10px; text-align: center; vertical-align: top;">
										<div style="font-size: 32px; margin-bottom: 8px;">✓</div>
										<p style="margin: 0; color: #333333; font-size: 13px; font-weight: 600;">24/7 Support</p>
									</td>
									<td width="25%" style="padding: 10px; text-align: center; vertical-align: top;">
										<div style="font-size: 32px; margin-bottom: 8px;">✓</div>
										<p style="margin: 0; color: #333333; font-size: 13px; font-weight: 600;">Secure Checkout</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					
					<!-- Footer -->
					<tr>
						<td style="background-color: #2c3e50; padding: 40px 30px; text-align: center;">
							<p style="margin: 0 0 15px 0; color: #ffffff; font-size: 18px; font-weight: 600;">{site_name}</p>
							<p style="margin: 0 0 20px 0; color: #bdc3c7; font-size: 14px; line-height: 1.6;">
								This email was sent to {email}<br>
								You received this because you are subscribed to our newsletter.
							</p>
							<div style="margin: 25px 0; padding-top: 25px; border-top: 1px solid #34495e;">
								<a href="{site_url}" style="color: #3498db; text-decoration: none; margin: 0 15px; font-size: 14px;">Visit Website</a>
								<span style="color: #7f8c8d;">|</span>
								<a href="{unsubscribe_link}" style="color: #3498db; text-decoration: none; margin: 0 15px; font-size: 14px;">Unsubscribe</a>
								<span style="color: #7f8c8d;">|</span>
								<a href="{site_url}/contact" style="color: #3498db; text-decoration: none; margin: 0 15px; font-size: 14px;">Contact Us</a>
							</div>
							<p style="margin: 20px 0 0 0; color: #95a5a6; font-size: 12px;">
								© ' . date('Y') . ' {site_name}. All rights reserved.
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	}
}

