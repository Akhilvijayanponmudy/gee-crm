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
		
		// Check if unsubscribe_token column exists
		if ( ! in_array( 'unsubscribe_token', $columns ) ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD COLUMN unsubscribe_token varchar(64) NULL AFTER consent_date" );
			// Generate tokens for existing contacts
			$this->generate_tokens_for_existing_contacts();
		}
	}
	
	/**
	 * Generate secure unsubscribe token
	 */
	private function generate_unsubscribe_token( $email, $contact_id = 0 ) {
		// Create a unique, secure token using email, contact ID, and a secret salt
		$secret = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'gee-crm-secret-salt-' . get_option( 'siteurl' );
		$data = $email . '|' . $contact_id . '|' . $secret;
		return hash( 'sha256', $data );
	}
	
	/**
	 * Generate tokens for existing contacts that don't have one
	 */
	private function generate_tokens_for_existing_contacts() {
		global $wpdb;
		$contacts = $wpdb->get_results( "SELECT id, email FROM $this->table_name WHERE unsubscribe_token IS NULL OR unsubscribe_token = ''" );
		foreach ( $contacts as $contact ) {
			$token = $this->generate_unsubscribe_token( $contact->email, $contact->id );
			$wpdb->update(
				$this->table_name,
				array( 'unsubscribe_token' => $token ),
				array( 'id' => $contact->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}
	
	/**
	 * Get or generate unsubscribe token for a contact
	 */
	public function get_unsubscribe_token( $contact_id ) {
		global $wpdb;
		$contact = $wpdb->get_row( $wpdb->prepare( "SELECT id, email, unsubscribe_token FROM $this->table_name WHERE id = %d", $contact_id ) );
		
		if ( ! $contact ) {
			return false;
		}
		
		// Generate token if it doesn't exist
		if ( empty( $contact->unsubscribe_token ) ) {
			$token = $this->generate_unsubscribe_token( $contact->email, $contact->id );
			$wpdb->update(
				$this->table_name,
				array( 'unsubscribe_token' => $token ),
				array( 'id' => $contact_id ),
				array( '%s' ),
				array( '%d' )
			);
			return $token;
		}
		
		return $contact->unsubscribe_token;
	}
	
	/**
	 * Verify unsubscribe token
	 */
	public function verify_unsubscribe_token( $email, $token ) {
		global $wpdb;
		$contact = $wpdb->get_row( $wpdb->prepare( "SELECT id, email, unsubscribe_token FROM $this->table_name WHERE email = %s", $email ) );
		
		if ( ! $contact ) {
			return false;
		}
		
		// If no token exists, generate one (shouldn't happen, but handle it)
		if ( empty( $contact->unsubscribe_token ) ) {
			$new_token = $this->generate_unsubscribe_token( $contact->email, $contact->id );
			$wpdb->update(
				$this->table_name,
				array( 'unsubscribe_token' => $new_token ),
				array( 'id' => $contact->id ),
				array( '%s' ),
				array( '%d' )
			);
			return hash_equals( $new_token, $token );
		}
		
		// Use hash_equals for timing-safe comparison
		return hash_equals( $contact->unsubscribe_token, $token );
	}

	public function create_or_update( $data ) {
		global $wpdb;
		
		$exists = $wpdb->get_row( $wpdb->prepare( "SELECT id, wp_user_id, unsubscribe_token FROM $this->table_name WHERE email = %s", $data['email'] ) );

		if ( $exists ) {
			$update_data = array();
			
			// Only update first_name if provided and not empty
			if ( isset( $data['first_name'] ) && ! empty( trim( $data['first_name'] ) ) ) {
				$update_data['first_name'] = sanitize_text_field( $data['first_name'] );
			}
			
			// Only update last_name if provided and not empty
			if ( isset( $data['last_name'] ) && ! empty( trim( $data['last_name'] ) ) ) {
				$update_data['last_name'] = sanitize_text_field( $data['last_name'] );
			}
			
			// Only update phone if provided and not empty
			if ( isset( $data['phone'] ) && ! empty( trim( $data['phone'] ) ) ) {
				$update_data['phone'] = sanitize_text_field( $data['phone'] );
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
			}
			
			// Update marketing consent if provided
			if ( isset( $data['marketing_consent'] ) ) {
				$update_data['marketing_consent'] = $data['marketing_consent'] ? 1 : 0;
				if ( $data['marketing_consent'] ) {
					$update_data['consent_date'] = current_time( 'mysql' );
				} else {
					$update_data['consent_date'] = null;
				}
			}
			
			// Only update if there's data to update
			if ( ! empty( $update_data ) ) {
				// Build format array to match update_data keys in the same order
				// $wpdb->update() expects format array as simple array (not associative) matching data order
				$format = array();
				foreach ( $update_data as $key => $value ) {
					if ( $key === 'wp_user_id' ) {
						$format[] = '%d';
					} elseif ( $key === 'marketing_consent' ) {
						$format[] = '%d';
					} elseif ( $key === 'consent_date' ) {
						$format[] = '%s'; // NULL is handled as string in WordPress
					} else {
						$format[] = '%s';
					}
				}
				
				$wpdb->update(
					$this->table_name,
					$update_data,
					array( 'id' => $exists->id ),
					$format,
					array( '%d' )
				);
			}
			// Ensure token exists for existing contact
			if ( empty( $exists->unsubscribe_token ) ) {
				$token = $this->generate_unsubscribe_token( $data['email'], $exists->id );
				$wpdb->update(
					$this->table_name,
					array( 'unsubscribe_token' => $token ),
					array( 'id' => $exists->id ),
					array( '%s' ),
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
			
			$contact_id = $wpdb->insert_id;
			
			// Generate unsubscribe token for new contact
			$token = $this->generate_unsubscribe_token( $data['email'], $contact_id );
			$wpdb->update(
				$this->table_name,
				array( 'unsubscribe_token' => $token ),
				array( 'id' => $contact_id ),
				array( '%s' ),
				array( '%d' )
			);
			
			return $contact_id;
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
