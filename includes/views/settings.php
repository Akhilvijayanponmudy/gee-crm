<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-settings.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';

$settings_model = new Gee_Woo_CRM_Settings();
$template_model = new Gee_Woo_CRM_Email_Template();

/**
 * Get HTML form snippet
 */
function gee_get_form_snippet_html( $settings_model, $api_endpoint, $api_key ) {
	$privacy_url = $settings_model->get_setting( 'privacy_policy_url' );
	
	$snippet = '<!-- Gee CRM Marketing Consent Checkbox -->
<div style="margin:15px 0;">
	<label>
		<input type="checkbox" name="gee_crm_marketing_consent" id="gee-crm-consent" value="1" required>
		I agree to receive marketing emails';
	
	if ( $privacy_url ) {
		$snippet .= ' (<a href="' . esc_url( $privacy_url ) . '" target="_blank">Privacy Policy</a>)';
	}
	
	$snippet .= '
	</label>
</div>

<!-- Gee CRM Form Handler Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
	var forms = document.querySelectorAll("form");
	forms.forEach(function(form) {
		form.addEventListener("submit", function(e) {
			var consentCheckbox = document.getElementById("gee-crm-consent");
			if (consentCheckbox && consentCheckbox.checked) {
				var formData = new FormData(form);
				var email = formData.get("email") || formData.get("your-email") || formData.get("e-mail") || "";
				var firstName = formData.get("first_name") || formData.get("first-name") || formData.get("fname") || "";
				var lastName = formData.get("last_name") || formData.get("last-name") || formData.get("lname") || "";
				
				if (email) {
					fetch("' . esc_url( $api_endpoint ) . '", {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
							"X-API-Key": "' . esc_js( $api_key ) . '"
						},
						body: JSON.stringify({
							email: email,
							first_name: firstName,
							last_name: lastName,
							marketing_consent: true
						})
					}).catch(function(err) {
						console.log("Gee CRM: Consent update failed", err);
					});
				}
			}
		});
	});
});
</script>';
	
	return $snippet;
}

/**
 * Get JavaScript form snippet
 */
function gee_get_form_snippet_js( $settings_model, $api_endpoint, $api_key ) {
	return '// Gee CRM Marketing Consent Handler
// Add this to your form submission handler

function geeCrmUpdateConsent(formData) {
	var consentCheckbox = document.querySelector(\'[name="gee_crm_marketing_consent"]\');
	if (consentCheckbox && consentCheckbox.checked) {
		var email = formData.email || formData["your-email"] || "";
		var firstName = formData.first_name || formData["first-name"] || "";
		var lastName = formData.last_name || formData["last-name"] || "";
		
		if (email) {
			fetch("' . esc_url( $api_endpoint ) . '", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					"X-API-Key": "' . esc_js( $api_key ) . '"
				},
				body: JSON.stringify({
					email: email,
					first_name: firstName,
					last_name: lastName,
					marketing_consent: true
				})
			}).catch(function(err) {
				console.log("Gee CRM: Consent update failed", err);
			});
		}
	}
}

// Example usage in your form handler:
// geeCrmUpdateConsent({
//     email: "user@example.com",
//     first_name: "John",
//     last_name: "Doe"
// });';
}

// Handle Settings Save
if ( isset( $_POST['gee_save_settings'] ) && check_admin_referer( 'gee_save_settings_nonce' ) ) {
		$settings = array(
			'privacy_policy_url' => esc_url_raw( $_POST['privacy_policy_url'] ),
			'unsubscribe_page_url' => esc_url_raw( $_POST['unsubscribe_page_url'] ),
			'gdpr_compliance_mode' => isset( $_POST['gdpr_compliance_mode'] ) ? 1 : 0,
			'test_email' => isset( $_POST['test_email'] ) ? sanitize_email( $_POST['test_email'] ) : get_option( 'admin_email' ),
		);
	
	$settings_model->update_settings( $settings );
	echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
}

// Handle API Key Regeneration
if ( isset( $_GET['action'] ) && $_GET['action'] == 'regenerate_api_key' ) {
	if ( check_admin_referer( 'regenerate_api_key' ) ) {
		$settings_model->generate_api_key();
		echo '<div class="notice notice-success is-dismissible"><p>API key regenerated successfully!</p></div>';
	}
}

