<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';

$template_model = new Gee_Woo_CRM_Email_Template();
$template_model->ensure_default_templates();

// Handle form submission
if ( isset( $_POST['gee_create_template'] ) && check_admin_referer( 'gee_create_template_nonce' ) ) {
    $name = sanitize_text_field( $_POST['name'] );
    $subject = sanitize_text_field( $_POST['subject'] );
    $content_html = wp_kses_post( $_POST['content_html'] );
    $description = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';
    $is_default = isset( $_POST['is_default'] ) ? 1 : 0;
    
    if ( $name && $subject && $content_html ) {
        $template_model->create_template( array(
            'name' => $name,
            'subject' => $subject,
            'content_html' => $content_html,
            'description' => $description,
            'is_default' => $is_default
        ) );
        echo '<div class="notice notice-success"><p>Email template created.</p></div>';
    }
}

if ( isset( $_POST['gee_update_template'] ) && check_admin_referer( 'gee_update_template_nonce' ) ) {
    $id = absint( $_POST['template_id'] );
    $name = sanitize_text_field( $_POST['name'] );
    $subject = sanitize_text_field( $_POST['subject'] );
    $content_html = wp_kses_post( $_POST['content_html'] );
    $description = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';
    $is_default = isset( $_POST['is_default'] ) ? 1 : 0;
    
    if ( $id && $name && $subject && $content_html ) {
        $template_model->update_template( $id, array(
            'name' => $name,
            'subject' => $subject,
            'content_html' => $content_html,
            'description' => $description,
            'is_default' => $is_default
        ) );
        echo '<div class="notice notice-success"><p>Email template updated.</p></div>';
        $editing_template = null;
    }
}

$editing_template = null;
$edit_id = isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
if ( $edit_id ) {
    $editing_template = $template_model->get_template( $edit_id );
}

$templates = $template_model->get_templates();
?>

