<?php

class Gee_Woo_CRM_Campaign {

	private $table_name;
    private $log_table;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_campaigns';
        $this->log_table = $wpdb->prefix . 'gee_crm_campaign_logs';
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
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->log_table WHERE campaign_id = %d", $campaign_id ) );
    }

    public function create_campaign( $data ) {
        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            array(
                'name' => $data['name'],
                'subject' => $data['subject'],
                'content_html' => $data['content'],
                'status' => 'draft'
            )
        );
        return $wpdb->insert_id;
    }

    public function send_campaign( $id, $target_type, $target_id ) {
        global $wpdb;
        
        // 1. Get Recipients
        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
        $contact_model = new Gee_Woo_CRM_Contact();
        
        $args = array();
        if ( $target_type == 'tag' && $target_id ) {
             // Find contacts via tag pivot manually or use Segment logic? 
             // Let's do a quick query for tag
             $contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';
             $ids = $wpdb->get_col( "SELECT contact_id FROM $contact_tags_table WHERE tag_id = " . absint($target_id) );
             $args['include_ids'] = $ids ?: array(0);
        } elseif ( $target_type == 'segment' && $target_id ) {
            $segment_model = new Gee_Woo_CRM_Segment();
            $ids = $segment_model->get_contact_ids_in_segment( $target_id );
            $args['include_ids'] = $ids ?: array(0);
        }
        
        // If type is 'all', args is empty, gets all contacts
        
        $recipients = $contact_model->get_contacts( $args );
        
        if ( empty( $recipients ) ) return 0;
        
        $campaign = $this->get_campaign( $id );
        
        $count = 0;
        foreach ( $recipients as $recipient ) {
            // Send Email
            $to = $recipient->email;
            $subject = $campaign->subject;
            $body = $campaign->content_html;
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            $sent = wp_mail( $to, $subject, $body, $headers );
            
            // Log
            $wpdb->insert(
                $this->log_table,
                array(
                    'campaign_id' => $id,
                    'contact_id' => $recipient->id,
                    'email' => $to,
                    'status' => $sent ? 'sent' : 'failed'
                )
            );
            $count++;
        }
        
        // Update Campaign Status
        $wpdb->update(
            $this->table_name,
            array( 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ),
            array( 'id' => $id )
        );
        
        return $count;
    }
}
