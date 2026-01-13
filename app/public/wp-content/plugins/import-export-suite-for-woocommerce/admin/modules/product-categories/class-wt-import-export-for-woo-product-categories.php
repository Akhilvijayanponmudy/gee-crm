<?php
/**
 * Handles the product categories actions.
 *
 * @package   ImportExportSuite\Admin\Modules\ProductCategories
 * @version   1.2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Wt_Import_Export_For_Woo_Product_Categories Class.
 */
class Wt_Import_Export_For_Woo_Product_Categories {


	/**
	 * Module ID
	 *
	 * @var string
	 */
	public $module_id = '';
	/**
	 * Module ID
	 *
	 * @var string
	 */
	public static $module_id_static = '';
	/**
	 * Module ID
	 *
	 * @var string
	 */
	public $module_base = 'product_categories';
	/**
	 * Module ID
	 *
	 * @var string
	 */
	public $module_name = 'Categories Import Export for WooCommerce';
	/**
	 * Minimum `Import export plugin` required to run this add on plugin
	 *
	 * @var string
	 */
	public $min_base_version = '1.2.7';
	/**
	 * Module meta keys
	 *
	 * @var array
	 */
	private $all_meta_keys = array();
	/**
	 * Module meta
	 *
	 * @var array
	 */
	private $found_product_cat_meta = array();
	/**
	 * Module selected columns
	 *
	 * @var array
	 */
	private $selected_column_names = null;
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/**
		 *   Checking the minimum required version of `Import export plugin` plugin available
		 */
		if ( ! Wt_Import_Export_For_Woo_Common_Helper::check_base_version( $this->module_base, $this->module_name, $this->min_base_version ) ) {
			return;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->module_id = Wt_Import_Export_For_Woo::get_module_id( $this->module_base );

		self::$module_id_static = $this->module_id;

		add_filter( 'wt_iew_exporter_post_types', array( $this, 'wt_iew_exporter_post_types' ), 10, 1 );
		add_filter( 'wt_iew_importer_post_types', array( $this, 'wt_iew_exporter_post_types' ), 10, 1 );

		add_filter( 'wt_iew_exporter_alter_filter_fields', array( $this, 'exporter_alter_filter_fields' ), 10, 3 );

		add_filter( 'wt_iew_exporter_alter_mapping_fields', array( $this, 'exporter_alter_mapping_fields' ), 10, 3 );
		add_filter( 'wt_iew_importer_alter_mapping_fields', array( $this, 'get_importer_post_columns' ), 10, 3 );

		add_filter( 'wt_iew_exporter_alter_advanced_fields', array( $this, 'exporter_alter_advanced_fields' ), 10, 3 );
		add_filter( 'wt_iew_importer_alter_advanced_fields', array( $this, 'importer_alter_advanced_fields' ), 10, 3 );

		add_filter( 'wt_iew_exporter_alter_meta_mapping_fields', array( $this, 'exporter_alter_meta_mapping_fields' ), 10, 3 );
		add_filter( 'wt_iew_importer_alter_meta_mapping_fields', array( $this, 'importer_alter_meta_mapping_fields' ), 10, 3 );

		add_filter( 'wt_iew_exporter_alter_mapping_enabled_fields', array( $this, 'exporter_alter_mapping_enabled_fields' ), 10, 3 );
		add_filter( 'wt_iew_importer_alter_mapping_enabled_fields', array( $this, 'exporter_alter_mapping_enabled_fields' ), 10, 3 );

		add_filter( 'wt_iew_exporter_do_export', array( $this, 'exporter_do_export' ), 10, 7 );
		add_filter( 'wt_iew_importer_do_import', array( $this, 'importer_do_import' ), 10, 8 );

		add_filter( 'wt_iew_importer_steps', array( $this, 'importer_steps' ), 10, 2 );

		add_action( 'wt_category_addon_help_content', array( $this, 'wt_category_import_export_help_content' ) );
	}//end __construct()

	/**
	 *   Altering advanced step description
	 *
	 * @param array  $steps Steps.
	 * @param string $base Base class.
	 */
	public function importer_steps( $steps, $base ) {
		if ( $this->module_base === $base ) {
			$steps['advanced']['description'] = __( 'Use advanced options from below to decide updates to existing categories, batch import count or schedule an import. You can also save the template file for future imports.' );
		}
		return $steps;
	}//end importer_steps()

