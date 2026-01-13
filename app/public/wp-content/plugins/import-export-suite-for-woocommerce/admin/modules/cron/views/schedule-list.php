<?php
/**
 * Schedule listing
 *
 * @package ImportExportSuite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $cron_list ) && is_array( $cron_list ) && count( $cron_list ) > 0 ) {
	?>
<div class="cron_list_wrapper">
	<table class="wp-list-table widefat fixed striped cron_list_tb" style="margin-bottom:55px;">
	<thead>
		<tr>
			<th width="50"><?php esc_html_e( 'No.', 'import-export-suite-for-woocommerce' ); ?></th>
			<th width="100"><?php esc_html_e( 'Action type', 'import-export-suite-for-woocommerce' ); ?></th>
			<th width="100"><?php esc_html_e( 'Post type', 'import-export-suite-for-woocommerce' ); ?></th>
			<th width="100"><?php esc_html_e( 'Cron type', 'import-export-suite-for-woocommerce' ); ?></th>
			<th width="100">
				<?php esc_html_e( 'Status', 'import-export-suite-for-woocommerce' ); ?>
				<span class="dashicons dashicons-editor-help wt-iew-tips" 
					data-wt-iew-tip="
					<span class='wt_iew_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */ printf( esc_html__( '%1$sFinished%2$s - Process completed', 'import-export-suite-for-woocommerce' ), '<b>', '</b>' ); ?></span><br />
					<span class='wt_iew_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sDisabled%2$s - The process has been disabled temporarily', 'import-export-suite-for-woocommerce' ), '<b>', '</b>' ); ?> </span><br />
					<span class='wt_iew_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sRunning%2$s - Process currently active and running', 'import-export-suite-for-woocommerce' ), '<b>', '</b>' ); ?> </span><br />
					<span class='wt_iew_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sUploading%2$s - Processed records are being uploaded to the specified location, finalizing export.', 'import-export-suite-for-woocommerce' ), '<b>', '</b>' ); ?> </span><br />
					<span class='wt_iew_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sDownloading%2$s - Input records are being downloaded from the specified location prior to import process.', 'import-export-suite-for-woocommerce' ), '<b>', '</b>' ); ?> </span>">            
				</span>
			</th>
			<th><?php esc_html_e( 'Time', 'import-export-suite-for-woocommerce' ); ?></th>
			<th width="150"><?php esc_html_e( 'History', 'import-export-suite-for-woocommerce' ); ?></th>
			<th width="200"><?php esc_html_e( 'Actions', 'import-export-suite-for-woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	$i = 0;
	foreach ( $cron_list as $key => $cron_item ) {
		++$i;
				$item_type = ucfirst( $cron_item['item_type'] );
		?>
		<tr>
			<td><?php echo absint( $i ); ?></td>
			<td><?php echo esc_attr( ucfirst( $cron_item['action_type'] ) ); ?></td>
			<td><?php echo esc_attr( $item_type ); ?></td>
			<td><?php ( 'server_cron' === $cron_item['schedule_type'] ? esc_html_e( 'Server cron', 'import-export-suite-for-woocommerce' ) : esc_html_e( 'WordPress cron', 'import-export-suite-for-woocommerce' ) ); ?></td>
			<td>
				<?php $td_style_bg = isset( self::$status_color_arr[ $cron_item['status'] ] ) ? 'background:' . self::$status_color_arr[ $cron_item['status'] ] : ''; ?>
				<span class="wt_iew_badge" style="<?php echo wp_kses_post( $td_style_bg ); ?>">
		<?php
		$td_status_text = isset( self::$status_label_arr[ $cron_item['status'] ] ) ? self::$status_label_arr[ $cron_item['status'] ] : __( 'Unknown', 'import-export-suite-for-woocommerce' );
		echo esc_attr( $td_status_text );
		?>
				</span>
		<?php
		/**
		 *     Show completed percentage if status is running
		 */
		if ( $cron_item['status'] === self::$status_arr['running'] && $cron_item['history_id'] > 0 ) {
			$history_module_obj = Wt_Import_Export_For_Woo::load_modules( 'history' );
			if ( ! is_null( $history_module_obj ) ) {
				$history_entry = $history_module_obj->get_history_entry_by_id( $cron_item['history_id'] );
				if ( $history_entry ) {
					echo esc_attr( number_format( ( ( $history_entry['offset'] / $history_entry['total'] ) * 100 ), 2 ) . '% ' . __( ' Done', 'import-export-suite-for-woocommerce' ) );
				}
			}
		}
		?>
			</td>
			<td>
		<?php
		// Ensure status is an integer for comparison.
		$cron_status_value = isset( $cron_item['status'] ) ? absint( $cron_item['status'] ) : 0;
		$last_run = isset( $cron_item['last_run'] ) ? absint( $cron_item['last_run'] ) : 0;
		$start_time = isset( $cron_item['start_time'] ) ? absint( $cron_item['start_time'] ) : 0;

		$time_displayed = false;

		if ( $cron_status_value === self::$status_arr['finished'] || $cron_status_value === self::$status_arr['disabled'] ) {
			if ( $last_run > 0 ) {
				/* translators:%s: last access date */
				$last_run_string = sprintf( __( 'Last run: %s', 'import-export-suite-for-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $last_run ) );
				echo esc_html( $last_run_string ) . '<br />';
				$time_displayed = true;
			}

			/*
			 *    Finished, so waiting for next run
			 */
			if ( $cron_status_value === self::$status_arr['finished'] && $start_time > 0 && $start_time !== $last_run ) {
				/* translators:%s: next access date */
				$next_run_string = sprintf( __( 'Next run: %s', 'import-export-suite-for-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $start_time ) );
				echo esc_html( $next_run_string ) . '<br />';
				$time_displayed = true;
			}
		}

		if ( $cron_status_value === self::$status_arr['running'] || $cron_status_value === self::$status_arr['uploading'] || $cron_status_value === self::$status_arr['downloading'] ) {
			if ( $last_run > 0 && $start_time !== $last_run ) {
				/* translators:%s: last access date */
				$last_run_stringi = sprintf( __( 'Last run: %s', 'import-export-suite-for-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $last_run ) );
				echo esc_html( $last_run_stringi ) . '<br />';
				$time_displayed = true;
			} elseif ( $start_time > 0 ) {
				/* translators:%s: started date */
				$started_string = sprintf( __( 'Started at: %s', 'import-export-suite-for-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $start_time ) );
				echo esc_html( $started_string ) . '<br />';
				$time_displayed = true;
			}
		}

		if ( $cron_status_value === self::$status_arr['not_started'] && $start_time > 0 ) {
			/* translators:%s: next access date */
			$next_run_string = sprintf( __( 'Will start at: %s', 'import-export-suite-for-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $start_time ) );
			echo esc_html( $next_run_string ) . '<br />';
			$time_displayed = true;
		}

		// Fallback if no time information is displayed.
		if ( ! $time_displayed ) {
			echo '<span style="color:#999;">' . esc_html__( 'No time information available', 'import-export-suite-for-woocommerce' ) . '</span>';
		}
		?>
			</td>
			<td>
		<?php
		$history_arr = ( '' !== $cron_item['history_id_list'] ? Wt_Import_Export_For_Woo_Common_Helper::wt_unserialize_safe( $cron_item['history_id_list'] ) : array() );
		$history_arr = ( is_array( $history_arr ) ? $history_arr : array() );
		if ( count( $history_arr ) > 0 ) {
			$history_module_obj = Wt_Import_Export_For_Woo::load_modules( 'history' );
			if ( ! is_null( $history_module_obj ) ) {
				$history_entry = $history_module_obj->get_history_entry_by_id( $history_arr );
				if ( $history_entry ) {
					/* translators: %d: History ID */
					echo esc_attr( sprintf( __( 'Total %d entries found.', 'import-export-suite-for-woocommerce' ), count( $history_entry ) ) );
					?>
						<br />
						<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=wt_import_export_for_woo&tab=history&wt_iew_cron_id=' . $cron_item['id'] ) ); ?>">
					<?php esc_html_e( 'View', 'import-export-suite-for-woocommerce' ); ?> <span class="dashicons dashicons-external"></span>
						</a>
					<?php
				}
			}
		} else {
			esc_html_e( 'No entries found.', 'import-export-suite-for-woocommerce' );
		}
		?>
			</td>
			<td>
		<?php
		$page_id = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'wt_import_export_for_woo' );

		// Status change section.
		// Ensure status is an integer for comparison.
		$cron_status = isset( $cron_item['status'] ) ? absint( $cron_item['status'] ) : 0;
		$action_label = __( 'Disable', 'import-export-suite-for-woocommerce' );
		$action_str   = 'disable';
		if ( $cron_status === self::$status_arr['disabled'] ) {
			$action_str   = 'enable';
			$action_label = __( 'Enable', 'import-export-suite-for-woocommerce' );
		}

		$action_url = wp_nonce_url( admin_url( 'admin.php?page=' . $page_id . '&tab=cron&wt_iew_change_schedule_status=' . $action_str . '&wt_iew_cron_id=' . $cron_item['id'] ), WT_IEW_PLUGIN_ID );

		// delete section.
		$delete_url = wp_nonce_url( admin_url( 'admin.php?page=' . $page_id . '&tab=cron&wt_iew_delete_schedule=1&wt_iew_cron_id=' . $cron_item['id'] ), WT_IEW_PLUGIN_ID );

								// edit action.
		if ( 'import' === $cron_item['action_type'] ) {
			$edit_url = admin_url( 'admin.php?page=wt_import_export_for_woo&tab=import&wt_iew_cron_edit_id=' . $cron_item['id'] );
		} else {
			$edit_url = admin_url( 'admin.php?page=wt_import_export_for_woo&tab=export&wt_iew_cron_edit_id=' . $cron_item['id'] );
		}

		if ( ! class_exists( "Wt_Import_Export_For_Woo_$item_type" ) ) {
			$edit_url = '#';
		}

		?>
							<a class="wt_iew_cron_edit wt_iew_action_btn" href="<?php echo esc_url( $edit_url ); ?>" ><?php esc_html_e( 'Edit', 'import-export-suite-for-woocommerce' ); ?></a> | <a href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_attr( $action_label ); ?></a> | <a class="wt_iew_delete_cron" data-href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'import-export-suite-for-woocommerce' ); ?></a>
		<?php
		if ( 'server_cron' === $cron_item['schedule_type'] ) {
			$cron_url = $this->generate_cron_url( $cron_item['id'], $cron_item['action_type'], $cron_item['item_type'] );
			?>
					| <a class="wt_iew_cron_url" data-href="<?php echo esc_url( $cron_url ); ?>" title="<?php esc_html_e( 'Generate new cron URL.', 'import-export-suite-for-woocommerce' ); ?>"><?php esc_html_e( 'Cron URL', 'import-export-suite-for-woocommerce' ); ?></a>
			<?php
		}
		?>
			</td>
		</tr>
		<?php
	}//end foreach
	?>
	</tbody>
	</table>
</div>

	<?php
	// include plugin_dir_path(__FILE__).'/_schedule_update.php';.
	?>
	<?php
} else {
	?>
	<h4 style="margin-bottom:55px; text-align:center; background:#fff; padding:15px 0px;"><?php esc_html_e( 'No scheduled actions found.', 'import-export-suite-for-woocommerce' ); ?></h4>
	<?php
}//end if

