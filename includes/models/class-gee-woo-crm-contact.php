<?php

class Gee_Woo_CRM_Contact {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'gee_crm_contacts';
		
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
		
		// Check if marketing_consent column exists
		if ( ! in_array( 'marketing_consent', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN marketing_consent tinyint(1) DEFAULT 0 AFTER status" );
		}
		
		// Check if consent_date column exists
		if ( ! in_array( 'consent_date', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN consent_date datetime NULL AFTER marketing_consent" );
		}
	}

	public function create_or_update( $data ) {
		global $wpdb;
		
		$exists = $wpdb->get_row( $wpdb->prepare( "SELECT id, wp_user_id FROM $this->table_name WHERE email = %s", $data['email'] ) );

		if ( $exists ) {
			$update_data = array();
			$format = array();
			
			// Only update first_name if provided and not empty
			if ( isset( $data['first_name'] ) && ! empty( trim( $data['first_name'] ) ) ) {
				$update_data['first_name'] = sanitize_text_field( $data['first_name'] );
				$format[] = '%s';
			}
			
			// Only update last_name if provided and not empty
			if ( isset( $data['last_name'] ) && ! empty( trim( $data['last_name'] ) ) ) {
				$update_data['last_name'] = sanitize_text_field( $data['last_name'] );
				$format[] = '%s';
			}
			
			// Only update phone if provided and not empty
			if ( isset( $data['phone'] ) && ! empty( trim( $data['phone'] ) ) ) {
				$update_data['phone'] = sanitize_text_field( $data['phone'] );
				$format[] = '%s';
			}
			
			// Handle wp_user_id - try to find WordPress user by email if not provided
			$wp_user_id = null;
			if ( isset( $data['wp_user_id'] ) && $data['wp_user_id'] ) {
				$wp_user_id = absint( $data['wp_user_id'] );
			} elseif ( empty( $exists->wp_user_id ) ) {
				// Try to find WordPress user by email
				$wp_user = get_user_by( 'email', $data['email'] );
				if ( $wp_user ) {
					$wp_user_id = $wp_user->ID;
				}
			} else {
				// Keep existing wp_user_id
				$wp_user_id = $exists->wp_user_id;
			}
			
			if ( $wp_user_id ) {
				$update_data['wp_user_id'] = $wp_user_id;
				$format[] = '%d';
			}
			
			// Update marketing consent if provided
			if ( isset( $data['marketing_consent'] ) ) {
				$update_data['marketing_consent'] = $data['marketing_consent'] ? 1 : 0;
				$format[] = '%d';
				if ( $data['marketing_consent'] ) {
					$update_data['consent_date'] = current_time( 'mysql' );
					$format[] = '%s';
				} else {
					$update_data['consent_date'] = null;
					$format[] = '%s';
				}
			}
			
			// Only update if there's data to update
			if ( ! empty( $update_data ) ) {
				$wpdb->update(
					$this->table_name,
					$update_data,
					array( 'id' => $exists->id ),
					$format,
					array( '%d' )
				);
			}
			return $exists->id;
		} else {
			$marketing_consent = isset( $data['marketing_consent'] ) && $data['marketing_consent'] ? 1 : 0;
			$consent_date = $marketing_consent ? current_time( 'mysql' ) : null;
			
			$insert_data = array(
				'email'            => $data['email'],
				'first_name'       => isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
				'last_name'        => isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
				'phone'            => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
				'status'           => 'subscribed',
				'marketing_consent' => $marketing_consent,
				'consent_date'     => $consent_date,
				'source'           => isset( $data['source'] ) ? $data['source'] : 'manual',
				'created_at'       => current_time( 'mysql' ),
			);
			
			$format = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' );
			
			// Try to find WordPress user by email if wp_user_id not provided
			$wp_user_id = null;
			if ( isset( $data['wp_user_id'] ) && $data['wp_user_id'] ) {
				$wp_user_id = absint( $data['wp_user_id'] );
			} else {
				// Try to find WordPress user by email
				$wp_user = get_user_by( 'email', $data['email'] );
				if ( $wp_user ) {
					$wp_user_id = $wp_user->ID;
				}
			}
			
			if ( $wp_user_id ) {
				$insert_data['wp_user_id'] = $wp_user_id;
				$format[] = '%d';
			}
			
			$result = $wpdb->insert(
				$this->table_name,
				$insert_data,
				$format
			);
			
			if ( $result === false ) {
				return false;
			}
			
			return $wpdb->insert_id;
		}
	}
	
	/**
	 * Update marketing consent for a contact
	 */
	public function update_marketing_consent( $contact_id, $consent ) {
		global $wpdb;
		
		// Check if consent is being granted (changed from false to true)
		$contact = $wpdb->get_row( $wpdb->prepare( "SELECT marketing_consent, email, first_name, last_name FROM $this->table_name WHERE id = %d", $contact_id ) );
		$was_consented = ! empty( $contact->marketing_consent );
		$is_granting_consent = $consent && ! $was_consented;
		
		$data = array(
			'marketing_consent' => $consent ? 1 : 0
		);
		
		if ( $consent ) {
			$data['consent_date'] = current_time( 'mysql' );
		} else {
			$data['consent_date'] = null;
		}
		
		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $contact_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
		
		// Send thank you email if consent was just granted
		if ( $result !== false && $is_granting_consent ) {
			require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-settings.php';
			$settings = new Gee_Woo_CRM_Settings();
			if ( $settings->get_setting( 'thank_you_email_enabled', 0 ) ) {
				if ( function_exists( 'gee_woo_crm_send_thank_you_email' ) ) {
					gee_woo_crm_send_thank_you_email( $contact_id, $contact->email, $contact->first_name, $contact->last_name, $settings );
				}
			}
		}
		
		return $result;
	}

	public function get_contact( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $id ) );
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
		
		// Filter by marketing consent if specified
		if ( isset( $args['marketing_consent'] ) ) {
			$consent = $args['marketing_consent'] ? 1 : 0;
			$query .= " AND marketing_consent = $consent";
		}

		return $query;
	}
}
