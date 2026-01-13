<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';

$template_model = new Gee_Woo_CRM_Email_Template();

// Ensure default templates exist
$template_model->ensure_default_templates();

// Handle Use Template - Create new template from existing
$using_template = null;
$use_template_id = isset( $_GET['action'] ) && $_GET['action'] == 'use' && isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
if ( $use_template_id ) {
    $using_template = $template_model->get_template( $use_template_id );
    if ( $using_template ) {
        $editing_template = (object) array(
            'id' => 0,
            'name' => $using_template->name . ' (Copy)',
            'subject' => $using_template->subject,
            'content_html' => $using_template->content_html,
            'description' => $using_template->description,
            'is_default' => 0
        );
    }
}

// Handle Edit - Get template to edit
if ( ! isset( $editing_template ) ) {
    $editing_template = null;
    $edit_id = isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( $edit_id ) {
        $editing_template = $template_model->get_template( $edit_id );
    }
}

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
    } else {
        echo '<div class="notice notice-error"><p>Please fill in all required fields.</p></div>';
    }
}

// Handle Update
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
        $editing_template = null; // Clear edit mode
        $edit_id = 0;
    } else {
        echo '<div class="notice notice-error"><p>Please fill in all required fields.</p></div>';
    }
}

// Handle Set Default
if ( isset( $_GET['action'] ) && $_GET['action'] == 'set_default' && isset( $_GET['id'] ) ) {
    if ( check_admin_referer( 'set_default_template_' . absint( $_GET['id'] ) ) ) {
        $template_model->set_default_template( absint( $_GET['id'] ) );
        echo '<div class="notice notice-success"><p>Default template updated.</p></div>';
    }
}

// Handle Delete
if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['id'] ) ) {
    if ( check_admin_referer( 'delete_template_' . absint( $_GET['id'] ) ) ) {
        $template = $template_model->get_template( absint( $_GET['id'] ) );
        if ( $template && $template->is_default ) {
            echo '<div class="notice notice-error"><p>Cannot delete the default template. Please set another template as default first.</p></div>';
        } else {
            $template_model->delete_template( absint( $_GET['id'] ) );
            echo '<div class="notice notice-success"><p>Email template deleted.</p></div>';
        }
    }
}

// Handle Preview
$preview_template = null;
if ( isset( $_GET['action'] ) && $_GET['action'] == 'preview' && isset( $_GET['id'] ) ) {
    $preview_template = $template_model->get_template( absint( $_GET['id'] ) );
    if ( $preview_template ) {
        $preview_data = $template_model->preview_template( $preview_template->content_html, $preview_template->subject );
    }
}

$templates = $template_model->get_templates();

// Show preview if requested
if ( isset( $preview_template ) && $preview_template ) :
?>
<div class="gee-crm-card" style="max-width: 100%;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Preview: <?php echo esc_html( $preview_template->name ); ?></h2>
        <a href="?page=gee-woo-crm&view=email-templates" class="gee-crm-btn">← Back to Templates</a>
    </div>
    
    <div style="background:#f5f5f5; padding:20px; border-radius:4px; margin-bottom:20px;">
        <p><strong>Subject:</strong> <?php echo esc_html( $preview_data['subject'] ); ?></p>
        <p style="color:#666; font-size:13px; margin-top:5px;">
            <em>This is a preview with sample data. Variables have been replaced with example values.</em>
        </p>
    </div>
    
    <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:20px; max-width:800px; margin:0 auto;">
        <iframe
            id="email-preview-iframe"
            title="Email Template Preview"
            style="width:100%; height:800px; border:none; background:#fff;"
            srcdoc="<?php echo esc_attr( $preview_data['content'] ); ?>">
        </iframe>
    </div>
    
    <div style="margin-top:20px; text-align:center;">
        <a href="?page=gee-woo-crm&view=email-templates" class="gee-crm-btn">← Back to Templates</a>
        <a href="?page=gee-woo-crm&view=email-templates&action=edit&id=<?php echo $preview_template->id; ?>" class="gee-crm-btn gee-crm-btn-primary" style="margin-left:10px;">Edit Template</a>
    </div>
</div>
<?php
    return; // Exit early to show only preview
endif;
?>

