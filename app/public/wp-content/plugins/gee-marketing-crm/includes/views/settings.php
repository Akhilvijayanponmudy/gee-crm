<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-settings.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';

$settings_model = new Gee_Woo_CRM_Settings();
$template_model = new Gee_Woo_CRM_Email_Template();
$tag_model = new Gee_Woo_CRM_Tag();
$all_tags = $tag_model->get_tags();

// Ensure default tag exists
$settings = $settings_model->get_settings();
if ( empty( $all_tags ) ) {
	// Create default tag if no tags exist
	$default_tag_id = $tag_model->create_tag( 'Form Submission' );
	if ( $default_tag_id && ! is_wp_error( $default_tag_id ) ) {
		// Set as default form tag
		if ( ! isset( $settings['default_form_tag'] ) || $settings['default_form_tag'] == 0 ) {
			$settings_model->update_settings( array( 'default_form_tag' => $default_tag_id ) );
		}
		$all_tags = $tag_model->get_tags(); // Refresh tags list
		$settings = $settings_model->get_settings(); // Refresh settings
	}
} elseif ( ! isset( $settings['default_form_tag'] ) || $settings['default_form_tag'] == 0 ) {
	// If tags exist but no default is set, use the first tag
	$first_tag = reset( $all_tags );
	if ( $first_tag ) {
		$settings_model->update_settings( array( 'default_form_tag' => $first_tag->id ) );
		$settings = $settings_model->get_settings(); // Refresh settings
	}
}

/**
 * Get HTML form checkbox snippet (just the form element)
 */
function gee_get_form_checkbox_html( $settings_model, $default_tag_id = 0 ) {
	$privacy_url = $settings_model->get_setting( 'privacy_policy_url' );
	$default_tag = $default_tag_id > 0 ? absint( $default_tag_id ) : 0;
	
	$input_attributes = 'type="checkbox" name="gee_crm_marketing_consent" id="gee-crm-consent" value="1" required';
	if ( $default_tag > 0 ) {
		$input_attributes .= ' data-gee-crm-tag-id="' . esc_attr( $default_tag ) . '"';
	}
	
	$snippet = '<div style="margin:15px 0;" data-gee-crm-tag-id="' . esc_attr( $default_tag ) . '">
	<label>
		<input ' . $input_attributes . '>
		I agree to receive marketing emails';
	
	if ( $privacy_url ) {
		$snippet .= ' (<a href="' . esc_url( $privacy_url ) . '" target="_blank">Privacy Policy</a>)';
	}
	
	$snippet .= '
	</label>
</div>';
	
	return $snippet;
}

/**
 * Get JavaScript handler snippet (separate from form)
 */
function gee_get_form_handler_js( $api_endpoint, $api_key ) {
	return 'document.addEventListener("DOMContentLoaded", function() {
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
					var payload = {
						email: email,
						first_name: firstName,
						last_name: lastName,
						marketing_consent: true
					};
					
				// Get tag ID from the checkbox input first, then fallback to container
				var tagId = 0;
				if (consentCheckbox && consentCheckbox.hasAttribute(\'data-gee-crm-tag-id\')) {
					tagId = parseInt(consentCheckbox.getAttribute(\'data-gee-crm-tag-id\')) || 0;
				} else {
					var tagContainer = document.querySelector(\'[data-gee-crm-tag-id]\');
					tagId = tagContainer ? parseInt(tagContainer.getAttribute(\'data-gee-crm-tag-id\')) || 0 : 0;
				}
				if (tagId > 0) {
					payload.tags = [tagId];
				}
					
					fetch("' . esc_url( $api_endpoint ) . '", {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
							"X-API-Key": "' . esc_js( $api_key ) . '"
						},
						body: JSON.stringify(payload)
					}).catch(function(err) {
						console.log("Gee CRM: Consent update failed", err);
					});
				}
			}
		});
	});
});';
}

/**
 * Get JavaScript function snippet (for custom implementations)
 */
