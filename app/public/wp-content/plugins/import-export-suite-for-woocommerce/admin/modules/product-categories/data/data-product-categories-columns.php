<?php
/**
 * Product categories reserved post columns
 *
 * @link
 *
 * @package ImportExportSuite
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

/**
 * Filter the query arguments for a request.
 *
 * Enables adding extra arguments or setting defaults for the request.
 *
 * @since 1.0.0
 *
 * @param array   $columns    Import columns.
 */
$post_columns = array(
	'term_id'      => 'term_id',
	'name'         => 'name',
	'slug'         => 'slug',
	'description'  => 'description',
	'display_type' => 'display_type',
	'parent'       => 'parent',
	'thumbnail'    => 'thumbnail',
);

/* Yoast SEO */

if ( class_exists( 'WPSEO_Options' ) ) {
	/* Yoast is active */

	$post_columns['meta:_yoast_data'] = 'meta:_yoast_data';

}

/**
 * Filter the product categories post columns
 *
 * @param array $post_columns Post columns.
 * @return array
 * @since 1.0.0
 */
return apply_filters( 'taxonomies_csv_product_post_columns', $post_columns );
