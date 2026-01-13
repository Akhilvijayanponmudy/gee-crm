<?php
/**
 * Add New Category Mapping View
 *
 * @link       https://webtoffee.com/
 * @since      1.0.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}






// Category mapping.
if ( ! function_exists( 'wt_onbuy_feed_render_categories' ) ) {
    /**
     * Get Product Categories
     *
     * @param int    $parent Parent ID.
     * @param string $par separator.
     * @param string $value mapped values.
     */
    function wt_onbuy_feed_render_categories( $parent = 0, $par = '', $value = '' ) {
        
        $category_query =   isset($_POST['cat_filter_type']) ? Wt_Pf_Sh::sanitize_item(wp_unslash($_POST['cat_filter_type']), 'text') : ''; //phpcs:ignore
        $query_categories = isset($_POST['inc_exc_cat']) ? Wt_Pf_Sh::sanitize_item(wp_unslash($_POST['inc_exc_cat']), 'text_arr') : array(); //phpcs:ignore

        $ids_to_include_or_exclude = array();
        $get_terms_to_include_or_exclude =  get_terms(
            array(
                'fields'  => 'ids',
                'slug'    => $query_categories,
                'taxonomy' => 'product_cat',
                'hide_empty'	 => 0,
            )
        );
        if( !is_wp_error( $get_terms_to_include_or_exclude ) && count($get_terms_to_include_or_exclude) > 0){
            $ids_to_include_or_exclude = $get_terms_to_include_or_exclude; 
        }        
        
        // Get all categories first, then filter out those with meta
        $category_args = [
			'taxonomy'		 => 'product_cat',
			'parent'		 => $parent,
			'orderby'		 => 'term_group',
			'show_count'	 => 1,
			'pad_counts'	 => 1,
			'hierarchical'	 => 1,
			'title_li'		 => '',
			'hide_empty'	 => 1,
			'fields'         => 'all', // Get all fields for filtering
		];
        
        if( !empty( $ids_to_include_or_exclude ) ){
            if( 'exclude_cat' ===  $category_query ){
                // Use include with all IDs except excluded ones for better performance
                $all_cat_ids = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'fields' => 'ids',
                    'hide_empty' => 1,
                ));
                $category_args['include'] = array_diff($all_cat_ids, $ids_to_include_or_exclude);
            }else{
                $category_args['include'] = $ids_to_include_or_exclude;
            }
        }
        
        $categories   = get_categories( $category_args );
        
        // Filter out categories that already have the meta key (more efficient than meta_query)
        if ( ! empty( $categories ) ) {
            $filtered_categories = array();
            foreach ( $categories as $cat ) {
                $meta_value = get_term_meta( $cat->term_id, 'wt_onbuy_category', true );
                if ( empty( $meta_value ) ) {
                    $filtered_categories[] = $cat;
                }
            }
            $categories = $filtered_categories;
        }
        
        if ( ! empty( $categories ) ) {
            if ( ! empty( $par ) ) {
                $par = $par . ' > ';
            }
			
			
            foreach ( $categories as $cat ) {
                $class = $parent ? "treegrid-parent-{$parent} category-mapping" : 'treegrid-parent category-mapping';
                ?>
                <tr class="treegrid-1 ">
                    <th>
                        <label for="cat_mapping_<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $par . $cat->name ); ?></label>
                    </th>
                    <td>
                        <select id= "cat_mapping_<?php echo esc_attr( $cat->term_id ); ?>" name="map_to[<?php echo esc_attr( $cat->term_id ); ?>]" class="wc-enhanced-select wt-wc-enhanced-search">
                                <?php //echo wt_onbuy_feed_category_dropdown(); ?>
                            </select>
                    </td>
                </tr>
                <?php
                // call for child category if any.
		if(!empty($par))
                wt_onbuy_feed_render_categories( $cat->term_id, $par . $cat->name, $value );
            }
        }else{
            ?>
                <tr class="treegrid-1">
                        <td>
                                <?php esc_html_e('All categories have already been mapped', 'webtoffee-product-feed'); ?>
                        </td>
                </tr>
            <?php
            }
        }
}

