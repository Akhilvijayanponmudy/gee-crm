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

    public function update_tag( $id, $name ) {
        global $wpdb;
        $slug = sanitize_title( $name );
        
        // Check if slug already exists for a different tag
        $exists = $wpdb->get_var( $wpdb->prepare( 
            "SELECT id FROM $this->table_name WHERE slug = %s AND id != %d", 
            $slug, 
            $id 
        ) );
        if ( $exists ) {
            return new WP_Error( 'exists', 'A tag with this name already exists.' );
        }

        return $wpdb->update(
            $this->table_name,
            array(
                'name' => sanitize_text_field( $name ),
                'slug' => $slug
            ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    public function get_tag( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) );
    }

    public function delete_tag( $id ) {
        global $wpdb;
        $wpdb->delete( $this->pivot_table, array( 'tag_id' => $id ) ); // Delete associations first
        return $wpdb->delete( $this->table_name, array( 'id' => $id ) );
    }

    public function assign_tag( $contact_id, $tag_id ) {
        global $wpdb;
        
        // Check if tag is already assigned to prevent duplicate key errors
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT contact_id FROM $this->pivot_table WHERE contact_id = %d AND tag_id = %d",
            $contact_id,
            $tag_id
        ) );
        
        if ( $exists ) {
            return false; // Already assigned
        }
        
        $wpdb->insert(
            $this->pivot_table,
            array(
                'contact_id' => $contact_id,
                'tag_id'     => $tag_id
            ),
            array( '%d', '%d' )
        );
        
        return $wpdb->insert_id;
    }

    public function remove_tag( $contact_id, $tag_id ) {
        global $wpdb;
        return $wpdb->delete(
            $this->pivot_table,
            array(
                'contact_id' => $contact_id,
                'tag_id'     => $tag_id
            ),
            array( '%d', '%d' )
        );
    }

    /**
     * Get the number of contacts assigned to a tag
     * 
     * @param int $tag_id Tag ID
     * @return int Number of contacts with this tag
     */
    public function get_contact_count( $tag_id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is derived from $wpdb->prefix, not user input.
        $count = $wpdb->get_var( $wpdb->prepare( 
            "SELECT COUNT(DISTINCT contact_id) FROM {$this->pivot_table} WHERE tag_id = %d",
            $tag_id
        ) );
        return absint( $count );
    }
}
