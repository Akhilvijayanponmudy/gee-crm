<?php

class Gee_Woo_CRM_Admin {

	public function run() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'mce_buttons', array( $this, 'remove_image_buttons' ), 10, 2 );
		add_filter( 'mce_external_plugins', array( $this, 'disable_image_plugins' ), 10, 1 );
		add_filter( 'tiny_mce_before_init', array( $this, 'configure_tinymce_for_email' ), 10, 2 );
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
		
		// Check if we're on campaigns or email-templates page (need editor)
		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'dashboard';
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		
		// Enqueue WordPress editor scripts for campaigns and email-templates pages
		if ( ( $view === 'campaigns' && ( $action === 'new' || $action === 'edit' ) ) || 
		     ( $view === 'email-templates' && ( $action === 'edit' || $action === 'use' || empty( $action ) ) ) ) {
			// Enable rich editing
			add_filter( 'user_can_richedit', '__return_true' );
			
			// Enqueue editor scripts
			wp_enqueue_editor();
			wp_enqueue_media();
			
			// Enqueue TinyMCE scripts
			wp_tinymce_inline_scripts();
		}
		
		// Enqueue Chart.js only for dashboard if needed, or globally for the SPA feel
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );
		wp_enqueue_script( 'gee-woo-crm-admin', GEE_WOO_CRM_URL . 'assets/js/admin.js', array( 'jquery', 'chart-js' ), GEE_WOO_CRM_VERSION, true );
		
		wp_localize_script( 'gee-woo-crm-admin', 'geeWooCRM', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gee_woo_crm_nonce' ),
		));
	}

	public function display_plugin_admin_page() {
		$page = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'dashboard';
		?>
		<div class="gee-woo-crm-wrapper">
			<!-- Top Navigation Bar -->
			<div class="gee-woo-crm-top-nav">
				<div class="gee-woo-crm-nav-container">
					<div class="gee-woo-crm-logo">
						<span class="dashicons dashicons-email-alt"></span>
						<span class="gee-woo-crm-logo-text">Gee Woo CRM</span>
					</div>
					<nav class="gee-woo-crm-nav">
						<a href="?page=gee-woo-crm&view=dashboard" class="gee-woo-crm-nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
							<span class="dashicons dashicons-dashboard"></span> Dashboard
						</a>
						<a href="?page=gee-woo-crm&view=contacts" class="gee-woo-crm-nav-item <?php echo $page === 'contacts' ? 'active' : ''; ?>">
							<span class="dashicons dashicons-groups"></span> Contacts
						</a>
						<a href="?page=gee-woo-crm&view=campaigns" class="gee-woo-crm-nav-item <?php echo $page === 'campaigns' ? 'active' : ''; ?>">
							<span class="dashicons dashicons-megaphone"></span> Campaigns
						</a>
						<a href="?page=gee-woo-crm&view=analytics" class="gee-woo-crm-nav-item <?php echo $page === 'analytics' ? 'active' : ''; ?>">
							<span class="dashicons dashicons-chart-bar"></span> Analytics
						</a>
						<a href="?page=gee-woo-crm&view=automation" class="gee-woo-crm-nav-item <?php echo $page === 'automation' ? 'active' : ''; ?>">
							<span class="dashicons dashicons-update"></span> Automation
						</a>
						<a href="?page=gee-woo-crm&view=email-templates" class="gee-woo-crm-nav-item <?php echo $page === 'email-templates' ? 'active' : ''; ?>">
							<span class="dashicons dashicons-email"></span> Templates
						</a>
						<div class="gee-woo-crm-nav-dropdown">
							<a href="#" class="gee-woo-crm-nav-item">
								<span class="dashicons dashicons-admin-generic"></span> More <span class="dashicons dashicons-arrow-down-alt2" style="font-size:12px; margin-left:4px;"></span>
							</a>
							<div class="gee-woo-crm-dropdown-menu">
								<a href="?page=gee-woo-crm&view=tags" class="gee-woo-crm-dropdown-item <?php echo $page === 'tags' ? 'active' : ''; ?>">
									<span class="dashicons dashicons-tag"></span> Tags
								</a>
								<a href="?page=gee-woo-crm&view=segments" class="gee-woo-crm-dropdown-item <?php echo $page === 'segments' ? 'active' : ''; ?>">
									<span class="dashicons dashicons-filter"></span> Segments
								</a>
								<a href="?page=gee-woo-crm&view=mail" class="gee-woo-crm-dropdown-item <?php echo $page === 'mail' ? 'active' : ''; ?>">
									<span class="dashicons dashicons-email-alt2"></span> Mail
								</a>
								<a href="?page=gee-woo-crm&view=settings" class="gee-woo-crm-dropdown-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
									<span class="dashicons dashicons-admin-settings"></span> Settings
								</a>
							</div>
						</div>
					</nav>
				</div>
			</div>

			<!-- Main Content Area -->
			<div class="gee-woo-crm-main">
				<div class="gee-woo-crm-header">
					<div class="gee-woo-crm-header-left">
						<h1><?php 
							$page_titles = array(
								'dashboard' => 'Dashboard',
								'contacts' => 'Contacts',
								'tags' => 'Tags',
								'segments' => 'Segments',
								'campaigns' => 'Campaigns',
								'analytics' => 'Analytics',
								'automation' => 'Automation',
								'email-templates' => 'Email Templates',
								'mail' => 'Mail',
								'settings' => 'Settings'
							);
							echo isset( $page_titles[$page] ) ? $page_titles[$page] : ucfirst( $page ); 
						?></h1>
						<span class="dashicons dashicons-info" style="color:#666; margin-left:8px; cursor:help;" title="View your CRM data and analytics"></span>
					</div>
			
				</div>
				<div class="gee-woo-crm-content">
					<?php $this->render_view( $page ); ?>
				</div>
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

	/**
	 * Remove image/media buttons from TinyMCE toolbar
	 */
	public function remove_image_buttons( $buttons, $editor_id ) {
		// Only apply to our email editors
		if ( strpos( $editor_id, 'content-visual' ) !== false ) {
			// Remove image button if present
			$buttons = array_diff( $buttons, array( 'image', 'wp_img_edit' ) );
		}
		return $buttons;
	}

	/**
	 * Disable image-related plugins
	 */
	public function disable_image_plugins( $plugins ) {
		// Remove image-related plugins
		unset( $plugins['image'] );
		unset( $plugins['wp_img_edit'] );
		return $plugins;
	}

	/**
	 * Configure TinyMCE specifically for email editing
	 */
	public function configure_tinymce_for_email( $init, $editor_id ) {
		// Only apply to our email editors
		if ( strpos( $editor_id, 'content-visual' ) !== false ) {
			// Completely disable image insertion
			$init['invalid_elements'] = 'img,iframe,object,embed';
			$init['extended_valid_elements'] = '';
			
			// Remove image from context menu
			$init['contextmenu'] = 'link | copy cut paste';
			
			// Disable drag and drop and image pasting
			$init['paste_data_images'] = false;
			$init['paste_as_text'] = false;
			$init['paste_remove_spans'] = false;
			$init['paste_remove_styles'] = false;
			
			// Ensure media buttons are disabled
			$init['media_buttons'] = false;
			
			// Block image insertion via various methods
			$init['file_picker_types'] = '';
			$init['file_picker_callback'] = '';
			
			// Remove image button from toolbar if somehow added
			if ( isset( $init['toolbar1'] ) ) {
				$init['toolbar1'] = str_replace( array( 'image', 'wp_img_edit', ',' ), array( '', '', '' ), $init['toolbar1'] );
			}
		}
		return $init;
	}
}
