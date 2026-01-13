<?php
/**
 * Plugin Name: CRM Marketing, Mail & Analytics for WooCommerce
 * Description: Customer Relationship Management, Email campaigns, contact segmentation, analytics and marketing for your Woo store—with easy form integration.
 * Version:     1.0.0
 * Author:      GeeStack Labs
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crm-marketing-woo
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

/**
 * The code that runs during plugin uninstall.
 * This function is called by WordPress when the plugin is uninstalled.
 */
function uninstall_gee_woo_crm() {
	global $wpdb;
	
	// Ensure we have database access
	if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
		return;
	}
	
	// Load WordPress database functions
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
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
}

register_activation_hook( __FILE__, 'activate_gee_woo_crm' );
register_deactivation_hook( __FILE__, 'deactivate_gee_woo_crm' );
register_uninstall_hook( __FILE__, 'uninstall_gee_woo_crm' );

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
	
	// Register REST API endpoints for form integration
	add_action( 'rest_api_init', 'gee_woo_crm_register_rest_routes' );
}
run_gee_woo_crm();

/**
 * Register REST API routes for form integration
 */
function gee_woo_crm_register_rest_routes() {
	register_rest_route( 'gee-crm/v1', '/subscribe', array(
		'methods' => 'POST',
		'callback' => 'gee_woo_crm_handle_subscribe',
		'permission_callback' => 'gee_woo_crm_verify_api_key',
	) );
	
	register_rest_route( 'gee-crm/v1', '/unsubscribe', array(
		'methods' => array( 'POST', 'GET' ),
		'callback' => 'gee_woo_crm_handle_unsubscribe',
		'permission_callback' => '__return_true', // Public endpoint
	) );
}

/**
 * Verify API key for REST API requests
 */
function gee_woo_crm_verify_api_key() {
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-settings.php';
	$settings = new Gee_Woo_CRM_Settings();
	$api_key = $settings->get_api_key();
	
	$request_api_key = isset( $_SERVER['HTTP_X_API_KEY'] ) ? sanitize_text_field( $_SERVER['HTTP_X_API_KEY'] ) : '';
	
	return ! empty( $api_key ) && $api_key === $request_api_key;
}

/**
 * Handle subscribe API request
 */
function gee_woo_crm_handle_subscribe( $request ) {
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-settings.php';
	
	// Support both JSON and form-encoded POST data
	$json_params = $request->get_json_params();
	$form_params = $request->get_body_params();
	$params = ! empty( $json_params ) ? $json_params : $form_params;
	
	$email = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
	$first_name = isset( $params['first_name'] ) && ! empty( trim( $params['first_name'] ) ) ? sanitize_text_field( trim( $params['first_name'] ) ) : '';
	$last_name = isset( $params['last_name'] ) && ! empty( trim( $params['last_name'] ) ) ? sanitize_text_field( trim( $params['last_name'] ) ) : '';
	$phone = isset( $params['phone'] ) && ! empty( trim( $params['phone'] ) ) ? sanitize_text_field( trim( $params['phone'] ) ) : '';
	$marketing_consent = isset( $params['marketing_consent'] ) && $params['marketing_consent'] ? true : false;
	$tags = isset( $params['tags'] ) ? $params['tags'] : array();
	
	if ( empty( $email ) ) {
		return new WP_Error( 'missing_email', 'Email is required', array( 'status' => 400 ) );
	}
	
	$contact_model = new Gee_Woo_CRM_Contact();
	$contact_data = array(
		'email' => $email,
		'source' => 'form_submission',
		'marketing_consent' => $marketing_consent,
	);
	
	// Only include fields if they have values
	if ( ! empty( $first_name ) ) {
		$contact_data['first_name'] = $first_name;
	}
	if ( ! empty( $last_name ) ) {
		$contact_data['last_name'] = $last_name;
	}
	if ( ! empty( $phone ) ) {
		$contact_data['phone'] = $phone;
	}
	
	$contact_id = $contact_model->create_or_update( $contact_data );
	
	// Assign tags if provided, or use default/common tag if none specified
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
	$tag_model = new Gee_Woo_CRM_Tag();
	
	if ( $contact_id ) {
		// If tags are provided, use them
		if ( ! empty( $tags ) && is_array( $tags ) ) {
			foreach ( $tags as $tag_id ) {
				$tag_id = absint( $tag_id );
				if ( $tag_id > 0 ) {
					$tag_model->assign_tag( $contact_id, $tag_id );
				}
			}
		} else {
			// If no tags specified, use default/common tag from settings
			$default_tag_id = $settings_model->get_setting( 'default_form_tag', 0 );
			if ( $default_tag_id > 0 ) {
				$tag_model->assign_tag( $contact_id, absint( $default_tag_id ) );
			}
		}
	}
	
	// Check if consent was just granted (new consent or changed from false to true)
	global $wpdb;
	$contact = $wpdb->get_row( $wpdb->prepare( "SELECT marketing_consent, consent_date FROM {$wpdb->prefix}gee_crm_contacts WHERE id = %d", $contact_id ) );
	
	if ( $contact_id && $marketing_consent && ( ! $contact || empty( $contact->consent_date ) || strtotime( $contact->consent_date ) == strtotime( current_time( 'mysql' ) ) ) ) {
		// Send thank you email if enabled and consent was just granted
		$settings = new Gee_Woo_CRM_Settings();
		if ( $settings->get_setting( 'thank_you_email_enabled', 0 ) ) {
			gee_woo_crm_send_thank_you_email( $contact_id, $email, $first_name, $last_name, $settings );
		}
	}
	
	return new WP_REST_Response( array( 
		'success' => true, 
		'message' => 'Contact updated successfully',
		'contact_id' => $contact_id
	), 200 );
}

