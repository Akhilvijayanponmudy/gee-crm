<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

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
                <th>Contacts</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="gee-tags-list">
            <?php if ( $tags ) : ?>
                <?php foreach ( $tags as $tag ) : ?>
                    <?php 
                    // Get contact count for this tag
                    $contact_count = $tag_model->get_contact_count( $tag->id );
                    ?>
                    <tr data-tag-id="<?php echo esc_attr( $tag->id ); ?>">
                        <td><?php echo esc_html( $tag->id ); ?></td>
                        <td class="tag-name-cell">
                            <span class="tag-name-display"><?php echo esc_html( $tag->name ); ?></span>
                            <input type="text" class="tag-name-edit" value="<?php echo esc_attr( $tag->name ); ?>" style="display:none; width:100%; padding:5px;">
                        </td>
                        <td>
                            <strong style="font-size:16px; color:#2271b1;"><?php echo esc_html( number_format_i18n( $contact_count ) ); ?></strong>
                        </td>
                        <td><?php echo esc_html( $tag->slug ); ?></td>
                        <td>
                            <button class="gee-edit-tag-btn gee-crm-btn" data-id="<?php echo esc_attr( $tag->id ); ?>" style="margin-right:5px;">Edit</button>
                            <button class="gee-save-tag-btn gee-crm-btn gee-crm-btn-primary" data-id="<?php echo esc_attr( $tag->id ); ?>" style="display:none; margin-right:5px;">Save</button>
                            <button class="gee-cancel-edit-tag-btn gee-crm-btn" data-id="<?php echo esc_attr( $tag->id ); ?>" style="display:none; margin-right:5px;">Cancel</button>
                            <button class="gee-delete-tag-btn gee-crm-btn" data-id="<?php echo esc_attr( $tag->id ); ?>" style="color:red; border-color:red;">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="5">No tags found.</td></tr>
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

    // Edit tag
    $('.gee-edit-tag-btn').on('click', function() {
        var $row = $(this).closest('tr');
        var id = $(this).data('id');
        
        $row.find('.tag-name-display').hide();
        $row.find('.tag-name-edit').show().focus();
        $row.find('.gee-edit-tag-btn').hide();
        $row.find('.gee-save-tag-btn').show();
        $row.find('.gee-cancel-edit-tag-btn').show();
    });

    // Cancel edit
    $(document).on('click', '.gee-cancel-edit-tag-btn', function() {
        var $row = $(this).closest('tr');
        var originalName = $row.find('.tag-name-display').text();
        
        $row.find('.tag-name-edit').val(originalName).hide();
        $row.find('.tag-name-display').show();
        $row.find('.gee-edit-tag-btn').show();
        $row.find('.gee-save-tag-btn').hide();
        $row.find('.gee-cancel-edit-tag-btn').hide();
    });

    // Save tag
    $(document).on('click', '.gee-save-tag-btn', function() {
        var $row = $(this).closest('tr');
        var id = $(this).data('id');
        var name = $row.find('.tag-name-edit').val().trim();
        
        if(!name) {
            alert('Tag name cannot be empty');
            return;
        }
        
        $.post(geeWooCRM.ajaxurl, {
            action: 'gee_crm_update_tag',
            nonce: geeWooCRM.nonce,
            id: id,
            name: name
        }, function(res) {
            if(res.success) {
                location.reload(); 
            } else {
                alert(res.data);
            }
        });
    });

    // Allow Enter key to save
    $(document).on('keypress', '.tag-name-edit', function(e) {
        if(e.which === 13) { // Enter key
            $(this).closest('tr').find('.gee-save-tag-btn').click();
        }
    });
});
</script>
