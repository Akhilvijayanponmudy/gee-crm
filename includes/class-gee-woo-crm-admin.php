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
}
