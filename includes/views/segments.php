<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';

$segment_model = new Gee_Woo_CRM_Segment();
$tag_model = new Gee_Woo_CRM_Tag();

$segments = $segment_model->get_segments();
$tags = $tag_model->get_tags();

// Handle form submission (POST) - Minimal implementation logic here instead of AJAX for speed
if ( isset( $_POST['gee_create_segment'] ) && check_admin_referer( 'gee_create_segment_nonce' ) ) {
    $name = sanitize_text_field( $_POST['name'] );
    $tag_id = absint( $_POST['rule_has_tag'] );
    
    if ( $name && $tag_id ) {
        $segment_model->create_segment( $name, array( 'has_tag' => $tag_id ) );
        echo '<div class="notice notice-success"><p>Segment created.</p></div>';
        $segments = $segment_model->get_segments(); // Refresh
    }
}

// Handle Delete
if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['id'] ) ) {
    $segment_model->delete_segment( absint( $_GET['id'] ) );
    echo '<script>window.location.href="?page=gee-woo-crm&view=segments";</script>';
}

?>

<div class="gee-crm-card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Segments</h2>
    </div>

    <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; margin-bottom:20px;">
        <h3>Create New Segment</h3>
        <form method="post">
            <?php wp_nonce_field( 'gee_create_segment_nonce' ); ?>
            <p>
                <label>Segment Name:</label><br>
                <input type="text" name="name" required style="width:100%; max-width:300px;">
            </p>
            <p>
                <label>Contacts MUST have Tag:</label><br>
                <select name="rule_has_tag" required>
                    <option value="">Select Tag...</option>
                    <?php foreach ($tags as $tag) : ?>
                        <option value="<?php echo $tag->id; ?>"><?php echo esc_html($tag->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <input type="submit" name="gee_create_segment" class="gee-crm-btn gee-crm-btn-primary" value="Create Segment">
        </form>
    </div>

    <table class="gee-crm-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Rule (Has Tag)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $segments ) : ?>
                <?php foreach ( $segments as $seg ) : ?>
                    <?php 
                        $rules = json_decode( $seg->rules_json, true );
                        $tag_idx = isset($rules['has_tag']) ? $rules['has_tag'] : 0;
                        // Find tag name
                        $tag_name = 'Unknown';
                        foreach($tags as $t) { if($t->id == $tag_idx) $tag_name = $t->name; }
                    ?>
                    <tr>
                        <td><?php echo $seg->id; ?></td>
                        <td><?php echo esc_html( $seg->name ); ?></td>
                        <td><span class="gee-crm-badge"><?php echo esc_html($tag_name); ?></span></td>
                        <td>
                            <a href="?page=gee-woo-crm&view=segments&action=delete&id=<?php echo $seg->id; ?>" class="gee-crm-btn" style="color:red; border-color:red;" onclick="return confirm('Delete?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">No segments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
