<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
$tag_model = new Gee_Woo_CRM_Tag();
$tags = $tag_model->get_tags();
?>

<div class="gee-crm-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Tags</h2>
        <div style="display:flex; gap:10px;">
            <input type="text" id="gee-new-tag-name" placeholder="New Tag Name" style="padding:5px;">
            <button id="gee-add-tag-btn" class="gee-crm-btn gee-crm-btn-primary">Add Tag</button>
        </div>
    </div>

    <table class="gee-crm-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="gee-tags-list">
            <?php if ( $tags ) : ?>
                <?php foreach ( $tags as $tag ) : ?>
                    <tr>
                        <td><?php echo $tag->id; ?></td>
                        <td><?php echo esc_html( $tag->name ); ?></td>
                        <td><?php echo esc_html( $tag->slug ); ?></td>
                        <td>
                            <button class="gee-delete-tag-btn gee-crm-btn" data-id="<?php echo $tag->id; ?>" style="color:red; border-color:red;">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">No tags found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('#gee-add-tag-btn').on('click', function() {
        var name = $('#gee-new-tag-name').val();
        if(!name) return;
        
        $.post(geeWooCRM.ajaxurl, {
            action: 'gee_crm_create_tag',
            nonce: geeWooCRM.nonce,
            name: name
        }, function(res) {
            if(res.success) {
                location.reload(); 
            } else {
                alert(res.data);
            }
        });
    });

    $('.gee-delete-tag-btn').on('click', function() {
        if(!confirm('Delete this tag?')) return;
        var id = $(this).data('id');
        $.post(geeWooCRM.ajaxurl, {
            action: 'gee_crm_delete_tag',
            nonce: geeWooCRM.nonce,
            id: id
        }, function(res) {
            if(res.success) {
                location.reload(); 
            } else {
                alert(res.data);
            }
        });
    });
});
</script>
