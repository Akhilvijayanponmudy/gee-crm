<?php
/**
 * Import page post type select
 *
 * @package ImportExportSuite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_iew_import_main">
	<div class="wt-migrations-sequence-headsup" style="background: #E4F1FF;padding: 1px 12px;">
		<p>
			<?php
				echo wp_kses_post(
					sprintf(
						/* translators: 1: html b. 2: html b close.*/
						__(
							'For a complete store migration, we recommend you to import the files in the following sequence: %1$s User/Customer > Product categories > Product tags > Product > Product Review > Coupon > Order > Subscription %2$s ',
							'import-export-suite-for-woocommerce'
						),
						'<b>',
						'</b>'
					)
				);
				?>
		</p>
	</div>
	<p><?php echo esc_html( $this->step_description ); ?></p>
	<div class="wt_iew_post-type-cards">
			<?php
			foreach ( $post_types as $key => $value ) {
				$post_image_link        = WT_IEW_PLUGIN_URL . 'assets/images/post_types/' . strtolower( $key ) . '.svg';
				$post_image_link_active = WT_IEW_PLUGIN_URL . 'assets/images/post_types/' . strtolower( $key ) . 'active.svg';
				?>
				<div class="wt_iew_post-type-card <?php echo ( $item_type === $key ) ? 'selected' : ''; ?>" data-post-type="<?php echo esc_attr( $key ); ?>">
					<div class="wt_iew_post-type-card2">
						<div class="wt_iew_image <?php echo 'wt_iew_image_' . esc_html( $key ); ?>" style="display : <?php echo ( $item_type === $key ) ? 'none' : 'block'; ?>">
							<img src="<?php echo esc_url( $post_image_link ); ?>" />
						</div>
						<div class="<?php echo 'wt_iew_active_image_' . esc_html( $key ); ?>" style="display : <?php echo ( $item_type === $key ) ? 'block' : 'none'; ?>">
							<img src="<?php echo esc_url( $post_image_link_active ); ?>" />
						</div>

					</div>
					<h3 class="wt_iew_post-type-card-hd"><?php echo esc_html( $value ); ?></h3>
					<div class="wt_iew_free_addon_warn <?php echo 'wt_iew_type_' . esc_html( $key ); ?>" style="display:block;">
					</div>

				</div>
			<?php } ?>
		</div>
</div>
