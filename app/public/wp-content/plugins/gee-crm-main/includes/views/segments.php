<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';

$segment_model = new Gee_Woo_CRM_Segment();
$tag_model = new Gee_Woo_CRM_Tag();
$contact_model = new Gee_Woo_CRM_Contact();

$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
$segment_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
    die('Unauthorized');
}

// Handle Save
if ( isset( $_POST['gee_save_segment'] ) && check_admin_referer( 'gee_save_segment_nonce' ) ) {
    $name = sanitize_text_field( $_POST['name'] );
    $rules = json_decode( stripslashes( $_POST['rules_json'] ), true );
    
    if ( $name ) {
        if ( $segment_id ) {
            $segment_model->update_segment( $segment_id, $name, $rules );
            echo '<div class="notice notice-success"><p>Segment updated.</p></div>';
        } else {
            $segment_id = $segment_model->create_segment( $name, $rules );
            echo '<div class="notice notice-success"><p>Segment created.</p></div>';
        }
        delete_transient( 'gee_crm_segment_count_' . $segment_id ); // Clear cache
    }
}

// Handle Delete
if ( 'delete' === $action && $segment_id ) {
    $segment_model->delete_segment( $segment_id );
    echo '<script>window.location.href="?page=gee-woo-crm&view=segments";</script>';
    exit;
}

// Refresh Count
if ( isset( $_GET['refresh_count'] ) ) {
    delete_transient( 'gee_crm_segment_count_' . absint( $_GET['refresh_count'] ) );
    wp_redirect( admin_url( 'admin.php?page=gee-woo-crm&view=segments' ) );
    exit;
}

