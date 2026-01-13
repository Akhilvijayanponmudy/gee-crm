<?php
/**
 * Handles the product categories export.
 *
 * @package   ImportExportSuite\Admin\Modules\ProductCategories\Export
 * @version   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}
/**
 * Wt_Import_Export_For_Woo_Product_Categories_Export Class.
 */
class Wt_Import_Export_For_Woo_Product_Categories_Export {

	/**
	 * Parent module object
	 *
	 * @var object
	 */
	public $parent_module = null;

	/**
	 * Constructor.
	 *
	 * @param object $parent_object Parent module object.
	 * @since 1.0.0
	 */
	public function __construct( $parent_object ) {

		$this->parent_module = $parent_object;
	}//end __construct()

	/**
	 * Prepare CSV header
	 *
	 * @return type
	 */
	public function prepare_header() {

		$export_columns = $this->parent_module->get_selected_column_names();
		/**
		 * Filter to alter the product categories export columns
		 *
		 * @param array $export_columns Export columns.
		 * @return array
		 * @since 1.0.0
		 */
		return apply_filters( 'wt_alter_product_reviews_export_csv_columns', $export_columns );
	}//end prepare_header()

	/**
	 * Prepare data that will be exported.
	 *
	 * @param array   $form_data Form data.
	 * @param integer $batch_offset Offset.
	 * @return array
	 */
	public function prepare_data_to_export( $form_data, $batch_offset ) {
		$batch_offset = intval( $batch_offset );
		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		$sortcolumn        = ! empty( $form_data['filter_form_data']['wt_iew_sort_columns'] ) ? $form_data['filter_form_data']['wt_iew_sort_columns'] : 'id';
		$export_sort_order = ! empty( $form_data['filter_form_data']['wt_iew_order_by'] ) ? $form_data['filter_form_data']['wt_iew_order_by'] : 'ASC';
		$taxonomy_type     = 'product_cat';

		$export_limit   = ! empty( $form_data['filter_form_data']['wt_iew_limit'] ) ? intval( $form_data['filter_form_data']['wt_iew_limit'] ) : 999999999;
		$current_offset = ! empty( $form_data['filter_form_data']['wt_iew_offset'] ) ? intval( $form_data['filter_form_data']['wt_iew_offset'] ) : 0;
		$batch_count    = ! empty( $form_data['advanced_form_data']['wt_iew_batch_count'] ) ? $form_data['advanced_form_data']['wt_iew_batch_count'] : Wt_Import_Export_For_Woo_Common_Helper::get_advanced_settings( 'default_export_batch' );

		$real_offset = ( $current_offset + $batch_offset );

		if ( $batch_count <= $export_limit ) {
			if ( ( $batch_offset + $batch_count ) > $export_limit ) {
				$limit = $export_limit - $batch_offset;
			} else {
				$limit = $batch_count;
			}
		} else {
			$limit = $export_limit;
		}

		$data_array = array();
		if ( $batch_offset < $export_limit ) {

			$args = array(
				'taxonomy'   => $taxonomy_type,
				'orderby'    => $sortcolumn,
				'order'      => $export_sort_order,
				'hide_empty' => 0,
				'offset'     => $real_offset,
				'number'     => $limit,
			);

			$terms = get_terms( $args );

			foreach ( $terms as $term ) {
				$data_array[] = $this->hf_import_to_csv( $term, $terms, $taxonomy_type );

			}
			/**
			*   Taking total records
			*/
			$total_records = 0;
			if ( 0 === $batch_offset ) {
				$total_item_args           = $args;
				$total_item_args['number'] = $export_limit;
				$total_item_args['offset'] = $current_offset;
				$all_terms                 = get_terms( $total_item_args );
				$total_records             = is_array( $all_terms ) ? count( $all_terms ) : 0;
			}

			$return['total'] = $total_records;
			$return['data']  = $data_array;

			if ( 0 === $batch_offset && 0 === $total_records ) {
				$return['no_post'] = __( 'Nothing to export under the selected criteria. Please check and try adjusting the filters.', 'import-export-suite-for-woocommerce' );
			}

			return $return;
		}
	}//end prepare_data_to_export()

	/**
	 * Prepare data that will be exported.
	 *
	 * @param object $term Term object.
	 * @param array  $terms Terms array.
	 * @param string $taxonomy_type Taxonomy type.
	 * @return array
	 */
	public function hf_import_to_csv( $term, $terms, $taxonomy_type ) {
		$row = array();

		$csv_columns = $this->parent_module->get_selected_column_names();

		foreach ( $csv_columns as $column => $value ) {

			if ( 'term_id' === $column ) {
				$row[ $column ] = $term->term_id;
				continue;
			}
			if ( 'name' === $column ) {
				$row[ $column ] = htmlspecialchars_decode( $term->name );
				continue;
			}
			if ( 'slug' === $column ) {
				$row[ $column ] = rawurldecode( $term->slug );
				continue;
			}
			if ( 'description' === $column ) {
				$row[ $column ] = $term->description;
				continue;
			}
			if ( 'display_type' === $column ) {
				$display_type   = get_term_meta( $term->term_id, 'display_type', true );
				$row[ $column ] = $display_type ? $display_type : 'default';
				continue;
			}
			if ( 'parent' === $column ) {
				$row[ $column ] = $term->parent;
				continue;
			}

			if ( 'thumbnail' === $column ) {
				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$thumbnail    = wp_get_attachment_url( $thumbnail_id );
				if ( filter_var( $thumbnail, FILTER_VALIDATE_URL ) === false ) {
					$thumbnail = '';
				}
				$row[ $column ] = $thumbnail;
				continue;
			}

			if ( class_exists( 'WPSEO_Options' ) ) {

				if ( 'meta:_yoast_data' === $column ) {
					$yoast_data      = get_option( 'wpseo_taxonomy_meta' );
							$term_id = $row['term_id'];

							$row[ $column ] = isset( $yoast_data['product_cat'][ $term_id ] ) ? maybe_serialize( $yoast_data['product_cat'][ $term_id ] ) : '';
							continue;
				}
			}

			if ( 'meta:' === substr( $column, 0, 5 ) ) {
				$cat_meta_key   = substr( $column, 5 );
				$cat_meta_value = get_term_meta( $term->term_id, $cat_meta_key, true );
				$row[ $column ] = isset( $cat_meta_value ) ? $cat_meta_value : '';
				continue;
			}
		}

		/**
		 * Filter to alter the product categories export data
		 *
		 * @param array $row Row data.
		 * @param int   $term_id Term ID.
		 * @param array $csv_columns CSV columns.
		 * @return array
		 * @since 1.0.0
		 */
		$row = apply_filters( 'wt_alter_product_categories_export_csv_data', $row, $term->term_id, $csv_columns );
		return $row;
	}//end hf_import_to_csv()
}
