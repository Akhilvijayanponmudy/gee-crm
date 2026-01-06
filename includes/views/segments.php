<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';

$segment_model = new Gee_Woo_CRM_Segment();
$tag_model = new Gee_Woo_CRM_Tag();

$segments = $segment_model->get_segments();
$tags = $tag_model->get_tags();

// Handle Edit - Get segment to edit
$editing_segment = null;
$edit_id = isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
if ( $edit_id ) {
    $editing_segment = $segment_model->get_segment( $edit_id );
}

// Handle form submission
if ( isset( $_POST['gee_create_segment'] ) && check_admin_referer( 'gee_create_segment_nonce' ) ) {
    $name = sanitize_text_field( $_POST['name'] );
    $logic = isset( $_POST['logic'] ) ? sanitize_text_field( $_POST['logic'] ) : 'AND';
    $conditions = isset( $_POST['conditions'] ) ? $_POST['conditions'] : array();
    
    // Sanitize conditions
    $sanitized_conditions = array();
    foreach ( $conditions as $condition ) {
        if ( ! empty( $condition['field'] ) && ! empty( $condition['operator'] ) ) {
            $sanitized_conditions[] = array(
                'field' => sanitize_text_field( $condition['field'] ),
                'operator' => sanitize_text_field( $condition['operator'] ),
                'value' => isset( $condition['value'] ) ? sanitize_text_field( $condition['value'] ) : ''
            );
        }
    }
    
    if ( $name && ! empty( $sanitized_conditions ) ) {
        $rules = array(
            'logic' => $logic,
            'conditions' => $sanitized_conditions
        );
        $segment_model->create_segment( $name, $rules );
        echo '<div class="notice notice-success"><p>Segment created.</p></div>';
        $segments = $segment_model->get_segments(); // Refresh
    } else {
        echo '<div class="notice notice-error"><p>Please provide a name and at least one condition.</p></div>';
    }
}

// Handle Update
if ( isset( $_POST['gee_update_segment'] ) && check_admin_referer( 'gee_update_segment_nonce' ) ) {
    $id = absint( $_POST['segment_id'] );
    $name = sanitize_text_field( $_POST['name'] );
    $logic = isset( $_POST['logic'] ) ? sanitize_text_field( $_POST['logic'] ) : 'AND';
    $conditions = isset( $_POST['conditions'] ) ? $_POST['conditions'] : array();
    
    // Sanitize conditions
    $sanitized_conditions = array();
    foreach ( $conditions as $condition ) {
        if ( ! empty( $condition['field'] ) && ! empty( $condition['operator'] ) ) {
            $sanitized_conditions[] = array(
                'field' => sanitize_text_field( $condition['field'] ),
                'operator' => sanitize_text_field( $condition['operator'] ),
                'value' => isset( $condition['value'] ) ? sanitize_text_field( $condition['value'] ) : ''
            );
        }
    }
    
    if ( $id && $name && ! empty( $sanitized_conditions ) ) {
        $rules = array(
            'logic' => $logic,
            'conditions' => $sanitized_conditions
        );
        $segment_model->update_segment( $id, $name, $rules );
        echo '<div class="notice notice-success"><p>Segment updated.</p></div>';
        $segments = $segment_model->get_segments(); // Refresh
        $editing_segment = null; // Clear edit mode
        $edit_id = 0;
    } else {
        echo '<div class="notice notice-error"><p>Please provide a name and at least one condition.</p></div>';
    }
}

// Handle Delete
if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['id'] ) ) {
    $segment_model->delete_segment( absint( $_GET['id'] ) );
    echo '<script>window.location.href="?page=gee-woo-crm&view=segments";</script>';
}

// Get existing conditions for edit mode
$existing_conditions = array();
$existing_logic = 'AND';
if ( $editing_segment ) {
    $edit_rules = json_decode( $editing_segment->rules_json, true );
    if ( $edit_rules && isset( $edit_rules['conditions'] ) ) {
        $existing_conditions = $edit_rules['conditions'];
        $existing_logic = isset( $edit_rules['logic'] ) ? $edit_rules['logic'] : 'AND';
    }
}

?>