	/**
	 *   Do the import process
	 *
	 * @param   array   $import_data Form data.
	 * @param string  $base Base.
	 * @param   string  $step export step.
	 * @param   array   $form_data to export type.
	 * @param   string  $selected_template_data Template.
	 * @param   integer $method_import id of export.
	 * @param   integer $batch_offset offset.
	 * @param   bool    $is_last_batch Is last.
	 *
	 * @return array
	 */
	public function importer_do_import( $import_data, $base, $step, $form_data, $selected_template_data, $method_import, $batch_offset, $is_last_batch ) {
		if ( $this->module_base !== $base ) {
			return $import_data;
		}

		if ( 0 === $batch_offset ) {
			$memory    = size_format( self::wt_let_to_num( ini_get( 'memory_limit' ) ) );
			$wp_memory = size_format( self::wt_let_to_num( WP_MEMORY_LIMIT ) );
			Wt_Import_Export_For_Woo_Logwriter::write_log( $this->module_base, 'import', '---[ New import started at ' . gmdate( 'Y-m-d H:i:s' ) . ' ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory );
		}

		include plugin_dir_path( __FILE__ ) . 'import/class-wt-import-export-for-woo-product-categories-import.php';
		$import = new Wt_Import_Export_For_Woo_Product_Categories_Import( $this );

		$response = $import->prepare_data_to_import( $import_data, $form_data, $batch_offset, $is_last_batch );

		if ( $is_last_batch ) {
			Wt_Import_Export_For_Woo_Logwriter::write_log( $this->module_base, 'import', '---[ Import ended at ' . gmdate( 'Y-m-d H:i:s' ) . ']---' );
		}

		return $response;
	}//end importer_do_import()

	/**
	 * Convert memory size to bytes
	 *
	 * @param string $size Size.
	 * @return int
	 */
	public static function wt_let_to_num( $size ) {
		$l   = substr( $size, -1 );
		$ret = (int) substr( $size, 0, -1 );
		switch ( strtoupper( $l ) ) {
			case 'P':
				$ret *= 1024;
				// No break.
			case 'T':
				$ret *= 1024;
				// No break.
			case 'G':
				$ret *= 1024;
				// No break.
			case 'M':
				$ret *= 1024;
				// No break.
			case 'K':
				$ret *= 1024;
				// No break.
		}
		return $ret;
	}//end wt_let_to_num()

	/**
	 * Export process initiate.
	 *
	 * @param array   $export_data Export data.
	 * @param string  $base Base.
	 * @param string  $step Step.
	 * @param array   $form_data Form data.
	 * @param array   $selected_template_data Template data.
	 * @param string  $method_export Method.
	 * @param integer $batch_offset Offset.
	 * @return type
	 */
	public function exporter_do_export( $export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset ) {
		if ( $this->module_base !== $base ) {
			return $export_data;
		}

		switch ( $method_export ) {
			case 'quick':
				$this->set_export_columns_for_quick_export( $form_data );
				break;

			case 'template':
			case 'new':
				$this->set_selected_column_names( $form_data );
				break;

			default:
				break;
		}

		include plugin_dir_path( __FILE__ ) . 'export/class-wt-import-export-for-woo-product-categories-export.php';
		$export = new Wt_Import_Export_For_Woo_Product_Categories_Export( $this );

		$header_row = $export->prepare_header();

		$data_row = $export->prepare_data_to_export( $form_data, $batch_offset );

		$export_data = array(
			'head_data' => $header_row,
			'body_data' => $data_row['data'],
		);

		if ( isset( $data_row['total'] ) && ! empty( $data_row['total'] ) ) {
			$export_data['total'] = $data_row['total'];
		}

		if ( isset( $data_row['no_post'] ) ) {
			$export_data['no_post'] = $data_row['no_post'];
		}

		return $export_data;
	}//end exporter_do_export()

	/**
	 * Adding current post type to export list
	 *
	 * @param array $arr Post types.
	 */
	public function wt_iew_exporter_post_types( $arr ) {
		$arr['product_categories'] = __( 'Product Categories' );
		return $arr;
	}//end wt_iew_exporter_post_types()

	/**
	 * Setting default export columns for quick export
	 *
	 * @param array $form_data Form data.
	 */
	public function set_export_columns_for_quick_export( $form_data ) {

		$post_columns = self::get_categories_post_columns();

		$this->selected_column_names = array_combine( array_keys( $post_columns ), array_keys( $post_columns ) );

		if ( isset( $form_data['method_export_form_data']['mapping_enabled_fields'] ) && ! empty( $form_data['method_export_form_data']['mapping_enabled_fields'] ) ) {
			foreach ( $form_data['method_export_form_data']['mapping_enabled_fields'] as $value ) {
				$additional_quick_export_fields[ $value ] = array( 'fields' => array() );
			}

			$export_additional_columns = $this->exporter_alter_meta_mapping_fields( $additional_quick_export_fields, $this->module_base, array() );
			foreach ( $export_additional_columns as $value ) {
				$this->selected_column_names = array_merge( $this->selected_column_names, $value['fields'] );
			}
		}
	}//end set_export_columns_for_quick_export()

	/**
	 * Get categories sort columns
	 *
	 * @return array
	 */
	public static function get_categories_sort_columns() {
		$sort_columns = array(
			'id'   => __( 'Category ID' ),
			'name' => __( 'Category name' ),
			'slug' => __( 'Category slug' ),
		);

		/**
		 * Filter the product categories sort columns
		 *
		 * @param array $sort_columns Sort columns.
		 * @return array
		 * @since 1.0.0
		 */
		return apply_filters( 'wt_iew_allowed_categories_sort_columns', $sort_columns );
	}//end get_categories_sort_columns()

	/**
	 * Get categories post columns
	 *
	 * @return array
	 */
	public static function get_categories_post_columns() {

		return include plugin_dir_path( __FILE__ ) . 'data/data-product-categories-columns.php';
	}//end get_categories_post_columns()

	/**
	 * Post columns
	 *
	 * @param array  $fields Fields.
	 * @param string $base Base.
	 * @param array  $step_page_form_data Form data.
	 * @return type
	 */
	public function get_importer_post_columns( $fields, $base, $step_page_form_data ) {

		if ( $base !== $this->module_base ) {
			return $fields;
		}
		$colunm = include plugin_dir_path( __FILE__ ) . 'data/data/data-wf-reserved-fields-pair.php';
		return $colunm;
	}//end get_importer_post_columns()

	/**
	 * Mapping Enabled fields
	 *
	 * @param array  $mapping_enabled_fields Mapping Enabled fields.
	 * @param string $base Base.
	 * @param array  $form_data_mapping_enabled_fields Mapping Enabled fields.
	 * @return int
	 */
	public function exporter_alter_mapping_enabled_fields( $mapping_enabled_fields, $base, $form_data_mapping_enabled_fields ) {

		if ( $base === $this->module_base ) {
			unset( $mapping_enabled_fields['hidden_meta'] );
		}

		return $mapping_enabled_fields;
	}//end exporter_alter_mapping_enabled_fields()

	/**
	 * Mapping Enabled fields
	 *
	 * @param array  $fields Mapping Enabled fields.
	 * @param string $base Base.
	 * @param array  $step_page_form_data Mapping Enabled fields.
	 * @return string
	 */
	public function exporter_alter_meta_mapping_fields( $fields, $base, $step_page_form_data ) {
		if ( $base !== $this->module_base ) {
			return $fields;
		}

		foreach ( $fields as $key => $value ) {
			switch ( $key ) {
				case 'meta':
					$meta_attributes        = array();
					$found_product_cat_meta = $this->wt_get_found_product_cat_meta();

					foreach ( $found_product_cat_meta as $product_meta ) {
						$fields[ $key ]['fields'][ 'meta:' . $product_meta ] = 'meta:' . $product_meta;
					}
					break;

				default:
					break;
			}
		}

		return $fields;
	}//end exporter_alter_meta_mapping_fields()

	/**
	 * Mapping Enabled fields
	 *
	 * @param array  $fields Mapping Enabled fields.
	 * @param string $base Base.
	 * @param array  $step_page_form_data Mapping Enabled fields.
	 * @return type
	 */
	public function importer_alter_meta_mapping_fields( $fields, $base, $step_page_form_data ) {
		if ( $base !== $this->module_base ) {
			return $fields;
		}
		$fields = $this->exporter_alter_meta_mapping_fields( $fields, $base, $step_page_form_data );
		$out    = array();
		foreach ( $fields as $key => $value ) {
			$value['fields'] = array_map(
				function ( $vl ) {
					return array(
						'title'       => $vl,
						'description' => $vl,
					);
				},
				$value['fields']
			);
			$out[ $key ]     = $value;
		}
		return $out;
	}//end importer_alter_meta_mapping_fields()

	/**
	 * Product meta
	 *
	 * @return array
	 */
	public function wt_get_found_product_cat_meta() {

		if ( ! empty( $this->found_product_cat_meta ) ) {
			return $this->found_product_cat_meta;
		}

		$term_args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		);
		$terms     = get_terms( $term_args );

		$term_keys = array();
		$i         = 0;
		foreach ( $terms as $term ) {
			$keys = get_term_meta( $term->term_id );
			foreach ( $keys as $key => $val ) {
				$term_keys[ $i ] = $key;
				++$i;
			}
		}
		$cat_meta_keys = array_diff( array_unique( $term_keys ), array( 'product_count_product_cat', 'order', 'display_type', 'thumbnail_id' ) );

		$this->found_product_cat_meta = $cat_meta_keys;
		return $this->found_product_cat_meta;
	}//end wt_get_found_product_cat_meta()

