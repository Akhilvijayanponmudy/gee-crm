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
			<div class="gee-woo-crm-sidebar">
				<div class="gee-woo-crm-logo">
					<h2>Gee Woo CRM</h2>
				</div>
				<ul class="gee-woo-crm-nav">
					<li class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=dashboard"><span class="dashicons dashicons-dashboard"></span> Dashboard</a>
					</li>
					<li class="<?php echo $page === 'contacts' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=contacts"><span class="dashicons dashicons-groups"></span> Contacts</a>
					</li>
					<li class="<?php echo $page === 'tags' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=tags"><span class="dashicons dashicons-tag"></span> Tags</a>
					</li>
					<li class="<?php echo $page === 'segments' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=segments"><span class="dashicons dashicons-filter"></span> Segments</a>
					</li>
					<li class="<?php echo $page === 'campaigns' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=campaigns"><span class="dashicons dashicons-megaphone"></span> Campaigns</a>
					</li>
					<li class="<?php echo $page === 'email-templates' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=email-templates"><span class="dashicons dashicons-email"></span> Email Templates</a>
					</li>
					<li class="<?php echo $page === 'settings' ? 'active' : ''; ?>">
						<a href="?page=gee-woo-crm&view=settings"><span class="dashicons dashicons-admin-settings"></span> Settings</a>
					</li>
				</ul>
			</div>

			<div class="gee-woo-crm-main">
				<div class="gee-woo-crm-header">
					<h1><?php echo ucfirst( $page ); ?></h1>
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
