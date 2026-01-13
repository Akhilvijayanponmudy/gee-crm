<?php
/**
 * Product section of the plugin
 *
 * @link          
 *
 * @package  Webtoffee_Product_Feed_Sync_Shopmania 
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Webtoffee_Product_Feed_Sync_Shopmania')) {

	class Webtoffee_Product_Feed_Sync_Shopmania {

		public $module_id = '';
		public static $module_id_static = '';
		public $module_base = 'shopmania';
		public $module_name = 'Webtoffee Product Feed Catlaog for Shopmania';
		public $min_base_version = '1.0.0'; /* Minimum `Import export plugin` required to run this add on plugin */
		private $importer = null;
		private $exporter = null;
		private $product_categories = null;
		private $product_tags = null;
		private $product_taxonomies = array();
		private $all_meta_keys = array();
		private $product_attributes = array();
		private $exclude_hidden_meta_columns = array();
		private $found_product_meta = array();
		private $found_product_hidden_meta = array();
		private $selected_column_names = null;

		public function __construct() {
			/**
			 *   Checking the minimum required version of `Import export plugin` plugin available
			 */
			if (!Webtoffee_Product_Feed_Sync_Common_Helper::check_base_version($this->module_base, $this->module_name, $this->min_base_version)) {
				return;
			}
			if (!function_exists('is_plugin_active')) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			if (!is_plugin_active('woocommerce/woocommerce.php')) {
				return;
			}

			$this->module_id = Webtoffee_Product_Feed_Sync::get_module_id($this->module_base);
			self::$module_id_static = $this->module_id;

			add_filter('wt_pf_exporter_post_types_basic', array($this, 'wt_pf_exporter_post_types_basic'), 10, 1);

			add_filter('wt_pf_exporter_alter_mapping_fields_basic', array($this, 'exporter_alter_mapping_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_advanced_fields_basic', array($this, 'exporter_alter_advanced_fields'), 10, 3);

			add_filter('wt_pf_exporter_do_export_basic', array($this, 'exporter_do_export'), 10, 7);

			add_filter('wt_pf_feed_category_mapping', array($this, 'map_google_category'), 10, 1);

            add_filter('wt_pf_exporter_steps_basic', array($this, 'wt_pf_exporter_steps_basic'), 10, 2);

            add_filter('wt_feed_product_attributes_dropdown', array($this, 'product_attributes_dropdown'), 10, 3);
				
		}


		/**
		 * Add/Remove steps in export section.
		 * @param array $steps array of built in steps
		 * @param string $base or aka $to_export product, order etc
		 * @return array $steps 
		 */
		public function wt_pf_exporter_steps_basic($steps, $to_export) {

			if ('shopmania' === $to_export) {
				unset($steps['category_mapping']);
			}
			return $steps;
		}           

		public function map_google_category($form_data) {

			if ( ( isset($form_data['post_type_form_data']['item_type']) &&  $form_data['post_type_form_data']['item_type'] != $this->module_base ) || ( isset($form_data['post_type_form_data']['wt_pf_export_post_type']) &&  $form_data['post_type_form_data']['wt_pf_export_post_type'] != $this->module_base )) {
				return $form_data;
			} else {

				foreach ($form_data['category_mapping_form_data'] as $local_cat => $merchant_cat) {
					if (!empty($merchant_cat)) {
						$term_id = absint(str_replace('cat_mapping_', '', $local_cat));
						$wt_fb_category = absint($merchant_cat);
						update_term_meta($term_id, 'wt_google_category', $wt_fb_category);
					}
				}
				return $form_data;
			}
		}



		public function exporter_do_export($export_data, $base, $step, $form_data, $selected_template_data, $method_export, $batch_offset) {
			if ($this->module_base != $base) {
				return $export_data;
			}

			$this->set_selected_column_names($form_data);

            include WT_PRODUCT_FEED_PLUGIN_PATH . '/admin/modules/export/wt-product.php';
			include plugin_dir_path(__FILE__) . 'export/export.php';
			$export = new Webtoffee_Product_Feed_Sync_Shopmania_Export($this);

			$header_row = $export->prepare_header();

			$data_row = $export->prepare_data_to_export($form_data, $batch_offset, $step);

                        $export_data = array(
				'head_data' => $header_row,
				'body_data' => $data_row['data'],
				'total' => $data_row['total'],
			);

			if (isset($data_row['no_post'])) {
				$export_data['no_post'] = $data_row['no_post'];
			}


			return $export_data;
		}

		/**
		 * Adding current post type to export list
		 *
		 */
		public function wt_pf_exporter_post_types_basic($arr) {

			$arr['shopmania'] = __('Shopmania', 'webtoffee-product-feed');
			return $arr;
		}

		/**
		 * Read txt file which contains facebook taxonomy list
		 *
		 * @return array
		 */
		public static function get_category_array() {
			// Get All Shopmania Taxonomies
					
			$taxonomy = wp_cache_get('wt_iew_feed_google_categories');

			if (false === $taxonomy) {
			
			$fileName = WT_PRODUCT_FEED_PLUGIN_PATH . '/admin/modules/google/data/google_taxonomy.txt';
			$customTaxonomyFile = fopen($fileName, 'r');  // phpcs:ignore
			$taxonomy = array();
			$taxonomy[''] = 'Do not map';
			if ($customTaxonomyFile) {
				// First line contains metadata, ignore it
				fgets($customTaxonomyFile);  // phpcs:ignore
				while ($line = fgets($customTaxonomyFile)) {  // phpcs:ignore
					list( $catId, $cat ) = explode('-', $line);
					$cat_key = absint(trim($catId));
					$cat_val = trim($cat);
					$taxonomy[$cat_key] = $cat_val;
				}
			}
				wp_cache_set('wt_iew_feed_google_categories', $taxonomy, '', WEEK_IN_SECONDS);
			}

			return $taxonomy;
		}



        public function product_attributes_dropdown($attribute_dropdown, $export_channel, $selected=''){
                    
                    
            if( 'shopmania' === $export_channel ){
                
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Name', 'Name' );
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'URL', 'URL' );                                               
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Image', 'Image' );
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Currency', 'Currency' );
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'MPC', 'MPC' );
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Manufacturer', 'Manufacturer' );                                               
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'MPN', 'MPN' );
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'GTIN', 'GTIN' );
                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Availability', 'Availability' );
                //$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Shipping', 'Shipping' );                
                
                if( $selected && strpos($selected, 'wt_static_map_vl:') !== false ){
                    $selected = 'wt-static-map-vl';
                }
                if ( $selected && strpos( $attribute_dropdown, 'value="' . $selected . '"' ) !== false ) {
                        $attribute_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"' . ' selected', $attribute_dropdown );
                }
            }

                
            return $attribute_dropdown;
        }         

		/**
		 * Get product categories
		 * @return array $categories 
		 */
		private function get_product_categories() {
			if (!is_null($this->product_categories)) {
				return $this->product_categories;
			}
			$out = array();
			$product_categories = get_terms(array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false
			));
			if (!is_wp_error($product_categories)) {
				$version = get_bloginfo('version');
				foreach ($product_categories as $category) {
					$out[$category->slug] = (( $version < '4.8') ? $category->name : get_term_parents_list($category->term_id, 'product_cat', array('separator' => ' -> ')));
				}
			}
			$this->product_categories = $out;
			return $out;
		}

		private function get_product_tags() {
			if (!is_null($this->product_tags)) {
				return $this->product_tags;
			}
			$out = array();
			$product_tags = get_terms('product_tag');
			if (!is_wp_error($product_tags)) {
				foreach ($product_tags as $tag) {
					$out[$tag->slug] = $tag->name;
				}
			}
			$this->product_tags = $out;
			return $out;
		}

		public static function get_product_statuses() {
			$product_statuses = array('publish', 'private', 'draft', 'pending', 'future');
			return apply_filters('wt_pf_allowed_product_statuses', array_combine($product_statuses, $product_statuses));
		}

		public static function get_product_post_columns() {
			return include plugin_dir_path(__FILE__) . 'data/data-product-post-columns.php';
		}



		public function set_selected_column_names($full_form_data) {

			if (is_null($this->selected_column_names)) {
				$this->selected_column_names = array();
				if (isset($full_form_data['mapping_form_data']['mapping_selected_fields']) && !empty($full_form_data['mapping_form_data']['mapping_selected_fields'])) {
					$selected_mapped_fields = array();
					foreach ($full_form_data['mapping_form_data']['mapping_selected_fields'] as $key => $value) {
						if ("" != $value) {
							$this->selected_column_names[$key] = $value;
						}
					}
				}
				if (isset($full_form_data['meta_step_form_data']['mapping_selected_fields']) && !empty($full_form_data['meta_step_form_data']['mapping_selected_fields'])) {
					$export_additional_columns = $full_form_data['meta_step_form_data']['mapping_selected_fields'];

					foreach ($export_additional_columns as $value) {
						foreach ($value as $key => $vl) {
							if ("" != $vl) {
								$this->selected_column_names[$key] = $vl;
							}
						}
					}
				}
				$this->selected_column_names = ($this->selected_column_names);
			}


			return $full_form_data;
		}

		public function get_selected_column_names() {

			return $this->selected_column_names;
		}

		public function exporter_alter_mapping_fields($fields, $base, $mapping_form_data) {
			if ($base == $this->module_base) {
				$fields = self::get_product_post_columns();
			}
			return $fields;
		}

		public function exporter_alter_advanced_fields($fields, $base, $advanced_form_data) {
			if ($this->module_base != $base) {
				return $fields;
			}
			$out = array();
			$out['header_empty_row'] = array(
				'tr_html' => '<tr id="header_empty_row"><th></th><td></td></tr>'
			);
			foreach ($fields as $fieldk => $fieldv) {
				$out[$fieldk] = $fieldv;
			}
                        if ('shopmania' === $base) {

                            $out['file_as']['sele_vals'] = array(
                                'xml'=>__('XML', 'webtoffee-product-feed'), 
                                'csv'=>__('CSV', 'webtoffee-product-feed')                                
                            );
                            $out['delimiter']['sele_vals'] = array(
                                'pipe' => array('value' => __('Pipe', 'webtoffee-product-feed'), 'val' => "|")                                
                            );
                        }

			return $out;
		}

		

public static function wt_feed_get_product_conditions() {
	$conditions = array(
		'new'           => _x( 'New', 'product condition', 'webtoffee-product-feed' ),
		'refurbished'   => _x( 'Refurbished', 'product condition', 'webtoffee-product-feed' ),
		'used'          => _x( 'Used', 'product condition', 'webtoffee-product-feed' ),
	);

	return apply_filters( 'wt_feed_google_product_conditions', $conditions );
}	

	public static function get_age_group() {
			$google_age_group = array(
				'adult' => __('Adult', 'webtoffee-product-feed'),
				'kids' => __('Kids', 'webtoffee-product-feed'),
				'toddler' => __('Toddler', 'webtoffee-product-feed'),
				'infant' => __('Infant', 'webtoffee-product-feed'),
				'newborn' => __('Newborn', 'webtoffee-product-feed')
			);
			return apply_filters( 'wt_feed_google_product_agegroup', $google_age_group );

	}
		
		

	}

}

new Webtoffee_Product_Feed_Sync_Shopmania();