function gee_get_form_function_js( $api_endpoint, $api_key, $default_tag_id = 0 ) {
	$default_tag = $default_tag_id > 0 ? absint( $default_tag_id ) : 0;
	return 'function geeCrmUpdateConsent(formData, tagId) {
	var consentCheckbox = document.querySelector(\'[name="gee_crm_marketing_consent"]\');
	if (consentCheckbox && consentCheckbox.checked) {
		var email = formData.email || formData["your-email"] || "";
		var firstName = formData.first_name || formData["first-name"] || "";
		var lastName = formData.last_name || formData["last-name"] || "";
		
		// Use provided tagId, or fallback to default tag
		var finalTagId = tagId || ' . $default_tag . ' || 0;
		
		if (email) {
			var payload = {
				email: email,
				first_name: firstName,
				last_name: lastName,
				marketing_consent: true
			};
			
			if (finalTagId > 0) {
				payload.tags = [finalTagId];
			}
			
			fetch("' . esc_url( $api_endpoint ) . '", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					"X-API-Key": "' . esc_js( $api_key ) . '"
				},
				body: JSON.stringify(payload)
			}).catch(function(err) {
				console.log("Gee CRM: Consent update failed", err);
			});
		}
	}
}';
}

// Handle Settings Save
if ( isset( $_POST['gee_save_settings'] ) && check_admin_referer( 'gee_save_settings_nonce' ) ) {
		$settings = array(
			'privacy_policy_url' => esc_url_raw( $_POST['privacy_policy_url'] ),
			'unsubscribe_page_url' => esc_url_raw( $_POST['unsubscribe_page_url'] ),
			'gdpr_compliance_mode' => isset( $_POST['gdpr_compliance_mode'] ) ? 1 : 0,
			'test_email' => isset( $_POST['test_email'] ) ? sanitize_email( $_POST['test_email'] ) : get_option( 'admin_email' ),
			'default_form_tag' => isset( $_POST['default_form_tag'] ) ? absint( $_POST['default_form_tag'] ) : 0,
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

// Settings already loaded above
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
			<h3 style="margin-top:0;">Default Tag Assignment</h3>
			<p style="color:#666; margin-bottom:20px;">
				Select a default tag to automatically assign to contacts when they submit forms using the code snippets below. You can also specify different tags for different forms by modifying the code snippets.
			</p>
			
			<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
				<label><strong>Default/Common Tag for Form Submissions:</strong></label><br>
				<select name="default_form_tag" id="default-form-tag" style="width:100%; max-width:400px; padding:8px; margin-top:5px;">
					<option value="0">-- No Default Tag --</option>
					<?php foreach ( $all_tags as $tag ) : ?>
						<option value="<?php echo esc_attr( $tag->id ); ?>" <?php selected( $settings['default_form_tag'] ?? 0, $tag->id ); ?>><?php echo esc_html( $tag->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<br>
				<small style="color:#666;">This is the default/common tag that will be used when no specific tag is specified in the form code. You can override this for specific forms by changing the tag ID in the code snippet. This allows you to use the same form code in multiple areas and just change the tag parameter.</small>
			</div>
			
			<div style="background:#f0f8ff; padding:15px; border:1px solid #2271b1; border-radius:4px; margin-bottom:20px;">
				<h4 style="margin-top:0; color:#2271b1;">📋 Available Tags & Their IDs</h4>
				<p style="color:#666; margin-bottom:10px;">Use these tag IDs in your form code. Change <code>data-gee-crm-tag-id="X"</code> to the ID of the tag you want to assign.</p>
				<?php if ( ! empty( $all_tags ) ) : ?>
					<table style="width:100%; border-collapse:collapse; margin-top:10px;">
						<thead>
							<tr style="background:#e7f3ff;">
								<th style="padding:10px; text-align:left; border:1px solid #ddd;">Tag ID</th>
								<th style="padding:10px; text-align:left; border:1px solid #ddd;">Tag Name</th>
								<th style="padding:10px; text-align:left; border:1px solid #ddd;">Contacts</th>
								<th style="padding:10px; text-align:left; border:1px solid #ddd;">Example Usage</th>
							</tr>
						</thead>
						<tbody>
							<?php 
							$default_tag_id = $settings['default_form_tag'] ?? 0;
							foreach ( $all_tags as $tag ) : 
								$tag_contact_count = $tag_model->get_contact_count( $tag->id );
								$is_default = ( $tag->id == $default_tag_id );
							?>
								<tr style="<?php echo $is_default ? 'background:#e7f3ff;' : ''; ?>">
									<td style="padding:10px; border:1px solid #ddd; font-weight:bold; color:#2271b1;"><?php echo esc_html( $tag->id ); ?></td>
									<td style="padding:10px; border:1px solid #ddd;">
										<?php echo esc_html( $tag->name ); ?>
										<?php if ( $is_default ) : ?>
											<span style="background:#2271b1; color:#fff; padding:2px 8px; border-radius:3px; font-size:11px; margin-left:8px;">Default</span>
										<?php endif; ?>
									</td>
									<td style="padding:10px; border:1px solid #ddd;"><?php echo esc_html( number_format_i18n( $tag_contact_count ) ); ?></td>
									<td style="padding:10px; border:1px solid #ddd; font-family:monospace; font-size:12px; color:#666;">
										<code>data-gee-crm-tag-id="<?php echo esc_attr( $tag->id ); ?>"</code>
										<?php if ( $is_default ) : ?>
											<br><small style="color:#2271b1;">← Currently used as default</small>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p style="color:#666; margin:10px 0;">No tags created yet. <a href="<?php echo esc_url( admin_url( 'admin.php?page=gee-woo-crm-tags' ) ); ?>">Create tags here</a> to organize your contacts.</p>
				<?php endif; ?>
			</div>
			
			<h3 style="margin-top:30px;">Step 1: Add the Consent Checkbox to Your Form</h3>
			<p style="color:#666; margin-bottom:15px;">
				<strong>What this does:</strong> This is the checkbox that users will see and check to give marketing consent. Add this HTML code inside your form, wherever you want the consent checkbox to appear.
			</p>
			<p style="color:#666; margin-bottom:20px;">
				<strong>Tag Configuration:</strong> Change the <code>data-gee-crm-tag-id</code> value to assign a specific tag. For example:
				<ul style="margin:10px 0; padding-left:25px; color:#666;">
					<li>Checkout form: <code>data-gee-crm-tag-id="5"</code> (where 5 is your "Checkout" tag ID)</li>
					<li>Newsletter form: <code>data-gee-crm-tag-id="10"</code> (where 10 is your "Newsletter" tag ID)</li>
					<li>Leave as default (or set to 0): Uses the default/common tag from settings above</li>
				</ul>
			</p>
			
			<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
				<h4 style="margin-top:0; color:#2271b1;">📝 Form Checkbox HTML (Add this to your form):</h4>
				<p style="color:#666; margin-bottom:10px; font-size:13px;">
					<strong>Note:</strong> The code below includes <code>data-gee-crm-tag-id="<?php echo esc_html( $settings['default_form_tag'] ?? 0 ); ?>"</code> which assigns the default tag. 
					You can change this value to use a different tag (see the tag IDs table above).
				</p>
				<textarea readonly id="form-checkbox-html" style="width:100%; height:120px; font-family:monospace; font-size:12px; padding:10px; background:#f5f5f5; border:1px solid #ddd;" onclick="this.select();"><?php echo esc_textarea( gee_get_form_checkbox_html( $settings_model, $settings['default_form_tag'] ?? 0 ) ); ?></textarea>
				<button type="button" class="button" onclick="copyToClipboard(document.getElementById('form-checkbox-html'));" style="margin-top:10px;">Copy Checkbox Code</button>
				<?php 
				$default_tag_id = $settings['default_form_tag'] ?? 0;
				if ( $default_tag_id > 0 ) {
					$default_tag = $tag_model->get_tag( $default_tag_id );
					if ( $default_tag ) {
						echo '<p style="margin-top:10px; color:#2271b1; font-size:13px;"><strong>✓ Default tag assigned:</strong> "' . esc_html( $default_tag->name ) . '" (ID: ' . esc_html( $default_tag_id ) . ')</p>';
					}
				}
				?>
			</div>
			
			<h3 style="margin-top:30px;">Step 2: Add the JavaScript Handler</h3>
			<p style="color:#666; margin-bottom:15px;">
				<strong>What this does:</strong> This JavaScript code automatically sends the contact information to your CRM when the form is submitted. Add this code once per page (usually in the footer or before the closing <code>&lt;/body&gt;</code> tag).
			</p>
			<p style="color:#666; margin-bottom:20px;">
				<strong>How it works:</strong> The script automatically finds the checkbox you added in Step 1, reads the tag ID from the <code>data-gee-crm-tag-id</code> attribute, and sends the contact data to your CRM when the form is submitted.
			</p>
			
			<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
				<h4 style="margin-top:0; color:#2271b1;">⚙️ JavaScript Handler (Add this to your page):</h4>
				<textarea readonly id="form-handler-js" style="width:100%; height:200px; font-family:monospace; font-size:12px; padding:10px; background:#f5f5f5; border:1px solid #ddd;" onclick="this.select();"><script>
<?php echo esc_textarea( gee_get_form_handler_js( $api_endpoint, $api_key ) ); ?>
</script></textarea>
				<button type="button" class="button" onclick="copyToClipboard(document.getElementById('form-handler-js'));" style="margin-top:10px;">Copy JavaScript Code</button>
			</div>
			
			<div style="background:#e7f3ff; padding:20px; border-left:4px solid #2271b1; border-radius:4px; margin-top:20px;">
				<h4 style="margin-top:0; color:#2271b1;">📖 Complete Integration Guide</h4>
				<ol style="margin:10px 0; padding-left:25px; line-height:2; color:#333;">
					<li><strong>Copy the checkbox HTML</strong> from Step 1 and paste it inside your form where you want the consent checkbox to appear.</li>
					<li><strong>Change the tag ID</strong> if needed: Modify <code>data-gee-crm-tag-id="X"</code> to use a specific tag for this form. If you want to use the default tag, leave it as is.</li>
					<li><strong>Copy the JavaScript code</strong> from Step 2 and add it to your page (footer, header, or before closing <code>&lt;/body&gt;</code> tag). You only need to add this once per page, even if you have multiple forms.</li>
					<li><strong>Test it:</strong> Submit your form and check if the contact appears in your CRM with the correct tag assigned.</li>
				</ol>
				<p style="margin:15px 0 0 0; line-height:1.8; color:#333;">
					<strong>💡 Pro Tip:</strong> You can use the same form code in multiple places (checkout, newsletter, contact form) and just change the <code>data-gee-crm-tag-id</code> value to assign different tags. If no tag is specified (or set to 0), contacts will be assigned to the default/common tag you selected above.
				</p>
			</div>
			
			<h3 style="margin-top:40px;">Alternative: JavaScript Function (For Custom Forms)</h3>
			<p style="color:#666; margin-bottom:15px;">
				<strong>When to use this:</strong> If you're building a custom form with your own JavaScript handler, use this function instead. Call it when your form is submitted.
			</p>
			
			<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
				<h4 style="margin-top:0; color:#2271b1;">🔧 JavaScript Function (For custom implementations):</h4>
				<textarea readonly id="form-function-js" style="width:100%; height:180px; font-family:monospace; font-size:12px; padding:10px; background:#f5f5f5; border:1px solid #ddd;" onclick="this.select();"><?php echo esc_textarea( gee_get_form_function_js( $api_endpoint, $api_key, $settings['default_form_tag'] ?? 0 ) ); ?></textarea>
				<button type="button" class="button" onclick="copyToClipboard(document.getElementById('form-function-js'));" style="margin-top:10px;">Copy Function Code</button>
				<p style="margin-top:10px; color:#666; font-size:13px;">
					<strong>Usage example:</strong><br>
					<code style="background:#f5f5f5; padding:5px; display:block; margin-top:5px;">
					geeCrmUpdateConsent({<br>
					&nbsp;&nbsp;email: "user@example.com",<br>
					&nbsp;&nbsp;first_name: "John",<br>
					&nbsp;&nbsp;last_name: "Doe"<br>
					}, 5); // Pass tag ID as second parameter (optional - uses default if not provided)
					</code>
				</p>
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
			
			<div style="background:#e7f3ff; padding:20px; border-left:4px solid #2271b1; border-radius:4px; margin-top:20px;">
				<h4 style="margin-top:0; color:#2271b1;">🏷️ Understanding Tags</h4>
				<p style="margin:10px 0; line-height:1.8; color:#333;">
					<strong>What are tags?</strong> Tags help you organize contacts based on where they signed up. For example, you might have tags like "Newsletter", "Checkout", "Contact Form", etc.
				</p>
				<p style="margin:10px 0; line-height:1.8; color:#333;">
					<strong>How tags work in forms:</strong>
				</p>
				<ul style="margin:10px 0; padding-left:25px; line-height:1.8; color:#333;">
					<li>Each form can assign a specific tag to contacts when they submit</li>
					<li>If you don't specify a tag (or set it to 0), contacts get the default/common tag you selected above</li>
					<li>You can use the same form code everywhere and just change the tag ID for different areas</li>
					<li>Create tags in the <a href="<?php echo esc_url( admin_url( 'admin.php?page=gee-woo-crm-tags' ) ); ?>" target="_blank">Tags section</a> and use their IDs in your forms</li>
				</ul>
				<p style="margin:15px 0 0 0; line-height:1.8; color:#333;">
					<strong>Example:</strong> If you have a "Checkout" tag with ID 5, change <code>data-gee-crm-tag-id="0"</code> to <code>data-gee-crm-tag-id="5"</code> in your checkout form. All contacts from that form will be tagged as "Checkout".
				</p>
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
	var apiEndpoint = '<?php echo esc_js( $api_endpoint ); ?>';
	var apiKey = '<?php echo esc_js( $api_key ); ?>';
	var privacyUrl = '<?php echo esc_js( esc_url( $settings['privacy_policy_url'] ?? '' ) ); ?>';
	
	// Update form snippets when tag selection changes
	function updateFormSnippets() {
		var defaultTagId = $('#default-form-tag').val() || 0;
		
		// Update checkbox HTML
		var privacyLink = privacyUrl ? ' (<a href="' + privacyUrl + '" target="_blank">Privacy Policy</a>)' : '';
		var inputAttr = 'type="checkbox" name="gee_crm_marketing_consent" id="gee-crm-consent" value="1" required';
		if (defaultTagId > 0) {
			inputAttr += ' data-gee-crm-tag-id="' + defaultTagId + '"';
		}
		var checkboxHtml = '<div style="margin:15px 0;" data-gee-crm-tag-id="' + defaultTagId + '">\n' +
			'\t<label>\n' +
			'\t\t<input ' + inputAttr + '>\n' +
			'\t\tI agree to receive marketing emails' + privacyLink + '\n' +
			'\t</label>\n' +
			'</div>';
		
		// Update JavaScript handler
		var handlerJs = '<script>\n' +
			'document.addEventListener("DOMContentLoaded", function() {\n' +
			'\tvar forms = document.querySelectorAll("form");\n' +
			'\tforms.forEach(function(form) {\n' +
			'\t\tform.addEventListener("submit", function(e) {\n' +
			'\t\t\tvar consentCheckbox = document.getElementById("gee-crm-consent");\n' +
			'\t\t\tif (consentCheckbox && consentCheckbox.checked) {\n' +
			'\t\t\t\tvar formData = new FormData(form);\n' +
			'\t\t\t\tvar email = formData.get("email") || formData.get("your-email") || formData.get("e-mail") || "";\n' +
			'\t\t\t\tvar firstName = formData.get("first_name") || formData.get("first-name") || formData.get("fname") || "";\n' +
			'\t\t\t\tvar lastName = formData.get("last_name") || formData.get("last-name") || formData.get("lname") || "";\n\n' +
			'\t\t\t\tif (email) {\n' +
			'\t\t\t\t\tvar payload = {\n' +
			'\t\t\t\t\t\temail: email,\n' +
			'\t\t\t\t\t\tfirst_name: firstName,\n' +
			'\t\t\t\t\t\tlast_name: lastName,\n' +
			'\t\t\t\t\t\tmarketing_consent: true\n' +
			'\t\t\t\t\t};\n\n' +
					'\t\t\t\t\t// Get tag ID from the checkbox input first, then fallback to container\n' +
					'\t\t\t\t\tvar tagId = 0;\n' +
					'\t\t\t\t\tif (consentCheckbox && consentCheckbox.hasAttribute(\'data-gee-crm-tag-id\')) {\n' +
					'\t\t\t\t\t\ttagId = parseInt(consentCheckbox.getAttribute(\'data-gee-crm-tag-id\')) || 0;\n' +
					'\t\t\t\t\t} else {\n' +
					'\t\t\t\t\t\tvar tagContainer = document.querySelector(\'[data-gee-crm-tag-id]\');\n' +
					'\t\t\t\t\t\ttagId = tagContainer ? parseInt(tagContainer.getAttribute(\'data-gee-crm-tag-id\')) || 0 : 0;\n' +
					'\t\t\t\t\t}\n' +
					'\t\t\t\t\tif (tagId > 0) {\n' +
					'\t\t\t\t\t\tpayload.tags = [tagId];\n' +
					'\t\t\t\t\t}\n\n' +
			'\t\t\t\t\tfetch("' + apiEndpoint + '", {\n' +
			'\t\t\t\t\t\tmethod: "POST",\n' +
			'\t\t\t\t\t\theaders: {\n' +
			'\t\t\t\t\t\t\t"Content-Type": "application/json",\n' +
			'\t\t\t\t\t\t\t"X-API-Key": "' + apiKey + '"\n' +
			'\t\t\t\t\t\t},\n' +
			'\t\t\t\t\t\tbody: JSON.stringify(payload)\n' +
			'\t\t\t\t\t}).catch(function(err) {\n' +
			'\t\t\t\t\t\tconsole.log("Gee CRM: Consent update failed", err);\n' +
			'\t\t\t\t\t});\n' +
			'\t\t\t\t}\n' +
			'\t\t\t}\n' +
			'\t\t});\n' +
			'\t});\n' +
			'});\n' +
			'<\/script>';
		
		// Update JavaScript function
		var functionJs = 'function geeCrmUpdateConsent(formData, tagId) {\n' +
			'\tvar consentCheckbox = document.querySelector(\'[name="gee_crm_marketing_consent"]\');\n' +
			'\tif (consentCheckbox && consentCheckbox.checked) {\n' +
			'\t\tvar email = formData.email || formData["your-email"] || "";\n' +
			'\t\tvar firstName = formData.first_name || formData["first-name"] || "";\n' +
			'\t\tvar lastName = formData.last_name || formData["last-name"] || "";\n\n' +
			'\t\t// Use provided tagId, or fallback to default tag\n' +
			'\t\tvar finalTagId = tagId || ' + defaultTagId + ' || 0;\n\n' +
			'\t\tif (email) {\n' +
			'\t\t\tvar payload = {\n' +
			'\t\t\t\temail: email,\n' +
			'\t\t\t\tfirst_name: firstName,\n' +
			'\t\t\t\tlast_name: lastName,\n' +
			'\t\t\t\tmarketing_consent: true\n' +
			'\t\t\t};\n\n' +
			'\t\t\tif (finalTagId > 0) {\n' +
			'\t\t\t\tpayload.tags = [finalTagId];\n' +
			'\t\t\t}\n\n' +
			'\t\t\tfetch("' + apiEndpoint + '", {\n' +
			'\t\t\t\tmethod: "POST",\n' +
			'\t\t\t\theaders: {\n' +
			'\t\t\t\t\t"Content-Type": "application/json",\n' +
			'\t\t\t\t\t"X-API-Key": "' + apiKey + '"\n' +
			'\t\t\t\t},\n' +
			'\t\t\t\tbody: JSON.stringify(payload)\n' +
			'\t\t\t}).catch(function(err) {\n' +
			'\t\t\t\tconsole.log("Gee CRM: Consent update failed", err);\n' +
			'\t\t\t});\n' +
			'\t\t}\n' +
			'\t}\n' +
			'}';
		
		$('#form-checkbox-html').val(checkboxHtml);
		$('#form-handler-js').val(handlerJs);
		$('#form-function-js').val(functionJs);
	}
	
	// Update snippets when tag changes
	$('#default-form-tag').on('change', updateFormSnippets);
	
	// Initial update
	updateFormSnippets();
	
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
