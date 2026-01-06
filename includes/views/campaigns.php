<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-campaign.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';

$campaign_model = new Gee_Woo_CRM_Campaign();
$tag_model = new Gee_Woo_CRM_Tag();
$segment_model = new Gee_Woo_CRM_Segment();
$template_model = new Gee_Woo_CRM_Email_Template();

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';

// Handle Campaign Creation/Update
if ( isset( $_POST['gee_save_campaign'] ) && check_admin_referer( 'gee_save_campaign_nonce' ) ) {
    // Validate required fields
    if ( empty( $_POST['name'] ) || empty( $_POST['subject'] ) || empty( $_POST['content_html'] ) ) {
        echo '<div class="notice notice-error"><p>Please fill in all required fields: Campaign Name, Email Subject, and Email Content.</p></div>';
    } else {
        $name = sanitize_text_field( $_POST['name'] );
        $subject = sanitize_text_field( $_POST['subject'] );
        $content_html = wp_kses_post( $_POST['content_html'] );
        $template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
        
        // Targeting
        $targeting = array(
            'type' => sanitize_text_field( $_POST['target_type'] ),
            'tags' => isset( $_POST['target_tags'] ) ? array_map( 'absint', $_POST['target_tags'] ) : array(),
            'segments' => isset( $_POST['target_segments'] ) ? array_map( 'absint', $_POST['target_segments'] ) : array(),
            'tag_operator' => isset( $_POST['tag_operator'] ) ? sanitize_text_field( $_POST['tag_operator'] ) : 'any'
        );
        
        // Get recipient count
        $recipients = $campaign_model->get_recipients( $targeting );
        $total_recipients = count( $recipients );
        
        // Scheduling
        $send_type = sanitize_text_field( $_POST['send_type'] );
        $scheduled_at = null;
        $status = 'draft';
        
        if ( $send_type == 'schedule' && ! empty( $_POST['scheduled_date'] ) && ! empty( $_POST['scheduled_time'] ) ) {
            $scheduled_at = sanitize_text_field( $_POST['scheduled_date'] ) . ' ' . sanitize_text_field( $_POST['scheduled_time'] ) . ':00';
            $status = 'scheduled';
        } elseif ( $send_type == 'now' ) {
            $status = 'draft'; // Will be sent immediately after save
        }
        
        $campaign_data = array(
            'name' => $name,
            'subject' => $subject,
            'content_html' => $content_html,
            'template_id' => $template_id > 0 ? $template_id : null,
            'targeting' => $targeting,
            'status' => $status,
            'scheduled_at' => $scheduled_at,
            'total_recipients' => $total_recipients
        );
        
        $campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
        
        if ( $campaign_id > 0 ) {
            // Update existing campaign
            $campaign_model->update_campaign( $campaign_id, $campaign_data );
            $campaign_id = $campaign_id;
        } else {
            // Create new campaign
            $campaign_id = $campaign_model->create_campaign( $campaign_data );
        }
        
        if ( $campaign_id ) {
            if ( $send_type == 'now' ) {
                // Send immediately
                $sent_count = $campaign_model->send_campaign( $campaign_id, true );
                wp_redirect( admin_url( 'admin.php?page=gee-woo-crm&view=campaigns&campaign_created=1&sent=' . $sent_count ) );
                exit;
            } else {
                // Redirect to list view after creation/update
                if ( isset( $_POST['campaign_id'] ) && $_POST['campaign_id'] > 0 ) {
                    wp_redirect( admin_url( 'admin.php?page=gee-woo-crm&view=campaigns&campaign_updated=1' ) );
                } else {
                    wp_redirect( admin_url( 'admin.php?page=gee-woo-crm&view=campaigns&campaign_created=1' ) );
                }
                exit;
            }
        } else {
            global $wpdb;
            $error_message = 'Failed to create campaign.';
            if ( ! empty( $wpdb->last_error ) ) {
                $error_message .= ' Error: ' . esc_html( $wpdb->last_error );
            }
            echo '<div class="notice notice-error"><p>' . $error_message . '</p></div>';
        }
    }
}