	/**
	 * Selected column names.
	 *
	 * @param array $full_form_data Form data.
	 * @return array
	 */
	public function set_selected_column_names( $full_form_data ) {

		if ( is_null( $this->selected_column_names ) ) {
			$this->selected_column_names = array();

			if ( isset( $full_form_data['mapping_form_data']['mapping_selected_fields'] ) && ! empty( $full_form_data['mapping_form_data']['mapping_selected_fields'] ) ) {
				$this->selected_column_names = $full_form_data['mapping_form_data']['mapping_selected_fields'];
			}
			if ( isset( $full_form_data['meta_step_form_data']['mapping_selected_fields'] ) && ! empty( $full_form_data['meta_step_form_data']['mapping_selected_fields'] ) ) {
				$export_additional_columns = $full_form_data['meta_step_form_data']['mapping_selected_fields'];
				foreach ( $export_additional_columns as $value ) {
					// Ensure $value is an array before merging.
					if ( is_array( $value ) ) {
						$this->selected_column_names = array_merge( $this->selected_column_names, $value );
					}
				}
			}
		}

		return $full_form_data;
	}//end set_selected_column_names()

	/**
	 * Selected column names
	 *
	 * @return array
	 */
	public function get_selected_column_names() {
		return $this->selected_column_names;
	}//end get_selected_column_names()

