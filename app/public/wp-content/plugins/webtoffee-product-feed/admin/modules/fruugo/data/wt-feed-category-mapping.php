<?php
/**
 * Add New Category Mapping View
 *
 * @link       https://webtoffee.com/
 * @since      1.0.0
 *
 */
if (!defined('ABSPATH')) {
    die();
}






// Category mapping.
if (!function_exists('wt_fruugo_feed_render_categories')) {

    /**
     * Get Product Categories
     *
     * @param int    $parent Parent ID.
     * @param string $par separator.
     * @param string $value mapped values.
     */
    function wt_fruugo_feed_render_categories($parent = 0, $par = '', $value = '') {

        $category_query = isset($_POST['cat_filter_type']) ? Wt_Pf_Sh::sanitize_item(wp_unslash($_POST['cat_filter_type']), 'text') : ''; //phpcs:ignore
        $query_categories = isset($_POST['inc_exc_cat']) ? Wt_Pf_Sh::sanitize_item(wp_unslash($_POST['inc_exc_cat']), 'text_arr') : array(); //phpcs:ignore

        $ids_to_include_or_exclude = array();
        $get_terms_to_include_or_exclude = get_terms(
                array(
                    'fields' => 'ids',
                    'slug' => $query_categories,
                    'taxonomy' => 'product_cat',
                    'hide_empty' => 0,
                )
        );
        if (!is_wp_error($get_terms_to_include_or_exclude) && count($get_terms_to_include_or_exclude) > 0) {
            $ids_to_include_or_exclude = $get_terms_to_include_or_exclude;
        }

        $category_args = [
            'taxonomy' => 'product_cat',
            'parent' => $parent,
            'orderby' => 'term_group',
            'show_count' => 1,
            'pad_counts' => 1,
            'hierarchical' => 1,
            'title_li' => '',
            'hide_empty' => 1,
            'meta_query' => [ //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key' => 'wt_fruugo_category',
                    'compare' => 'NOT EXISTS',
                ]
            ]
        ];

        if (!empty($ids_to_include_or_exclude)) {
            if ('exclude_cat' === $category_query) {
                $category_args['exclude'] = $ids_to_include_or_exclude; //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
            } else {
                $category_args['include'] = $ids_to_include_or_exclude;
            }
        }

        $categories = get_categories($category_args);
        if (!empty($categories)) {
            if (!empty($par)) {
                $par = $par . ' > ';
            }


            foreach ($categories as $cat) {
                $class = $parent ? "treegrid-parent-{$parent} category-mapping" : 'treegrid-parent category-mapping';
                ?>
                <tr class="treegrid-1 ">
                    <th>
                        <label for="cat_mapping_<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($par . $cat->name); ?></label>
                    </th>
                    <td>
                        <select id= "cat_mapping_<?php echo esc_attr($cat->term_id); ?>" name="map_to[<?php echo esc_attr($cat->term_id); ?>]" class="wc-enhanced-select wt-wc-enhanced-search">
                            <?php //echo wt_google_feed_category_dropdown(); ?>
                        </select>
                    </td>
                </tr>
                <?php
                // call for child category if any.
                if (!empty($par))
                    wt_fruugo_feed_render_categories($cat->term_id, $par . $cat->name, $value);
            }
        } else {
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






$value = array();
?>
<div class="wt-wrap">


    <h4><?php esc_html_e('Map WooCommerce categories with Fruugo categories.', 'webtoffee-product-feed'); ?></h4>          
    <span><?php esc_html_e('Fruugo has it\'s own', 'webtoffee-product-feed' ); ?> <a target="_blank" href="https://fruugo.atlassian.net/wiki/spaces/RR/pages/67792527/Mapping+Category+Tree?preview=/67792527/67874831/Fruugo_Mapping_Categories.xlsx"><?php esc_html_e('category list', 'webtoffee-product-feed'); ?></a>. <?php esc_html_e(' It is mandatory to map the store categories against Fruugo categories. To edit the mapping go to the respective', 'webtoffee-product-feed'); ?> <a target="_blank" href="<?php echo esc_url( admin_url('edit-tags.php?taxonomy=product_cat&post_type=product') ); ?>"><?php esc_html_e('categories page', 'webtoffee-product-feed'); ?></a></span>
    <form action="" name="feed" id="category-mapping-form" class="category-mapping-form" method="post" autocomplete="off">
        <?php wp_nonce_field('wt-category-mapping'); ?>

        <br/>
        <table class="table tree widefat fixed wt-pf-category-default-mapping-tb">
            <thead>
                <tr>
                    <th><?php esc_html_e('Store Categories', 'webtoffee-product-feed'); ?></th>
                    <th><?php esc_html_e('Fruugo Category', 'webtoffee-product-feed'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php wt_fruugo_feed_render_categories(0, '', $value); ?>
            </tbody>
        </table>
    </form>
</div>