$settings = $settings_model->get_settings();
$api_key = $settings_model->get_api_key();
$api_endpoint = home_url( '/wp-json/gee-crm/v1/subscribe' );
?>

<div class="gee-crm-card">
	<h2>Settings</h2>
	
	<!-- Settings Tabs -->
	<div style="border-bottom:2px solid #e5e5e5; margin-bottom:20px;">
		<button type="button" class="gee-settings-tab active" data-tab="consent-form" style="padding:12px 24px; background:none; border:none; border-bottom:3px solid #2271b1; color:#2271b1; font-weight:600; cursor:pointer; font-size:14px;">Consent Form Integration</button>
		<button type="button" class="gee-settings-tab" data-tab="privacy" style="padding:12px 24px; background:none; border:none; border-bottom:3px solid transparent; color:#666; font-weight:600; cursor:pointer; font-size:14px;">Privacy & Legal</button>
		<button type="button" class="gee-settings-tab" data-tab="sync" style="padding:12px 24px; background:none; border:none; border-bottom:3px solid transparent; color:#666; font-weight:600; cursor:pointer; font-size:14px;">Data Sync</button>
	</div>
	
	<form method="post" id="gee-settings-form">
		<?php wp_nonce_field( 'gee_save_settings_nonce' ); ?>
		
		<!-- Consent Form Integration Tab -->
		<div id="tab-consent-form" class="gee-settings-tab-content">
			<div style="background:#e8f4f8; padding:20px; border-left:4px solid #2271b1; border-radius:4px; margin-bottom:25px;">
				<h3 style="margin-top:0; color:#2271b1;">📋 Marketing Consent Form Integration</h3>
				<p style="margin:10px 0; line-height:1.6; color:#333;">
					<strong>What is this?</strong> The Marketing Consent Form Integration allows you to capture user consent for marketing emails directly from your website forms. 
					When users check the consent checkbox and submit any form on your site, their marketing consent status is automatically updated in your CRM.
				</p>
				<p style="margin:10px 0; line-height:1.6; color:#333;">
					<strong>How it works:</strong> Add the provided code snippet to your contact forms (Contact Form 7, Gravity Forms, custom forms, etc.). 
					The code includes a consent checkbox and JavaScript that automatically sends the user's email and consent status to the CRM via a secure REST API endpoint.
				</p>
				<p style="margin:10px 0; line-height:1.6; color:#333;">
					<strong>Priority & Importance:</strong> This integration is <strong style="color:#d63638;">critical for GDPR compliance</strong> and ensures you only send marketing emails to users who have explicitly consented. 
					Contacts without marketing consent will be marked as "Unsubscribed" and will NOT receive campaign emails, protecting you from compliance issues and improving email deliverability.
				</p>
			</div>
		
		<!-- Form Integration Section -->
		<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
			<h3 style="margin-top:0;">Integration Methods</h3>
			<p style="color:#666; margin-bottom:20px;">
				Choose the integration method that best fits your form setup. The HTML snippet works with most forms automatically, while the JavaScript method gives you more control for custom implementations.
			</p>
			
			<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:15px;">
				<h4 style="margin-top:0;">HTML Code Snippet:</h4>
				<textarea readonly id="form-snippet-html" style="width:100%; height:200px; font-family:monospace; font-size:12px; padding:10px; background:#f5f5f5; border:1px solid #ddd;" onclick="this.select();"><?php echo esc_textarea( gee_get_form_snippet_html( $settings_model, $api_endpoint, $api_key ) ); ?></textarea>
				<button type="button" class="button" onclick="copyToClipboard(document.getElementById('form-snippet-html'));" style="margin-top:10px;">Copy Code</button>
			</div>
			
			<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:15px;">
				<h4 style="margin-top:0;">JavaScript Integration (Alternative):</h4>
				<textarea readonly id="form-snippet-js" style="width:100%; height:250px; font-family:monospace; font-size:12px; padding:10px; background:#f5f5f5; border:1px solid #ddd;" onclick="this.select();"><?php echo esc_textarea( gee_get_form_snippet_js( $settings_model, $api_endpoint, $api_key ) ); ?></textarea>
				<button type="button" class="button" onclick="copyToClipboard(document.getElementById('form-snippet-js'));" style="margin-top:10px;">Copy Code</button>
			</div>
			
			<div style="background:#fff3cd; padding:15px; border-left:4px solid #ffc107; border-radius:4px; margin-bottom:20px;">
				<p style="margin:0 0 10px 0; color:#856404;">
					<strong>API Endpoint:</strong> <code><?php echo esc_html( $api_endpoint ); ?></code><br>
					<strong>API Key:</strong> <code><?php echo esc_html( $api_key ); ?></code>
					<a href="<?php echo wp_nonce_url( '?page=gee-woo-crm&view=settings&action=regenerate_api_key', 'regenerate_api_key' ); ?>" class="button button-small" style="margin-left:10px;" onclick="return confirm('Regenerate API key? Existing forms will need to be updated.');">Regenerate</a>
				</p>
			</div>
			
			<div style="background:#d1ecf1; padding:20px; border-left:4px solid #0c5460; border-radius:4px; margin-top:20px;">
				<h4 style="margin-top:0; color:#0c5460;">🔒 Security & API Authentication</h4>
				<p style="margin:10px 0; line-height:1.6; color:#0c5460;">
					All form submissions are secured using an API key that must be included in the request headers. 
					This prevents unauthorized access and ensures only your forms can update consent status. 
					Keep your API key secure and regenerate it if you suspect it has been compromised.
				</p>
			</div>
			
			<div style="background:#f0f6fc; padding:20px; border-left:4px solid #2271b1; border-radius:4px; margin-top:20px;">
				<h4 style="margin-top:0; color:#2271b1;">⚡ Integration Priority & Best Practices</h4>
				<ul style="margin:10px 0; padding-left:20px; line-height:1.8; color:#333;">
					<li><strong>High Priority:</strong> Add consent forms to all lead capture forms (contact forms, newsletter signups, checkout pages)</li>
					<li><strong>GDPR Compliance:</strong> Only send marketing emails to contacts with explicit consent (marketing_consent = true)</li>
					<li><strong>Campaign Filtering:</strong> Campaigns automatically exclude unsubscribed contacts (marketing_consent = false)</li>
					<li><strong>Data Accuracy:</strong> Consent status is updated in real-time when forms are submitted</li>
					<li><strong>Thank You Emails:</strong> Contacts who grant consent may receive a welcome/thank you email automatically</li>
				</ul>
			</div>
		</div>
		</div>
		
		<!-- Privacy & Legal Tab -->
		<div id="tab-privacy" class="gee-settings-tab-content" style="display:none;">
			<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
				<h3 style="margin-top:0;">Privacy & Legal Settings</h3>
				<p style="color:#666; margin-bottom:20px;">
					Configure privacy policy and unsubscribe links that will be included in your email campaigns and form integrations.
				</p>
				
				<div style="margin-bottom:15px;">
					<label><strong>Privacy Policy URL:</strong></label><br>
					<input type="url" name="privacy_policy_url" value="<?php echo esc_url( $settings['privacy_policy_url'] ); ?>" style="width:100%; max-width:600px; padding:8px; margin-top:5px;" placeholder="https://yoursite.com/privacy-policy">
					<br>
					<small style="color:#666;">Link to your privacy policy page. This will be included in form snippets and helps with GDPR compliance.</small>
				</div>
				
				<div style="margin-bottom:15px;">
					<label><strong>Unsubscribe Page URL:</strong></label><br>
					<input type="url" name="unsubscribe_page_url" value="<?php echo esc_url( $settings['unsubscribe_page_url'] ); ?>" style="width:100%; max-width:600px; padding:8px; margin-top:5px;" placeholder="https://yoursite.com/unsubscribe">
					<br>
					<small style="color:#666;">Page where users can unsubscribe from marketing emails. You can use the endpoint: <code><?php echo home_url( '/wp-json/gee-crm/v1/unsubscribe?email={email}' ); ?></code></small>
				</div>
			</div>
		</div>
		
		<!-- Data Synchronization Tab -->
		<div id="tab-sync" class="gee-settings-tab-content" style="display:none;">
			<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
				<h3 style="margin-top:0;">Data Synchronization</h3>
				<p style="color:#666; margin-bottom:20px;">
					Import customers from WooCommerce (Registered Users + Guest Orders). This will create contacts in your CRM for all WooCommerce customers.
				</p>
				<button id="gee-crm-sync-btn" class="gee-crm-btn gee-crm-btn-primary">Sync WooCommerce Customers</button>
				<p id="gee-crm-sync-status" style="margin-top: 10px; color: #666;"></p>
			</div>
			
			<!-- Test Email Section -->
			<div style="background:#f0f8ff; padding:20px; border-left:4px solid #2271b1; border-radius:4px; margin-bottom:30px;">
				<h3 style="margin-top:0; color:#2271b1;">Test Email</h3>
				<p style="color:#666; margin-bottom:20px;">
					Send a test email to verify your email configuration and template rendering. This is useful for testing email templates before sending campaigns.
				</p>
				<div style="margin-bottom:15px;">
					<label><strong>Test Email Address:</strong></label><br>
					<input type="email" name="test_email" id="gee-crm-test-email" value="<?php echo esc_attr( $settings['test_email'] ?? get_option( 'admin_email' ) ); ?>" style="width:100%; max-width:400px; padding:8px; margin-top:5px;" placeholder="test@example.com">
					<br>
					<small style="color:#666;">Enter the email address where you want to receive test emails. This will be saved when you click "Save Settings".</small>
				</div>
				<button id="gee-crm-send-test-email" class="gee-crm-btn gee-crm-btn-primary">Send Test Email</button>
				<p id="gee-crm-test-email-status" style="margin-top: 10px; color: #666;"></p>
			</div>
		</div>
		
		<div style="margin-top:30px; border-top:1px solid #e5e5e5; padding-top:20px;">
			<input type="submit" name="gee_save_settings" class="gee-crm-btn gee-crm-btn-primary" value="Save Settings">
		</div>
	</form>
