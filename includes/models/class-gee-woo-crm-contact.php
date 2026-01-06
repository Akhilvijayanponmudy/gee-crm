<?php

class Gee_Woo_CRM_Contact {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_contacts';
	}

	public function create_or_update( $data ) {
		global $wpdb;
		
		$exists = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $this->table_name WHERE email = %s", $data['email'] ) );

		if ( $exists ) {
			$wpdb->update(
				$this->table_name,
				array(
					'first_name' => $data['first_name'],
					'last_name'  => $data['last_name'],
					'phone'      => $data['phone'],
					// Don't overwrite source if it exists, or update if user wants? Let's keep source for now.
					// 'source'     => $data['source'], 
					'wp_user_id' => isset($data['wp_user_id']) ? $data['wp_user_id'] : $exists->wp_user_id,
				),
				array( 'id' => $exists->id )
			);
			return $exists->id;
		} else {
			$wpdb->insert(
				$this->table_name,
				array(
					'email'      => $data['email'],
					'first_name' => $data['first_name'],
					'last_name'  => $data['last_name'],
					'phone'      => $data['phone'],
					'status'     => 'subscribed',
					'source'     => $data['source'],
					'wp_user_id' => isset($data['wp_user_id']) ? $data['wp_user_id'] : null,
					'created_at' => current_time( 'mysql' ),
				)
			);
			return $wpdb->insert_id;
		}
	}

	public function get_contacts( $args = array() ) {
		global $wpdb;
		
		$query = "SELECT * FROM $this->table_name WHERE 1=1";
		$query = $this->build_where_clause( $query, $args );

		// Pagination
		$per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 20;
		$page = isset( $args['page'] ) ? absint( $args['page'] ) : 1;
		$offset = ( $page - 1 ) * $per_page;

		$query .= " ORDER BY created_at DESC LIMIT $offset, $per_page";

		return $wpdb->get_results( $query );
	}
	
	public function get_count( $args = array() ) {
		global $wpdb;
		
		$query = "SELECT COUNT(*) FROM $this->table_name WHERE 1=1";
		$query = $this->build_where_clause( $query, $args );
		
		return (int) $wpdb->get_var( $query );
	}

	private function build_where_clause( $query, $args ) {
		global $wpdb;
		
		if ( ! empty( $args['search'] ) ) {
			$search = esc_sql( $wpdb->esc_like( $args['search'] ) );
			$query .= " AND (email LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%')";
		}

		if ( isset( $args['include_ids'] ) ) {
			$ids = implode( ',', array_map( 'absint', $args['include_ids'] ) );
			if ( empty( $ids ) ) $ids = '0';
			$query .= " AND id IN ($ids)";
		}

		if ( isset( $args['tag_id'] ) && $args['tag_id'] > 0 ) {
			$tag_id = absint( $args['tag_id'] );
			$contact_tags_table = $wpdb->prefix . 'gee_crm_contact_tags';
			$query .= " AND id IN (SELECT contact_id FROM $contact_tags_table WHERE tag_id = $tag_id)";
		}

		return $query;
	}
}
