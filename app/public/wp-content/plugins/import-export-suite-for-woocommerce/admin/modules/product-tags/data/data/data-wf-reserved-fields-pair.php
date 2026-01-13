<?php
/**
 * Reserved fields for product tags
 *
 * @package WP_Import_Export_For_Woo
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

/**
 * Reserved fields for product tags
 *
 * @return array
 * @since 1.0.0
 */
return apply_filters(
	'woocommerce_csv_tags_import_reserved_fields_pair',
	array(
		'term_id'          => array(
			'title'       => 'Tag term ID',
			'description' => 'Tag term ID',
		),
		'name'             => array(
			'title'       => 'Name',
			'description' => 'Name of the tag',
		),
		'slug'             => array(
			'title'       => 'Slug',
			'description' => 'Slug of the tag',
		),
		'description'      => array(
			'title'       => 'Description',
			'description' => 'Description of the tag',
		),
		'meta:_yoast_data' => array(
			'title'       => 'meta:_yoast_data',
			'description' => 'yoast_data',
		),
	)
);
