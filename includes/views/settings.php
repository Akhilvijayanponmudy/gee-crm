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
	
	<form method="post" id="gee-settings-form">
		<?php wp_nonce_field( 'gee_save_settings_nonce' ); ?>
		
		<!-- GDPR Compliance Section -->
		<div style="background:#e8f4f8; padding:20px; border-radius:4px; margin-bottom:30px; border-left:4px solid #4e28a5;">
			<h3 style="margin-top:0; color:#4e28a5;">GDPR Compliance</h3>
			<p style="color:#666;">
				This plugin is designed to be GDPR compliant. Marketing emails are only sent to contacts who have explicitly consented.
				All consent is tracked with timestamps, and contacts can unsubscribe at any time.
			</p>
			<p>
				<label>
					<input type="checkbox" name="gdpr_compliance_mode" value="1" <?php checked( $settings['gdpr_compliance_mode'], 1 ); ?>>
					<strong>Enable GDPR Compliance Mode</strong>
				</label>
				<br>
				<small style="color:#666;">When enabled, only contacts with explicit marketing consent will receive campaigns.</small>
			</p>
		</div>
		
		<!-- Form Integration Section -->
		<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
			<h3 style="margin-top:0;">Form Integration - Marketing Consent</h3>
			<p style="color:#666; margin-bottom:20px;">
				Add this code snippet to your contact forms to capture marketing consent. When users check the consent checkbox and submit the form, 
				their marketing consent will be automatically updated in the CRM.
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
			
			<div style="background:#fff3cd; padding:15px; border-left:4px solid #ffc107; border-radius:4px;">
				<p style="margin:0; color:#856404;">
					<strong>API Endpoint:</strong> <code><?php echo esc_html( $api_endpoint ); ?></code><br>
					<strong>API Key:</strong> <code><?php echo esc_html( $api_key ); ?></code>
					<a href="<?php echo wp_nonce_url( '?page=gee-woo-crm&view=settings&action=regenerate_api_key', 'regenerate_api_key' ); ?>" class="button button-small" style="margin-left:10px;" onclick="return confirm('Regenerate API key? Existing forms will need to be updated.');">Regenerate</a>
				</p>
			</div>
		</div>
		
		
		<!-- Data Synchronization Section -->
		<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
			<h3 style="margin-top:0;">Data Synchronization</h3>
			<p style="color:#666; margin-bottom:20px;">
				Import customers from WooCommerce (Registered Users + Guest Orders).
			</p>
			<button id="gee-crm-sync-btn" class="gee-crm-btn gee-crm-btn-primary">Sync WooCommerce Customers</button>
			<p id="gee-crm-sync-status" style="margin-top: 10px; color: #666;"></p>
		</div>
		
		<!-- Privacy & Legal Section -->
		<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
			<h3 style="margin-top:0;">Privacy & Legal</h3>
			
			<div style="margin-bottom:15px;">
				<label><strong>Privacy Policy URL:</strong></label><br>
				<input type="url" name="privacy_policy_url" value="<?php echo esc_url( $settings['privacy_policy_url'] ); ?>" style="width:100%; max-width:600px; padding:8px; margin-top:5px;" placeholder="https://yoursite.com/privacy-policy">
				<br>
				<small style="color:#666;">Link to your privacy policy page. This will be included in form snippets.</small>
			</div>
			
			<div style="margin-bottom:15px;">
				<label><strong>Unsubscribe Page URL:</strong></label><br>
				<input type="url" name="unsubscribe_page_url" value="<?php echo esc_url( $settings['unsubscribe_page_url'] ); ?>" style="width:100%; max-width:600px; padding:8px; margin-top:5px;" placeholder="https://yoursite.com/unsubscribe">
				<br>
				<small style="color:#666;">Page where users can unsubscribe from marketing emails.</small>
			</div>
		</div>
		
		<div style="margin-top:30px;">
			<input type="submit" name="gee_save_settings" class="gee-crm-btn gee-crm-btn-primary" value="Save Settings">
		</div>
	</form>
</div>

<script>
function copyToClipboard(element) {
	element.select();
	document.execCommand('copy');
	alert('Code copied to clipboard!');
}

</script>
