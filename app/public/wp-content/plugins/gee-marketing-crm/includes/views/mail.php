<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-settings.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';

$settings_model = new Gee_Woo_CRM_Settings();
$template_model = new Gee_Woo_CRM_Email_Template();

// Handle Settings Save
if ( isset( $_POST['gee_save_mail_settings'] ) && check_admin_referer( 'gee_save_mail_settings_nonce' ) ) {
	$settings = array(
		'thank_you_email_enabled' => isset( $_POST['thank_you_email_enabled'] ) ? 1 : 0,
		'default_campaign_template_id' => isset( $_POST['default_campaign_template_id'] ) ? absint( $_POST['default_campaign_template_id'] ) : 0,
		'default_thank_you_template_id' => isset( $_POST['default_thank_you_template_id'] ) ? absint( $_POST['default_thank_you_template_id'] ) : 0,
	);
	
	$settings_model->update_settings( $settings );
	echo '<div class="notice notice-success is-dismissible"><p>Mail settings saved successfully!</p></div>';
}

$settings = $settings_model->get_settings();
$templates = $template_model->get_templates();
$default_campaign_template_id = $settings_model->get_setting( 'default_campaign_template_id', 0 );
$default_thank_you_template_id = $settings_model->get_setting( 'default_thank_you_template_id', 0 );
?>

<div class="gee-crm-card">
	<h2>Mail Settings</h2>
	
	<form method="post" id="gee-mail-settings-form">
		<?php wp_nonce_field( 'gee_save_mail_settings_nonce' ); ?>
		
		<!-- Default Campaign Template Section -->
		<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
			<h3 style="margin-top:0;">Default Campaign Email Template</h3>
			<p style="color:#666; margin-bottom:20px;">
				Select the default email template to use for campaigns. This template will be pre-selected when creating new campaigns.
			</p>
			
			<div style="margin-bottom:15px;">
				<label><strong>Select Default Campaign Template:</strong></label><br>
				<select name="default_campaign_template_id" id="default-campaign-template" style="width:100%; max-width:500px; padding:8px; margin-top:5px;">
					<option value="0">-- No Default Template --</option>
					<?php foreach ( $templates as $template ) : ?>
						<?php 
						// Show all templates except "Thank You" templates (they're for thank you emails)
						$is_thank_you = strpos( strtolower( $template->name ), 'thank' ) !== false;
						if ( ! $is_thank_you ) : 
						?>
							<option value="<?php echo $template->id; ?>" <?php selected( $default_campaign_template_id, $template->id ); ?>>
								<?php echo esc_html( $template->name ); ?><?php echo $template->is_default ? ' (Default)' : ''; ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<br>
				<small style="color:#666;">This template will be used as the default when creating new campaigns.</small>
			</div>
			
			<?php if ( $default_campaign_template_id ) : 
				$selected_template = $template_model->get_template( $default_campaign_template_id );
				if ( $selected_template ) :
			?>
				<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-top:15px;">
					<h4 style="margin-top:0;">Selected Template Preview:</h4>
					<p><strong>Name:</strong> <?php echo esc_html( $selected_template->name ); ?></p>
					<p><strong>Subject:</strong> <?php echo esc_html( $selected_template->subject ); ?></p>
					<a href="?page=gee-woo-crm&view=email-templates&action=edit&id=<?php echo $selected_template->id; ?>" class="button button-small">Edit Template</a>
					<button type="button" class="button button-small" onclick="previewTemplate(<?php echo $selected_template->id; ?>)">Preview</button>
				</div>
			<?php endif; endif; ?>
		</div>
		
		<!-- Default Thank You Email Template Section -->
		<div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:30px;">
			<h3 style="margin-top:0;">Default Thank You Email Template</h3>
			<p style="color:#666; margin-bottom:20px;">
				Select the default email template to send when a contact subscribes to marketing emails. You can also enable/disable thank you emails.
			</p>
			
			<p>
				<label>
					<input type="checkbox" name="thank_you_email_enabled" value="1" <?php checked( $settings['thank_you_email_enabled'], 1 ); ?>>
					<strong>Enable Thank You Email</strong>
				</label>
				<br>
				<small style="color:#666;">Send an email when a contact grants marketing consent.</small>
			</p>
			
			<div style="margin-top:20px;">
				<label><strong>Select Default Thank You Template:</strong></label><br>
				<select name="default_thank_you_template_id" id="default-thank-you-template" style="width:100%; max-width:500px; padding:8px; margin-top:5px;">
					<option value="0">-- No Default Template --</option>
					<?php 
					// Get thank you templates (templates with "thank" in name)
					foreach ( $templates as $template ) : 
						$is_thank_you = strpos( strtolower( $template->name ), 'thank' ) !== false;
						if ( $is_thank_you ) :
					?>
						<option value="<?php echo $template->id; ?>" <?php selected( $default_thank_you_template_id, $template->id ); ?>>
							<?php echo esc_html( $template->name ); ?>
						</option>
					<?php 
						endif;
					endforeach; 
					?>
				</select>
				<br>
				<small style="color:#666;">This template will be sent when a contact subscribes to marketing emails.</small>
			</div>
			
			<?php if ( $default_thank_you_template_id ) : 
				$selected_thank_you_template = $template_model->get_template( $default_thank_you_template_id );
				if ( $selected_thank_you_template ) :
			?>
				<div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px; margin-top:15px;">
					<h4 style="margin-top:0;">Selected Template Preview:</h4>
					<p><strong>Name:</strong> <?php echo esc_html( $selected_thank_you_template->name ); ?></p>
					<p><strong>Subject:</strong> <?php echo esc_html( $selected_thank_you_template->subject ); ?></p>
					<a href="?page=gee-woo-crm&view=email-templates&action=edit&id=<?php echo $selected_thank_you_template->id; ?>" class="button button-small">Edit Template</a>
					<button type="button" class="button button-small" onclick="previewTemplate(<?php echo $selected_thank_you_template->id; ?>)">Preview</button>
				</div>
			<?php endif; endif; ?>
		</div>
		
		<div style="margin-top:30px;">
			<input type="submit" name="gee_save_mail_settings" class="gee-crm-btn gee-crm-btn-primary" value="Save Mail Settings">
		</div>
	</form>
