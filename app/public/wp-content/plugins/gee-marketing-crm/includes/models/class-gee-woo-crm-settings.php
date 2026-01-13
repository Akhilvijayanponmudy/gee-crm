<?php

class Gee_Woo_CRM_Settings {

	private $option_name = 'gee_woo_crm_settings';

	public function __construct() {
		// Initialize default settings if not exists
		$this->init_default_settings();
	}

	/**
	 * Initialize default settings
	 */
	private function init_default_settings() {
		$settings = get_option( $this->option_name );
		if ( false === $settings ) {
			$defaults = array(
				'thank_you_email_enabled' => 1,
				'default_campaign_template_id' => 0,
				'default_thank_you_template_id' => 0,
				'privacy_policy_url' => '',
				'unsubscribe_page_url' => '',
				'form_api_key' => wp_generate_password( 32, false ),
				'gdpr_compliance_mode' => 1,
			);
			update_option( $this->option_name, $defaults );
		}
	}

	/**
	 * Get default thank you email template
	 */
	private function get_default_thank_you_template() {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Thank You</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
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
					<tr>
						<td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
							<p style="margin: 0 0 15px 0; color: #666666; font-size: 14px;">
								You can <a href="{unsubscribe_link}" style="color: #667eea; text-decoration: underline;">unsubscribe</a> at any time.
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
	 * Get all settings
	 */
	public function get_settings() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Get a specific setting
	 */
	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update settings
	 */
	public function update_settings( $settings ) {
		$current = $this->get_settings();
		$updated = array_merge( $current, $settings );
		return update_option( $this->option_name, $updated );
	}

	/**
	 * Get form API key
	 */
	public function get_api_key() {
		return $this->get_setting( 'form_api_key', '' );
	}

	/**
	 * Generate new API key
	 */
	public function generate_api_key() {
		$new_key = wp_generate_password( 32, false );
		$this->update_settings( array( 'form_api_key' => $new_key ) );
		return $new_key;
	}
}

