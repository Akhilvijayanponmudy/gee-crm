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
}
