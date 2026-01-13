<?php
/**
 * Main view file of export section
 *
 * @link
 *
 * @package ImportExportSuite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
/**
 * Before setting from.
 *
 * Enables adding extra arguments or setting defaults for a request.
 *
 * @since 1.0.0
 */
do_action( 'wt_iew_exporter_before_head' );
?>
<style type="text/css">
.wt_iew_export_step{ display:none; }
.wt_iew_export_step_loader{ width:100%; height:400px; text-align:center; line-height:400px; font-size:14px; }
.wt_iew_export_step_main{ float:left; box-sizing:border-box; padding:15px; padding-bottom:0px; width:95%; margin:30px 0.5%; background:#fff; box-shadow:0px 2px 2px #ccc; border:solid 1px #efefef; }
.wt_iew_export_main{ padding:20px 0px; }
.wt_iew_file_ext_info_td{ vertical-align:top !important; }
.wt_iew_file_ext_info{ display:inline-block; margin-top:3px; }
.wt-something-went-wrong-wrap{position: absolute; margin-top: 150px; left:25%;color:#FFF;width:450px;height:275px;background: #FFF;padding: 25px; text-align: center;border: 1px solid #B32D2E;border-radius: 10px;box-shadow: 0px 0px 2px 2px #cdc8c8;z-index: 99998;}
</style>
<div class="wrap">
	<h2 class="wp-heading-inline">
	<?php esc_html_e( 'Import Export Suite' ); ?>
	</h2>
<?php
Wt_Iew_IE_Helper::debug_panel( $this->module_base );
?>
<?php require WT_IEW_PLUGIN_PATH . '/admin/views/save-template-popup.php'; ?>
<!--<h2 class="wt_iew_page_hd">
<?php
// _e('Export');
?>
<span class="wt_iew_post_type_name"></span></h2>-->

	<?php
	// Get the active tab from the $_GET param.
	$default_tab = null;
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab;
	?>
	<nav class="nav-tab-wrapper">        
		<a href="?page=wt_import_export_for_woo" class="nav-tab 
		<?php
		if ( null === $current_tab ) :
			?>
			nav-tab-active
			<?php
	endif;
		?>
	">Export</a>      
		<a href="?page=wt_import_export_for_woo&tab=import" class="nav-tab 
		<?php
		if ( 'import' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	endif;
		?>
	">Import</a>
		<a href="?page=wt_import_export_for_woo&tab=history" class="nav-tab 
		<?php
		if ( 'history' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	endif;
		?>
	">History</a>
		<a href="?page=wt_import_export_for_woo&tab=cron" class="nav-tab 
		<?php
		if ( 'cron' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	endif;
		?>
	">Scheduled actions</a>
		<a href="?page=wt_import_export_for_woo&tab=logs" class="nav-tab 
		<?php
		if ( 'logs' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	endif;
		?>
	"><?php esc_html_e( 'Import logs', 'import-export-suite-for-woocommerce' ); ?></a>
		<a href="?page=wt_import_export_for_woo&tab=settings" class="nav-tab 
		<?php
		if ( 'settings' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	endif;
		?>
	">Settings</a>
	</nav>


<?php
if ( $requested_rerun_id > 0 && 0 === $this->rerun_id ) {
	?>
		<div class="wt_iew_warn wt_iew_rerun_warn">
	<?php esc_html_e( 'Unable to handle Re-Run request.' ); ?>
		</div>
	<?php
}
?>

<div class="wt_iew_loader_info_box"></div>
<div class="wt_iew_overlayed_loader"></div>

<div class="wt-something-went-wrong" style="position:relative;display:none;">		
	<div class="wt-something-went-wrong-wrap">		
		<p class="wt_iew_popup_close" style="float:right;margin-top: -15px !important;margin-right: -15px !important;line-height: 0;"><a href="javascript:void(0)"><img src="<?php echo esc_url( WT_IEW_PLUGIN_URL . '/assets/images/wt-close-button.png' ); ?>"/></a></p>
		<img src="<?php echo esc_url( WT_IEW_PLUGIN_URL . '/assets/images/wt-error-icon.png' ); ?>"/>
		<h3><?php esc_html_e( 'Something went wrong' ); ?></h3>
		<p style="color:#000;text-align: left;"><?php esc_html_e( 'We are unable to complete your request.Try reducing the export batch count to 5 or less and increasing the Maximum execution time in the ' ); ?><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=wt_import_export_for_woo' ) ); ?>"><?php esc_html_e( 'General settings' ); ?></a>.</p>
		<p style="color:#000;text-align: left;"><?php esc_html_e( ' If not resolved, contact the' ); ?> <a target="_blank" href="https://www.webtoffee.com/contact/"><?php esc_html_e( 'support team' ); ?></a> <?php esc_html_e( 'with the' ); ?> <a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ); ?>"><?php esc_html_e( 'WooCommerce fatal error log' ); ?></a>, <?php esc_html_e( 'if any' ); ?>.</p>
		<br/>
		<a href="javascript:void(0)" onclick='wt_iew_export.refresh_export_page();' class="button button-primary"><?php esc_html_e( 'Try again' ); ?></a>
	</div>
</div>

<div class="wt_iew_export_step_main">
	<?php
	foreach ( $this->steps as $stepk => $stepv ) {
		?>
		<div class="wt_iew_export_step wt_iew_export_step_<?php echo esc_html( $stepk ); ?>" data-loaded="0"></div>
		<?php
	}
	?>
</div>
<script type="text/javascript">
/* external modules can hook */
function wt_iew_exporter_validate(action, action_type, is_previous_step)
{
	var is_continue=true;
	<?php
	/**
	 * Exporter form validate.
	 *
	 * Enables adding extra arguments or setting defaults for a request.
	 *
	 * @since 1.0.0
	 */
	do_action( 'wt_iew_exporter_validate' );
	?>
	return is_continue;
}
</script>
</div>
