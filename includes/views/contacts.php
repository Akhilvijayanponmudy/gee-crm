<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
$contact_model = new Gee_Woo_CRM_Contact();
$tag_model = new Gee_Woo_CRM_Tag();

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
            SELECT t.id, t.name 
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
            
            <div style="margin-top:20px; padding:15px; background:#f8f9fa; border-radius:4px;">
                <h3 style="margin-top:0;">Marketing Email Consent</h3>
                <p>
                    <label>
                        <input type="checkbox" id="marketing-consent-toggle" data-contact-id="<?php echo $contact_id; ?>" <?php checked( ! empty( $contact->marketing_consent ), true ); ?>>
                        <strong>User has consented to receive marketing emails</strong>
                    </label>
                </p>
                <?php if ( ! empty( $contact->consent_date ) ) : ?>
                    <p style="color:#666; font-size:13px; margin-top:10px;">
                        <strong>Consent Date:</strong> <?php echo date( 'F j, Y g:i A', strtotime( $contact->consent_date ) ); ?>
                    </p>
                <?php else : ?>
                    <p style="color:#999; font-size:13px; margin-top:10px;">
                        <em>No consent recorded. This contact will NOT receive marketing campaigns.</em>
                    </p>
                <?php endif; ?>
            </div>
            
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
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px;">
                    <?php foreach ($tags as $tag) : ?>
                        <?php if ( isset( $tag->id ) && isset( $tag->name ) ) : ?>
                            <span class="gee-tag-badge" style="background:#e5dafc; color:#4e28a5; padding:4px 8px; border-radius:4px; font-size:12px; display:inline-flex; align-items:center; gap:5px;">
                                <?php echo esc_html($tag->name); ?>
                                <button class="gee-remove-tag-btn" data-contact-id="<?php echo $contact_id; ?>" data-tag-id="<?php echo $tag->id; ?>" style="background:none; border:none; color:#4e28a5; cursor:pointer; font-size:14px; padding:0; margin:0; line-height:1;" title="Remove tag">×</button>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>No tags.</p>
            <?php endif; ?>
            
            <div style="margin-top:10px;">
                <?php 
                $all_tags = $tag_model->get_tags();
                // Filter out already assigned tags
                $assigned_tag_ids = array();
                if ( $tags && is_array( $tags ) ) {
                    foreach ( $tags as $tag ) {
                        if ( isset( $tag->id ) ) {
                            $assigned_tag_ids[] = $tag->id;
                        }
                    }
                }
                ?>
                <select id="gee-assign-tag-select">
                    <option value="">Select Tag...</option>
                    <?php foreach ($all_tags as $t) : ?>
                        <?php if ( ! in_array( $t->id, $assigned_tag_ids ) ) : ?>
                            <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->name); ?></option>
                        <?php endif; ?>
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

                $('.gee-remove-tag-btn').on('click', function() {
                    if(!confirm('Remove this tag from the contact?')) return;
                    
                    var contact_id = $(this).data('contact-id');
                    var tag_id = $(this).data('tag-id');
                    var $badge = $(this).closest('.gee-tag-badge');

                    $.post(geeWooCRM.ajaxurl, {
                        action: 'gee_crm_remove_tag',
                        nonce: geeWooCRM.nonce,
                        contact_id: contact_id,
                        tag_id: tag_id
                    }, function(res) {
                        if(res.success) {
                            $badge.fadeOut(300, function() {
                                $(this).remove();
                                location.reload(); // Reload to refresh tag dropdown
                            });
                        } else {
                            alert(res.data);
                        }
                    });
                });
                
                // Marketing consent toggle
                $('#marketing-consent-toggle').on('change', function() {
                    var contact_id = $(this).data('contact-id');
                    var consent = $(this).is(':checked') ? 1 : 0;
                    var $checkbox = $(this);
                    
                    $checkbox.prop('disabled', true);
                    
                    $.post(geeWooCRM.ajaxurl, {
                        action: 'gee_update_marketing_consent',
                        nonce: geeWooCRM.nonce,
                        contact_id: contact_id,
                        consent: consent
                    }, function(res) {
                        $checkbox.prop('disabled', false);
                        if(res.success) {
                            location.reload(); // Reload to show updated consent date
                        } else {
                            alert('Failed to update marketing consent: ' + (res.data || 'Unknown error'));
                            $checkbox.prop('checked', !consent); // Revert checkbox
                        }
                    }).fail(function() {
                        $checkbox.prop('disabled', false);
                        $checkbox.prop('checked', !consent); // Revert checkbox
                        alert('Failed to update marketing consent. Please try again.');
                    });
                });
            });
            </script>
		</div>
		<?php
	}

} else {
	// List View //
    require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
    require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
    $segment_model = new Gee_Woo_CRM_Segment();
    $tag_model = new Gee_Woo_CRM_Tag();
    $segments = $segment_model->get_segments();
    $tags = $tag_model->get_tags();

	$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
    $segment_filter = isset( $_GET['segment_id'] ) ? absint( $_GET['segment_id'] ) : 0;
    $tag_filter = isset( $_GET['tag_id'] ) ? absint( $_GET['tag_id'] ) : 0;
    $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    $per_page = 20;
    
    // Filtering Args
    $args = array( 
        'search' => $search,
        'page' => $current_page,
        'per_page' => $per_page
    );
    
    // Tag filter
    if ( $tag_filter ) {
        $args['tag_id'] = $tag_filter;
    }
    
    // Segment filter
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
	$total_contacts = $contact_model->get_count( $args );
	$total_pages = ceil( $total_contacts / $per_page );
	?>
	<div class="gee-crm-card">
		<div class="tablenav top" style="display:flex; justify-content:space-between; align-items:center;">
			<form method="get">
				<input type="hidden" name="page" value="gee-woo-crm" />
				<input type="hidden" name="view" value="contacts" />
				<input type="hidden" name="paged" value="1" />
				
                <select name="segment_id" style="margin-right:10px;">
                    <option value="">Filter by Segment</option>
                    <?php foreach($segments as $s): ?>
                        <option value="<?php echo $s->id; ?>" <?php selected($segment_filter, $s->id); ?>><?php echo esc_html($s->name); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="tag_id" style="margin-right:10px;">
                    <option value="">Filter by Tag</option>
                    <?php foreach($tags as $tag): ?>
                        <option value="<?php echo $tag->id; ?>" <?php selected($tag_filter, $tag->id); ?>><?php echo esc_html($tag->name); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search contacts..." style="width: 200px; margin-right:10px;" />
				<input type="submit"  class="button" value="Filter" />
			</form>
            <span style="color:#666;">
				<?php 
				$start = ( $current_page - 1 ) * $per_page + 1;
				$end = min( $current_page * $per_page, $total_contacts );
				if ( $total_contacts > 0 ) {
					echo sprintf( 'Showing %d-%d of %d', $start, $end, $total_contacts );
				} else {
					echo 'No contacts found';
				}
				?>
			</span>
		</div>

		<div style="margin-bottom:15px; display:flex; gap:10px; align-items:center;">
			<select id="gee-bulk-action" style="padding:5px;">
				<option value="">Bulk Actions</option>
				<option value="add_to_tag">Add to Tag</option>
				<option value="add_to_segment">Add to Segment</option>
			</select>
			<button id="gee-apply-bulk-action" class="button" disabled>Apply</button>
			<span id="gee-selected-count" style="margin-left:10px; color:#666;"></span>
		</div>

		<table class="gee-crm-table">
			<thead>
				<tr>
					<th style="width:30px;"><input type="checkbox" id="gee-select-all-contacts" /></th>
					<th>Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Marketing Consent</th>
					<th>Source</th>
					<th>Tags</th>
					<th>Segments</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $contacts ) : ?>
					<?php 
					global $wpdb;
					$contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';
					$tags_table = $wpdb->prefix . 'gee_crm_tags';
					
					foreach ( $contacts as $contact ) : 
						// Get tags for this contact
						$contact_tags = $wpdb->get_results( $wpdb->prepare(
							"SELECT t.id, t.name 
							FROM $tags_table t
							INNER JOIN $contact_tags_table ct ON ct.tag_id = t.id
							WHERE ct.contact_id = %d
							ORDER BY t.name ASC",
							$contact->id
						) );
						
						// Get segments this contact belongs to
						// Note: Segments are dynamic - they automatically include/exclude contacts
						// based on current conditions. No manual updates needed.
						$contact_segments = array();
						foreach ( $segments as $segment ) {
							if ( $segment_model->contact_matches_segment( $contact->id, $segment->id ) ) {
								$contact_segments[] = $segment;
							}
						}
					?>
						<tr>
							<td><input type="checkbox" class="gee-contact-checkbox" value="<?php echo $contact->id; ?>" /></td>
							<td>
                                <a href="?page=gee-woo-crm&view=contacts&action=view&id=<?php echo $contact->id; ?>" style="font-weight:bold;">
                                    <?php echo esc_html( $contact->first_name . ' ' . $contact->last_name ); ?>
                                </a>
                            </td>
							<td><?php echo esc_html( $contact->email ); ?></td>
							<td><?php echo esc_html( ucfirst( $contact->status ) ); ?></td>
						<td>
							<?php if ( ! empty( $contact->marketing_consent ) ) : ?>
								<span style="color:#28a745; font-weight:600;">✓ Yes</span>
								<?php if ( ! empty( $contact->consent_date ) ) : ?>
									<br><small style="color:#666;"><?php echo date( 'M j, Y', strtotime( $contact->consent_date ) ); ?></small>
								<?php endif; ?>
							<?php else : ?>
								<span style="color:#dc3545;">✗ No</span>
							<?php endif; ?>
						</td>
							<td><?php echo esc_html( $contact->source ); ?></td>
							<td>
								<?php if ( $contact_tags ) : ?>
									<div style="display:flex; flex-wrap:wrap; gap:5px;">
										<?php foreach ( $contact_tags as $tag ) : ?>
											<span style="background:#e5dafc; color:#4e28a5; padding:2px 6px; border-radius:3px; font-size:11px; white-space:nowrap;">
												<?php echo esc_html( $tag->name ); ?>
											</span>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<span style="color:#999;">—</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $contact_segments ) ) : ?>
									<div style="display:flex; flex-wrap:wrap; gap:5px;">
										<?php foreach ( $contact_segments as $seg ) : ?>
											<span style="background:#d1ecf1; color:#0c5460; padding:2px 6px; border-radius:3px; font-size:11px; white-space:nowrap;">
												<?php echo esc_html( $seg->name ); ?>
											</span>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<span style="color:#999;">—</span>
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
						<td colspan="9">No contacts found. Have you synced WooCommerce?</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom" style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:20px; border-top:1px solid #ddd;">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo sprintf( '%d items', $total_contacts ); ?></span>
					<span class="pagination-links">
						<?php
						$base_url = admin_url( 'admin.php?page=gee-woo-crm&view=contacts' );
						if ( $search ) {
							$base_url .= '&s=' . urlencode( $search );
						}
						if ( $segment_filter ) {
							$base_url .= '&segment_id=' . $segment_filter;
						}
						if ( $tag_filter ) {
							$base_url .= '&tag_id=' . $tag_filter;
						}
						
						// First page
						if ( $current_page > 1 ) {
							echo '<a class="first-page button" href="' . esc_url( $base_url . '&paged=1' ) . '"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>';
						} else {
							echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
						}
						
						// Previous page
						if ( $current_page > 1 ) {
							$prev_page = $current_page - 1;
							echo '<a class="prev-page button" href="' . esc_url( $base_url . '&paged=' . $prev_page ) . '"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
						} else {
							echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
						}
						
						// Page numbers
						echo '<span class="paging-input">';
						echo '<label for="current-page-selector" class="screen-reader-text">Current page</label>';
						echo '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . esc_attr( $current_page ) . '" size="2" aria-describedby="table-paging" data-base-url="' . esc_attr( $base_url ) . '" data-total-pages="' . esc_attr( $total_pages ) . '" />';
						echo '<span class="tablenav-paging-text"> of <span class="total-pages">' . esc_html( $total_pages ) . '</span></span>';
						echo '</span>';
						
						// Next page
						if ( $current_page < $total_pages ) {
							$next_page = $current_page + 1;
							echo '<a class="next-page button" href="' . esc_url( $base_url . '&paged=' . $next_page ) . '"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>';
						} else {
							echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
						}
						
						// Last page
						if ( $current_page < $total_pages ) {
							echo '<a class="last-page button" href="' . esc_url( $base_url . '&paged=' . $total_pages ) . '"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>';
						} else {
							echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
						}
						?>
					</span>
				</div>
			</div>
			<script>
			jQuery(document).ready(function($) {
				$('#current-page-selector').on('keypress', function(e) {
					if (e.which === 13) { // Enter key
						var page = parseInt($(this).val());
						var totalPages = parseInt($(this).data('total-pages'));
						var baseUrl = $(this).data('base-url');
						
						if (isNaN(page) || page < 1) {
							page = 1;
						} else if (page > totalPages) {
							page = totalPages;
						}
						
						window.location.href = baseUrl + '&paged=' + page;
					}
				});
			});
			</script>
		<?php endif; ?>

		<script>
		jQuery(document).ready(function($) {
			var tags = <?php echo json_encode( array_map( function($tag) { return array( 'id' => $tag->id, 'name' => $tag->name ); }, $tags ) ); ?>;
			var segments = <?php echo json_encode( array_map( function($seg) { return array( 'id' => $seg->id, 'name' => $seg->name ); }, $segments ) ); ?>;

			// Select all checkbox
			$('#gee-select-all-contacts').on('change', function() {
				$('.gee-contact-checkbox').prop('checked', $(this).prop('checked'));
				updateSelectedCount();
			});

			// Individual checkbox change
			$(document).on('change', '.gee-contact-checkbox', function() {
				var total = $('.gee-contact-checkbox').length;
				var checked = $('.gee-contact-checkbox:checked').length;
				$('#gee-select-all-contacts').prop('checked', total === checked);
				updateSelectedCount();
			});

			// Update selected count
			function updateSelectedCount() {
				var count = $('.gee-contact-checkbox:checked').length;
				if (count > 0) {
					$('#gee-selected-count').text(count + ' contact(s) selected');
					$('#gee-apply-bulk-action').prop('disabled', false);
				} else {
					$('#gee-selected-count').text('');
					$('#gee-apply-bulk-action').prop('disabled', true);
				}
			}

			// Apply bulk action
			$('#gee-apply-bulk-action').on('click', function() {
				var action = $('#gee-bulk-action').val();
				var selectedIds = $('.gee-contact-checkbox:checked').map(function() {
					return $(this).val();
				}).get();

				if (!action || selectedIds.length === 0) {
					alert('Please select an action and at least one contact.');
					return;
				}

				if (action === 'add_to_tag') {
					showTagSelector(selectedIds);
				} else if (action === 'add_to_segment') {
					showSegmentSelector(selectedIds);
				}
			});

			// Show tag selector
			function showTagSelector(contactIds) {
				var options = '<option value="">Select Tag...</option>';
				$.each(tags, function(i, tag) {
					options += '<option value="' + tag.id + '">' + tag.name + '</option>';
				});

				var html = '<div id="gee-bulk-tag-modal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000; display:flex; align-items:center; justify-content:center;">' +
					'<div style="background:#fff; padding:20px; border-radius:5px; min-width:300px;">' +
					'<h3>Add to Tag</h3>' +
					'<p>Select a tag to add ' + contactIds.length + ' contact(s) to:</p>' +
					'<select id="gee-bulk-tag-select" style="width:100%; padding:8px; margin:10px 0;">' + options + '</select>' +
					'<div style="margin-top:15px; text-align:right;">' +
					'<button class="button" id="gee-bulk-tag-cancel" style="margin-right:10px;">Cancel</button>' +
					'<button class="button button-primary" id="gee-bulk-tag-submit">Add to Tag</button>' +
					'</div>' +
					'</div>' +
					'</div>';

				$('body').append(html);

				$('#gee-bulk-tag-cancel').on('click', function() {
					$('#gee-bulk-tag-modal').remove();
				});

				$('#gee-bulk-tag-submit').on('click', function() {
					var tagId = $('#gee-bulk-tag-select').val();
					if (!tagId) {
						alert('Please select a tag.');
						return;
					}

					bulkAddToTag(contactIds, tagId);
				});
			}

			// Show segment selector
			function showSegmentSelector(contactIds) {
				var options = '<option value="">Select Segment...</option>';
				$.each(segments, function(i, seg) {
					options += '<option value="' + seg.id + '">' + seg.name + '</option>';
				});

				var html = '<div id="gee-bulk-segment-modal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000; display:flex; align-items:center; justify-content:center;">' +
					'<div style="background:#fff; padding:20px; border-radius:5px; min-width:300px;">' +
					'<h3>Add to Segment</h3>' +
					'<p>Note: Segments are dynamic based on conditions. Contacts will be included if they match the segment conditions.</p>' +
					'<p style="color:#d63638; font-size:12px;">This action does not directly add contacts to segments. Segments automatically include contacts that match their conditions.</p>' +
					'<div style="margin-top:15px; text-align:right;">' +
					'<button class="button" id="gee-bulk-segment-cancel">Close</button>' +
					'</div>' +
					'</div>' +
					'</div>';

				$('body').append(html);

				$('#gee-bulk-segment-cancel').on('click', function() {
					$('#gee-bulk-segment-modal').remove();
				});
			}

			// Bulk add to tag
			function bulkAddToTag(contactIds, tagId) {
				var processed = 0;
				var total = contactIds.length;
				var errors = [];

				$('#gee-bulk-tag-submit').prop('disabled', true).text('Processing...');

				function processNext() {
					if (processed >= total) {
						$('#gee-bulk-tag-modal').remove();
						if (errors.length > 0) {
							alert('Processed ' + (total - errors.length) + ' contacts. ' + errors.length + ' failed.');
						} else {
							alert('Successfully added ' + total + ' contact(s) to tag.');
						}
						location.reload();
						return;
					}

					var contactId = contactIds[processed];
					$.post(geeWooCRM.ajaxurl, {
						action: 'gee_crm_assign_tag',
						nonce: geeWooCRM.nonce,
						contact_id: contactId,
						tag_id: tagId
					}, function(res) {
						if (!res.success && res.data !== 'Tag is already assigned to this contact') {
							errors.push(contactId);
						}
						processed++;
						processNext();
					}).fail(function() {
						errors.push(contactId);
						processed++;
						processNext();
					});
				}

				processNext();
			}
		});
		</script>
	</div>
	<?php
}