// Handle Send Now
if ( isset( $_GET['action'] ) && $_GET['action'] == 'send' && isset( $_GET['id'] ) ) {
    if ( check_admin_referer( 'send_campaign_' . absint( $_GET['id'] ) ) ) {
        $sent_count = $campaign_model->send_campaign( absint( $_GET['id'] ), true );
        wp_redirect( admin_url( 'admin.php?page=gee-woo-crm&view=campaigns&campaign_sent=1&sent=' . $sent_count ) );
        exit;
    }
}

// Handle Delete
if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['id'] ) ) {
    if ( check_admin_referer( 'delete_campaign_' . absint( $_GET['id'] ) ) ) {
        $campaign_model->delete_campaign( absint( $_GET['id'] ) );
        wp_redirect( admin_url( 'admin.php?page=gee-woo-crm&view=campaigns&campaign_deleted=1' ) );
        exit;
    }
}

// Get data
$tags = $tag_model->get_tags();
$segments = $segment_model->get_segments();
$templates = $template_model->get_templates();

if ( $action == 'view' && isset( $_GET['id'] ) ) {
    // View Campaign Details
    $id = absint( $_GET['id'] );
    $campaign = $campaign_model->get_campaign( $id );
    $logs = $campaign_model->get_logs( $id );
    $stats = $campaign_model->get_campaign_stats( $id );
    
    if ( ! $campaign ) {
        echo '<div class="notice notice-error"><p>Campaign not found.</p></div>';
        return;
    }
    ?>
    <div class="gee-crm-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2><?php echo esc_html( $campaign->name ); ?></h2>
            <a href="?page=gee-woo-crm&view=campaigns" class="gee-crm-btn">← Back to Campaigns</a>
        </div>
        
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:30px;">
            <div style="background:#f8f9fa; padding:20px; border-radius:4px; border-left:4px solid #4e28a5;">
                <div style="font-size:32px; font-weight:bold; color:#4e28a5;"><?php echo number_format( $stats['total'] ); ?></div>
                <div style="color:#666; font-size:14px;">Total Recipients</div>
            </div>
            <div style="background:#f8f9fa; padding:20px; border-radius:4px; border-left:4px solid #28a745;">
                <div style="font-size:32px; font-weight:bold; color:#28a745;"><?php echo number_format( $stats['sent'] ); ?></div>
                <div style="color:#666; font-size:14px;">Sent</div>
            </div>
            <div style="background:#f8f9fa; padding:20px; border-radius:4px; border-left:4px solid #dc3545;">
                <div style="font-size:32px; font-weight:bold; color:#dc3545;"><?php echo number_format( $stats['failed'] ); ?></div>
                <div style="color:#666; font-size:14px;">Failed</div>
            </div>
            <div style="background:#f8f9fa; padding:20px; border-radius:4px; border-left:4px solid #ffc107;">
                <div style="font-size:32px; font-weight:bold; color:#ffc107;"><?php echo $stats['total'] > 0 ? number_format( ( $stats['sent'] / $stats['total'] ) * 100, 1 ) : 0; ?>%</div>
                <div style="color:#666; font-size:14px;">Success Rate</div>
            </div>
        </div>
        
        <div style="background:#f8f9fa; padding:15px; border-radius:4px; margin-bottom:20px;">
        <p><strong>Subject:</strong> <?php echo esc_html( $campaign->subject ); ?></p>
            <p><strong>Status:</strong> <span style="text-transform:capitalize;"><?php echo esc_html( $campaign->status ); ?></span></p>
            <p><strong>Created:</strong> <?php echo $campaign->created_at ? date( 'F j, Y g:i A', strtotime( $campaign->created_at ) ) : 'N/A'; ?></p>
            <?php if ( $campaign->sent_at ) : ?>
                <p><strong>Sent:</strong> <?php echo date( 'F j, Y g:i A', strtotime( $campaign->sent_at ) ); ?></p>
            <?php endif; ?>
            <?php if ( $campaign->scheduled_at ) : ?>
                <p><strong>Scheduled:</strong> <?php echo date( 'F j, Y g:i A', strtotime( $campaign->scheduled_at ) ); ?></p>
            <?php endif; ?>
        </div>
        
        <h3>Content Preview</h3>
        <div style="border:1px solid #ddd; padding:20px; background:#fff; border-radius:4px; margin-bottom:20px;">
            <iframe
                title="Campaign Preview"
                style="width:100%; height:600px; border:none; background:#fff;"
                srcdoc="<?php echo esc_attr( $campaign->content_html ); ?>">
            </iframe>
        </div>
        
        <h3>Recipient Log</h3>
        <table class="gee-crm-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Sent At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $logs ) : ?>
                    <?php foreach( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo esc_html( $log->email ); ?></td>
                            <td>
                                <span style="padding:4px 8px; border-radius:3px; font-size:12px; background:<?php echo $log->status == 'sent' ? '#28a745' : '#dc3545'; ?>; color:#fff;">
                                    <?php echo esc_html( ucfirst( $log->status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo $log->sent_at ? date( 'F j, Y g:i A', strtotime( $log->sent_at ) ) : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="3">No logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
} elseif ( $action == 'new' || ( $action == 'edit' && isset( $_GET['id'] ) ) ) {
    // Create/Edit Campaign Form
    $editing_campaign = null;
    if ( $action == 'edit' && isset( $_GET['id'] ) ) {
        $editing_campaign = $campaign_model->get_campaign( absint( $_GET['id'] ) );
        if ( ! $editing_campaign ) {
            echo '<div class="notice notice-error"><p>Campaign not found.</p></div>';
            return;
        }
    }
    
    $targeting = $editing_campaign ? json_decode( $editing_campaign->targeting_json, true ) : array( 'type' => 'all' );
    ?>
    <div class="gee-crm-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2><?php echo $editing_campaign ? 'Edit Campaign' : 'Create New Campaign'; ?></h2>
            <a href="?page=gee-woo-crm&view=campaigns" class="gee-crm-btn">← Back</a>
        </div>
        
        <form method="post" id="campaign-form">
            <?php wp_nonce_field( 'gee_save_campaign_nonce' ); ?>
            <?php if ( $editing_campaign ) : ?>
                <input type="hidden" name="campaign_id" value="<?php echo $editing_campaign->id; ?>">
            <?php endif; ?>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <label><strong>Campaign Name *</strong></label><br>
                    <input type="text" name="name" value="<?php echo $editing_campaign ? esc_attr( $editing_campaign->name ) : ''; ?>" required style="width:100%; padding:8px; margin-top:5px;">
                    <small style="color:#666;">Internal name for your reference</small>
                </div>
                
                <div>
                    <label><strong>Email Subject *</strong></label><br>
                    <input type="text" name="subject" value="<?php echo $editing_campaign ? esc_attr( $editing_campaign->subject ) : ''; ?>" required style="width:100%; padding:8px; margin-top:5px;" placeholder="e.g. Special Offer for You!">
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label><strong>Select Email Template</strong></label><br>
                <select name="template_id" id="template-select" style="width:100%; max-width:400px; padding:8px; margin-top:5px;">
                    <option value="0">-- Create from scratch --</option>
                    <?php foreach ( $templates as $template ) : ?>
                        <option value="<?php echo $template->id; ?>" <?php selected( $editing_campaign && $editing_campaign->template_id == $template->id, true ); ?>>
                            <?php echo esc_html( $template->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#666;">Select a template to pre-fill content, or create from scratch</small>
            </div>
            
            <div style="margin-bottom:20px;">
                <label><strong>Email Content (HTML) *</strong></label><br>
                <small style="color:#666;">You can use variables like {first_name}, {full_name}, {email}, {total_spent}, etc.</small><br>
                <textarea name="content_html" id="campaign-content" rows="15" required style="width:100%; font-family:monospace; padding:8px; margin-top:5px;"><?php echo $editing_campaign ? esc_textarea( $editing_campaign->content_html ) : ''; ?></textarea>
            </div>
            
            <div style="background:#fff3cd; border-left:4px solid #ffc107; padding:15px; margin-bottom:20px; border-radius:4px;">
                <p style="margin:0; color:#856404; font-size:14px;">
                    <strong>⚠️ Important:</strong> Only contacts who have given marketing email consent will receive campaigns. 
                    Contacts without consent will be automatically excluded, even if they match your targeting criteria.
                </p>
            </div>
            
            <div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:20px;">
                <h3 style="margin-top:0;">Target Audience</h3>
                
                <div style="margin-bottom:15px;">
                    <label>
                        <input type="radio" name="target_type" value="all" <?php checked( ! isset( $targeting['type'] ) || $targeting['type'] == 'all', true ); ?>>
                        <strong>All Contacts</strong>
                    </label>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>
                        <input type="radio" name="target_type" value="tags" <?php checked( isset( $targeting['type'] ) && $targeting['type'] == 'tags', true ); ?>>
                        <strong>By Tags</strong>
                    </label>
                    <div id="tags-targeting" style="margin-left:25px; margin-top:10px; <?php echo ( ! isset( $targeting['type'] ) || $targeting['type'] != 'tags' ) ? 'display:none;' : ''; ?>">
                        <!-- Selected Tags Display -->
                        <div id="selected-tags-container" style="min-height:50px; padding:10px; background:#f8f9fa; border:1px solid #ddd; border-radius:4px; margin-bottom:10px;">
                            <div style="font-size:12px; color:#666; margin-bottom:8px;">Selected Tags:</div>
                            <div id="selected-tags-list" style="display:flex; flex-wrap:wrap; gap:8px;">
                                <?php if ( isset( $targeting['tags'] ) && is_array( $targeting['tags'] ) ) : ?>
                                    <?php foreach ( $targeting['tags'] as $selected_tag_id ) : ?>
                                        <?php 
                                        $selected_tag = null;
                                        foreach ( $tags as $tag ) {
                                            if ( $tag->id == $selected_tag_id ) {
                                                $selected_tag = $tag;
                                                break;
                                            }
                                        }
                                        if ( $selected_tag ) :
                                        ?>
                                            <span class="selected-tag-badge" data-tag-id="<?php echo $selected_tag->id; ?>" style="background:#4e28a5; color:#fff; padding:6px 12px; border-radius:4px; font-size:13px; display:inline-flex; align-items:center; gap:8px;">
                                                <?php echo esc_html( $selected_tag->name ); ?>
                                                <button type="button" class="remove-tag-btn" data-tag-id="<?php echo $selected_tag->id; ?>" style="background:none; border:none; color:#fff; cursor:pointer; font-size:16px; padding:0; margin:0; line-height:1; font-weight:bold;" title="Remove tag">×</button>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <span id="no-tags-message" style="color:#999; font-size:13px; <?php echo ( isset( $targeting['tags'] ) && ! empty( $targeting['tags'] ) ) ? 'display:none;' : ''; ?>">No tags selected. Click tags below to add.</span>
                            </div>
                        </div>
                        
                        <!-- Available Tags -->
                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Available Tags:</label>
                            <div style="display:flex; flex-wrap:wrap; gap:8px; padding:10px; background:#fff; border:1px solid #ddd; border-radius:4px; max-height:200px; overflow-y:auto;">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <?php 
                                    $is_selected = isset( $targeting['tags'] ) && in_array( $tag->id, $targeting['tags'] );
                                    ?>
                                    <button type="button" class="tag-select-btn <?php echo $is_selected ? 'selected' : ''; ?>" data-tag-id="<?php echo $tag->id; ?>" data-tag-name="<?php echo esc_attr( $tag->name ); ?>" style="background:<?php echo $is_selected ? '#4e28a5' : '#e5dafc'; ?>; color:<?php echo $is_selected ? '#fff' : '#4e28a5'; ?>; padding:6px 12px; border:1px solid <?php echo $is_selected ? '#4e28a5' : '#d0b8f0'; ?>; border-radius:4px; font-size:13px; cursor:pointer; transition:all 0.2s;">
                                        <?php echo esc_html( $tag->name ); ?>
                                    </button>
                                <?php endforeach; ?>
                                <?php if ( empty( $tags ) ) : ?>
                                    <span style="color:#999; font-size:13px;">No tags available. Create tags first.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden input to store selected tag IDs -->
                        <input type="hidden" name="target_tags_json" id="target-tags-json" value="<?php echo isset( $targeting['tags'] ) ? esc_attr( json_encode( $targeting['tags'] ) ) : '[]'; ?>">
                        
                        <!-- Tag Operator -->
                        <div style="margin-top:15px; padding-top:15px; border-top:1px solid #ddd;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Match Condition:</label>
                            <label style="display:block; margin-bottom:8px;">
                                <input type="radio" name="tag_operator" value="any" <?php checked( ! isset( $targeting['tag_operator'] ) || $targeting['tag_operator'] == 'any', true ); ?>>
                                Contact has <strong>ANY</strong> of the selected tags
                            </label>
                            <label style="display:block;">
                                <input type="radio" name="tag_operator" value="all" <?php checked( isset( $targeting['tag_operator'] ) && $targeting['tag_operator'] == 'all', true ); ?>>
                                Contact has <strong>ALL</strong> of the selected tags
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>
                        <input type="radio" name="target_type" value="segments" <?php checked( isset( $targeting['type'] ) && $targeting['type'] == 'segments', true ); ?>>
                        <strong>By Segments</strong>
                    </label>
                    <div id="segments-targeting" style="margin-left:25px; margin-top:10px; <?php echo ( ! isset( $targeting['type'] ) || $targeting['type'] != 'segments' ) ? 'display:none;' : ''; ?>">
                        <!-- Selected Segments Display -->
                        <div id="selected-segments-container" style="min-height:50px; padding:10px; background:#f8f9fa; border:1px solid #ddd; border-radius:4px; margin-bottom:10px;">
                            <div style="font-size:12px; color:#666; margin-bottom:8px;">Selected Segments:</div>
                            <div id="selected-segments-list" style="display:flex; flex-wrap:wrap; gap:8px;">
                                <?php if ( isset( $targeting['segments'] ) && is_array( $targeting['segments'] ) ) : ?>
                                    <?php foreach ( $targeting['segments'] as $selected_segment_id ) : ?>
                                        <?php 
                                        $selected_segment = null;
                                        foreach ( $segments as $segment ) {
                                            if ( $segment->id == $selected_segment_id ) {
                                                $selected_segment = $segment;
                                                break;
                                            }
                                        }
                                        if ( $selected_segment ) :
                                        ?>
                                            <span class="selected-segment-badge" data-segment-id="<?php echo $selected_segment->id; ?>" style="background:#4e28a5; color:#fff; padding:6px 12px; border-radius:4px; font-size:13px; display:inline-flex; align-items:center; gap:8px;">
                                                <?php echo esc_html( $selected_segment->name ); ?>
                                                <button type="button" class="remove-segment-btn" data-segment-id="<?php echo $selected_segment->id; ?>" style="background:none; border:none; color:#fff; cursor:pointer; font-size:16px; padding:0; margin:0; line-height:1; font-weight:bold;" title="Remove segment">×</button>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <span id="no-segments-message" style="color:#999; font-size:13px; <?php echo ( isset( $targeting['segments'] ) && ! empty( $targeting['segments'] ) ) ? 'display:none;' : ''; ?>">No segments selected. Click segments below to add.</span>
                            </div>
                        </div>
                        
                        <!-- Available Segments -->
                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Available Segments:</label>
                            <div style="display:flex; flex-wrap:wrap; gap:8px; padding:10px; background:#fff; border:1px solid #ddd; border-radius:4px; max-height:200px; overflow-y:auto;">
                                <?php foreach ( $segments as $segment ) : ?>
                                    <?php 
                                    $is_selected = isset( $targeting['segments'] ) && in_array( $segment->id, $targeting['segments'] );
                                    ?>
                                    <button type="button" class="segment-select-btn <?php echo $is_selected ? 'selected' : ''; ?>" data-segment-id="<?php echo $segment->id; ?>" data-segment-name="<?php echo esc_attr( $segment->name ); ?>" style="background:<?php echo $is_selected ? '#4e28a5' : '#e5dafc'; ?>; color:<?php echo $is_selected ? '#fff' : '#4e28a5'; ?>; padding:6px 12px; border:1px solid <?php echo $is_selected ? '#4e28a5' : '#d0b8f0'; ?>; border-radius:4px; font-size:13px; cursor:pointer; transition:all 0.2s;">
                                        <?php echo esc_html( $segment->name ); ?>
                                    </button>
                                <?php endforeach; ?>
                                <?php if ( empty( $segments ) ) : ?>
                                    <span style="color:#999; font-size:13px;">No segments available. Create segments first.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden input to store selected segment IDs -->
                        <input type="hidden" name="target_segments_json" id="target-segments-json" value="<?php echo isset( $targeting['segments'] ) ? esc_attr( json_encode( $targeting['segments'] ) ) : '[]'; ?>">
                    </div>
                </div>
            </div>
            
            <div style="background:#f8f9fa; padding:20px; border-radius:4px; margin-bottom:20px;">
                <h3 style="margin-top:0;">Send Options</h3>
                
                <div style="margin-bottom:15px;">
                    <label>
                        <input type="radio" name="send_type" value="draft" <?php checked( $editing_campaign && $editing_campaign->status == 'draft', true ); ?>>
                        <strong>Save as Draft</strong>
                    </label>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>
                        <input type="radio" name="send_type" value="now" <?php checked( ! $editing_campaign || $editing_campaign->status == 'sent', true ); ?>>
                        <strong>Send Now</strong>
                    </label>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>
                        <input type="radio" name="send_type" value="schedule" <?php checked( $editing_campaign && $editing_campaign->status == 'scheduled', true ); ?>>
                        <strong>Schedule for Later</strong>
                    </label>
                    <div id="schedule-options" style="margin-left:25px; margin-top:10px; <?php echo ( ! $editing_campaign || $editing_campaign->status != 'scheduled' ) ? 'display:none;' : ''; ?>">
                        <input type="date" name="scheduled_date" value="<?php echo $editing_campaign && $editing_campaign->scheduled_at ? date( 'Y-m-d', strtotime( $editing_campaign->scheduled_at ) ) : date( 'Y-m-d' ); ?>" style="padding:8px; margin-right:10px;">
                        <input type="time" name="scheduled_time" value="<?php echo $editing_campaign && $editing_campaign->scheduled_at ? date( 'H:i', strtotime( $editing_campaign->scheduled_at ) ) : date( 'H:i', strtotime( '+1 hour' ) ); ?>" style="padding:8px;">
                    </div>
                </div>
            </div>
            
            <div style="margin-top:30px;">
                <input type="submit" name="gee_save_campaign" class="gee-crm-btn gee-crm-btn-primary" value="<?php echo $editing_campaign ? 'Update Campaign' : 'Create Campaign'; ?>">
                <a href="?page=gee-woo-crm&view=campaigns" class="gee-crm-btn" style="margin-left:10px;">Cancel</a>
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Template selection
        $('#template-select').on('change', function() {
            var templateId = $(this).val();
            if (templateId > 0) {
                $.ajax({
                    url: typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof geeWooCRM !== 'undefined' ? geeWooCRM.ajaxurl : '<?php echo admin_url( "admin-ajax.php" ); ?>'),
                    type: 'POST',
                    data: {
                        action: 'gee_get_template',
                        template_id: templateId,
                        nonce: '<?php echo wp_create_nonce( "gee_get_template" ); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            $('#campaign-content').val(response.data.content_html);
                            $('input[name="subject"]').val(response.data.subject);
                        }
                    }
                });
            }
        });
        
        // Target type toggle
        $('input[name="target_type"]').on('change', function() {
            var type = $(this).val();
            $('#tags-targeting, #segments-targeting').hide();
            if (type == 'tags') {
                $('#tags-targeting').show();
            } else if (type == 'segments') {
                $('#segments-targeting').show();
            }
        });
        
        // Send type toggle
        $('input[name="send_type"]').on('change', function() {
            var type = $(this).val();
            $('#schedule-options').hide();
            if (type == 'schedule') {
                $('#schedule-options').show();
            }
        });
        
        // Tag selection functionality
        var selectedTags = <?php echo isset( $targeting['tags'] ) ? json_encode( $targeting['tags'] ) : '[]'; ?>;
        
        function updateTagsDisplay() {
            var tagsJson = JSON.stringify(selectedTags);
            $('#target-tags-json').val(tagsJson);
            
            // Update selected tags display
            $('#selected-tags-list').find('.selected-tag-badge').remove();
            if (selectedTags.length > 0) {
                $('#no-tags-message').hide();
                selectedTags.forEach(function(tagId) {
                    var tagBtn = $('.tag-select-btn[data-tag-id="' + tagId + '"]');
                    var tagName = tagBtn.data('tag-name');
                    var badge = $('<span class="selected-tag-badge" data-tag-id="' + tagId + '" style="background:#4e28a5; color:#fff; padding:6px 12px; border-radius:4px; font-size:13px; display:inline-flex; align-items:center; gap:8px;">' +
                        tagName + 
                        '<button type="button" class="remove-tag-btn" data-tag-id="' + tagId + '" style="background:none; border:none; color:#fff; cursor:pointer; font-size:16px; padding:0; margin:0; line-height:1; font-weight:bold;" title="Remove tag">×</button>' +
                        '</span>');
                    $('#selected-tags-list').append(badge);
                });
            } else {
                $('#no-tags-message').show();
            }
            
            // Update tag buttons
            $('.tag-select-btn').each(function() {
                var tagId = parseInt($(this).data('tag-id'));
                if (selectedTags.indexOf(tagId) !== -1) {
                    $(this).addClass('selected').css({
                        'background': '#4e28a5',
                        'color': '#fff',
                        'border-color': '#4e28a5'
                    });
                } else {
                    $(this).removeClass('selected').css({
                        'background': '#e5dafc',
                        'color': '#4e28a5',
                        'border-color': '#d0b8f0'
                    });
                }
            });
        }
        
        // Tag button click
        $(document).on('click', '.tag-select-btn', function() {
            var tagId = parseInt($(this).data('tag-id'));
            var index = selectedTags.indexOf(tagId);
            
            if (index === -1) {
                // Add tag
                selectedTags.push(tagId);
            } else {
                // Remove tag
                selectedTags.splice(index, 1);
            }
            
            updateTagsDisplay();
        });
        
        // Remove tag button click
        $(document).on('click', '.remove-tag-btn', function(e) {
            e.stopPropagation();
            var tagId = parseInt($(this).data('tag-id'));
            var index = selectedTags.indexOf(tagId);
            if (index !== -1) {
                selectedTags.splice(index, 1);
                updateTagsDisplay();
            }
        });
        
        // Segment selection functionality
        var selectedSegments = <?php echo isset( $targeting['segments'] ) ? json_encode( $targeting['segments'] ) : '[]'; ?>;
        
        function updateSegmentsDisplay() {
            var segmentsJson = JSON.stringify(selectedSegments);
            $('#target-segments-json').val(segmentsJson);
            
            // Update selected segments display
            $('#selected-segments-list').find('.selected-segment-badge').remove();
            if (selectedSegments.length > 0) {
                $('#no-segments-message').hide();
                selectedSegments.forEach(function(segmentId) {
                    var segmentBtn = $('.segment-select-btn[data-segment-id="' + segmentId + '"]');
                    var segmentName = segmentBtn.data('segment-name');
                    var badge = $('<span class="selected-segment-badge" data-segment-id="' + segmentId + '" style="background:#4e28a5; color:#fff; padding:6px 12px; border-radius:4px; font-size:13px; display:inline-flex; align-items:center; gap:8px;">' +
                        segmentName + 
                        '<button type="button" class="remove-segment-btn" data-segment-id="' + segmentId + '" style="background:none; border:none; color:#fff; cursor:pointer; font-size:16px; padding:0; margin:0; line-height:1; font-weight:bold;" title="Remove segment">×</button>' +
                        '</span>');
                    $('#selected-segments-list').append(badge);
                });
            } else {
                $('#no-segments-message').show();
            }
            
            // Update segment buttons
            $('.segment-select-btn').each(function() {
                var segmentId = parseInt($(this).data('segment-id'));
                if (selectedSegments.indexOf(segmentId) !== -1) {
                    $(this).addClass('selected').css({
                        'background': '#4e28a5',
                        'color': '#fff',
                        'border-color': '#4e28a5'
                    });
                } else {
                    $(this).removeClass('selected').css({
                        'background': '#e5dafc',
                        'color': '#4e28a5',
                        'border-color': '#d0b8f0'
                    });
                }
            });
        }
        
        // Segment button click
        $(document).on('click', '.segment-select-btn', function() {
            var segmentId = parseInt($(this).data('segment-id'));
            var index = selectedSegments.indexOf(segmentId);
            
            if (index === -1) {
                // Add segment
                selectedSegments.push(segmentId);
            } else {
                // Remove segment
                selectedSegments.splice(index, 1);
            }
            
            updateSegmentsDisplay();
        });
        
        // Remove segment button click
        $(document).on('click', '.remove-segment-btn', function(e) {
            e.stopPropagation();
            var segmentId = parseInt($(this).data('segment-id'));
            var index = selectedSegments.indexOf(segmentId);
            if (index !== -1) {
                selectedSegments.splice(index, 1);
                updateSegmentsDisplay();
            }
        });
        
        // Initialize displays
        updateTagsDisplay();
        updateSegmentsDisplay();
        
        // Form submission - convert JSON to array format
        $('#campaign-form').on('submit', function() {
            // Convert tags JSON to array format for PHP
            var tagsArray = [];
            selectedTags.forEach(function(tagId) {
                tagsArray.push(tagId);
            });
            tagsArray.forEach(function(tagId, index) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'target_tags[]',
                    value: tagId
                }).appendTo('#campaign-form');
            });
            
            // Convert segments JSON to array format for PHP
            var segmentsArray = [];
            selectedSegments.forEach(function(segmentId) {
                segmentsArray.push(segmentId);
            });
            segmentsArray.forEach(function(segmentId, index) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'target_segments[]',
                    value: segmentId
                }).appendTo('#campaign-form');
            });
        });
    });
    </script>
    <?php
} else {
    // List Campaigns
    $campaigns = $campaign_model->get_campaigns();
    
    // Show success messages
    if ( isset( $_GET['campaign_created'] ) ) {
        if ( isset( $_GET['sent'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Campaign created and sent successfully to ' . absint( $_GET['sent'] ) . ' recipients!</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>Campaign created successfully!</p></div>';
        }
    }
    if ( isset( $_GET['campaign_updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Campaign updated successfully!</p></div>';
    }
    if ( isset( $_GET['campaign_sent'] ) && isset( $_GET['sent'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Campaign sent successfully to ' . absint( $_GET['sent'] ) . ' recipients!</p></div>';
    }
    if ( isset( $_GET['campaign_deleted'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Campaign deleted successfully!</p></div>';
    }
    ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Email Campaigns</h2>
        <a href="?page=gee-woo-crm&view=campaigns&action=new" class="gee-crm-btn gee-crm-btn-primary">+ Create New Campaign</a>
    </div>
    
    <div class="gee-crm-card">
        <table class="gee-crm-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Recipients</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $campaigns ) : ?>
                    <?php foreach ( $campaigns as $c ) : ?>
                        <?php
                        $stats = $campaign_model->get_campaign_stats( $c->id );
                        $status_colors = array(
                            'draft' => '#6c757d',
                            'scheduled' => '#ffc107',
                            'sent' => '#28a745'
                        );
                        $status_color = isset( $status_colors[ $c->status ] ) ? $status_colors[ $c->status ] : '#6c757d';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $c->name ); ?></strong></td>
                            <td><?php echo esc_html( $c->subject ); ?></td>
                            <td><?php echo number_format( $c->total_recipients ?: $stats['total'] ); ?></td>
                            <td>
                                <span style="padding:4px 8px; border-radius:3px; font-size:12px; background:<?php echo $status_color; ?>; color:#fff; text-transform:capitalize;">
                                    <?php echo esc_html( $c->status ); ?>
                                </span>
                            </td>
                            <td><?php echo $c->created_at ? date( 'M j, Y', strtotime( $c->created_at ) ) : 'N/A'; ?></td>
                            <td>
                                <a href="?page=gee-woo-crm&view=campaigns&action=view&id=<?php echo $c->id; ?>" class="gee-crm-btn" style="margin-right:5px;">View</a>
                                <?php if ( $c->status == 'draft' || $c->status == 'scheduled' ) : ?>
                                    <a href="?page=gee-woo-crm&view=campaigns&action=edit&id=<?php echo $c->id; ?>" class="gee-crm-btn" style="margin-right:5px;">Edit</a>
                                    <?php if ( $c->status == 'draft' ) : ?>
                                        <a href="<?php echo wp_nonce_url( '?page=gee-woo-crm&view=campaigns&action=send&id=' . $c->id, 'send_campaign_' . $c->id ); ?>" class="gee-crm-btn" style="background:#28a745; color:#fff; border-color:#28a745; margin-right:5px;">Send Now</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="<?php echo wp_nonce_url( '?page=gee-woo-crm&view=campaigns&action=delete&id=' . $c->id, 'delete_campaign_' . $c->id ); ?>" class="gee-crm-btn" style="color:red; border-color:red;" onclick="return confirm('Delete this campaign?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6">No campaigns yet. <a href="?page=gee-woo-crm&view=campaigns&action=new">Create your first campaign</a>!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