	/**
	 * Export alter mapping fields
	 *
	 * @param array  $fields Mapping Enabled fields.
	 * @param string $base Base.
	 * @param array  $mapping_form_data Mapping Enabled fields.
	 * @return type
	 */
	public function exporter_alter_mapping_fields( $fields, $base, $mapping_form_data ) {
		if ( $base !== $this->module_base ) {
			return $fields;
		}

		$fields = self::get_categories_post_columns();

		return $fields;
	}//end exporter_alter_mapping_fields()

	/**
	 * Customize the items in filter export page
	 *
	 * @param array  $fields Fields.
	 * @param string $base Base.
	 * @param array  $filter_form_data Form data.
	 * @return string
	 */
	public function exporter_alter_filter_fields( $fields, $base, $filter_form_data ) {
		if ( $this->module_base !== $base ) {
			return $fields;
		}

		$fields = array();

		$sort_columns           = self::get_categories_sort_columns();
		$fields['sort_columns'] = array(
			'label'       => __( 'Sort Columns' ),
			'placeholder' => __( 'comment_ID' ),
			'field_name'  => 'sort_columns',
			'sele_vals'   => $sort_columns,
			'help_text'   => __( 'Sort the exported data based on the selected column in the order specified. Defaulted to ascending order.' ),
			'type'        => 'select',
		);

		$fields['order_by'] = array(
			'label'       => __( 'Sort' ),
			'placeholder' => __( 'ASC' ),
			'field_name'  => 'order_by',
			'sele_vals'   => array(
				'ASC'  => 'Ascending',
				'DESC' => 'Descending',
			),
			'help_text'   => __( 'Defaulted to Ascending. Applicable to above selected columns in the order specified.' ),
			'type'        => 'select',
			'css_class'   => '',
		);

		return $fields;
	}//end exporter_alter_filter_fields()

