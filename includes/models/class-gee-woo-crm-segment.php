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
    
    public function delete_segment( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
    }

    // Dynamic membership calculation
    public function get_contact_ids_in_segment( $segment_id ) {
        global $wpdb;
        $segment = $this->get_segment( $segment_id );
        if ( ! $segment || ! $segment->rules_json ) return array();

        $rules = json_decode( $segment->rules_json, true );
        $contacts_table = $wpdb->prefix . 'gee_crm_contacts';
        $contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';

        // Basic implementation: Only supports "has_tag" for now
        if ( isset( $rules['has_tag'] ) && $rules['has_tag'] ) {
            $tag_id = absint( $rules['has_tag'] );
            $sql = "SELECT c.id FROM $contacts_table c 
                    INNER JOIN $contact_tags_table ct ON ct.contact_id = c.id 
                    WHERE ct.tag_id = $tag_id";
            return $wpdb->get_col( $sql );
        }

        return array();
    }
}
