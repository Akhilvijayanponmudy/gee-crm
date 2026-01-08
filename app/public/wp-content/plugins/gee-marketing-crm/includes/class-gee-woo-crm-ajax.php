<?php

class Gee_Woo_CRM_Ajax {

	public function init() {
        add_action( 'wp_ajax_gee_crm_sync_contacts', array( $this, 'sync_contacts' ) );
        add_action( 'wp_ajax_gee_crm_create_tag', array( $this, 'create_tag' ) );
        add_action( 'wp_ajax_gee_crm_update_tag', array( $this, 'update_tag' ) );
        add_action( 'wp_ajax_gee_crm_delete_tag', array( $this, 'delete_tag' ) );
        add_action( 'wp_ajax_gee_crm_assign_tag', array( $this, 'assign_tag' ) );
        add_action( 'wp_ajax_gee_crm_remove_tag', array( $this, 'remove_tag' ) );
        add_action( 'wp_ajax_gee_get_template', array( $this, 'get_template' ) );
        add_action( 'wp_ajax_gee_update_marketing_consent', array( $this, 'update_marketing_consent' ) );
        add_action( 'wp_ajax_gee_import_contacts', array( $this, 'import_contacts' ) );
        add_action( 'wp_ajax_gee_crm_send_test_email', array( $this, 'send_test_email' ) );
        add_action( 'wp_ajax_gee_crm_preview_segment', array( $this, 'preview_segment' ) );
	}

	public function sync_contacts() {
		check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
		$contact_model = new Gee_Woo_CRM_Contact();
		
		$count = 0;

		// 1. Sync Registered Users (Customers)
		$users = get_users( array( 'role__in' => array( 'customer', 'subscriber' ) ) );
		foreach ( $users as $user ) {
			$data = array(
				'email'      => $user->user_email,
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
				'phone'      => get_user_meta( $user->ID, 'billing_phone', true ),
				'source'     => 'woocommerce',
				'wp_user_id' => $user->ID,
			);
			$contact_model->create_or_update( $data );
			$count++;
		}

		// 2. Sync Guest Orders (if Woo is active)
		if ( class_exists( 'WooCommerce' ) ) {
			// This is a heavy query for large stores, limiting to last 100 for this "minimal" version to avoid timeouts
			$orders = wc_get_orders( array( 'limit' => 100, 'type' => 'shop_order' ) );
			foreach ( $orders as $order ) {
				$email = $order->get_billing_email();
				if ( ! $email ) continue;

				$data = array(
					'email'      => $email,
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'phone'      => $order->get_billing_phone(),
					'source'     => 'woocommerce_guest',
					// 'wp_user_id' => null, // default
				);
				$contact_model->create_or_update( $data );
				$count++; // Note: this increments even if update, which is fine for "processed" count
			}
		}

		wp_send_json_success( array( 'message' => "Synced $count contacts successfully!" ) );
	}

