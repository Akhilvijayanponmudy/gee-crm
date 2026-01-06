<?php

class Gee_Woo_CRM_Campaign {

	private $table_name;
    private $log_table;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_campaigns';
        $this->log_table = $wpdb->prefix . 'gee_crm_campaign_logs';
        
        // Ensure table structure is up to date
        $this->maybe_update_table();
	}
	
	/**
	 * Update table structure if needed (for existing installations)
	 */
	private function maybe_update_table() {
		global $wpdb;
		
		// Check if table exists first
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" );
		if ( ! $table_exists ) {
			return; // Table doesn't exist, activation will create it
		}
		
		// Get all existing columns
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM $this->table_name" );
		
		// Check if targeting_json column exists
		if ( ! in_array( 'targeting_json', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN targeting_json text AFTER content_html" );
		}
		
		// Check if template_id column exists
		if ( ! in_array( 'template_id', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN template_id mediumint(9) NULL AFTER content_html" );
		}
		
		// Check if scheduled_at column exists
		if ( ! in_array( 'scheduled_at', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN scheduled_at datetime NULL AFTER status" );
		}
		
		// Check if total_recipients column exists
		if ( ! in_array( 'total_recipients', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN total_recipients int(11) DEFAULT 0 AFTER scheduled_at" );
		}
		
		// Check if total_sent column exists
		if ( ! in_array( 'total_sent', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN total_sent int(11) DEFAULT 0 AFTER total_recipients" );
		}
		
		// Check if total_failed column exists
		if ( ! in_array( 'total_failed', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN total_failed int(11) DEFAULT 0 AFTER total_sent" );
		}
	}

    public function get_campaigns() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY created_at DESC" );
    }

    public function get_campaign( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) );
    }
    
    public function get_logs( $campaign_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->log_table WHERE campaign_id = %d ORDER BY sent_at DESC", $campaign_id ) );
    }

    public function get_campaign_stats( $campaign_id ) {
        global $wpdb;
        
        $stats = array(
            'total' => 0,
            'sent' => 0,
            'failed' => 0,
            'opened' => 0,
            'clicked' => 0
        );
        
        $results = $wpdb->get_results( $wpdb->prepare( 
            "SELECT status, COUNT(*) as count FROM $this->log_table WHERE campaign_id = %d GROUP BY status",
            $campaign_id
        ) );
        
        foreach ( $results as $result ) {
            $stats['total'] += intval( $result->count );
            if ( $result->status == 'sent' ) {
                $stats['sent'] = intval( $result->count );
            } elseif ( $result->status == 'failed' ) {
                $stats['failed'] = intval( $result->count );
            }
        }
        
        return $stats;
    }

    public function create_campaign( $data ) {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" );
        if ( ! $table_exists ) {
            error_log( 'Campaign table does not exist: ' . $this->table_name );
            return false;
        }
        
        // Validate required fields
        if ( empty( $data['name'] ) || empty( $data['subject'] ) || empty( $data['content_html'] ) ) {
            error_log( 'Campaign creation failed: Missing required fields' );
            return false;
        }
        
        $targeting = isset( $data['targeting'] ) ? json_encode( $data['targeting'] ) : '[]';
        $status = isset( $data['status'] ) ? $data['status'] : 'draft';
        $scheduled_at = isset( $data['scheduled_at'] ) && ! empty( $data['scheduled_at'] ) ? $data['scheduled_at'] : null;
        $template_id = isset( $data['template_id'] ) && $data['template_id'] > 0 ? absint( $data['template_id'] ) : null;
        
        $insert_data = array(
            'name' => sanitize_text_field( $data['name'] ),
            'subject' => sanitize_text_field( $data['subject'] ),
            'content_html' => wp_kses_post( $data['content_html'] ),
            'targeting_json' => $targeting,
            'status' => $status,
            'total_recipients' => isset( $data['total_recipients'] ) ? absint( $data['total_recipients'] ) : 0
        );
        
        $format = array( '%s', '%s', '%s', '%s', '%s', '%d' );
        
        // Add optional fields
        if ( $template_id !== null ) {
            $insert_data['template_id'] = $template_id;
            $format[] = '%d';
        }
        
        if ( $scheduled_at !== null ) {
            $insert_data['scheduled_at'] = $scheduled_at;
            $format[] = '%s';
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            $format
        );
        
        if ( $result === false ) {
            // Log error for debugging
            error_log( 'Campaign creation failed: ' . $wpdb->last_error );
            return false;
        }
        
        return $wpdb->insert_id;
    }

    public function update_campaign( $id, $data ) {
        global $wpdb;
        
        $update_data = array();
        $format = array();
        
        if ( isset( $data['name'] ) ) {
            $update_data['name'] = sanitize_text_field( $data['name'] );
            $format[] = '%s';
        }
        if ( isset( $data['subject'] ) ) {
            $update_data['subject'] = sanitize_text_field( $data['subject'] );
            $format[] = '%s';
        }
        if ( isset( $data['content_html'] ) ) {
            $update_data['content_html'] = wp_kses_post( $data['content_html'] );
            $format[] = '%s';
        }
        if ( isset( $data['template_id'] ) ) {
            $update_data['template_id'] = absint( $data['template_id'] );
            $format[] = '%d';
        }
        if ( isset( $data['targeting'] ) ) {
            $update_data['targeting_json'] = json_encode( $data['targeting'] );
            $format[] = '%s';
        }
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }
        if ( isset( $data['scheduled_at'] ) ) {
            $update_data['scheduled_at'] = ! empty( $data['scheduled_at'] ) ? $data['scheduled_at'] : null;
            $format[] = '%s';
        }
        
        if ( empty( $update_data ) ) {
            return false;
        }
        
        return $wpdb->update(
            $this->table_name,
            $update_data,
            array( 'id' => $id ),
            $format,
            array( '%d' )
        );
    }

    /**
     * Get recipients based on targeting configuration
     */
    public function get_recipients( $targeting ) {
        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
        
        $contact_model = new Gee_Woo_CRM_Contact();
        $segment_model = new Gee_Woo_CRM_Segment();
        $tag_model = new Gee_Woo_CRM_Tag();
        
        if ( ! is_array( $targeting ) ) {
            $targeting = json_decode( $targeting, true );
        }
        
        if ( empty( $targeting ) || ( isset( $targeting['type'] ) && $targeting['type'] == 'all' ) ) {
            // All contacts - but only those with marketing consent
            return $contact_model->get_contacts( array( 'marketing_consent' => true ) );
        }
        
        $contact_ids = array();
        
        // Handle tags
        if ( isset( $targeting['tags'] ) && is_array( $targeting['tags'] ) && ! empty( $targeting['tags'] ) ) {
            global $wpdb;
            $contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';
            $tag_ids = array_map( 'absint', $targeting['tags'] );
            $tag_ids_str = implode( ',', $tag_ids );
            
            if ( isset( $targeting['tag_operator'] ) && $targeting['tag_operator'] == 'any' ) {
                // Contact has ANY of the selected tags
                $ids = $wpdb->get_col( "SELECT DISTINCT contact_id FROM $contact_tags_table WHERE tag_id IN ($tag_ids_str)" );
            } else {
                // Contact has ALL of the selected tags
                $ids = $wpdb->get_col( $wpdb->prepare( 
                    "SELECT contact_id FROM $contact_tags_table WHERE tag_id IN ($tag_ids_str) GROUP BY contact_id HAVING COUNT(DISTINCT tag_id) = %d",
                    count( $tag_ids )
                ) );
            }
            $contact_ids = array_merge( $contact_ids, $ids );
        }
        
        // Handle segments
        if ( isset( $targeting['segments'] ) && is_array( $targeting['segments'] ) && ! empty( $targeting['segments'] ) ) {
            foreach ( $targeting['segments'] as $segment_id ) {
                $ids = $segment_model->get_contact_ids_in_segment( absint( $segment_id ) );
                $contact_ids = array_merge( $contact_ids, $ids );
            }
        }
        
        // Remove duplicates
        $contact_ids = array_unique( $contact_ids );
        
        if ( empty( $contact_ids ) ) {
            return array();
        }
        
        // Get contacts - only those with marketing consent
        $contacts = $contact_model->get_contacts( array( 'include_ids' => $contact_ids ) );
        
        // Filter to only include contacts with marketing consent
        $consented_contacts = array();
        foreach ( $contacts as $contact ) {
            if ( ! empty( $contact->marketing_consent ) ) {
                $consented_contacts[] = $contact;
            }
        }
        
        return $consented_contacts;
    }

    /**
     * Replace variables in email content with contact data
     */
    private function replace_variables( $content, $contact ) {
        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
        $contact_model = new Gee_Woo_CRM_Contact();
        
        // Get WooCommerce data if available
        $total_spent = '$0.00';
        $order_count = '0';
        $last_purchase_date = 'N/A';
        $last_purchase_value = '$0.00';
        
        if ( class_exists( 'WooCommerce' ) && $contact->wp_user_id ) {
            $customer = new WC_Customer( $contact->wp_user_id );
            $total_spent = wc_price( $customer->get_total_spent() );
            $order_count = $customer->get_order_count();
            
            $orders = wc_get_orders( array(
                'customer_id' => $contact->wp_user_id,
                'limit' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ) );
            
            if ( ! empty( $orders ) ) {
                $last_order = $orders[0];
                $last_purchase_date = $last_order->get_date_created()->date_i18n( 'F j, Y' );
                $last_purchase_value = wc_price( $last_order->get_total() );
            }
        }
        
        $replacements = array(
            '{first_name}' => $contact->first_name ?: 'there',
            '{last_name}' => $contact->last_name ?: '',
            '{full_name}' => trim( ( $contact->first_name ?: '' ) . ' ' . ( $contact->last_name ?: '' ) ) ?: 'there',
            '{email}' => $contact->email,
            '{phone}' => $contact->phone ?: 'N/A',
            '{status}' => ucfirst( $contact->status ?: 'subscribed' ),
            '{source}' => ucfirst( $contact->source ?: 'manual' ),
            '{created_date}' => $contact->created_at ? date( 'F j, Y', strtotime( $contact->created_at ) ) : 'N/A',
            '{total_spent}' => $total_spent,
            '{order_count}' => $order_count,
            '{last_purchase_date}' => $last_purchase_date,
            '{last_purchase_value}' => $last_purchase_value,
            '{site_name}' => get_bloginfo( 'name' ),
            '{site_url}' => home_url(),
            '{current_date}' => date( 'F j, Y' ),
            '{unsubscribe_link}' => home_url( '/unsubscribe?email=' . urlencode( $contact->email ) ),
        );
        
        return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
    }

    public function send_campaign( $id, $send_immediately = true ) {
        global $wpdb;
        
        $campaign = $this->get_campaign( $id );
        if ( ! $campaign ) {
            return false;
        }
        
        // Get recipients
        $recipients = $this->get_recipients( $campaign->targeting_json );
        
        if ( empty( $recipients ) ) {
            return 0;
        }
        
        $total_recipients = count( $recipients );
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ( $recipients as $recipient ) {
            // Replace variables in subject and content
            $subject = $this->replace_variables( $campaign->subject, $recipient );
            $body = $this->replace_variables( $campaign->content_html, $recipient );
            
            // Send Email
            $to = $recipient->email;
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            
            $sent = wp_mail( $to, $subject, $body, $headers );
            
            // Log
            $wpdb->insert(
                $this->log_table,
                array(
                    'campaign_id' => $id,
                    'contact_id' => $recipient->id,
                    'email' => $to,
                    'status' => $sent ? 'sent' : 'failed'
                ),
                array( '%d', '%d', '%s', '%s' )
            );
            
            if ( $sent ) {
                $sent_count++;
            } else {
                $failed_count++;
            }
        }
        
        // Update Campaign Status
        $wpdb->update(
            $this->table_name,
            array( 
                'status' => 'sent',
                'sent_at' => current_time( 'mysql' ),
                'total_recipients' => $total_recipients,
                'total_sent' => $sent_count,
                'total_failed' => $failed_count
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%d', '%d', '%d' ),
            array( '%d' )
        );
        
        return $sent_count;
    }

    public function delete_campaign( $id ) {
        global $wpdb;
        
        // Delete logs first
        $wpdb->delete( $this->log_table, array( 'campaign_id' => $id ), array( '%d' ) );
        
        // Delete campaign
        return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}
