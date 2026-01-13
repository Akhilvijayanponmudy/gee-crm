<?php
/**
 * Product Tags Export Columns
 *
 * @package WP_Import_Export_For_Woo
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

/**
 * Product Tags Export Columns
 *
 * @param array $post_columns Post Columns.
 */
$post_columns = array(
	'term_id'     => 'term_id',
	'name'        => 'name',
	'slug'        => 'slug',
	'description' => 'description',
);

if ( class_exists( 'WPSEO_Options' ) ) {
	/* Yoast is active */
	$post_columns['meta:_yoast_data'] = 'meta:_yoast_data';
}

/**
 * Filters the product tags export columns
 *
 * @param array $post_columns Post Columns.
 * @return array
 * @since 1.0.0
 */
return apply_filters( 'wt_tags_csv_product_post_columns', $post_columns );
