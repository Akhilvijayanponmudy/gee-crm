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
return apply_filters(
	'woocommerce_csv_taxonomies_import_reserved_fields_pair',
	array(
		'term_id'          => array(
			'title'       => 'Taxonomy term ID',
			'description' => 'Taxonomy term ID',
		),
		'name'             => array(
			'title'       => 'Name',
			'description' => 'Name of the taxonomy',
		),
		'slug'             => array(
			'title'       => 'Slug',
			'description' => 'Slug of the taxonomy',
		),
		'description'      => array(
			'title'       => 'Description',
			'description' => 'Description of the taxonomy',
		),
		'display_type'     => array(
			'title'       => 'Display type',
			'description' => 'Display type of the taxonomy',
		),
		'parent'           => array(
			'title'       => 'Parent ID',
			'description' => 'Parent ID',
		),
		'thumbnail'        => array(
			'title'       => 'Thumbnail',
			'description' => 'Thumbnail',
		),
		'meta:_yoast_data' => array(
			'title'       => 'meta:_yoast_data',
			'description' => 'yoast_data',
		),
	)
);
