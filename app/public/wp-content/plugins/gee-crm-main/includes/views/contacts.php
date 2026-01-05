<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
$contact_model = new Gee_Woo_CRM_Contact();

require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
$tag_model = new Gee_Woo_CRM_Tag();

require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
$segment_model = new Gee_Woo_CRM_Segment();

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$contact_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( 'view' === $action && $contact_id ) {
	// Contact Detail View //
	global $wpdb;
	$contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gee_crm_contacts WHERE id = %d", $contact_id ) );
	
	if ( ! $contact ) {
		echo '<div class="notice notice-error"><p>Contact not found.</p></div>';
	} else {
		// Calculate stats if Woo
		$total_spent = 0;
		$order_count = 0;
		if ( $contact->wp_user_id ) {
			// If user is registered
			$customer = new WC_Customer( $contact->wp_user_id );
			$total_spent = $customer->get_total_spent();
			$order_count = $customer->get_order_count();
		} elseif ( class_exists('WooCommerce') ) {
			// Guest check via billing email - expensive query, skipped for minimal version or need optimized query
			// Placeholder
		}

        // Tags
        $tags = $wpdb->get_results( $wpdb->prepare( "
            SELECT t.name 
            FROM {$wpdb->prefix}gee_crm_tags t
            INNER JOIN {$wpdb->prefix}gee_crm_contact_tags ct ON ct.tag_id = t.id
            WHERE ct.contact_id = %d
        ", $contact_id ) );
		?>
		<p><a href="?page=gee-woo-crm&view=contacts" class="button">&larr; Back to Contacts</a></p>
		
		<div class="gee-crm-card">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<h2><?php echo esc_html( $contact->first_name . ' ' . $contact->last_name ); ?></h2>
				<span class="gee-crm-badge"><?php echo esc_html( ucfirst( $contact->status ) ); ?></span>
			</div>
			<p><strong>Email:</strong> <?php echo esc_html( $contact->email ); ?></p>
			<p><strong>Phone:</strong> <?php echo esc_html( $contact->phone ); ?></p>
			<p><strong>Source:</strong> <?php echo esc_html( $contact->source ); ?></p>
            <p><strong>Joined:</strong> <?php echo esc_html( $contact->created_at ); ?></p>
            
            <hr>
            
            <h3>WooCommerce Stats</h3>
            <div class="gee-crm-stat-grid">
                <div class="gee-crm-stat-box">
                    <div class="gee-crm-stat-number"><?php echo wc_price( $total_spent ); ?></div>
                    <div class="gee-crm-stat-label">Total Spent</div>
                </div>
                <div class="gee-crm-stat-box">
                    <div class="gee-crm-stat-number"><?php echo $order_count; ?></div>
                    <div class="gee-crm-stat-label">Total Orders</div>
                </div>
            </div>

            <h3>Tags</h3>
            <?php if ( $tags ) : ?>
                <div style="display:flex; gap:10px;">
                    <?php foreach ($tags as $tag) : ?>
                        <span style="background:#e5dafc; color:#4e28a5; padding:4px 8px; border-radius:4px; font-size:12px;"><?php echo esc_html($tag->name); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>No tags.</p>
            <?php endif; ?>

            <h3>Segments</h3>
            <?php 
            $contact_segments = $segment_model->get_contact_segments( $contact_id );
            if ( $contact_segments ) : ?>
                <div style="display:flex; gap:10px;">
                    <?php foreach ($contact_segments as $segment) : ?>
                        <span style="background:#d1e7ff; color:#004085; padding:4px 8px; border-radius:4px; font-size:12px;"><?php echo esc_html($segment->name); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>No segments.</p>
            <?php endif; ?>
            
            <div style="margin-top:10px;">
                <?php $all_tags = $tag_model->get_tags(); ?>
                <select id="gee-assign-tag-select">
                    <option value="">Select Tag...</option>
                    <?php foreach ($all_tags as $t) : ?>
                        <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="gee-assign-tag-btn" class="gee-crm-btn gee-crm-btn-primary" data-contact-id="<?php echo $contact_id; ?>">Add Tag</button>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#gee-assign-tag-btn').on('click', function() {
                    var tag_id = $('#gee-assign-tag-select').val();
                    var contact_id = $(this).data('contact-id');
                    if(!tag_id) return;

                    $.post(geeWooCRM.ajaxurl, {
                        action: 'gee_crm_assign_tag',
                        nonce: geeWooCRM.nonce,
                        contact_id: contact_id,
                        tag_id: tag_id
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
		</div>
		<?php
	}

} else {
	// List View //
    $segments = $segment_model->get_segments();

	$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
    $segment_filter = isset( $_GET['segment_id'] ) ? absint( $_GET['segment_id'] ) : 0;
    
    // Filtering Args
    $args = array( 'search' => $search );
    if ( $segment_filter ) {
        // Get IDs in segment
        $ids_in_segment = $segment_model->get_contact_ids_in_segment( $segment_filter );
        if ( empty( $ids_in_segment ) ) {
            // Force return empty
            $args['include_ids'] = array(0); 
        } else {
            $args['include_ids'] = $ids_in_segment;
        }
    }

	$contacts = $contact_model->get_contacts( $args );
	?>
	<div class="gee-crm-card">
		<div class="tablenav top" style="display:flex; justify-content:space-between; align-items:center;">
			<form method="get">
				<input type="hidden" name="page" value="gee-woo-crm" />
				<input type="hidden" name="view" value="contacts" />
				
                <select name="segment_id">
                    <option value="">Filter by Segment</option>
                    <?php foreach($segments as $s): ?>
                        <option value="<?php echo $s->id; ?>" <?php selected($segment_filter, $s->id); ?>><?php echo esc_html($s->name); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search contacts..." style="width: 200px;" />
				<input type="submit"  class="button" value="Filter" />
			</form>
            <span style="color:#666;">Showing: <?php echo count($contacts); ?></span>
		</div>

		<table class="gee-crm-table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Source</th>
                    <th>Tags & Segments</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $contacts ) : ?>
					<?php foreach ( $contacts as $contact ) : ?>
						<tr>
							<td>
                                <a href="?page=gee-woo-crm&view=contacts&action=view&id=<?php echo $contact->id; ?>" style="font-weight:bold;">
                                    <?php echo esc_html( $contact->first_name . ' ' . $contact->last_name ); ?>
                                </a>
                            </td>
							<td><?php echo esc_html( $contact->email ); ?></td>
							<td><?php echo esc_html( ucfirst( $contact->status ) ); ?></td>
							<td><?php echo esc_html( $contact->source ); ?></td>
                            <td>
                                <?php 
                                $contact_tags = $tag_model->get_contact_tags( $contact->id );
                                if ( $contact_tags ) : ?>
                                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:4px;">
                                        <?php foreach ( $contact_tags as $t ) : ?>
                                            <span style="background:#e5dafc; color:#4e28a5; padding:2px 6px; border-radius:3px; font-size:11px;" title="Tag"><?php echo esc_html( $t->name ); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                $contact_segments = $segment_model->get_contact_segments( $contact->id );
                                if ( $contact_segments ) : ?>
                                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                        <?php foreach ( $contact_segments as $s ) : ?>
                                            <span style="background:#d1e7ff; color:#004085; padding:2px 6px; border-radius:3px; font-size:11px;" title="Segment"><?php echo esc_html( $s->name ); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
							<td><?php echo date( 'Y-m-d', strtotime( $contact->created_at ) ); ?></td>
							<td>
								<a href="?page=gee-woo-crm&view=contacts&action=view&id=<?php echo $contact->id; ?>" class="gee-crm-btn">View</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="7">No contacts found. Have you synced WooCommerce?</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