	/**
	 * Export alter advanced fields
	 *
	 * @param array  $fields Mapping Enabled fields.
	 * @param string $base Base.
	 * @param array  $advanced_form_data Mapping Enabled fields.
	 * @return string
	 */
	public function exporter_alter_advanced_fields( $fields, $base, $advanced_form_data ) {
		if ( $this->module_base !== $base ) {
			return $fields;
		}
		unset( $fields['export_shortcode_tohtml'] );

		return $fields;
	}//end exporter_alter_advanced_fields()

	/**
	 * Alter advanced fields
	 *
	 * @param array  $fields Mapping Enabled fields.
	 * @param string $base Base.
	 * @param array  $advanced_form_data Mapping Enabled fields.
	 * @return type
	 */
	public function importer_alter_advanced_fields( $fields, $base, $advanced_form_data ) {
		if ( $this->module_base !== $base ) {
			return $fields;
		}
		$out = array();

		$out['merge'] = array(
			'label'                 => __( 'If the category exists in the store', 'wt-import-export-for-woo' ),
			'type'                  => 'radio',
			'radio_fields'          => array(
				'0' => __( 'Skip' ),
				'1' => __( 'Update' ),
			),
			'value'                 => '0',
			'field_name'            => 'merge',
			'help_text'             => __( 'Categories are matched by their ID/slugs.' ),
			'help_text_conditional' => array(
				array(
					'help_text' => __( 'Retains the categories in the store as is and skips the matching category from the input file.' ),
					'condition' => array(
						array(
							'field' => 'wt_iew_merge',
							'value' => 0,
						),
					),
				),
				array(
					'help_text' => __( 'Update category as per data from the input file' ),
					'condition' => array(
						array(
							'field' => 'wt_iew_merge',
							'value' => 1,
						),
					),
				),
			),
			'form_toggler'          => array(
				'type'   => 'parent',
				'target' => 'wt_iew_found_action',
			),
		);

		foreach ( $fields as $fieldk => $fieldv ) {
			$out[ $fieldk ] = $fieldv;
		}
		unset( $out['enable_speed_mode'] );
		return $out;
	}//end importer_alter_advanced_fields()

	/**
	 * Get item link
	 *
	 * @param type $id ID.
	 * @return type
	 */
	public function get_item_by_id( $id ) {
		$post['edit_url'] = get_edit_term_link( $id );
		$post['title']    = @get_term( $id )->name;
		return $post;
	}//end get_item_by_id()

	/**
	 * Get item link by id
	 *
	 * @param type $id ID.
	 * @return type
	 */
	public static function get_item_link_by_id( $id ) {
		$post['edit_url'] = get_edit_term_link( $id );
		$post['title']    = @get_term( $id )->name;

		return $post;
	}//end get_item_link_by_id()

	/**
	 *  Add product review import help content to help section
	 */
	public function wt_category_import_export_help_content() {
		if ( defined( 'WT_IEW_PLUGIN_ID' ) ) {
			?>
		<li>
			<img src="<?php echo esc_url( WT_IEW_PLUGIN_URL ); ?>assets/images/sample-csv.png">
			<h3><?php esc_html_e( 'Sample Categories CSV', 'text-domain' ); ?></h3>
			<p><?php esc_html_e( 'Familiarize yourself with the sample CSV.', 'text-domain' ); ?></p>
			<a target="_blank" href="<?php echo esc_url( 'https://www.webtoffee.com/wp-content/uploads/2023/04/Sample_Product_categories.csv' ); ?>" class="button button-primary">
				<?php esc_html_e( 'Get Category CSV', 'text-domain' ); ?>
			</a>
		</li>
			<?php
		}
	}//end wt_category_import_export_help_content()
}

new Wt_Import_Export_For_Woo_Product_Categories();
