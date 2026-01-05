<?php

class Gee_Woo_CRM_Tag {

	private $table_name;
    private $pivot_table;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_tags';
        $this->pivot_table = $wpdb->prefix . 'gee_crm_contact_tags';
	}

	public function get_tags() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM $this->table_name ORDER BY name ASC" );
	}

    public function create_tag( $name ) {
        global $wpdb;
        $slug = sanitize_title( $name );
        
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $this->table_name WHERE slug = %s", $slug ) );
        if ( $exists ) {
            return new WP_Error( 'exists', 'Tag already exists.' );
        }

        $wpdb->insert(
            $this->table_name,
            array(
                'name' => sanitize_text_field( $name ),
                'slug' => $slug
            )
        );
        return $wpdb->insert_id;
    }

    public function delete_tag( $id ) {
        global $wpdb;
        $wpdb->delete( $this->pivot_table, array( 'tag_id' => $id ) ); // Delete associations first
        return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
    }

    public function assign_tag( $contact_id, $tag_id ) {
        global $wpdb;
        $wpdb->insert(
            $this->pivot_table,
            array(
                'contact_id' => $contact_id,
                'tag_id'     => $tag_id
            ),
            array( '%d', '%d' )
        );
    }
}