<div class="gee-crm-card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Segments</h2>
    </div>

    <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; margin-bottom:20px;">
        <h3><?php echo $editing_segment ? 'Edit Segment' : 'Create New Segment'; ?></h3>
        <form method="post" id="gee-segment-form">
            <?php if ( $editing_segment ) : ?>
                <?php wp_nonce_field( 'gee_update_segment_nonce' ); ?>
                <input type="hidden" name="segment_id" value="<?php echo esc_attr( $editing_segment->id ); ?>">
            <?php else : ?>
            <?php wp_nonce_field( 'gee_create_segment_nonce' ); ?>
            <?php endif; ?>
            
            <p>
                <label><strong>Segment Name:</strong></label><br>
                <input type="text" name="name" id="segment-name" value="<?php echo $editing_segment ? esc_attr( $editing_segment->name ) : ''; ?>" required style="width:100%; max-width:300px; padding:8px;">
            </p>

            <div style="margin:20px 0;">
                <label><strong>Match Conditions:</strong></label>
                <select name="logic" id="segment-logic" style="margin-left:10px; padding:5px;">
                    <option value="AND" <?php selected( $existing_logic, 'AND' ); ?>>All conditions (AND)</option>
                    <option value="OR" <?php selected( $existing_logic, 'OR' ); ?>>Any condition (OR)</option>
                </select>
            </div>

            <div id="gee-conditions-container" style="margin:20px 0;">
                <div style="margin-bottom:10px;">
                    <strong>Conditions:</strong>
                </div>
                <div id="gee-conditions-list">
                    <?php if ( ! empty( $existing_conditions ) ) : ?>
                        <?php foreach ( $existing_conditions as $index => $condition ) : ?>
                            <div class="gee-condition-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center; padding:10px; background:#fff; border:1px solid #ddd; border-radius:4px;">
                                <select name="conditions[<?php echo $index; ?>][field]" class="condition-field" style="flex:1; padding:5px;">
                                    <option value="">Select Field...</option>
                                    <option value="tag" <?php selected( $condition['field'], 'tag' ); ?>>Tag</option>
                                    <option value="status" <?php selected( $condition['field'], 'status' ); ?>>Status</option>
                                    <option value="source" <?php selected( $condition['field'], 'source' ); ?>>Source</option>
                                    <option value="email" <?php selected( $condition['field'], 'email' ); ?>>Email</option>
                                    <option value="first_name" <?php selected( $condition['field'], 'first_name' ); ?>>First Name</option>
                                    <option value="last_name" <?php selected( $condition['field'], 'last_name' ); ?>>Last Name</option>
                                    <option value="created_date" <?php selected( $condition['field'], 'created_date' ); ?>>Created Date</option>
                                    <option value="total_purchase_value" <?php selected( $condition['field'], 'total_purchase_value' ); ?>>Total Purchase Value</option>
                                    <option value="last_purchase_value" <?php selected( $condition['field'], 'last_purchase_value' ); ?>>Last Purchase Value</option>
                                    <option value="last_purchase_date" <?php selected( $condition['field'], 'last_purchase_date' ); ?>>Last Purchase Date</option>
                                    <option value="first_purchase_date" <?php selected( $condition['field'], 'first_purchase_date' ); ?>>First Purchase Date</option>
                                </select>
                                <select name="conditions[<?php echo $index; ?>][operator]" class="condition-operator" style="flex:1; padding:5px;" data-value="<?php echo esc_attr( $condition['operator'] ); ?>">
                                    <option value="">Select Operator...</option>
                                </select>
                                <div class="condition-value-wrapper" style="flex:2;">
                                    <?php if ( $condition['field'] === 'tag' ) : ?>
                                        <select name="conditions[<?php echo $index; ?>][value]" class="condition-value" style="width:100%; padding:5px;">
                                            <option value="">Select Tag...</option>
                                            <?php foreach ( $tags as $tag ) : ?>
                                                <option value="<?php echo $tag->id; ?>" <?php selected( $condition['value'], $tag->id ); ?>><?php echo esc_html( $tag->name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ( $condition['field'] === 'total_purchase_value' || $condition['field'] === 'last_purchase_value' ) : ?>
                                        <?php 
                                        $operator = isset( $condition['operator'] ) ? $condition['operator'] : '';
                                        $is_time_period = strpos( $operator, '_in_last' ) !== false;
                                        $placeholder = $is_time_period ? 'e.g. 100|30 (amount|days)' : 'e.g. 100.00';
                                        $input_type = $is_time_period ? 'text' : 'number';
                                        ?>
                                        <input type="<?php echo $input_type; ?>" <?php echo $input_type === 'number' ? 'step="0.01"' : ''; ?> name="conditions[<?php echo $index; ?>][value]" class="condition-value" value="<?php echo esc_attr( $condition['value'] ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" style="width:100%; padding:5px;">
                                    <?php else : ?>
                                        <input type="text" name="conditions[<?php echo $index; ?>][value]" class="condition-value" value="<?php echo esc_attr( $condition['value'] ); ?>" placeholder="Value" style="width:100%; padding:5px;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button remove-condition" style="background:#dc3232; color:#fff; border:none; padding:5px 10px; cursor:pointer;">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="gee-condition-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center; padding:10px; background:#fff; border:1px solid #ddd; border-radius:4px;">
                            <select name="conditions[0][field]" class="condition-field" style="flex:1; padding:5px;">
                                <option value="">Select Field...</option>
                                <option value="tag">Tag</option>
                                <option value="status">Status</option>
                                <option value="source">Source</option>
                                <option value="email">Email</option>
                                <option value="first_name">First Name</option>
                                <option value="last_name">Last Name</option>
                                <option value="created_date">Created Date</option>
                                <option value="total_purchase_value">Total Purchase Value</option>
                                <option value="last_purchase_value">Last Purchase Value</option>
                                <option value="last_purchase_date">Last Purchase Date</option>
                                <option value="first_purchase_date">First Purchase Date</option>
                            </select>
                            <select name="conditions[0][operator]" class="condition-operator" style="flex:1; padding:5px;">
                                <option value="">Select Operator...</option>
                            </select>
                            <div class="condition-value-wrapper" style="flex:2;">
                                <input type="text" name="conditions[0][value]" class="condition-value" placeholder="Value" style="width:100%; padding:5px;">
                            </div>
                            <button type="button" class="button remove-condition" style="background:#dc3232; color:#fff; border:none; padding:5px 10px; cursor:pointer;">Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" id="add-condition" class="button" style="margin-top:10px;">+ Add Condition</button>
            </div>

            <?php if ( $editing_segment ) : ?>
                <input type="submit" name="gee_update_segment" class="gee-crm-btn gee-crm-btn-primary" value="Update Segment">
                <a href="?page=gee-woo-crm&view=segments" class="gee-crm-btn" style="margin-left:10px;">Cancel</a>
            <?php else : ?>
            <input type="submit" name="gee_create_segment" class="gee-crm-btn gee-crm-btn-primary" value="Create Segment">
            <?php endif; ?>
        </form>
    </div>

    <table class="gee-crm-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Conditions</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $segments ) : ?>
                <?php foreach ( $segments as $seg ) : ?>
                    <?php 
                        $rules = json_decode( $seg->rules_json, true );
                    $condition_text = 'No conditions';
                    if ( $rules && isset( $rules['conditions'] ) && ! empty( $rules['conditions'] ) ) {
                        $logic = isset( $rules['logic'] ) ? $rules['logic'] : 'AND';
                        $condition_parts = array();
                        foreach ( $rules['conditions'] as $cond ) {
                            $field_label = ucwords( str_replace( '_', ' ', $cond['field'] ) );
                            $operator_label = ucwords( str_replace( '_', ' ', $cond['operator'] ) );
                            $condition_parts[] = "$field_label $operator_label " . ( $cond['value'] ? '"' . esc_html( $cond['value'] ) . '"' : '' );
                        }
                        $condition_text = implode( ' ' . $logic . ' ', $condition_parts );
                    }
                    ?>
                    <tr>
                        <td><?php echo $seg->id; ?></td>
                        <td><?php echo esc_html( $seg->name ); ?></td>
                        <td><span class="gee-crm-badge" style="font-size:12px;"><?php echo esc_html($condition_text); ?></span></td>
                        <td>
                            <a href="?page=gee-woo-crm&view=segments&action=edit&id=<?php echo $seg->id; ?>" class="gee-crm-btn" style="margin-right:5px;">Edit</a>
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

<script>
jQuery(document).ready(function($) {
    var conditionIndex = <?php echo ! empty( $existing_conditions ) ? count( $existing_conditions ) : 1; ?>;
    var tags = <?php echo json_encode( array_map( function($tag) { return array( 'id' => $tag->id, 'name' => $tag->name ); }, $tags ) ); ?>;

    // Operator options for each field type
    var operators = {
        'tag': [
            { value: 'has', label: 'Has Tag' },
            { value: 'not_has', label: 'Does Not Have Tag' }
        ],
        'status': [
            { value: 'equals', label: 'Equals' },
            { value: 'not_equals', label: 'Not Equals' }
        ],
        'source': [
            { value: 'equals', label: 'Equals' },
            { value: 'not_equals', label: 'Not Equals' }
        ],
        'email': [
            { value: 'equals', label: 'Equals' },
            { value: 'contains', label: 'Contains' },
            { value: 'not_contains', label: 'Does Not Contain' }
        ],
        'first_name': [
            { value: 'contains', label: 'Contains' },
            { value: 'not_contains', label: 'Does Not Contain' }
        ],
        'last_name': [
            { value: 'contains', label: 'Contains' },
            { value: 'not_contains', label: 'Does Not Contain' }
        ],
        'created_date': [
            { value: 'in_last', label: 'In Last (Days)' },
            { value: 'not_in_last', label: 'Not In Last (Days)' },
            { value: 'before', label: 'Before (YYYY-MM-DD)' },
            { value: 'after', label: 'After (YYYY-MM-DD)' }
        ],
        'total_purchase_value': [
            { value: 'greater_than', label: 'Greater Than' },
            { value: 'less_than', label: 'Less Than' },
            { value: 'equals', label: 'Equals' },
            { value: 'greater_than_equal', label: 'Greater Than or Equal' },
            { value: 'greater_than_in_last', label: 'Greater Than (In Last X Days)' },
            { value: 'less_than_in_last', label: 'Less Than (In Last X Days)' },
            { value: 'equals_in_last', label: 'Equals (In Last X Days)' },
            { value: 'greater_than_equal_in_last', label: 'Greater Than or Equal (In Last X Days)' }
        ],
        'last_purchase_value': [
            { value: 'greater_than', label: 'Greater Than' },
            { value: 'less_than', label: 'Less Than' },
            { value: 'equals', label: 'Equals' },
            { value: 'greater_than_equal', label: 'Greater Than or Equal' }
        ],
        'last_purchase_date': [
            { value: 'in_last', label: 'In Last (Days)' },
            { value: 'not_in_last', label: 'Not In Last (Days)' },
            { value: 'before', label: 'Before (YYYY-MM-DD)' },
            { value: 'after', label: 'After (YYYY-MM-DD)' }
        ],
        'first_purchase_date': [
            { value: 'in_last', label: 'In Last (Days)' },
            { value: 'not_in_last', label: 'Not In Last (Days)' },
            { value: 'before', label: 'Before (YYYY-MM-DD)' },
            { value: 'after', label: 'After (YYYY-MM-DD)' }
        ]
    };

    // Update operators when field changes
    $(document).on('change', '.condition-field', function() {
        var $row = $(this).closest('.gee-condition-row');
        var field = $(this).val();
        var $operator = $row.find('.condition-operator');
        var $valueWrapper = $row.find('.condition-value-wrapper');
        
        $operator.empty().append('<option value="">Select Operator...</option>');
        
        if (operators[field]) {
            $.each(operators[field], function(i, op) {
                $operator.append('<option value="' + op.value + '">' + op.label + '</option>');
            });
        }
        
        // Trigger operator change to update value input if field is selected
        if (field) {
            $operator.trigger('change');
        }
    });

    // Update value input when operator changes
    $(document).on('change', '.condition-operator', function() {
        var $row = $(this).closest('.gee-condition-row');
        var field = $row.find('.condition-field').val();
        var operator = $(this).val();
        var $valueWrapper = $row.find('.condition-value-wrapper');
        var $valueInput = $valueWrapper.find('.condition-value');
        var inputName = $valueInput.attr('name') || 'conditions[0][value]';
        
        if (field === 'tag') {
            $valueInput.replaceWith('<select name="' + inputName + '" class="condition-value" style="width:100%; padding:5px;"><option value="">Select Tag...</option></select>');
            var $select = $valueWrapper.find('.condition-value');
            $.each(tags, function(i, tag) {
                $select.append('<option value="' + tag.id + '">' + tag.name + '</option>');
            });
        } else if (field === 'status') {
            if ($valueInput.is('select')) {
                $valueInput.replaceWith('<input type="text" name="' + inputName + '" class="condition-value" placeholder="e.g. subscribed" style="width:100%; padding:5px;">');
            }
        } else if (field === 'source') {
            if ($valueInput.is('select')) {
                $valueInput.replaceWith('<input type="text" name="' + inputName + '" class="condition-value" placeholder="e.g. woocommerce" style="width:100%; padding:5px;">');
            }
        } else if (field === 'total_purchase_value' || field === 'last_purchase_value') {
            var placeholder = 'e.g. 100.00';
            if (operator && operator.indexOf('_in_last') !== -1) {
                placeholder = 'e.g. 100|30 (amount|days)';
            }
            if ($valueInput.is('select')) {
                if (operator && operator.indexOf('_in_last') !== -1) {
                    $valueInput.replaceWith('<input type="text" name="' + inputName + '" class="condition-value" placeholder="' + placeholder + '" style="width:100%; padding:5px;">');
                } else {
                    $valueInput.replaceWith('<input type="number" step="0.01" name="' + inputName + '" class="condition-value" placeholder="' + placeholder + '" style="width:100%; padding:5px;">');
                }
            } else {
                if (operator && operator.indexOf('_in_last') !== -1) {
                    $valueInput.attr('type', 'text').attr('placeholder', placeholder).removeAttr('step');
                } else {
                    $valueInput.attr('type', 'number').attr('step', '0.01').attr('placeholder', placeholder);
                }
            }
        } else if (field === 'last_purchase_date' || field === 'first_purchase_date' || field === 'created_date') {
            if ($valueInput.is('select')) {
                $valueInput.replaceWith('<input type="text" name="' + inputName + '" class="condition-value" placeholder="Days or YYYY-MM-DD" style="width:100%; padding:5px;">');
            } else {
                $valueInput.attr('placeholder', 'Days or YYYY-MM-DD');
            }
        } else {
            if ($valueInput.is('select')) {
                $valueInput.replaceWith('<input type="text" name="' + inputName + '" class="condition-value" placeholder="Value" style="width:100%; padding:5px;">');
            }
        }
    });

    // Initialize operators for existing conditions
    $('.condition-field').each(function() {
        if ($(this).val()) {
            var $row = $(this).closest('.gee-condition-row');
            var operatorValue = $row.find('.condition-operator').data('value');
            $(this).trigger('change');
            // Set the operator value after options are populated
            setTimeout(function() {
                if (operatorValue) {
                    $row.find('.condition-operator').val(operatorValue);
                }
            }, 100);
        }
    });

    // Add new condition
    $('#add-condition').on('click', function() {
        var html = '<div class="gee-condition-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center; padding:10px; background:#fff; border:1px solid #ddd; border-radius:4px;">' +
            '<select name="conditions[' + conditionIndex + '][field]" class="condition-field" style="flex:1; padding:5px;">' +
            '<option value="">Select Field...</option>' +
            '<option value="tag">Tag</option>' +
            '<option value="status">Status</option>' +
            '<option value="source">Source</option>' +
            '<option value="email">Email</option>' +
            '<option value="first_name">First Name</option>' +
            '<option value="last_name">Last Name</option>' +
            '<option value="created_date">Created Date</option>' +
            '<option value="total_purchase_value">Total Purchase Value</option>' +
            '<option value="last_purchase_value">Last Purchase Value</option>' +
            '<option value="last_purchase_date">Last Purchase Date</option>' +
            '<option value="first_purchase_date">First Purchase Date</option>' +
            '</select>' +
            '<select name="conditions[' + conditionIndex + '][operator]" class="condition-operator" style="flex:1; padding:5px;">' +
            '<option value="">Select Operator...</option>' +
            '</select>' +
            '<div class="condition-value-wrapper" style="flex:2;">' +
            '<input type="text" name="conditions[' + conditionIndex + '][value]" class="condition-value" placeholder="Value" style="width:100%; padding:5px;">' +
            '</div>' +
            '<button type="button" class="button remove-condition" style="background:#dc3232; color:#fff; border:none; padding:5px 10px; cursor:pointer;">Remove</button>' +
            '</div>';
        $('#gee-conditions-list').append(html);
        conditionIndex++;
    });

    // Remove condition
    $(document).on('click', '.remove-condition', function() {
        if ($('.gee-condition-row').length > 1) {
            $(this).closest('.gee-condition-row').remove();
        } else {
            alert('You must have at least one condition.');
        }
    });
});
</script>