if ( in_array( $action, ['add', 'edit'] ) ) {
    $segment = $segment_id ? $segment_model->get_segment( $segment_id ) : null;
    $rules = $segment ? json_decode( $segment->rules_json, true ) : array(
        'tags' => array( 'include' => array(), 'exclude' => array(), 'mode' => 'ANY' ),
        'conditions' => array( 'relation' => 'AND', 'items' => array() )
    );
    $all_tags = $tag_model->get_tags();
    ?>
    <div class="gee-crm-card">
        <h2><?php echo $segment ? 'Edit Segment' : 'New Segment'; ?></h2>
        <form method="post" id="gee-segment-builder-form">
            <?php wp_nonce_field( 'gee_save_segment_nonce' ); ?>
            <input type="hidden" name="rules_json" id="gee-rules-json-input">
            
            <table class="form-table">
                <tr>
                    <th>Segment Name</th>
                    <td><input type="text" name="name" value="<?php echo $segment ? esc_attr($segment->name) : ''; ?>" required class="regular-text" placeholder="e.g. VIP Customers"></td>
                </tr>
            </table>

            <div class="gee-segment-rule-box">
                <h3>Tag Filters</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <label><strong>Include Contacts with Tags:</strong></label>
                        <select id="include_tags" class="gee-select2" multiple style="width:100%;">
                            <?php foreach($all_tags as $t): ?>
                                <option value="<?php echo $t->id; ?>" <?php echo in_array($t->id, (array)$rules['tags']['include']) ? 'selected' : ''; ?>><?php echo esc_html($t->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Contacts must have at least one of these (Default: ANY).</p>
                    </div>
                    <div>
                        <label><strong>Exclude Contacts with Tags:</strong></label>
                        <select id="exclude_tags" class="gee-select2" multiple style="width:100%;">
                            <?php foreach($all_tags as $t): ?>
                                <option value="<?php echo $t->id; ?>" <?php echo in_array($t->id, (array)$rules['tags']['exclude']) ? 'selected' : ''; ?>><?php echo esc_html($t->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Exclude contacts having any of these tags.</p>
                    </div>
                </div>
                <div style="margin-top:15px;">
                    <label>Include Tags Match Mode:</label>
                    <select id="tag_mode">
                        <option value="ANY" <?php selected($rules['tags']['mode'], 'ANY'); ?>>Match ANY (OR)</option>
                        <option value="ALL" <?php selected($rules['tags']['mode'], 'ALL'); ?>>Match ALL (AND)</option>
                    </select>
                </div>
            </div>

            <div class="gee-segment-rule-box">
                <h3>Behavioral Conditions</h3>
                <p>Contacts MUST match 
                    <select id="cond_relation">
                        <option value="AND" <?php selected($rules['conditions']['relation'], 'AND'); ?>>ALL</option>
                        <option value="OR" <?php selected($rules['conditions']['relation'], 'OR'); ?>>ANY</option>
                    </select>
                    of the following:
                </p>

                <div id="conditions-list">
                    <!-- Loaded via JS -->
                </div>

                <button type="button" id="add-condition" class="gee-crm-btn">+ Add Condition</button>
            </div>

            <div style="margin-top:30px;">
                <input type="submit" name="gee_save_segment" class="button button-primary" value="Save Segment">
                <a href="?page=gee-woo-crm&view=segments" class="button">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Init Select2
        $('.gee-select2').select2({
            placeholder: "Search Tags...",
            allowClear: true
        });

        var conditions = <?php echo json_encode($rules['conditions']['items'] ?? []); ?>;

        function renderRow(data = {}) {
            var id = Date.now() + Math.random();
            var type = data.type || 'purchased_within_days';
            
            var row = $(`
                <div class="gee-condition-row" data-id="${id}">
                    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                        <select class="field-type" style="min-width:200px;">
                            <option value="purchased_within_days" ${type == 'purchased_within_days' ? 'selected' : ''}>Purchased Within Last X Days</option>
                            <option value="last_order_before_days" ${type == 'last_order_before_days' ? 'selected' : ''}>Inactive (Last Order Older Than X Days)</option>
                            <option value="total_spent_greater_than" ${type == 'total_spent_greater_than' ? 'selected' : ''}>Total Spent</option>
                            <option value="order_count_greater_than" ${type == 'order_count_greater_than' ? 'selected' : ''}>Order Count</option>
                            <option value="email_contains" ${type == 'email_contains' ? 'selected' : ''}>Email Contains</option>
                        </select>

                        <div class="dynamic-inputs" style="display:flex; gap:10px; align-items:center;">
                            <!-- Handled by toggleInputs -->
                        </div>

                        <button type="button" class="remove-row" style="margin-left:auto; background:none; border:none; color:#d63638; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                </div>
            `);

            $('#conditions-list').append(row);
            toggleInputs(row, data);
        }

        function toggleInputs(row, data = {}) {
            var type = row.find('.field-type').val();
            var container = row.find('.dynamic-inputs');
            container.empty();

            if (type == 'purchased_within_days' || type == 'last_order_before_days') {
                container.append(`<input type="number" class="val-days" value="${data.days || 30}" style="width:70px;"> days`);
            } else if (type == 'total_spent_greater_than' || type == 'order_count_greater_than') {
                container.append(`
                    <select class="val-op">
                        <option value=">" ${data.operator == '>' ? 'selected' : ''}>></option>
                        <option value=">=" ${data.operator == '>=' ? 'selected' : ''}>>=</option>
                        <option value="<" ${data.operator == '<' ? 'selected' : ''}><</option>
                        <option value="<=" ${data.operator == '<=' ? 'selected' : ''}><=</option>
                    </select>
                    <input type="number" class="val-num" value="${data.value || 0}" style="width:100px;">
                    <span style="margin-left:10px;">Within Last</span>
                    <input type="number" class="val-within-days" value="${data.within_days || 0}" placeholder="All Time" style="width:80px;"> days
                `);
            } else if (type == 'email_contains') {
                container.append(`<input type="text" class="val-text" value="${data.value || ''}" placeholder="substring...">`);
            }
        }

        $('#add-condition').on('click', function() { renderRow(); });
        $(document).on('change', '.field-type', function() { toggleInputs($(this).closest('.gee-condition-row')); });
        $(document).on('click', '.remove-row', function() { $(this).closest('.gee-condition-row').remove(); });

        // Hydrate
        if (conditions.length) {
            conditions.forEach(c => renderRow(c));
        }

        // Serialize on Submit
        $('#gee-segment-builder-form').on('submit', function(e) {
            var rules = {
                tags: {
                    include: $('#include_tags').val() || [],
                    exclude: $('#exclude_tags').val() || [],
                    mode: $('#tag_mode').val()
                },
                conditions: {
                    relation: $('#cond_relation').val(),
                    items: []
                }
            };

            $('.gee-condition-row').each(function() {
                var row = $(this);
                var type = row.find('.field-type').val();
                var item = { type: type };

                if (type == 'purchased_within_days' || type == 'last_order_before_days') {
                    item.days = row.find('.val-days').val();
                } else if (type == 'total_spent_greater_than' || type == 'order_count_greater_than') {
                    item.operator = row.find('.val-op').val();
                    item.value = row.find('.val-num').val();
                    item.within_days = row.find('.val-within-days').val();
                } else if (type == 'email_contains') {
                    item.value = row.find('.val-text').val();
                }
                rules.conditions.items.push(item);
            });

            $('#gee-rules-json-input').val(JSON.stringify(rules));
        });
    });
    </script>
    <?php
} elseif ( $action === 'view_members' && $segment_id ) {
    $segment = $segment_model->get_segment( $segment_id );
    $member_ids = $segment_model->get_contact_ids_in_segment( $segment_id );
    
    $args = array(
        'include_ids' => $member_ids,
        'search'      => isset($_GET['s']) ? $_GET['s'] : ''
    );
    $members = $contact_model->get_contacts($args);
    ?>
    <div class="gee-crm-card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Members: <?php echo esc_html($segment->name); ?></h2>
            <a href="?page=gee-woo-crm&view=segments" class="button">&larr; Back</a>
        </div>
        <hr>
        <table class="gee-crm-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members) : ?>
                    <?php foreach ($members as $m) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($m->first_name . ' ' . $m->last_name); ?></strong></td>
                            <td><?php echo esc_html($m->email); ?></td>
                            <td><span class="gee-crm-badge"><?php echo esc_html(ucfirst($m->status)); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($m->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4">No members found matching these rules.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
} else {
    // List View
    $segments = $segment_model->get_segments();
    ?>
    <div class="gee-crm-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Segments</h2>
            <a href="?page=gee-woo-crm&view=segments&action=add" class="gee-crm-btn gee-crm-btn-primary">+ New Segment</a>
        </div>

        <table class="gee-crm-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Matching Contacts</th>
                    <th>Rules Summary</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $segments ) : ?>
                    <?php foreach ( $segments as $seg ) : ?>
                        <?php 
                            $rules = json_decode( $seg->rules_json, true );
                            $inc = count($rules['tags']['include'] ?? []);
                            $conds = count($rules['conditions']['items'] ?? []);
                            $count = $segment_model->get_segment_count($seg->id);
                        ?>
                        <tr>
                            <td><strong><a href="?page=gee-woo-crm&view=segments&action=edit&id=<?php echo $seg->id; ?>"><?php echo esc_html( $seg->name ); ?></a></strong></td>
                            <td>
                                <span class="gee-crm-badge" style="background:#e3f2fd; color:#1976d2;"><?php echo $count; ?></span>
                                <a href="?page=gee-woo-crm&view=segments&refresh_count=<?php echo $seg->id; ?>" title="Force Recalculate" style="margin-left:5px; text-decoration:none;">&#8634;</a>
                            </td>
                            <td>
                                <span style="font-size:12px; color:#666;">
                                    <?php echo $inc; ?> Tags, <?php echo $conds; ?> Rules
                                </span>
                            </td>
                            <td>
                                <a href="?page=gee-woo-crm&view=segments&action=view_members&id=<?php echo $seg->id; ?>" class="gee-crm-btn">View Members</a>
                                <a href="?page=gee-woo-crm&view=segments&action=delete&id=<?php echo $seg->id; ?>" class="gee-crm-btn" style="color:#d63638; border-color:#d63638;" onclick="return confirm('Delete?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4">No segments created yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