// FB Category dropdown caching
if ( ! function_exists( 'wt_fb_feed_category_dropdown' ) ) {
	 function wt_onbuy_feed_category_dropdown( $selected = '' ) {
		
		$category_dropdown = wp_cache_get( 'wt_onbuyfeed_dropdown_product_categories' );

		if ( false === $category_dropdown ) {
			$categories = Webtoffee_Product_Feed_Sync_Pro_OnBuy::get_category_array();
			
			# Primary Attributes
			$category_dropdown = '';
			
				foreach ( $categories as $key => $value ) {
					$category_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
				}

			wp_cache_set( 'wt_onbuyfeed_dropdown_product_categories', $category_dropdown, '', WEEK_IN_SECONDS );
		}
		
		
		if ( $selected && strpos( $category_dropdown, 'value="' . $selected . '"' ) !== false ) {
			$category_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"' . ' selected', $category_dropdown );
		}
		 

		
		return $category_dropdown;
	}
}





$value           = array();

?>
<div class="wt-wrap">

	
	<h4><?php esc_html_e( 'Map WooCommerce categories with OnBuy categories.', 'webtoffee-product-feed' ); ?></h4>
	<?php
            $feed_channel_name =  ucwords($this->to_export);
            if( 'tiktok' === $this->to_export ){
                $feed_channel_name = 'TikTok Ads';
            }
            if( 'tiktokshop' === $this->to_export ){
                $feed_channel_name = 'TikTok Shop';
            }            
            if( 'price_grabber' === $this->to_export ){
                $feed_channel_name = 'Price Grabber';
            }
        ?>            
        <?php if( 'onbuy' === $this->to_export ): ?>
        <span><?php esc_html_e( 'OnBuy has a pre-defined set of categories', 'webtoffee-product-feed'); ?></a>. <?php esc_html_e( 'Mapping your store categories with the OnBuy categories will give more visibility to your products in OnBuy ads and listings. To edit the mapping go to the respective', 'webtoffee-product-feed'); ?> <a target="_blank" href="<?php echo esc_url( admin_url('edit-tags.php?taxonomy=product_cat&post_type=product') ); ?>"><?php esc_html_e( 'categories page', 'webtoffee-product-feed'); ?></a></span>
	<?php else: ?>
        <span><?php echo esc_html($feed_channel_name); if( 'tiktok' === $this->to_export ){ $feed_channel_name = 'TikTok';} // To avoid ads text multiple times ?> <?php esc_html_e( 'uses', 'webtoffee-product-feed' ); ?> <a target="_blank" href="https://www.onbuy.com/basepages/producttype/taxonomy.en-US.txt"><?php esc_html_e( 'OnBuy categories', 'webtoffee-product-feed'); ?></a>. <?php esc_html_e( 'Mapping your store categories with the OnBuy categories will give more visibility to your products in', 'webtoffee-product-feed');?> <?php echo esc_html($feed_channel_name); ?> <?php esc_html_e( 'ads. To edit the mapping go to the respective', 'webtoffee-product-feed'); ?> <a target="_blank" href="<?php echo esc_url( admin_url('edit-tags.php?taxonomy=product_cat&post_type=product') ); ?>"><?php esc_html_e( 'categories page', 'webtoffee-product-feed'); ?></a></span>
        <?php endif; ?>
	<form action="" name="feed" id="category-mapping-form" class="category-mapping-form" method="post" autocomplete="off">
		<?php wp_nonce_field( 'wt-category-mapping' ); ?>

		<br/>
		<table class="table tree widefat fixed wt-pf-category-default-mapping-tb">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Store Categories', 'webtoffee-product-feed' ); ?></th>
				<th><?php esc_html_e( 'OnBuy Category', 'webtoffee-product-feed' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php wt_onbuy_feed_render_categories( 0, '', $value ); ?>
			</tbody>
		</table>
	</form>
</div>