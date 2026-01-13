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
			'{unsubscribe_link}' => home_url( '/wp-json/gee-crm/v1/unsubscribe?email=john.doe@example.com&token=example_token_here' ),
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
		
		// Check if default template exists and update it if needed
		$default_template = $wpdb->get_row( "SELECT * FROM $this->table_name WHERE is_default = 1" );
		if ( ! $default_template ) {
			// Create new default template
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
				$this->table_name,
				array(
					'name' => 'Marketing Template',
					'subject' => 'Exclusive Offer for {first_name}!',
					'content_html' => $default_template,
					'description' => 'Default marketing email template with promotional content, CTAs, and product highlights',
					'is_default' => 1
				)
			);
		} else {
			// Update existing default template with new content if it's the old simple template
			// Check if it's the old template by checking if it has the new marketing template structure
			if ( strpos( $default_template->content_html, 'Exclusive Offer for' ) === false && strpos( $default_template->content_html, 'Up to 50% OFF' ) === false ) {
				// Update to new template
				$new_default_template = '<!DOCTYPE html>
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
				
				$wpdb->update(
					$this->table_name,
					array(
						'subject' => 'Exclusive Offer for {first_name}!',
						'content_html' => $new_default_template,
					),
					array( 'id' => $default_template->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			}
		}
		
		// Check if thank you template exists
		$thank_you_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE name LIKE '%Thank%' OR name LIKE '%thank%'" );
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
				$this->table_name,
				array(
					'name' => 'Thank You Template',
					'subject' => 'Thank You for Subscribing!',
					'content_html' => $thank_you_template,
					'description' => 'Default thank you email template sent when contacts subscribe to marketing emails',
					'is_default' => 0
				)
			);
		}
		
		// Create 4 additional product-focused templates
		$this->ensure_product_templates();
	}
	
	/**
	 * Ensure 4 product-focused email templates exist
	 */
	private function ensure_product_templates() {
		global $wpdb;
		
		// Template 1: Product Launch
		$launch_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE name = 'Product Launch Template'" );
		if ( ! $launch_exists ) {
			$wpdb->insert(
				$this->table_name,
				array(
					'name' => 'Product Launch Template',
					'subject' => '🎉 Introducing Our New Product - {first_name}!',
					'content_html' => $this->get_product_launch_template(),
					'description' => 'Perfect for announcing new product launches with excitement and clear call-to-action',
					'is_default' => 0
				)
			);
		}
		
		// Template 2: Flash Sale
		$sale_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE name = 'Flash Sale Template'" );
		if ( ! $sale_exists ) {
			$wpdb->insert(
				$this->table_name,
				array(
					'name' => 'Flash Sale Template',
					'subject' => '⚡ Flash Sale Alert - {first_name}! Limited Time Only',
					'content_html' => $this->get_flash_sale_template(),
					'description' => 'Urgent flash sale template with countdown feel and strong discount messaging',
					'is_default' => 0
				)
			);
		}
		
		// Template 3: Product Recommendation
		$recommend_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE name = 'Product Recommendation Template'" );
		if ( ! $recommend_exists ) {
			$wpdb->insert(
				$this->table_name,
				array(
					'name' => 'Product Recommendation Template',
					'subject' => 'Recommended for You, {first_name}!',
					'content_html' => $this->get_product_recommendation_template(),
					'description' => 'Personalized product recommendations based on customer preferences and purchase history',
					'is_default' => 0
				)
			);
		}
		
		// Template 4: Abandoned Cart Follow-up
		$cart_exists = $wpdb->get_var( "SELECT id FROM $this->table_name WHERE name = 'Abandoned Cart Template'" );
		if ( ! $cart_exists ) {
			$wpdb->insert(
				$this->table_name,
				array(
					'name' => 'Abandoned Cart Template',
					'subject' => 'You left something in your cart, {first_name}!',
					'content_html' => $this->get_abandoned_cart_template(),
					'description' => 'Recover abandoned carts with friendly reminder and incentive to complete purchase',
					'is_default' => 0
				)
			);
		}
	}
	
	/**
	 * Add unsubscribe link to email content if not present
	 */
	public function add_unsubscribe_link( $content, $email, $contact_id = 0 ) {
		require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
		$contact_model = new Gee_Woo_CRM_Contact();
		
		// Get or generate secure token for this contact
		if ( $contact_id > 0 ) {
			$token = $contact_model->get_unsubscribe_token( $contact_id );
		} else {
			// Fallback: find contact by email to get ID
			global $wpdb;
			$contact = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gee_crm_contacts WHERE email = %s", $email ) );
			if ( $contact ) {
				$token = $contact_model->get_unsubscribe_token( $contact->id );
			} else {
				// Contact doesn't exist yet - this shouldn't happen, but handle it
				$token = '';
			}
		}
		
		// Build secure unsubscribe link with token
		if ( ! empty( $token ) ) {
			$unsubscribe_link = home_url( '/wp-json/gee-crm/v1/unsubscribe?email=' . urlencode( $email ) . '&token=' . urlencode( $token ) );
		} else {
			// Security: Never generate unsubscribe links without tokens
			// If contact_id is 0 or contact lookup fails, we cannot securely unsubscribe
			// Log the issue and return content without unsubscribe link
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Gee CRM: Cannot generate secure unsubscribe link - missing contact_id or token for email: ' . $email );
			}
			// Return content without unsubscribe link - better than insecure link
			return $content;
		}
		
		// Check if unsubscribe link already exists
		if ( strpos( $content, '{unsubscribe_link}' ) !== false ) {
			// Replace placeholder
			$content = str_replace( '{unsubscribe_link}', $unsubscribe_link, $content );
		} elseif ( strpos( $content, 'unsubscribe' ) === false ) {
			// Add unsubscribe link at the bottom if not present
			$unsubscribe_footer = '
					<tr>
						<td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef;">
							<p style="margin: 0; color: #666666; font-size: 12px;">
								You are receiving this email because you subscribed to our marketing emails.<br>
								<a href="' . esc_url( $unsubscribe_link ) . '" style="color: #667eea; text-decoration: underline;">Unsubscribe</a> from marketing emails.
							</p>
						</td>
					</tr>';
			
			// Try to insert before closing </table> or </body>
			if ( strpos( $content, '</body>' ) !== false ) {
				$content = str_replace( '</body>', $unsubscribe_footer . '</body>', $content );
			} elseif ( strpos( $content, '</table>' ) !== false ) {
				// Find last </table> before </body> or at end
				$last_table_pos = strrpos( $content, '</table>' );
				if ( $last_table_pos !== false ) {
					$content = substr_replace( $content, $unsubscribe_footer . '</table>', $last_table_pos, strlen( '</table>' ) );
				}
			} else {
				// Just append at the end
				$content .= $unsubscribe_footer;
			}
		} else {
			// Replace any existing unsubscribe links with our link
			$content = preg_replace( '/href=["\']([^"\']*unsubscribe[^"\']*)["\']/i', 'href="' . esc_url( $unsubscribe_link ) . '"', $content );
		}
		
		return $content;
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
	
	/**
	 * Product Launch Template - For announcing new products
	 */
	private function get_product_launch_template() {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>New Product Launch</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 50px 30px; text-align: center;">
							<h1 style="margin: 0 0 10px 0; color: #ffffff; font-size: 36px; font-weight: 700;">🚀 NEW PRODUCT LAUNCH</h1>
							<p style="margin: 0; color: #ffffff; font-size: 20px; opacity: 0.95;">Be the first to experience it, {first_name}!</p>
						</td>
					</tr>
					<!-- Hero Section -->
					<tr>
						<td style="padding: 40px 30px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
							<h2 style="margin: 0 0 15px 0; color: #ffffff; font-size: 32px; font-weight: 700;">Introducing Our Latest Innovation</h2>
							<p style="margin: 0; color: #ffffff; font-size: 18px; opacity: 0.95;">Designed with you in mind</p>
						</td>
					</tr>
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px;">
							<h2 style="margin: 0 0 20px 0; color: #333333; font-size: 28px; font-weight: 600;">Hi {full_name},</h2>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								We\'re incredibly excited to introduce our newest product! After months of development and testing, we\'re finally ready to share it with you.
							</p>
							<p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								As one of our valued customers, you get exclusive early access before we launch it to the public.
							</p>
							<!-- Features List -->
							<div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0;">
								<h3 style="margin: 0 0 20px 0; color: #333333; font-size: 20px; font-weight: 600;">Key Features:</h3>
								<ul style="margin: 0; padding-left: 20px; color: #555555; font-size: 15px; line-height: 2;">
									<li>Premium quality materials</li>
									<li>Innovative design</li>
									<li>Customer-tested and approved</li>
									<li>Limited edition launch pricing</li>
								</ul>
							</div>
							<!-- CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 18px 50px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);">Shop Now - Early Access</a>
									</td>
								</tr>
							</table>
							<!-- Closing -->
							<p style="margin: 30px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Thank you for being part of our journey!<br>
								<strong style="color: #333333;">The {site_name} Team</strong>
							</p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td style="background-color: #2c3e50; padding: 30px; text-align: center;">
							<p style="margin: 0 0 15px 0; color: #ffffff; font-size: 16px; font-weight: 600;">{site_name}</p>
							<p style="margin: 0 0 20px 0; color: #bdc3c7; font-size: 14px;">
								<a href="{unsubscribe_link}" style="color: #3498db; text-decoration: none;">Unsubscribe</a> |
								<a href="{site_url}" style="color: #3498db; text-decoration: none;">Visit Website</a>
							</p>
							<p style="margin: 0; color: #95a5a6; font-size: 12px;">© ' . date('Y') . ' {site_name}. All rights reserved.</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	}
	
	/**
	 * Flash Sale Template - For urgent sales and discounts
	 */
	private function get_flash_sale_template() {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Flash Sale</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					<!-- Urgent Header -->
					<tr>
						<td style="background: #dc3545; padding: 20px 30px; text-align: center;">
							<p style="margin: 0; color: #ffffff; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px;">⚡ FLASH SALE ⚡</p>
						</td>
					</tr>
					<!-- Hero Section -->
					<tr>
						<td style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); padding: 60px 30px; text-align: center;">
							<h1 style="margin: 0 0 15px 0; color: #ffffff; font-size: 48px; font-weight: 900;">70% OFF</h1>
							<p style="margin: 0 0 20px 0; color: #ffffff; font-size: 24px; font-weight: 600;">Limited Time Only, {first_name}!</p>
							<div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 8px; display: inline-block;">
								<p style="margin: 0; color: #ffffff; font-size: 16px; font-weight: 600;">⏰ Ends in 24 Hours</p>
							</div>
						</td>
					</tr>
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px;">
							<h2 style="margin: 0 0 20px 0; color: #333333; font-size: 28px; font-weight: 600;">Don\'t Miss This Deal!</h2>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Hi {full_name}, we\'re running an exclusive flash sale just for you! Get massive savings on our best-selling products.
							</p>
							<!-- Discount Box -->
							<div style="background: #fff3cd; border: 3px solid #ffc107; padding: 25px; border-radius: 8px; text-align: center; margin: 30px 0;">
								<p style="margin: 0 0 10px 0; color: #856404; font-size: 18px; font-weight: 600;">Use Code: FLASH70</p>
								<p style="margin: 0; color: #856404; font-size: 14px;">Apply at checkout to save 70%</p>
							</div>
							<!-- CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 18px 50px; background: #dc3545; color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 18px; font-weight: 700; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);">Shop Now - 70% Off</a>
									</td>
								</tr>
							</table>
							<!-- Urgency -->
							<p style="margin: 30px 0 0 0; color: #dc3545; font-size: 16px; font-weight: 600; text-align: center;">
								⏰ This sale won\'t last long - shop now before it\'s gone!
							</p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td style="background-color: #2c3e50; padding: 30px; text-align: center;">
							<p style="margin: 0 0 15px 0; color: #ffffff; font-size: 16px; font-weight: 600;">{site_name}</p>
							<p style="margin: 0 0 20px 0; color: #bdc3c7; font-size: 14px;">
								<a href="{unsubscribe_link}" style="color: #3498db; text-decoration: none;">Unsubscribe</a> |
								<a href="{site_url}" style="color: #3498db; text-decoration: none;">Visit Website</a>
							</p>
							<p style="margin: 0; color: #95a5a6; font-size: 12px;">© ' . date('Y') . ' {site_name}. All rights reserved.</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	}
	
	/**
	 * Product Recommendation Template - Personalized recommendations
	 */
	private function get_product_recommendation_template() {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Product Recommendations</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 50px 30px; text-align: center;">
							<h1 style="margin: 0 0 10px 0; color: #ffffff; font-size: 36px; font-weight: 700;">✨ Recommended for You</h1>
							<p style="margin: 0; color: #ffffff; font-size: 20px; opacity: 0.95;">Handpicked just for {first_name}</p>
						</td>
					</tr>
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px;">
							<h2 style="margin: 0 0 20px 0; color: #333333; font-size: 28px; font-weight: 600;">Hi {full_name},</h2>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Based on your previous purchases and preferences, we thought you might love these products!
							</p>
							<!-- Product Recommendation Box -->
							<div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 30px; margin: 30px 0; background: #f8f9fa;">
								<h3 style="margin: 0 0 15px 0; color: #333333; font-size: 22px; font-weight: 600;">🎯 Perfect Match for You</h3>
								<p style="margin: 0 0 20px 0; color: #555555; font-size: 15px; line-height: 1.8;">
									We\'ve curated these recommendations based on your shopping history. You\'ve spent {total_spent} with us across {order_count} orders - we know what you like!
								</p>
								<!-- Product Features -->
								<table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
									<tr>
										<td width="50%" style="padding: 10px; vertical-align: top;">
											<div style="text-align: center;">
												<div style="font-size: 36px; margin-bottom: 10px;">⭐</div>
												<p style="margin: 0; color: #333333; font-size: 14px; font-weight: 600;">Top Rated</p>
											</div>
										</td>
										<td width="50%" style="padding: 10px; vertical-align: top;">
											<div style="text-align: center;">
												<div style="font-size: 36px; margin-bottom: 10px;">💎</div>
												<p style="margin: 0; color: #333333; font-size: 14px; font-weight: 600;">Premium Quality</p>
											</div>
										</td>
									</tr>
								</table>
							</div>
							<!-- CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 18px 50px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);">View Recommendations</a>
									</td>
								</tr>
							</table>
							<!-- Closing -->
							<p style="margin: 30px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								We hope you find something you love!<br>
								<strong style="color: #333333;">The {site_name} Team</strong>
							</p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td style="background-color: #2c3e50; padding: 30px; text-align: center;">
							<p style="margin: 0 0 15px 0; color: #ffffff; font-size: 16px; font-weight: 600;">{site_name}</p>
							<p style="margin: 0 0 20px 0; color: #bdc3c7; font-size: 14px;">
								<a href="{unsubscribe_link}" style="color: #3498db; text-decoration: none;">Unsubscribe</a> |
								<a href="{site_url}" style="color: #3498db; text-decoration: none;">Visit Website</a>
							</p>
							<p style="margin: 0; color: #95a5a6; font-size: 12px;">© ' . date('Y') . ' {site_name}. All rights reserved.</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
	}
	
	/**
	 * Abandoned Cart Template - For cart recovery
	 */
	private function get_abandoned_cart_template() {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Complete Your Purchase</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 50px 30px; text-align: center;">
							<h1 style="margin: 0 0 10px 0; color: #ffffff; font-size: 36px; font-weight: 700;">🛒 You Left Something Behind</h1>
							<p style="margin: 0; color: #ffffff; font-size: 20px; opacity: 0.95;">Complete your purchase, {first_name}!</p>
						</td>
					</tr>
					<!-- Main Content -->
					<tr>
						<td style="padding: 50px 40px;">
							<h2 style="margin: 0 0 20px 0; color: #333333; font-size: 28px; font-weight: 600;">Hi {full_name},</h2>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								We noticed you added some great items to your cart but didn\'t complete your purchase. Don\'t worry - your items are still waiting for you!
							</p>
							<!-- Reminder Box -->
							<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 4px; margin: 30px 0;">
								<p style="margin: 0 0 10px 0; color: #856404; font-size: 16px; font-weight: 600;">💡 Quick Reminder:</p>
								<p style="margin: 0; color: #856404; font-size: 14px; line-height: 1.6;">
									Your cart is saved for 24 hours. Complete your purchase now to secure your items before they\'re gone!
								</p>
							</div>
							<!-- Benefits -->
							<div style="margin: 30px 0;">
								<h3 style="margin: 0 0 15px 0; color: #333333; font-size: 20px; font-weight: 600;">Why Complete Your Order?</h3>
								<ul style="margin: 0; padding-left: 20px; color: #555555; font-size: 15px; line-height: 2;">
									<li>✅ Items reserved just for you</li>
									<li>🚚 Fast and free shipping available</li>
									<li>🔒 Secure checkout process</li>
									<li>↩️ Easy returns if needed</li>
								</ul>
							</div>
							<!-- CTA Button -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 40px 0;">
								<tr>
									<td align="center">
										<a href="{site_url}" style="display: inline-block; padding: 18px 50px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 18px; font-weight: 600; box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);">Complete My Purchase</a>
									</td>
								</tr>
							</table>
							<!-- Help Text -->
							<p style="margin: 30px 0 0 0; color: #666666; font-size: 14px; text-align: center;">
								Need help? <a href="{site_url}/contact" style="color: #667eea; text-decoration: underline;">Contact our support team</a>
							</p>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td style="background-color: #2c3e50; padding: 30px; text-align: center;">
							<p style="margin: 0 0 15px 0; color: #ffffff; font-size: 16px; font-weight: 600;">{site_name}</p>
							<p style="margin: 0 0 20px 0; color: #bdc3c7; font-size: 14px;">
								<a href="{unsubscribe_link}" style="color: #3498db; text-decoration: none;">Unsubscribe</a> |
								<a href="{site_url}" style="color: #3498db; text-decoration: none;">Visit Website</a>
							</p>
							<p style="margin: 0; color: #95a5a6; font-size: 12px;">© ' . date('Y') . ' {site_name}. All rights reserved.</p>
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