</div>

<!-- Template Preview Modal -->
<div id="template-preview-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); z-index:10000; align-items:center; justify-content:center; overflow-y:auto; padding:20px;">
	<div style="background:#fff; padding:30px; border-radius:8px; max-width:800px; width:90%; margin:20px auto;">
		<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
			<h2 style="margin:0;">Email Template Preview</h2>
			<button type="button" id="close-template-preview-modal" class="button" style="font-size:20px; line-height:1; padding:5px 10px;">×</button>
		</div>
		
		<div style="background:#f5f5f5; padding:15px; border-radius:4px; margin-bottom:20px;">
			<p style="margin:0;"><strong>Subject:</strong> <span id="preview-template-subject"></span></p>
			<p style="margin:5px 0 0 0; color:#666; font-size:13px;">
				<em>This is a preview with sample data. Variables have been replaced with example values.</em>
			</p>
		</div>
		
		<div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:20px; max-width:100%; overflow-x:auto;">
			<iframe
				id="template-preview-iframe"
				title="Email Template Preview"
				style="width:100%; height:600px; border:none; background:#fff;"
				srcdoc="">
			</iframe>
		</div>
	</div>
</div>

<script>
function previewTemplate(templateId) {
	jQuery.post(geeWooCRM.ajaxurl, {
		action: 'gee_get_template',
		nonce: geeWooCRM.nonce,
		template_id: templateId
	}, function(response) {
		if (response.success && response.data) {
			var template = response.data;
			
			// Replace variables with sample data
			var previewContent = template.content_html
				.replace(/{first_name}/g, 'John')
				.replace(/{last_name}/g, 'Doe')
				.replace(/{full_name}/g, 'John Doe')
				.replace(/{email}/g, 'john.doe@example.com')
				.replace(/{site_name}/g, '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>')
				.replace(/{site_url}/g, '<?php echo esc_js( home_url() ); ?>')
				.replace(/{unsubscribe_link}/g, '<?php echo esc_js( home_url( '/wp-json/gee-crm/v1/unsubscribe?email=john.doe@example.com' ) ); ?>')
				.replace(/{total_spent}/g, '$1,250.00')
				.replace(/{order_count}/g, '5')
				.replace(/{last_order_date}/g, '<?php echo esc_js( date( 'F j, Y' ) ); ?>')
				.replace(/{current_date}/g, '<?php echo esc_js( date( 'F j, Y' ) ); ?>');
			
			var previewSubject = template.subject
				.replace(/{first_name}/g, 'John')
				.replace(/{last_name}/g, 'Doe')
				.replace(/{full_name}/g, 'John Doe')
				.replace(/{email}/g, 'john.doe@example.com')
				.replace(/{site_name}/g, '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>')
				.replace(/{site_url}/g, '<?php echo esc_js( home_url() ); ?>');
			
			jQuery('#preview-template-subject').text(previewSubject);
			jQuery('#template-preview-iframe').attr('srcdoc', previewContent);
			jQuery('#template-preview-modal').css('display', 'flex');
		} else {
			alert('Failed to load template preview.');
		}
	});
}

jQuery(document).ready(function($) {
	// Close preview modal
	$('#close-template-preview-modal, #template-preview-modal').on('click', function(e) {
		if (e.target === this || $(e.target).attr('id') === 'close-template-preview-modal') {
			$('#template-preview-modal').hide();
		}
	});
	
	// Prevent modal close when clicking inside
	$('#template-preview-modal > div').on('click', function(e) {
		e.stopPropagation();
	});
});
</script>