<div class="gee-crm-card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Email Templates</h2>
    </div>

    <?php if ( ! $editing_template ) : ?>
        <?php if ( ! empty( $templates ) ) : ?>
            <div style="background:#e8f4f8; padding:15px; border:1px solid #b3d9e6; border-radius:4px; margin-bottom:20px;">
                <h3 style="margin-top:0;">Start from a Template</h3>
                <p style="margin-bottom:15px;">Choose a template to use as a starting point:</p>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:15px;">
                    <?php foreach ( $templates as $template ) : ?>
                        <div style="background:#fff; padding:15px; border:1px solid #ddd; border-radius:4px;">
                            <h4 style="margin:0 0 10px 0;">
                                <?php echo esc_html( $template->name ); ?>
                                <?php if ( $template->is_default ) : ?>
                                    <span class="gee-crm-badge" style="background:#4e28a5; color:#fff; margin-left:5px; font-size:11px; padding:2px 6px;">Default</span>
                                <?php endif; ?>
                            </h4>
                            <p style="margin:0 0 10px 0; color:#666; font-size:13px;"><?php echo esc_html( $template->description ? $template->description : 'No description' ); ?></p>
                            <div style="display:flex; gap:8px;">
                                <a href="?page=gee-woo-crm&view=email-templates&action=preview&id=<?php echo $template->id; ?>" class="gee-crm-btn" style="flex:1; text-align:center; background:#2196F3; color:#fff; border-color:#2196F3;">Preview</a>
                                <a href="?page=gee-woo-crm&view=email-templates&action=use&id=<?php echo $template->id; ?>" class="gee-crm-btn gee-crm-btn-primary" style="flex:1; text-align:center;">Use This</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr style="margin:20px 0; border:none; border-top:1px solid #ddd;">
            </div>
        <?php else : ?>
            <div style="background:#fff3cd; padding:15px; border:1px solid #ffc107; border-radius:4px; margin-bottom:20px;">
                <p style="margin:0;"><strong>No templates found.</strong> Please refresh the page to create default templates, or create a new template below.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; margin-bottom:20px;">
        <h3><?php echo $editing_template ? ( $editing_template->id > 0 ? 'Edit Email Template' : 'Create New Email Template from ' . ( $using_template ? esc_html( $using_template->name ) : 'Template' ) ) : 'Create New Email Template'; ?></h3>
        <form method="post" id="gee-template-form">
            <?php if ( $editing_template ) : ?>
                <?php wp_nonce_field( 'gee_update_template_nonce' ); ?>
                <input type="hidden" name="template_id" value="<?php echo esc_attr( $editing_template->id ); ?>">
            <?php else : ?>
                <?php wp_nonce_field( 'gee_create_template_nonce' ); ?>
            <?php endif; ?>
            
            <p>
                <label><strong>Template Name:</strong></label><br>
                <input type="text" name="name" value="<?php echo $editing_template ? esc_attr( $editing_template->name ) : ''; ?>" required style="width:100%; max-width:400px; padding:8px;">
            </p>

            <p>
                <label><strong>Description (optional):</strong></label><br>
                <textarea name="description" rows="2" style="width:100%; max-width:600px; padding:8px;"><?php echo $editing_template ? esc_textarea( $editing_template->description ) : ''; ?></textarea>
            </p>

            <p>
                <label><strong>Email Subject:</strong></label><br>
                <input type="text" name="subject" value="<?php echo $editing_template ? esc_attr( $editing_template->subject ) : ''; ?>" required style="width:100%; max-width:600px; padding:8px;" placeholder="e.g. Welcome to our newsletter!">
            </p>

            <div style="margin-bottom:20px;">
                <label><strong>Email Content *</strong></label><br>
                <small style="color:#666;">Use the visual editor to format text, add images, links, and create professional email templates. You can use variables like {first_name}, {email}, etc.</small>
                <?php
                $template_editor_content = $editing_template ? $editing_template->content_html : '';
                $template_editor_id = 'template-content';
                $template_settings = array(
                    'textarea_name' => 'content_html',
                    'textarea_rows' => 20,
                    'media_buttons' => true, // Enable image/media buttons
                    'teeny' => false, // Full editor, not minimal
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,|,blockquote,|,alignleft,aligncenter,alignright,alignjustify,|,link,unlink,|,forecolor,backcolor,|,hr,|,removeformat,|,fullscreen',
                        'toolbar2' => 'undo,redo,|,outdent,indent,|,image,|,charmap,|,code',
                        'menubar' => true,
                        'plugins' => 'lists,link,paste,hr,textcolor,colorpicker,charmap,image,wordpress,wpautoresize,wpeditimage,wplink,wpdialogs,wpview',
                        'body_class' => 'email-content-editor',
                        'height' => 500,
                    ),
                    'quicktags' => true,
                    'editor_height' => 500,
                    'drag_drop_upload' => true,
                );
                wp_editor( $template_editor_content, $template_editor_id, $template_settings );
                ?>
            </div>



            <p>
                <label>
                    <input type="checkbox" name="is_default" value="1" <?php checked( $editing_template && $editing_template->is_default, 1 ); ?>>
                    <strong>Set as Default Template</strong>
                </label>
                <br>
                <small style="color:#666;">The default template will be used when no specific template is selected.</small>
            </p>

            <div style="margin:10px 0; padding:10px; background:#fff; border:1px solid #ddd; border-radius:4px;">
                <strong>Available Variables:</strong>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; margin-top:10px;">
                    <div>
                        <strong>Contact Information:</strong>
                        <ul style="margin:5px 0; padding-left:20px; font-size:13px;">
                            <li><code>{first_name}</code> - Contact's first name</li>
                            <li><code>{last_name}</code> - Contact's last name</li>
                            <li><code>{full_name}</code> - Contact's full name</li>
                            <li><code>{email}</code> - Contact's email address</li>
                            <li><code>{phone}</code> - Contact's phone number</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Contact Details:</strong>
                        <ul style="margin:5px 0; padding-left:20px; font-size:13px;">
                            <li><code>{status}</code> - Contact status (subscribed, etc.)</li>
                            <li><code>{source}</code> - Contact source</li>
                            <li><code>{created_date}</code> - Date contact joined</li>
                        </ul>
                    </div>
                    <div>
                        <strong>WooCommerce Data:</strong>
                        <ul style="margin:5px 0; padding-left:20px; font-size:13px;">
                            <li><code>{total_spent}</code> - Total purchase value</li>
                            <li><code>{order_count}</code> - Number of orders</li>
                            <li><code>{last_purchase_date}</code> - Last purchase date</li>
                            <li><code>{last_purchase_value}</code> - Last purchase amount</li>
                        </ul>
                    </div>
                    <div>
                        <strong>System Variables:</strong>
                        <ul style="margin:5px 0; padding-left:20px; font-size:13px;">
                            <li><code>{site_name}</code> - Website name</li>
                            <li><code>{site_url}</code> - Website URL</li>
                            <li><code>{current_date}</code> - Current date</li>
                            <li><code>{unsubscribe_link}</code> - Unsubscribe link</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if ( $editing_template && $editing_template->id > 0 ) : ?>
                <input type="submit" name="gee_update_template" class="gee-crm-btn gee-crm-btn-primary" value="Update Template">
                <a href="?page=gee-woo-crm&view=email-templates" class="gee-crm-btn" style="margin-left:10px;">Cancel</a>
            <?php else : ?>
                <input type="submit" name="gee_create_template" class="gee-crm-btn gee-crm-btn-primary" value="<?php echo $editing_template ? 'Create Template from ' . esc_attr( $using_template ? $using_template->name : 'Template' ) : 'Create Template'; ?>">
                <?php if ( $editing_template ) : ?>
                    <a href="?page=gee-woo-crm&view=email-templates" class="gee-crm-btn" style="margin-left:10px;">Cancel</a>
                <?php endif; ?>
            <?php endif; ?>
        </form>
    </div>


    <table class="gee-crm-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Subject</th>
                <th>Description</th>
                <th>Default</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $templates ) : ?>
                <?php foreach ( $templates as $template ) : ?>
                    <tr>
                        <td><?php echo $template->id; ?></td>
                        <td>
                            <strong><?php echo esc_html( $template->name ); ?></strong>
                            <?php if ( $template->is_default ) : ?>
                                <span class="gee-crm-badge" style="background:#4e28a5; color:#fff; margin-left:5px; font-size:11px;">Default</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $template->subject ); ?></td>
                        <td><?php echo esc_html( $template->description ? $template->description : '—' ); ?></td>
                        <td>
                            <?php if ( $template->is_default ) : ?>
                                <span style="color:#4e28a5; font-weight:bold;">✓ Default</span>
                            <?php else : ?>
                                <a href="<?php echo wp_nonce_url( '?page=gee-woo-crm&view=email-templates&action=set_default&id=' . $template->id, 'set_default_template_' . $template->id ); ?>" class="button button-small">Set Default</a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date( 'Y-m-d', strtotime( $template->created_at ) ); ?></td>
                        <td>
                            <a href="?page=gee-woo-crm&view=email-templates&action=preview&id=<?php echo $template->id; ?>" class="gee-crm-btn" style="margin-right:5px; background:#2196F3; color:#fff; border-color:#2196F3;">Preview</a>
                            <a href="?page=gee-woo-crm&view=email-templates&action=edit&id=<?php echo $template->id; ?>" class="gee-crm-btn" style="margin-right:5px;">Edit</a>
                            <?php if ( ! $template->is_default ) : ?>
                                <a href="<?php echo wp_nonce_url( '?page=gee-woo-crm&view=email-templates&action=delete&id=' . $template->id, 'delete_template_' . $template->id ); ?>" class="gee-crm-btn" style="color:red; border-color:red;" onclick="return confirm('Delete this template?');">Delete</a>
                            <?php else : ?>
                                <span style="color:#999; font-size:12px;">Cannot delete default</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7">No email templates found. Create your first template above.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

