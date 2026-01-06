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
                'rules_json' => json_encode( $rules )
            )
        );
        return $wpdb->insert_id;
    }

    public function update_segment( $id, $name, $rules ) {
        global $wpdb;
        $slug = sanitize_title( $name );
        
        return $wpdb->update(
            $this->table_name,
            array(
                'name' => sanitize_text_field( $name ),
                'slug' => $slug,
                'rules_json' => json_encode( $rules )
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );
    }
    
    public function delete_segment( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
    }

    /**
     * Dynamic membership calculation
     * 
     * Segments are calculated dynamically based on conditions - they automatically
     * include/exclude contacts based on current data. This means:
     * - New contacts are automatically included if they match conditions
     * - Contacts are automatically excluded if they no longer match conditions
     * - No manual updates needed - segments are always up-to-date
     * 
     * @param int $segment_id Segment ID
     * @return array Array of contact IDs that match the segment conditions
     */
    public function get_contact_ids_in_segment( $segment_id ) {
        global $wpdb;
        $segment = $this->get_segment( $segment_id );
        if ( ! $segment || ! $segment->rules_json ) return array();

        $rules = json_decode( $segment->rules_json, true );
        if ( ! $rules || ! isset( $rules['conditions'] ) || empty( $rules['conditions'] ) ) {
            return array();
        }

        $contacts_table = $wpdb->prefix . 'gee_crm_contacts';
        $contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';
        $logic = isset( $rules['logic'] ) ? strtoupper( $rules['logic'] ) : 'AND';

        $where_clauses = array();
        $join_clauses = array();
        $params = array();
        $tag_join_index = 0;

        foreach ( $rules['conditions'] as $index => $condition ) {
            if ( ! isset( $condition['field'] ) || ! isset( $condition['operator'] ) ) {
                continue;
            }

            $field = sanitize_text_field( $condition['field'] );
            $operator = sanitize_text_field( $condition['operator'] );
            $value = isset( $condition['value'] ) ? $condition['value'] : '';

            $clause = $this->build_condition_clause( $field, $operator, $value, $contacts_table, $contact_tags_table, $tag_join_index );
            if ( $clause && ! empty( $clause['where'] ) ) {
                $where_clauses[] = $clause['where'];
                if ( ! empty( $clause['join'] ) ) {
                    $join_clauses[] = $clause['join'];
                    if ( $field === 'tag' && $operator === 'has' ) {
                        $tag_join_index++;
                    }
                }
                if ( ! empty( $clause['params'] ) ) {
                    $params = array_merge( $params, $clause['params'] );
                }
            }
        }

        if ( empty( $where_clauses ) ) {
            return array();
        }

        $join_sql = ! empty( $join_clauses ) ? implode( ' ', array_unique( $join_clauses ) ) : '';
        $where_sql = '(' . implode( ' ' . $logic . ' ', $where_clauses ) . ')';

        $sql = "SELECT DISTINCT c.id FROM $contacts_table c $join_sql WHERE $where_sql";

        if ( ! empty( $params ) ) {
            $prepared = $wpdb->prepare( $sql, $params );
            return $wpdb->get_col( $prepared );
        }

        return $wpdb->get_col( $sql );
    }

    /**
     * Check if a specific contact matches a segment's conditions
     * 
     * This is useful for checking individual contact membership.
     * Segments are dynamic, so this always reflects current data.
     * 
     * @param int $contact_id Contact ID
     * @param int $segment_id Segment ID
     * @return bool True if contact matches segment conditions, false otherwise
     */
    public function contact_matches_segment( $contact_id, $segment_id ) {
        $contact_ids = $this->get_contact_ids_in_segment( $segment_id );
        return in_array( $contact_id, $contact_ids );
    }

    private function build_condition_clause( $field, $operator, $value, $contacts_table, $contact_tags_table, $join_index = 0 ) {
        global $wpdb;

        $clause = array( 'where' => '', 'join' => '', 'params' => array() );

        switch ( $field ) {
            case 'tag':
                $tag_id = absint( $value );
                if ( $operator === 'has' ) {
                    $alias = $join_index > 0 ? "ct{$join_index}" : 'ct';
                    $clause['join'] = "INNER JOIN $contact_tags_table {$alias} ON {$alias}.contact_id = c.id";
                    $clause['where'] = "{$alias}.tag_id = %d";
                    $clause['params'][] = $tag_id;
                } elseif ( $operator === 'not_has' ) {
                    $clause['where'] = "c.id NOT IN (SELECT contact_id FROM $contact_tags_table WHERE tag_id = %d)";
                    $clause['params'][] = $tag_id;
                }
                break;

            case 'status':
                $status = sanitize_text_field( $value );
                if ( $operator === 'equals' ) {
                    $clause['where'] = "c.status = %s";
                    $clause['params'][] = $status;
                } elseif ( $operator === 'not_equals' ) {
                    $clause['where'] = "c.status != %s";
                    $clause['params'][] = $status;
                }
                break;

            case 'source':
                $source = sanitize_text_field( $value );
                if ( $operator === 'equals' ) {
                    $clause['where'] = "c.source = %s";
                    $clause['params'][] = $source;
                } elseif ( $operator === 'not_equals' ) {
                    $clause['where'] = "c.source != %s";
                    $clause['params'][] = $source;
                }
                break;

            case 'email':
                $email_value = '%' . $wpdb->esc_like( $value ) . '%';
                if ( $operator === 'contains' ) {
                    $clause['where'] = "c.email LIKE %s";
                    $clause['params'][] = $email_value;
                } elseif ( $operator === 'not_contains' ) {
                    $clause['where'] = "c.email NOT LIKE %s";
                    $clause['params'][] = $email_value;
                } elseif ( $operator === 'equals' ) {
                    $clause['where'] = "c.email = %s";
                    $clause['params'][] = $value;
                }
                break;

            case 'first_name':
            case 'last_name':
                $name_value = '%' . $wpdb->esc_like( $value ) . '%';
                if ( $operator === 'contains' ) {
                    $clause['where'] = "c.$field LIKE %s";
                    $clause['params'][] = $name_value;
                } elseif ( $operator === 'not_contains' ) {
                    $clause['where'] = "c.$field NOT LIKE %s";
                    $clause['params'][] = $name_value;
                }
                break;

            case 'created_date':
                if ( $operator === 'in_last' ) {
                    $days = absint( $value );
                    $clause['where'] = "c.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)";
                    $clause['params'][] = $days;
                } elseif ( $operator === 'not_in_last' ) {
                    $days = absint( $value );
                    $clause['where'] = "c.created_at < DATE_SUB(NOW(), INTERVAL %d DAY)";
                    $clause['params'][] = $days;
                } elseif ( $operator === 'before' ) {
                    $clause['where'] = "c.created_at < %s";
                    $clause['params'][] = $value;
                } elseif ( $operator === 'after' ) {
                    $clause['where'] = "c.created_at > %s";
                    $clause['params'][] = $value;
                }
                break;

            case 'total_purchase_value':
                if ( ! class_exists( 'WooCommerce' ) ) {
                    // If WooCommerce is not active, no contacts match
                    $clause['where'] = "1 = 0";
                    break;
                }
                
                $amount = floatval( $value );
                $posts_table = $wpdb->posts;
                $postmeta_table = $wpdb->postmeta;
                
                if ( $operator === 'greater_than' ) {
                    $clause['where'] = "(
                        SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(10,2))), 0)
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) > %f";
                    $clause['params'][] = $amount;
                } elseif ( $operator === 'less_than' ) {
                    $clause['where'] = "(
                        SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(10,2))), 0)
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) < %f";
                    $clause['params'][] = $amount;
                } elseif ( $operator === 'equals' ) {
                    $clause['where'] = "ABS((
                        SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(10,2))), 0)
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) - %f) < 0.01";
                    $clause['params'][] = $amount;
                } elseif ( $operator === 'greater_than_equal' ) {
                    $clause['where'] = "(
                        SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(10,2))), 0)
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) >= %f";
                    $clause['params'][] = $amount;
                } elseif ( in_array( $operator, array( 'greater_than_in_last', 'less_than_in_last', 'equals_in_last', 'greater_than_equal_in_last' ) ) ) {
                    // Value format: "amount|days" e.g., "100|30" means 100 in last 30 days
                    $value_parts = explode( '|', $value );
                    $amount = isset( $value_parts[0] ) ? floatval( $value_parts[0] ) : 0;
                    $days = isset( $value_parts[1] ) ? absint( $value_parts[1] ) : 30;
                    
                    $comparison = '';
                    if ( $operator === 'greater_than_in_last' ) {
                        $comparison = '>';
                    } elseif ( $operator === 'less_than_in_last' ) {
                        $comparison = '<';
                    } elseif ( $operator === 'equals_in_last' ) {
                        // Special handling for equals
                        $clause['where'] = "ABS((
                            SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(10,2))), 0)
                            FROM $posts_table p
                            INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                            WHERE p.post_type = 'shop_order'
                            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                            AND pm.meta_key = '_order_total'
                            AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
                            AND (
                                (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                                OR (c.wp_user_id IS NULL AND EXISTS (
                                    SELECT 1 FROM $postmeta_table pm2 
                                    WHERE pm2.post_id = p.ID 
                                    AND pm2.meta_key = '_billing_email' 
                                    AND pm2.meta_value = c.email
                                ))
                            )
                        ) - %f) < 0.01";
                        $clause['params'][] = $days;
                        $clause['params'][] = $amount;
                        break;
                    } elseif ( $operator === 'greater_than_equal_in_last' ) {
                        $comparison = '>=';
                    }
                    
                    $clause['where'] = "(
                        SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(10,2))), 0)
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) $comparison %f";
                    $clause['params'][] = $days;
                    $clause['params'][] = $amount;
                }
                break;

            case 'last_purchase_value':
                if ( ! class_exists( 'WooCommerce' ) ) {
                    $clause['where'] = "1 = 0";
                    break;
                }
                
                $amount = floatval( $value );
                $posts_table = $wpdb->posts;
                $postmeta_table = $wpdb->postmeta;
                
                if ( $operator === 'greater_than' ) {
                    $clause['where'] = "(
                        SELECT CAST(pm.meta_value AS DECIMAL(10,2))
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                        ORDER BY p.post_date DESC
                        LIMIT 1
                    ) > %f";
                    $clause['params'][] = $amount;
                } elseif ( $operator === 'less_than' ) {
                    $clause['where'] = "(
                        SELECT CAST(pm.meta_value AS DECIMAL(10,2))
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                        ORDER BY p.post_date DESC
                        LIMIT 1
                    ) < %f";
                    $clause['params'][] = $amount;
                } elseif ( $operator === 'equals' ) {
                    $clause['where'] = "ABS((
                        SELECT CAST(pm.meta_value AS DECIMAL(10,2))
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                        ORDER BY p.post_date DESC
                        LIMIT 1
                    ) - %f) < 0.01";
                    $clause['params'][] = $amount;
                } elseif ( $operator === 'greater_than_equal' ) {
                    $clause['where'] = "(
                        SELECT CAST(pm.meta_value AS DECIMAL(10,2))
                        FROM $posts_table p
                        INNER JOIN $postmeta_table pm ON p.ID = pm.post_id
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND pm.meta_key = '_order_total'
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                        ORDER BY p.post_date DESC
                        LIMIT 1
                    ) >= %f";
                    $clause['params'][] = $amount;
                }
                break;

            case 'last_purchase_date':
            case 'first_purchase_date':
                if ( ! class_exists( 'WooCommerce' ) ) {
                    $clause['where'] = "1 = 0";
                    break;
                }
                
                $posts_table = $wpdb->posts;
                $postmeta_table = $wpdb->postmeta;
                $date_field = $field === 'last_purchase_date' ? 'MAX' : 'MIN';
                
                if ( $operator === 'in_last' ) {
                    $days = absint( $value );
                    $clause['where'] = "(
                        SELECT $date_field(p.post_date)
                        FROM $posts_table p
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) >= DATE_SUB(NOW(), INTERVAL %d DAY)";
                    $clause['params'][] = $days;
                } elseif ( $operator === 'not_in_last' ) {
                    $days = absint( $value );
                    $clause['where'] = "(
                        SELECT $date_field(p.post_date)
                        FROM $posts_table p
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) < DATE_SUB(NOW(), INTERVAL %d DAY)";
                    $clause['params'][] = $days;
                } elseif ( $operator === 'before' ) {
                    $clause['where'] = "(
                        SELECT $date_field(p.post_date)
                        FROM $posts_table p
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) < %s";
                    $clause['params'][] = $value;
                } elseif ( $operator === 'after' ) {
                    $clause['where'] = "(
                        SELECT $date_field(p.post_date)
                        FROM $posts_table p
                        WHERE p.post_type = 'shop_order'
                        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                        AND (
                            (c.wp_user_id > 0 AND p.post_author = c.wp_user_id)
                            OR (c.wp_user_id IS NULL AND EXISTS (
                                SELECT 1 FROM $postmeta_table pm2 
                                WHERE pm2.post_id = p.ID 
                                AND pm2.meta_key = '_billing_email' 
                                AND pm2.meta_value = c.email
                            ))
                        )
                    ) > %s";
                    $clause['params'][] = $value;
                }
                break;
        }

        return $clause;
    }
}
