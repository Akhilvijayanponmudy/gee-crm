<?php

class Gee_Woo_CRM_Segment {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_segments';
	}

	public function get_segments() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY name ASC" );
	}
    
    public function get_segment( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) );
    }

    public function create_segment( $name, $rules ) {
        global $wpdb;
        $slug = sanitize_title( $name );
        
        $wpdb->insert(
            $this->table_name,
            array(
                'name' => sanitize_text_field( $name ),
                'slug' => $slug,
                'rules_json' => json_encode( $rules ),
                'created_at' => current_time( 'mysql' )
            )
        );
        return $wpdb->insert_id;
    }

    public function update_segment( $id, $name, $rules ) {
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'name' => sanitize_text_field( $name ),
                'rules_json' => json_encode( $rules )
            ),
            array( 'id' => absint( $id ) )
        );
    }
    
    public function delete_segment( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
    }

    /**
     * Get the count of contacts in a segment
     */
    public function get_segment_count( $segment_id ) {
        $cache_key = 'gee_crm_segment_count_' . $segment_id;
        $count = get_transient( $cache_key );
        
        if ( false === $count ) {
            $ids = $this->get_contact_ids_in_segment( $segment_id );
            $count = count( $ids );
            set_transient( $cache_key, $count, 10 * MINUTE_IN_SECONDS );
        }
        
        return $count;
    }

    /**
     * Get IDs of contacts that match the segment rules
     */
    public function get_contact_ids_in_segment( $segment_id ) {
        $segment = $this->get_segment( $segment_id );
        if ( ! $segment || ! $segment->rules_json ) return array();

        $rules = json_decode( $segment->rules_json, true );
        return $this->get_matching_contact_ids( $rules );
    }

    /**
     * Core logic to find matching contact IDs based on rules
     */
    public function get_matching_contact_ids( $rules ) {
        global $wpdb;
        
        $contacts_table = $wpdb->prefix . 'gee_crm_contacts';
        $contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';
        
        // Initial set of IDs from CRM contacts table
        $all_candidates = $wpdb->get_results( "SELECT id, email, wp_user_id, created_at FROM $contacts_table", ARRAY_A );
        
        $matched_ids = array();

        foreach ( $all_candidates as $contact ) {
            if ( $this->evaluate_rules( $contact, $rules ) ) {
                $matched_ids[] = $contact['id'];
            }
        }

        return $matched_ids;
    }

    /**
     * Evaluate all rules for a single contact
     */
    public function evaluate_rules( $contact, $rules ) {
        $tags_pass = $this->evaluate_tags( $contact['id'], $rules['tags'] ?? array() );
        if ( ! $tags_pass ) return false;

        $conditions_pass = $this->evaluate_conditions( $contact, $rules['conditions'] ?? array() );
        return $conditions_pass;
    }

    /**
     * Evaluate tag rules
     */
    private function evaluate_tags( $contact_id, $tag_rules ) {
        global $wpdb;
        $pivot = $wpdb->prefix . 'gee_crm_contact_tags';
        
        $include = isset($tag_rules['include']) ? array_map('absint', (array)$tag_rules['include']) : array();
        $exclude = isset($tag_rules['exclude']) ? array_map('absint', (array)$tag_rules['exclude']) : array();
        $mode = strtoupper($tag_rules['mode'] ?? 'ANY');

        // Check if no tag rules are defined
        if ( empty($include) && empty($exclude) ) return true;

        // Get actual tags for this contact
        $current_tags = $wpdb->get_col( $wpdb->prepare( "SELECT tag_id FROM $pivot WHERE contact_id = %d", $contact_id ) );
        $current_tags = array_map('absint', $current_tags);

        // 1. Check EXCLUDE (If ANY excluded tag is present, fail)
        if ( ! empty($exclude) ) {
            if ( count( array_intersect( $current_tags, $exclude ) ) > 0 ) return false;
        }

        // 2. Check INCLUDE
        if ( ! empty($include) ) {
            $intersection = array_intersect( $current_tags, $include );
            if ( $mode === 'ALL' ) {
                return count($intersection) === count($include);
            } else {
                return count($intersection) > 0;
            }
        }

        return true;
    }

    /**
     * Evaluate dynamic conditions
     */
    private function evaluate_conditions( $contact, $conditions_rule ) {
        $relation = strtoupper($conditions_rule['relation'] ?? 'AND');
        $items = $conditions_rule['items'] ?? array();

        if ( empty($items) ) return true;

        $results = array();
        foreach ( $items as $item ) {
            $results[] = $this->match_single_condition( $contact, $item );
        }

        if ( $relation === 'OR' ) {
            return in_array( true, $results, true );
        } else {
            return ! in_array( false, $results, true );
        }
    }

    /**
     * Match a single behavioral condition
     */
    private function match_single_condition( $contact, $item ) {
        $type = $item['type'] ?? '';
        $op = $item['operator'] ?? '>';
        $val = $item['value'] ?? 0;
        $days = isset($item['days']) ? intval($item['days']) : 0;
        $within_days = isset($item['within_days']) ? intval($item['within_days']) : 0;
        $date_col = $item['date_column'] ?? 'last_order_date';

        // Get Stats (cached per request)
        $stats = $this->get_contact_wc_stats( $contact );

        switch ( $type ) {
            case 'purchased_within_days':
                if ( ! $stats['last_order_date'] ) return false;
                $diff = ( time() - strtotime($stats['last_order_date']) ) / 86400;
                return $diff <= $days;

            case 'last_order_before_days':
                if ( ! $stats['last_order_date'] ) return false;
                $diff = ( time() - strtotime($stats['last_order_date']) ) / 86400;
                return $diff > $days;

            case 'total_spent_greater_than':
                $spent = $within_days > 0 ? $this->get_spent_in_range($contact, $within_days) : $stats['total_spent'];
                return $this->compare($spent, $op, $val);

            case 'order_count_greater_than':
                $count = $within_days > 0 ? $this->get_orders_in_range($contact, $within_days) : $stats['order_count'];
                return $this->compare($count, $op, $val);

            case 'email_contains':
                return stripos( $contact['email'], $val ) !== false;

            default:
                return false;
        }
    }

    private function compare($a, $op, $b) {
        $a = floatval($a);
        $b = floatval($b);
        switch($op) {
            case '>': return $a > $b;
            case '>=': return $a >= $b;
            case '<': return $a < $b;
            case '<=': return $a <= $b;
            case '==': return $a == $b;
        }
        return false;
    }

    /**
     * Helper to get WooCommerce stats for a contact
     */
    private function get_contact_wc_stats( $contact ) {
        static $stats_cache = array();
        $email = $contact['email'];
        if ( isset($stats_cache[$email]) ) return $stats_cache[$email];

        global $wpdb;
        $stats = array(
            'total_spent' => 0,
            'order_count' => 0,
            'last_order_date' => null
        );

        if ( $contact['wp_user_id'] ) {
            // Logged in user
            $customer = new WC_Customer( $contact['wp_user_id'] );
            $stats['total_spent'] = $customer->get_total_spent();
            $stats['order_count'] = $customer->get_order_count();
            
            // Get last order date
            $last_order = wc_get_orders( array( 'customer' => $contact['wp_user_id'], 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC' ) );
            if ( $last_order ) {
                $stats['last_order_date'] = $last_order[0]->get_date_created()->date('Y-m-d H:i:s');
            }
        } else {
            // Guest - use billing email
            $orders = wc_get_orders( array( 'billing_email' => $email, 'limit' => -1 ) );
            foreach ( $orders as $o ) {
                $stats['total_spent'] += $o->get_total();
                $stats['order_count']++;
                if ( ! $stats['last_order_date'] || $o->get_date_created()->getTimestamp() > strtotime($stats['last_order_date']) ) {
                    $stats['last_order_date'] = $o->get_date_created()->date('Y-m-d H:i:s');
                }
            }
        }

        $stats_cache[$email] = $stats;
        return $stats;
    }

    private function get_spent_in_range( $contact, $days ) {
        // Implementation for spend in date range
        $orders = $this->get_orders_in_date_range($contact, $days);
        $total = 0;
        foreach($orders as $o) $total += $o->get_total();
        return $total;
    }

    private function get_orders_in_range( $contact, $days ) {
        return count($this->get_orders_in_date_range($contact, $days));
    }

    private function get_orders_in_date_range($contact, $days) {
        $date_limit = date('Y-m-d', strtotime("-$days days"));
        $args = array(
            'limit' => -1,
            'date_created' => '>=' . $date_limit,
        );
        if ($contact['wp_user_id']) $args['customer'] = $contact['wp_user_id'];
        else $args['billing_email'] = $contact['email'];

        return wc_get_orders($args);
    }

    public function get_contact_segments( $contact_id ) {
        $segments = $this->get_segments();
        $contact_segments = array();
        
        // Load contact data
        global $wpdb;
        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gee_crm_contacts WHERE id = %d", $contact_id ), ARRAY_A );
        if (!$contact) return array();

        foreach ( $segments as $segment ) {
            $rules = json_decode( $segment->rules_json, true );
            if ( $this->evaluate_rules( $contact, $rules ) ) {
                $contact_segments[] = $segment;
            }
        }
        return $contact_segments;
    }
}
