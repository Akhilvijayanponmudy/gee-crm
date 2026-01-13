<?php
/**
 * Export page post type select
 *
 * @package ImportExportSuite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_iew_export_main">
	<p><?php echo esc_html( $step_info['description'] ); ?></p>
		<?php if ( empty( $post_types ) ) { ?>
			<div class="wt_iew_warn wt_iew_post_type_wrn">
				<?php

				echo wp_kses_post(
					sprintf(
						/* translators: 1: html b. 2: html b close. 3: link to my-account. 4: link to admin */
						__(
							'Atleast one of the %1$s WebToffee add-ons(Product/Reviews, User, Order/Coupon/Subscription)%2$s should be activated to start exporting the respective post type. Go to <a href="%3$s" target="_blank">My accounts->Subscriptions</a> to download and activate the add-on. If already installed activate the respective add-on plugin under <a href="%4$s" target="_blank">Plugins</a>.',
							'import-export-suite-for-woocommerce'
						),
						'<b>',
						'</b>',
						'https://www.webtoffee.com/my-account/my-subscriptions/',
						admin_url( 'plugins.php?s=webtoffee' )
					)
				);
				?>
			</div>
		<?php } ?>
		
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