</div>

<style>
.gee-settings-tab {
	transition: all 0.2s ease;
}
.gee-settings-tab:hover {
	color: #2271b1 !important;
}
.gee-settings-tab-content {
	animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
	from { opacity: 0; }
	to { opacity: 1; }
}
</style>

<script>
function copyToClipboard(element) {
	element.select();
	document.execCommand('copy');
	alert('Code copied to clipboard!');
}

// Tab switching functionality
jQuery(document).ready(function($) {
	$('.gee-settings-tab').on('click', function() {
		var tab = $(this).data('tab');
		
		// Update tab buttons
		$('.gee-settings-tab').removeClass('active').css({
			'border-bottom-color': 'transparent',
			'color': '#666'
		});
		$(this).addClass('active').css({
			'border-bottom-color': '#2271b1',
			'color': '#2271b1'
		});
		
		// Show/hide tab content
		$('.gee-settings-tab-content').hide();
		$('#tab-' + tab).show();
	});
	
	// Send Test Email
	$('#gee-crm-send-test-email').on('click', function() {
		var $btn = $(this);
		var $status = $('#gee-crm-test-email-status');
		var testEmail = $('#gee-crm-test-email').val();
		
		if (!testEmail || !testEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
			$status.html('<span style="color:#dc3545;">Please enter a valid email address.</span>');
			return;
		}
		
		$btn.prop('disabled', true).text('Sending...');
		$status.html('<span style="color:#666;">Sending test email...</span>');
		
		$.ajax({
			url: typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof geeWooCRM !== 'undefined' ? geeWooCRM.ajaxurl : '<?php echo admin_url( "admin-ajax.php" ); ?>'),
			type: 'POST',
			data: {
				action: 'gee_crm_send_test_email',
				email: testEmail,
				nonce: '<?php echo wp_create_nonce( 'gee_crm_test_email' ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$status.html('<span style="color:#28a745;">✓ Test email sent successfully to ' + testEmail + '</span>');
				} else {
					$status.html('<span style="color:#dc3545;">✗ Error: ' + (response.data || 'Failed to send email') + '</span>');
				}
			},
			error: function() {
				$status.html('<span style="color:#dc3545;">✗ Error: Failed to send test email. Please check your server configuration.</span>');
			},
			complete: function() {
				$btn.prop('disabled', false).text('Send Test Email');
			}
		});
	});
});
</script>
