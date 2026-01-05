<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-campaign.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';

$campaign_model = new Gee_Woo_CRM_Campaign();
$tag_model = new Gee_Woo_CRM_Tag();
$segment_model = new Gee_Woo_CRM_Segment();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle Send
if ( isset( $_POST['gee_create_campaign'] ) && check_admin_referer( 'gee_create_campaign_nonce' ) ) {
    $name = sanitize_text_field( $_POST['name'] );
    $subject = sanitize_text_field( $_POST['subject'] );
    $content = wp_kses_post( $_POST['content'] );
    
    $id = $campaign_model->create_campaign( array(
        'name' => $name,
        'subject' => $subject,
        'content' => $content
    ));
    
    if ( $id ) {
        // Send immediately
        $target = sanitize_text_field( $_POST['target'] ); // all, tag-1, segment-3
        $parts = explode('-', $target);
        $type = $parts[0]; 
        $t_id = isset($parts[1]) ? $parts[1] : 0;
        
        $count = $campaign_model->send_campaign( $id, $type, $t_id );
        echo '<div class="notice notice-success"><p>Campaign Sent to ' . $count . ' recipients!</p></div>';
    }
}

if ( $action == 'view' && isset( $_GET['id'] ) ) {
    // View Details
    $id = absint( $_GET['id'] );
    $campaign = $campaign_model->get_campaign( $id );
    $logs = $campaign_model->get_logs( $id );
    ?>
    <p><a href="?page=gee-woo-crm&view=campaigns" class="button">&larr; Back</a></p>
    <div class="gee-crm-card">
        <h2><?php echo esc_html( $campaign->name ); ?></h2>
        <p><strong>Subject:</strong> <?php echo esc_html( $campaign->subject ); ?></p>
        <p><strong>Sent:</strong> <?php echo $campaign->sent_at; ?></p>
        
        <h3>Content Preview</h3>
        <div style="border:1px solid #ddd; padding:20px; background:#f9f9f9;">
            <?php echo wp_kses_post( $campaign->content_html ); ?>
        </div>
        
        <h3>Recipient Log</h3>
        <table class="gee-crm-table">
            <thead><tr><th>Email</th><th>Status</th><th>Time</th></tr></thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html( $log->email ); ?></td>
                        <td><?php echo esc_html( $log->status ); ?></td>
                        <td><?php echo $log->sent_at; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
} elseif ( $action == 'new' ) {
    // New Campaign Form
    $tags = $tag_model->get_tags();
    $segments = $segment_model->get_segments();
    ?>
    <p><a href="?page=gee-woo-crm&view=campaigns" class="button">&larr; Back</a></p>
    <div class="gee-crm-card">
        <h2>Create Campaign</h2>
        <form method="post">
            <?php wp_nonce_field( 'gee_create_campaign_nonce' ); ?>
            <p>
                <label>Internal Name</label><br>
                <input type="text" name="name" required style="width:100%;">
            </p>
            <p>
                <label>Email Subject</label><br>
                <input type="text" name="subject" required style="width:100%;">
            </p>
            <p>
                <label>Target Audience</label><br>
                <select name="target" required>
                    <option value="all">All Subscribers</option>
                    <optgroup label="Tags">
                        <?php foreach($tags as $t) echo "<option value='tag-{$t->id}'>Tag: {$t->name}</option>"; ?>
                    </optgroup>
                    <optgroup label="Segments">
                        <?php foreach($segments as $s) echo "<option value='segment-{$s->id}'>Segment: {$s->name}</option>"; ?>
                    </optgroup>
                </select>
            </p>
            <p>
                <label>Email Content (HTML)</label><br>
                <?php wp_editor( '', 'content', array('textarea_name' => 'content', 'textarea_rows' => 10) ); ?>
            </p>
            
            <input type="submit" name="gee_create_campaign" class="gee-crm-btn gee-crm-btn-primary" value="Send Broadcast">
        </form>
    </div>
    <?php
} else {
    // List Campaigns
    $campaigns = $campaign_model->get_campaigns();
    ?>
    <p><a href="?page=gee-woo-crm&view=campaigns&action=new" class="gee-crm-btn gee-crm-btn-primary">Create New Campaign</a></p>
    
    <div class="gee-crm-card">
        <table class="gee-crm-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $campaigns ) : ?>
                    <?php foreach ( $campaigns as $c ) : ?>
                        <tr>
                            <td><?php echo esc_html( $c->name ); ?></td>
                            <td><?php echo esc_html( $c->subject ); ?></td>
                            <td><?php echo ucfirst( $c->status ); ?></td>
                            <td><?php echo $c->sent_at; ?></td>
                            <td>
                                <a href="?page=gee-woo-crm&view=campaigns&action=view&id=<?php echo $c->id; ?>" class="gee-crm-btn">Report</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5">No campaigns sent yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
