<?php

class Gee_Woo_CRM_Admin {

	public function run() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'Gee Woo CRM',
			'Gee Woo CRM',
			'manage_options',
			'gee-woo-crm',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-email-alt',
			6
		);
	}

	public function enqueue_styles( $hook ) {
		if ( 'toplevel_page_gee-woo-crm' !== $hook ) {
			return;
		}
		
		wp_enqueue_style( 'gee-woo-crm-admin', GEE_WOO_CRM_URL . 'assets/css/admin.css', array(), GEE_WOO_CRM_VERSION, 'all' );
	}

	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_gee-woo-crm' !== $hook ) {
			return;
		}
		
		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'dashboard';
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		
		// Enqueue WordPress editor scripts for email templates
		if ( $view === 'email-templates' && ( $action === 'edit' || empty( $action ) ) ) {
			// Enable rich editing
			add_filter( 'user_can_richedit', '__return_true' );
			
			// Enqueue editor scripts
			wp_enqueue_editor();
			wp_enqueue_media();
			
			// Enqueue TinyMCE scripts
			wp_tinymce_inline_scripts();
		}
		
		wp_enqueue_script( 'gee-woo-crm-admin', GEE_WOO_CRM_URL . 'assets/js/admin.js', array( 'jquery' ), GEE_WOO_CRM_VERSION, true );
		
		wp_localize_script( 'gee-woo-crm-admin', 'geeWooCRM', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gee_woo_crm_nonce' ),
		));
	}

	public function display_plugin_admin_page() {
		$page = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'dashboard';
		?>
		<div class="gee-woo-crm-wrapper">
			<div class="gee-woo-crm-content">
				<?php $this->render_view( $page ); ?>
			</div>
		</div>
		<?php
	}

	private function render_view( $view ) {
		$file = GEE_WOO_CRM_PATH . 'includes/views/' . $view . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		} else {
			echo '<p>View not found.</p>';
		}
	}
}