/**
 * Handle unsubscribe API request
 */
function gee_woo_crm_handle_unsubscribe( $request ) {
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
	
	// Support both GET and POST, and both JSON and form-encoded data
	$email = '';
	$token = '';
	$format = $request->get_param( 'format' ); // Allow ?format=html for browser visits
	
	if ( $request->get_method() === 'GET' ) {
		$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';
		$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
	} else {
		// Support both JSON and form-encoded POST data
		$json_params = $request->get_json_params();
		$form_params = $request->get_body_params();
		$params = ! empty( $json_params ) ? $json_params : $form_params;
		$email = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
		$token = isset( $params['token'] ) ? sanitize_text_field( $params['token'] ) : '';
	}
	
	// SECURITY: Require both email and token
	if ( empty( $email ) || empty( $token ) ) {
		// Return JSON error even for GET requests if email is missing
		if ( $format !== 'html' ) {
			return new WP_Error( 'missing_email', 'Email is required', array( 'status' => 400 ) );
		}
		// For HTML format, show error page
		$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Unsubscribe Error</title>
	<style>
		body { font-family: Arial, sans-serif; text-align: center; padding: 50px 20px; background: #f5f5f5; }
		.container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
		h1 { color: #d32f2f; }
		p { color: #666; line-height: 1.6; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Invalid Unsubscribe Link</h1>
		<p>This unsubscribe link is invalid or has expired. Please use the unsubscribe link from your most recent email.</p>
		<p><a href="' . esc_url( home_url() ) . '">Return to Homepage</a></p>
	</div>
</body>
</html>';
		echo wp_kses_post( $html );
		exit;
	}
	
	// SECURITY: Verify token before unsubscribing
	$contact_model = new Gee_Woo_CRM_Contact();
	
	if ( ! $contact_model->verify_unsubscribe_token( $email, $token ) ) {
		// Invalid token - security check failed
		if ( $format === 'html' ) {
			$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Unsubscribe Error</title>
	<style>
		body { font-family: Arial, sans-serif; text-align: center; padding: 50px 20px; background: #f5f5f5; }
		.container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
		h1 { color: #d32f2f; }
		p { color: #666; line-height: 1.6; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Invalid Unsubscribe Link</h1>
		<p>This unsubscribe link is invalid or has been tampered with. For security reasons, only the actual email owner can unsubscribe.</p>
		<p>Please use the unsubscribe link from your most recent email, or contact support if you need assistance.</p>
		<p><a href="' . esc_url( home_url() ) . '">Return to Homepage</a></p>
	</div>
</body>
</html>';
			echo $html;
			exit;
		}
		
		return new WP_Error( 'invalid_token', 'Invalid or missing unsubscribe token. Only the email owner can unsubscribe.', array( 'status' => 403 ) );
	}
	
	// Token is valid - proceed with unsubscribe
	global $wpdb;
	$table_name = $wpdb->prefix . 'gee_crm_contacts';
	$contact = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_name WHERE email = %s", $email ) );
	
	if ( $contact ) {
		$contact_model->update_marketing_consent( $contact->id, false );
		
		// Return HTML page only if format=html is explicitly requested (for direct browser visits)
		if ( $request->get_method() === 'GET' && $format === 'html' ) {
			$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Unsubscribed</title>
	<style>
		body { font-family: Arial, sans-serif; text-align: center; padding: 50px 20px; background: #f5f5f5; }
		.container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
		h1 { color: #333; }
		p { color: #666; line-height: 1.6; }
		a { color: #667eea; text-decoration: none; }
		a:hover { text-decoration: underline; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Successfully Unsubscribed</h1>
		<p>You have been successfully unsubscribed from marketing emails.</p>
		<p>You will no longer receive marketing communications from us.</p>
		<p><a href="' . esc_url( home_url() ) . '">Return to Homepage</a></p>
	</div>
</body>
</html>';
			echo $html;
			exit;
		}
		
		// Default: Return JSON response for API calls
		return new WP_REST_Response( array( 
			'success' => true, 
			'message' => 'Successfully unsubscribed',
			'email' => $email
		), 200 );
	}
	
	// Contact not found
	if ( $format === 'html' ) {
		$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Unsubscribe Error</title>
	<style>
		body { font-family: Arial, sans-serif; text-align: center; padding: 50px 20px; background: #f5f5f5; }
		.container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
		h1 { color: #d32f2f; }
		p { color: #666; line-height: 1.6; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Not Found</h1>
		<p>We could not find a contact with that email address.</p>
		<p><a href="' . esc_url( home_url() ) . '">Return to Homepage</a></p>
	</div>
</body>
</html>';
		echo wp_kses_post( $html );
		exit;
	}
	
	return new WP_Error( 'contact_not_found', 'Contact not found', array( 'status' => 404 ) );
}

/**
 * Send thank you email
 */
function gee_woo_crm_send_thank_you_email( $contact_id, $email, $first_name, $last_name, $settings ) {
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
	
	$template_model = new Gee_Woo_CRM_Email_Template();
	$contact_model = new Gee_Woo_CRM_Contact();
	
	// Get template ID from settings
	$template_id = $settings->get_setting( 'default_thank_you_template_id', 0 );
	
	if ( $template_id ) {
		$template = $template_model->get_template( $template_id );
		if ( $template ) {
			$subject = $template->subject;
			$content = $template->content_html;
		} else {
			return; // Template not found
		}
	} else {
		// Fallback to default template
		$subject = 'Thank You for Subscribing!';
		$content = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Thank You</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px 0;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px;">
					<tr>
						<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 30px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 36px; font-weight: 700;">Thank You, {first_name}!</h1>
						</td>
					</tr>
					<tr>
						<td style="padding: 50px 40px;">
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Hi {full_name},
							</p>
							<p style="margin: 0 0 20px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Thank you for subscribing to our marketing emails! We\'re excited to have you on board.
							</p>
							<p style="margin: 0 0 30px 0; color: #555555; font-size: 16px; line-height: 1.8;">
								You\'ll now receive our latest updates, exclusive offers, and special promotions directly in your inbox.
							</p>
							<p style="margin: 30px 0 0 0; color: #555555; font-size: 16px; line-height: 1.8;">
								Best regards,<br>
								<strong style="color: #333333;">The {site_name} Team</strong>
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
	
	// Get contact for variable replacement
	$contact = $contact_model->get_contact( $contact_id );
	if ( ! $contact ) {
		$contact = (object) array(
			'id' => $contact_id,
			'email' => $email,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'wp_user_id' => null,
		);
	}
	
	// Replace variables using campaign's replace_variables method
	require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-campaign.php';
	$campaign_model = new Gee_Woo_CRM_Campaign();
	$reflection = new ReflectionClass( $campaign_model );
	$method = $reflection->getMethod( 'replace_variables' );
	$method->setAccessible( true );
	
	$subject = $method->invoke( $campaign_model, $subject, $contact );
	$content = $method->invoke( $campaign_model, $content, $contact );
	
	// Ensure unsubscribe link is added with secure token
	$content = $template_model->add_unsubscribe_link( $content, $email, isset( $contact->id ) ? $contact->id : $contact_id );
	
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $email, $subject, $content, $headers );
}