    public function create_tag() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        if ( ! $name ) wp_send_json_error( 'Name required' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
        $model = new Gee_Woo_CRM_Tag();
        $result = $model->create_tag( $name );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array( 'message' => 'Tag created' ) );
    }

    public function update_tag() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';

        if ( ! $id || ! $name ) wp_send_json_error( 'ID and name required' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
        $model = new Gee_Woo_CRM_Tag();
        $result = $model->update_tag( $id, $name );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array( 'message' => 'Tag updated' ) );
    }

    public function delete_tag() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) wp_send_json_error( 'ID required' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
        $model = new Gee_Woo_CRM_Tag();
        $model->delete_tag( $id );

        wp_send_json_success( array( 'message' => 'Tag deleted' ) );
    }

    public function assign_tag() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $contact_id = isset( $_POST['contact_id'] ) ? absint( $_POST['contact_id'] ) : 0;
        $tag_id = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;

        if ( ! $contact_id || ! $tag_id ) wp_send_json_error( 'Missing Params' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
        $model = new Gee_Woo_CRM_Tag();
        $result = $model->assign_tag( $contact_id, $tag_id );

        if ( $result === false ) {
            wp_send_json_error( 'Tag is already assigned to this contact' );
        }

        wp_send_json_success( array( 'message' => 'Tag assigned' ) );
    }

    public function remove_tag() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $contact_id = isset( $_POST['contact_id'] ) ? absint( $_POST['contact_id'] ) : 0;
        $tag_id = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;

        if ( ! $contact_id || ! $tag_id ) wp_send_json_error( 'Missing Params' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-tag.php';
        $model = new Gee_Woo_CRM_Tag();
        $model->remove_tag( $contact_id, $tag_id );

        wp_send_json_success( array( 'message' => 'Tag removed' ) );
    }

    public function get_template() {
        check_ajax_referer( 'gee_get_template', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
        if ( ! $template_id ) wp_send_json_error( 'Template ID required' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';
        $template_model = new Gee_Woo_CRM_Email_Template();
        $template = $template_model->get_template( $template_id );

        if ( ! $template ) {
            wp_send_json_error( 'Template not found' );
        }

        wp_send_json_success( array(
            'subject' => $template->subject,
            'content_html' => $template->content_html
        ) );
    }

    public function update_marketing_consent() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $contact_id = isset( $_POST['contact_id'] ) ? absint( $_POST['contact_id'] ) : 0;
        $consent = isset( $_POST['consent'] ) && $_POST['consent'] == '1' ? true : false;

        if ( ! $contact_id ) wp_send_json_error( 'Contact ID required' );

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
        $contact_model = new Gee_Woo_CRM_Contact();
        $result = $contact_model->update_marketing_consent( $contact_id, $consent );

        if ( $result === false ) {
            wp_send_json_error( 'Failed to update marketing consent' );
        }

        wp_send_json_success( array( 
            'message' => $consent ? 'Marketing consent granted' : 'Marketing consent revoked',
            'consent_date' => $consent ? current_time( 'mysql' ) : null
        ) );
    }

    public function import_contacts() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( 'No file uploaded or upload error' );
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $skip_duplicates = isset( $_POST['skip_duplicates'] ) && $_POST['skip_duplicates'] == '1';
        $marketing_consent_default = isset( $_POST['marketing_consent_default'] ) && $_POST['marketing_consent_default'] == '1';

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-contact.php';
        $contact_model = new Gee_Woo_CRM_Contact();

        $handle = fopen( $file, 'r' );
        if ( $handle === false ) {
            wp_send_json_error( 'Could not open CSV file' );
        }

        // Read header row
        $headers = fgetcsv( $handle );
        if ( ! $headers || ! in_array( 'email', $headers ) ) {
            fclose( $handle );
            wp_send_json_error( 'CSV must have an "email" column' );
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( count( $row ) !== count( $headers ) ) {
                continue; // Skip malformed rows
            }

            $data = array_combine( $headers, $row );
            
            $email = sanitize_email( $data['email'] );
            if ( empty( $email ) || ! is_email( $email ) ) {
                $skipped++;
                continue;
            }

            // Check if contact exists
            global $wpdb;
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gee_crm_contacts WHERE email = %s", $email ) );

            if ( $exists && ! $skip_duplicates ) {
                $skipped++;
                continue;
            }

            $contact_data = array(
                'email' => $email,
                'first_name' => isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
                'last_name' => isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
                'phone' => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
                'source' => 'csv_import',
            );

            // Handle marketing consent
            if ( isset( $data['marketing_consent'] ) ) {
                $contact_data['marketing_consent'] = ( $data['marketing_consent'] == '1' || strtolower( $data['marketing_consent'] ) == 'yes' );
            } elseif ( $marketing_consent_default ) {
                $contact_data['marketing_consent'] = true;
            }

            try {
                $contact_id = $contact_model->create_or_update( $contact_data );
                if ( $exists ) {
                    $updated++;
                } else {
                    $imported++;
                }
            } catch ( Exception $e ) {
                $errors[] = $email . ': ' . $e->getMessage();
            }
        }

        fclose( $handle );

        $message = sprintf( 
            'Import completed! %d imported, %d updated, %d skipped.',
            $imported,
            $updated,
            $skipped
        );

        if ( ! empty( $errors ) ) {
            $message .= ' ' . count( $errors ) . ' errors occurred.';
        }

        wp_send_json_success( array( 
            'message' => $message,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        ) );
    }

    public function send_test_email() {
        check_ajax_referer( 'gee_crm_test_email', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $test_email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        if ( empty( $test_email ) || ! is_email( $test_email ) ) {
            wp_send_json_error( 'Invalid email address' );
        }

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-email-template.php';
        $template_model = new Gee_Woo_CRM_Email_Template();
        
        // Get default marketing template for test
        $template = $template_model->get_default_template();
        if ( ! $template ) {
            wp_send_json_error( 'No email template found' );
        }

        // Replace variables with test data
        $subject = str_replace( 
            array( '{first_name}', '{full_name}', '{site_name}', '{current_date}' ),
            array( 'Test', 'Test User', get_bloginfo( 'name' ), date( 'F j, Y' ) ),
            $template->subject
        );

        $content = str_replace(
            array( '{first_name}', '{full_name}', '{email}', '{site_name}', '{site_url}', '{current_date}', '{unsubscribe_link}' ),
            array( 
                'Test', 
                'Test User', 
                $test_email,
                get_bloginfo( 'name' ), 
                home_url(), 
                date( 'F j, Y' ),
                home_url( '/wp-json/gee-crm/v1/unsubscribe?email=' . urlencode( $test_email ) . '&token=test_token' )
            ),
            $template->content_html
        );

        // Send test email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $sent = wp_mail( $test_email, $subject, $content, $headers );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => 'Test email sent successfully!' ) );
        } else {
            wp_send_json_error( 'Failed to send email. Please check your WordPress mail configuration.' );
        }
    }

    public function preview_segment() {
        check_ajax_referer( 'gee_woo_crm_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-segment.php';
        $segment_model = new Gee_Woo_CRM_Segment();

        $logic = isset( $_POST['logic'] ) ? sanitize_text_field( wp_unslash( $_POST['logic'] ) ) : 'AND';
        $conditions = isset( $_POST['conditions'] ) ? json_decode( stripslashes( $_POST['conditions'] ), true ) : array();

        if ( empty( $conditions ) || ! is_array( $conditions ) ) {
            wp_send_json_success( array( 'count' => 0 ) );
        }

        // Sanitize conditions
        $sanitized_conditions = array();
        foreach ( $conditions as $condition ) {
            if ( ! empty( $condition['field'] ) && ! empty( $condition['operator'] ) ) {
                $sanitized_conditions[] = array(
                    'field' => sanitize_text_field( $condition['field'] ),
                    'operator' => sanitize_text_field( $condition['operator'] ),
                    'value' => isset( $condition['value'] ) ? sanitize_text_field( $condition['value'] ) : ''
                );
            }
        }

        if ( empty( $sanitized_conditions ) ) {
            wp_send_json_success( array( 'count' => 0 ) );
        }

        // Create a temporary segment rules object
        $rules = array(
            'logic' => $logic,
            'conditions' => $sanitized_conditions
        );

        // Create a temporary segment, get count, then delete it
        $temp_id = $segment_model->create_segment( 'temp_preview_' . time(), $rules );
        if ( $temp_id ) {
            $contact_ids = $segment_model->get_contact_ids_in_segment( $temp_id );
            $count = count( $contact_ids );
            $segment_model->delete_segment( $temp_id );
            wp_send_json_success( array( 'count' => $count ) );
        } else {
            wp_send_json_success( array( 'count' => 0 ) );
        }
    }
}