<div class="gee-crm-card">
    <?php if ( $editing_template ) : ?>
        <h2>Edit Email Template</h2>
    <?php else : ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Email Templates</h2>
            <a href="?page=gee-woo-crm&view=email-templates&action=edit&id=0" class="gee-crm-btn gee-crm-btn-primary">Create New Template</a>
        </div>
    <?php endif; ?>

    <?php if ( $editing_template || ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' && $_GET['id'] == '0' ) ) : ?>
        <form method="post" id="gee-template-form">
            <?php wp_nonce_field( $editing_template && $editing_template->id ? 'gee_update_template_nonce' : 'gee_create_template_nonce' ); ?>
            <?php if ( $editing_template && $editing_template->id ) : ?>
                <input type="hidden" name="template_id" value="<?php echo esc_attr( $editing_template->id ); ?>">
            <?php endif; ?>

            <p>
                <label><strong>Template Name *</strong></label><br>
                <input type="text" name="name" value="<?php echo $editing_template ? esc_attr( $editing_template->name ) : ''; ?>" required style="width:100%; max-width:600px; padding:8px;">
            </p>

            <p>
                <label><strong>Description (optional):</strong></label><br>
                <textarea name="description" rows="2" style="width:100%; max-width:600px; padding:8px;"><?php echo $editing_template ? esc_textarea( $editing_template->description ) : ''; ?></textarea>
            </p>

            <p>
                <label><strong>Email Subject *</strong></label><br>
                <input type="text" name="subject" value="<?php echo $editing_template ? esc_attr( $editing_template->subject ) : ''; ?>" required style="width:100%; max-width:600px; padding:8px;" placeholder="e.g. Welcome to our newsletter!">
            </p>

            <!-- FluentCRM-like Block Editor -->
            <div class="gee-crm-block-editor-wrapper">
                <div class="gee-crm-block-editor-sidebar">
                    <h3>Content Blocks</h3>
                    <div class="gee-crm-blocks-list">
                        <div class="gee-crm-block-item" data-block-type="text">
                            <span class="dashicons dashicons-text"></span>
                            <span>Text</span>
                        </div>
                        <div class="gee-crm-block-item" data-block-type="heading">
                            <span class="dashicons dashicons-heading"></span>
                            <span>Heading</span>
                        </div>
                        <div class="gee-crm-block-item" data-block-type="button">
                            <span class="dashicons dashicons-admin-links"></span>
                            <span>Button</span>
                        </div>
                        <div class="gee-crm-block-item" data-block-type="divider">
                            <span class="dashicons dashicons-minus"></span>
                            <span>Divider</span>
                        </div>
                        <div class="gee-crm-block-item" data-block-type="spacer">
                            <span class="dashicons dashicons-arrow-down-alt"></span>
                            <span>Spacer</span>
                        </div>
                        <div class="gee-crm-block-item" data-block-type="html">
                            <span class="dashicons dashicons-editor-code"></span>
                            <span>HTML</span>
                        </div>
                    </div>
                    
                    <div class="gee-crm-variables-section">
                        <h4>Variables</h4>
                        <div class="gee-crm-variables-list">
                            <button type="button" class="gee-crm-var-btn" data-var="{first_name}">{first_name}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{last_name}">{last_name}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{full_name}">{full_name}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{email}">{email}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{phone}">{phone}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{total_spent}">{total_spent}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{order_count}">{order_count}</button>
                            <button type="button" class="gee-crm-var-btn" data-var="{site_name}">{site_name}</button>
                        </div>
                    </div>
                </div>

                <div class="gee-crm-block-editor-canvas">
                    <div class="gee-crm-canvas-header">
                        <span class="dashicons dashicons-email-alt"></span>
                        <strong>Email Content</strong>
                    </div>
                    <div id="gee-crm-blocks-container" class="gee-crm-blocks-container">
                        <?php if ( $editing_template && $editing_template->content_html ) : ?>
                            <!-- Blocks will be loaded from existing HTML -->
                        <?php endif; ?>
                    </div>
                    <div class="gee-crm-empty-state" id="gee-crm-empty-state">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <p>Click a block from the sidebar to add content</p>
                    </div>
                </div>
            </div>

            <!-- Hidden field for HTML output -->
            <textarea name="content_html" id="gee-crm-html-output" style="display:none;"><?php echo $editing_template ? esc_textarea( $editing_template->content_html ) : ''; ?></textarea>

            <p style="margin-top:20px;">
                <label>
                    <input type="checkbox" name="is_default" value="1" <?php checked( $editing_template && $editing_template->is_default, 1 ); ?>>
                    <strong>Set as Default Template</strong>
                </label>
            </p>

            <div style="margin-top:20px;">
                <?php if ( $editing_template && $editing_template->id ) : ?>
                    <input type="submit" name="gee_update_template" class="gee-crm-btn gee-crm-btn-primary" value="Update Template">
                <?php else : ?>
                    <input type="submit" name="gee_create_template" class="gee-crm-btn gee-crm-btn-primary" value="Create Template">
                <?php endif; ?>
                <a href="?page=gee-woo-crm&view=email-templates" class="gee-crm-btn" style="margin-left:10px;">Cancel</a>
            </div>
        </form>
    <?php else : ?>
        <?php if ( ! empty( $templates ) ) : ?>
            <table class="gee-crm-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $templates as $template ) : ?>
                        <tr>
                            <td><?php echo esc_html( $template->name ); ?></td>
                            <td><?php echo esc_html( $template->subject ); ?></td>
                            <td><?php echo $template->is_default ? '<span style="color:#4e28a5;font-weight:600;">Yes</span>' : 'No'; ?></td>
                            <td>
                                <a href="?page=gee-woo-crm&view=email-templates&action=edit&id=<?php echo $template->id; ?>" class="gee-crm-btn" style="padding:4px 8px;font-size:12px;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No templates found. <a href="?page=gee-woo-crm&view=email-templates&action=edit&id=0">Create your first template</a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Block Editor JavaScript will be in a separate file
    if (typeof GeeCRMBlockEditor !== 'undefined') {
        GeeCRMBlockEditor.init();
    }
});
</script>

