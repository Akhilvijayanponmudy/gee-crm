<?php
/**
 * Product section of the plugin
 *
 * @link          
 *
 * @package  Webtoffee_Product_Feed_SyncOnBuy 
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Webtoffee_Product_Feed_Sync_OnBuy')) {

	class Webtoffee_Product_Feed_Sync_OnBuy {

		public $module_id = '';
		public static $module_id_static = '';
		public $module_base = 'onbuy';
		public $module_name = 'Webtoffee Product Feed Catlaog for OnBuy';
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

			add_filter('wt_pf_exporter_alter_filter_fields_basic', array($this, 'exporter_alter_filter_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_mapping_fields_basic', array($this, 'exporter_alter_mapping_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_advanced_fields_basic', array($this, 'exporter_alter_advanced_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_meta_mapping_fields_basic', array($this, 'exporter_alter_meta_mapping_fields'), 10, 3);

			add_filter('wt_pf_exporter_alter_mapping_enabled_fields_basic', array($this, 'exporter_alter_mapping_enabled_fields'), 10, 3);			

			add_filter('wt_pf_exporter_do_export_basic', array($this, 'exporter_do_export'), 10, 7);

			add_filter('wt_pf_feed_category_mapping', array($this, 'map_onbuy_category'), 10, 1);

			
			add_action( 'product_cat_edit_form_fields', array($this, 'wt_fbfeed_category_form_fields_pro'), 10, 1 );
			add_action( 'product_cat_add_form_fields', array($this, 'wt_fbfeed_category_form_fields_pro'), 10, 1 );

			add_action( 'edit_product_cat', array($this, 'wt_fbfeed_category_form_save_pro'), 10, 1 );
			add_action( 'create_category', array($this, 'wt_fbfeed_category_form_save_pro'), 10, 1 );
                        
                        if ( version_compare( get_bloginfo( 'version' ), '6.1', '>=' ) ) {
                            add_action('saved_product_cat', array($this, 'wt_fbfeed_category_form_save_pro_ajx_new'), 10, 4);
                        }else{
                            add_action('saved_product_cat', array($this, 'wt_fbfeed_category_form_save_pro_ajx'), 10, 3);
                        }
                        
                        add_filter('wt_feed_product_attributes_dropdown', array($this, 'product_attributes_dropdown'), 10, 3);
				
		}

		
		
		
		
		public function wt_fbfeed_category_form_fields_pro( $category ) {


	$fb_category_id = '';
	if ( current_filter() == 'product_cat_edit_form_fields' ) {
		$fb_category_id = get_term_meta( $category->term_id, 'wt_onbuy_category', true );
	}
	?>

		<tr class="form-field">
                <style>
                    .select2-container{
                        width:100% !important;
                    }
                </style>
			<th scope="row" valign="top"><label for="wt_onbuy_category"><?php esc_html_e('OnBuy Category', 'webtoffee-product-feed'); ?></label></th>
			<td>
                            <select name="wt_onbuy_category" class="wc-enhanced-select">
		<?php 
		$allowed_html = array(
			'option' => array(
				'value'    => true,
				'selected' => true,
			),
		);
		echo wp_kses( wt_onbuy_category_dropdown( $fb_category_id ), $allowed_html ); 
		?>
				</select>

				<p class="description"><?php esc_html_e('The OnBuy Category corresponding to this category in the website.', 'webtoffee-product-feed')?>
				</p>
			</td>
		</tr>
		<input type="hidden" name="wt_category_edit_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wt_category_edit_nonce' ) ); ?>" />

		<?php
	}
	
	public function wt_fbfeed_category_form_save_pro( $term_id ) {



	if ( isset( $_POST[ 'wt_onbuy_category' ] ) ) {
		if(! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['wt_category_edit_nonce'] ?? '')), 'wt_category_edit_nonce' )){ 
			return false;
		}

		$wt_onbuy_category = absint( $_POST[ 'wt_onbuy_category' ] ); //phpcs:ignore 
		if(0 == $wt_onbuy_category){
			delete_term_meta($term_id, 'wt_onbuy_category');
		}else{
			update_term_meta( $term_id, 'wt_onbuy_category', $wt_onbuy_category );
		}
	}
       
        }
        public function wt_fbfeed_category_form_save_pro_ajx_new( $term_id, $tt_id, $update, $args ) {

            if ( isset( $args[ 'wt_onbuy_category' ] ) ) {
                        if(! wp_verify_nonce( wp_unslash(sanitize_text_field($args['wt_category_edit_nonce'] ?? '')), 'wt_category_edit_nonce' )){
                                return false;
                        }

                        $wt_onbuy_category = absint( $args[ 'wt_onbuy_category' ] );
                        if(0 == $wt_onbuy_category){
                                delete_term_meta($term_id, 'wt_onbuy_category');
                        }else{
                                update_term_meta( $term_id, 'wt_onbuy_category', $wt_onbuy_category );
                        }
                }

        }
        public function wt_fbfeed_category_form_save_pro_ajx( $term_id, $tt_id, $update ) {

            if ( isset( $_POST[ 'wt_onbuy_category' ] ) ) {
                        if(! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['wt_category_edit_nonce'] ?? '')), 'wt_category_edit_nonce' )){ 
                                return false;
                        }

                        $wt_onbuy_category = absint( $_POST[ 'wt_onbuy_category' ] ); //phpcs:ignore
                        if(0 == $wt_onbuy_category){
                                delete_term_meta($term_id, 'wt_onbuy_category');
                        }else{
                                update_term_meta( $term_id, 'wt_onbuy_category', $wt_onbuy_category );
                        }
                }

        }        
		
		public function map_onbuy_category($form_data) {

			if ( ( isset($form_data['post_type_form_data']['item_type']) &&  $form_data['post_type_form_data']['item_type'] != $this->module_base ) || ( isset($form_data['post_type_form_data']['wt_pf_export_post_type']) &&  $form_data['post_type_form_data']['wt_pf_export_post_type'] != $this->module_base )) {
				return $form_data;
			} else {

				foreach ($form_data['category_mapping_form_data'] as $local_cat => $merchant_cat) {
					if (!empty($merchant_cat)) {
						$term_id = absint(str_replace('cat_mapping_', '', $local_cat));
						$wt_fb_category = absint($merchant_cat);
						update_term_meta($term_id, 'wt_onbuy_category', $wt_fb_category);
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
			$export = new Webtoffee_Product_Feed_Sync_OnBuy_Export($this);

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

			$arr['onbuy'] = __('OnBuy', 'webtoffee-product-feed');
			return $arr;
		}

		/**
		 * Read txt file which contains facebook taxonomy list
		 *
		 * @return array
		 */
		public static function get_category_array() {
			// Get All OnBuy Taxonomies
					
			$taxonomy = wp_cache_get('wt_iew_feed_onbuy_categories');

			if (false === $taxonomy) {
			
			$fileName = WT_PRODUCT_FEED_PLUGIN_PATH . '/admin/modules/onbuy/data/onbuy_taxonomy.txt';
			$customTaxonomyFile = fopen($fileName, 'r');  // phpcs:ignore
			$taxonomy = array();
			$taxonomy[''] = 'Do not map';
			if ($customTaxonomyFile) {
				// First line contains metadata, ignore it
				fgets($customTaxonomyFile);  // phpcs:ignore
				while ($line = fgets($customTaxonomyFile)) {  // phpcs:ignore
					list( $catId, $cat ) = explode('--', $line);
					$cat_key = absint(trim($catId));
					$cat_val = trim($cat);
					$taxonomy[$cat_key] = $cat_val;
				}
			}
				wp_cache_set('wt_iew_feed_onbuy_categories', $taxonomy, '', WEEK_IN_SECONDS);
			}

			return $taxonomy;
		}
                
                public function product_attributes_dropdown($attribute_dropdown, $export_channel, $selected = '') {

                    if ('onbuy' === $export_channel) {

                        //$attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'onbuy_product_category', 'OnBuy Product Category[onbuy_product_category]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Default_Image', 'Main Image[Default_Image]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'SKU', 'sku[SKU]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Product_Name', 'Product Title[Product_Name]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Stock', 'StocK[Stock]' );
                        
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_One', 'Additional Image 1 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Two', 'Additional Image 2 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Three', 'Additional Image 3 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Four', 'Additional Image 4 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Five', 'Additional Image 5 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Six', 'Additional Image 6 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Seven', 'Additional Image 7 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Eight', 'Additional Image 8 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Nine', 'Additional Image 9 [additional_image_link]' );
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Additional_images_Ten', 'Additional Image 10 [additional_image_link]' );                        
                        
                        $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', 'Parent_Group', 'Parent_Group [Parent_Group]' );                        
                        
                        $max_variations = $this->get_max_variations_count_for_all_variable_products();

                        if( $max_variations > 0 ){                            
                            for($i=1; $i<=$max_variations; $i++){
                                $en_number = $this->numToWords($i);                                
                                $attr_name = 'Variant_'.$en_number.'_Name';
                                $attr_val = 'Variant_'.$en_number.'_Value';
                                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', $attr_name, $attr_name );  
                                $attribute_dropdown .= sprintf( '<option value="%s">%s</option>', $attr_val, $attr_val );                                
                            }
                        }
                        
                        if ($selected && strpos($selected, 'wt_static_map_vl:') !== false) {
                            $selected = 'wt-static-map-vl';
                        }
                        if ($selected && strpos($attribute_dropdown, 'value="' . $selected . '"') !== false) {
                            $attribute_dropdown = str_replace('value="' . $selected . '"', 'value="' . $selected . '"' . ' selected', $attribute_dropdown);
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

		public function exporter_alter_mapping_enabled_fields($mapping_enabled_fields, $base, $form_data_mapping_enabled_fields) {
			if ($base === $this->module_base) {
				$mapping_enabled_fields = array();
			$mapping_enabled_fields['listing_details'] = array(__('Listing Details', 'webtoffee-product-feed'), 1);
			$mapping_enabled_fields['recommended_details'] = array(__('Recommended Details', 'webtoffee-product-feed'), 1);
			$mapping_enabled_fields['variation_data'] = array(__('Variation Data', 'webtoffee-product-feed'), 1);
			$mapping_enabled_fields['features'] = array(__('Features', 'webtoffee-product-feed'), 1);
			$mapping_enabled_fields['additional_product_data'] = array(__('Additional product data', 'webtoffee-product-feed'), 1);
			$mapping_enabled_fields['optional_listing_details'] = array(__('Optional Listing Details', 'webtoffee-product-feed'), 1);
								
			}
			return $mapping_enabled_fields;
		}

		public function exporter_alter_meta_mapping_fields($fields, $base, $step_page_form_data) {
			if ($base != $this->module_base) {
				return $fields;
			}
			foreach ($fields as $key => $value) {
				switch ($key) {
					case 'listing_details':
						$fields[$key]['fields']['Price'] = 'Price';					
						$fields[$key]['fields']['Stock'] = 'Stock';
						$fields[$key]['fields']['Handling_Time'] = 'Handling_Time';
						$fields[$key]['fields']['Shipping_Template_Id'] = 'Shipping_Template_Id';
						$fields[$key]['fields']['Shipping_Weight_(Kg)'] = 'Shipping_Weight_(Kg)';
						$fields[$key]['fields']['Warranty (Months)'] = 'Warranty (Months)';
						$fields[$key]['fields']['Free Returns'] = 'Free Returns';
						break;

					case 'recommended_details':
						
						$fields[$key]['fields']['ASIN'] = 'ASIN';
						$fields[$key]['fields']['MPN'] = 'MPN';
                                                $fields[$key]['fields']['RRP'] = 'RRP';
						break;

					case 'variation_data':

						$fields[$key]['fields']['Parent_Group'] = 'Item Group Id[Parent_Group]';
                                                $max_variations = $this->get_max_variations_count_for_all_variable_products();

                                                if( $max_variations > 0 ){                            
                                                    for($i=1; $i<=$max_variations; $i++){
                                                        $en_number = $this->numToWords($i);
                                                        $fields[$key]['fields']['Variant_'.$en_number.'_Name'] = 'Variant_'.$en_number.'_Name';                                
                                                        $fields[$key]['fields']['Variant_'.$en_number.'_Value'] = 'Variant_'.$en_number.'_Value';                               
                                                    }
                                                }                                                
						
						break;

					case 'features':
						$fields[$key]['fields']['Clothing Size'] = 'Clothing Size';
						$fields[$key]['fields']['Colour'] = 'Colour';
						
						break;

					case 'additional_product_data':
                                            
                                            
                                            
                                                for($i=1; $i<=5; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Summary_Point_'.$en_number] = 'Summary_Point_'.$en_number;                                                                                    
                                                }                                            
                                                
                                                for($i=1; $i<=10; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Additional_images_'.$en_number] = 'Additional Image '.$i.' [additional_image_link]';                                                                                    
                                                }
                                                
                                                for($i=1; $i<=10; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Data_'.$en_number.'_Name'] = 'Data_'.$en_number.'_Name';                                
                                                    $fields[$key]['fields']['Data_'.$en_number.'_Value'] = 'Data_'.$en_number.'_Value';                               
                                                }
                                                
                                                for($i=1; $i<=10; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Data_'.$en_number.'_Group'] = 'Data_'.$en_number.'_Group';                                                                                    
                                                }
                                                
                                                for($i=1; $i<=2; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Video_'.$en_number] = 'Video_'.$en_number;                                
                                                    $fields[$key]['fields']['Video_'.$en_number.'_Name'] = 'Video_'.$en_number.'_Name';                               
                                                }  
                                                
                                                for($i=1; $i<=2; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Attachment_'.$en_number] = 'Attachment_'.$en_number;                                
                                                    $fields[$key]['fields']['Attachment_'.$en_number.'_Name'] = 'Attachment_'.$en_number.'_Name';                               
                                                }                                                
                                                
						break;

					case 'optional_listing_details':
						for($i=1; $i<=5; $i++){
                                                    $en_number = $this->numToWords($i);
                                                    $fields[$key]['fields']['Condition_Note_'.$en_number] = 'Condition_Note_'.$en_number;                                                                                    
                                                }
						break;					

					default:
						break;
				}
			}

			return $fields;
		}
                
        public function numToWords($number) {
                $units = array('', 'One', 'Two', 'Three', 'Four',
                    'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten');

                $tens = array('', 'ten', 'twenty', 'thirty', 'forty',
                    'fifty', 'sixty', 'seventy', 'eighty',
                    'ninety');

                $special = array('eleven', 'twelve', 'thirteen',
                    'fourteen', 'fifteen', 'sixteen',
                    'seventeen', 'eighteen', 'nineteen');

                $words = '';
                if ($number <= 10) {
                    $words .= $units[$number];
                } elseif ($number < 20) {
                    $words .= $special[$number - 11];
                } else {
                    $words .= $tens[(int) ($number / 10)] . ' '
                            . $units[$number % 10];
                }

                return $words;
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
                        if ('onbuy' === $base) {

                            $out['file_as']['sele_vals'] = array( 
                                'csv'=>__('CSV', 'webtoffee-product-feed'),    
                                'xlsx'=>__('XLSX', 'webtoffee-product-feed'),
                                'xml'=>__('XML', 'webtoffee-product-feed'),
                            );
                            $out['delimiter']['sele_vals'] = array(                                
                                'comma' => array('value' => __('Comma', 'webtoffee-product-feed'), 'val' => ",")
                            );
                        }

			return $out;
		}

		
                
                public function get_max_variations_count_for_all_variable_products(){
                    global $wpdb;

                    $query = $wpdb->prepare("
                        SELECT MAX(variation_count) as max_variation_count
                        FROM (
                            SELECT COUNT(p.ID) as variation_count
                            FROM {$wpdb->prefix}posts as p
                            WHERE p.post_type = %s
                            AND p.post_status = %s
                            GROUP BY p.post_parent
                        ) as variation_counts
                    ", 'product_variation', 'publish');

   
                    $result = $wpdb->get_results($query); //phpcs:ignore 
                    if ($result) {
                        return $result[0]->max_variation_count;
                    }

                    return 0;
                }                
                
		/**
		 *  Customize the items in filter export page
		 */
		public function exporter_alter_filter_fields($fields, $base, $filter_form_data) {
			if ($this->module_base != $base) {
				return $fields;
			}

			/* altering help text of default fields */
			$fields['limit']['label'] = __('Total number of products to export', 'webtoffee-product-feed');
			$fields['limit']['help_text'] = __('Exports specified number of products. e.g. Entering 500 with a skip count of 10 will export products from 11th to 510th position.', 'webtoffee-product-feed');
			$fields['offset']['label'] = __('Skip first <i>n</i> products', 'webtoffee-product-feed');
			$fields['offset']['help_text'] = __('Skips specified number of products from the beginning of the database. e.g. Enter 10 to skip first 10 products from export.', 'webtoffee-product-feed');

			$fields['product'] = array(
				'label' => __('Products', 'webtoffee-product-feed'),
				'placeholder' => __('All products', 'webtoffee-product-feed'),
				'attr' => array('data-exclude_type' => 'variable,variation'),
				'field_name' => 'product',
				'sele_vals' => array(),
				'help_text' => __('Export specific products. Keyin the product names to export multiple products.', 'webtoffee-product-feed'),
				'type' => 'multi_select',
				'css_class' => 'wc-product-search',
				'validation_rule' => array('type' => 'text_arr')
			);
			$fields['stock_status'] = array(
				'label' => __('Stock status', 'webtoffee-product-feed'),
				'placeholder' => __('All status', 'webtoffee-product-feed'),
				'field_name' => 'stock_status',
				'sele_vals' => array('' => __('All status', 'webtoffee-product-feed'), 'instock' => __('In Stock', 'webtoffee-product-feed'), 'outofstock' => __('Out of Stock', 'webtoffee-product-feed'), 'onbackorder' => __('On backorder', 'webtoffee-product-feed')),
				'help_text' => __('Export products based on stock status.', 'webtoffee-product-feed'),
				'type' => 'select',
				'validation_rule' => array('type' => 'text_arr')
			);
			$fields['exclude_product'] = array(
				'label' => __('Exclude products', 'webtoffee-product-feed'),
				'placeholder' => __('Exclude products', 'webtoffee-product-feed'),
				'attr' => array('data-exclude_type' => 'variable,variation'),
				'field_name' => 'exclude_product',
				'sele_vals' => array(),
				'help_text' => __('Use this if you need to exclude a specific or multiple products from your export list.', 'webtoffee-product-feed'),
				'type' => 'multi_select',
				'css_class' => 'wc-product-search',
				'validation_rule' => array('type' => 'text_arr')
			);

			$fields['product_categories'] = array(
				'label' => __('Product categories', 'webtoffee-product-feed'),
				'placeholder' => __('Any category', 'webtoffee-product-feed'),
				'field_name' => 'product_categories',
				'sele_vals' => $this->get_product_categories(),
				'help_text' => __('Export products belonging to a particular or from multiple categories. Just select the respective categories.', 'webtoffee-product-feed'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array('type' => 'sanitize_title_with_dashes_arr')
			);

			$fields['product_tags'] = array(
				'label' => __('Product tags', 'webtoffee-product-feed'),
				'placeholder' => __('Any tag', 'webtoffee-product-feed'),
				'field_name' => 'product_tags',
				'sele_vals' => $this->get_product_tags(),
				'help_text' => __('Enter the product tags to export only the respective products that have been tagged accordingly.', 'webtoffee-product-feed'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array('type' => 'sanitize_title_with_dashes_arr')
			);

			$fields['product_status'] = array(
				'label' => __('Product status', 'webtoffee-product-feed'),
				'placeholder' => __('Any status', 'webtoffee-product-feed'),
				'field_name' => 'product_status',
				'sele_vals' => self::get_product_statuses(),
				'help_text' => __('Filter products by their status.', 'webtoffee-product-feed'),
				'type' => 'multi_select',
				'css_class' => 'wc-enhanced-select',
				'validation_rule' => array('type' => 'text_arr')
			);

			return $fields;
		}		
		
		

public static function wt_feed_get_product_conditions() {
	$conditions = array(
		'new'           => _x( 'New', 'product condition', 'webtoffee-product-feed' ),
		'refurbished'   => _x( 'Refurbished', 'product condition', 'webtoffee-product-feed' ),
		'used'          => _x( 'Used', 'product condition', 'webtoffee-product-feed' ),
	);

	return apply_filters( 'wt_feed_onbuy_product_conditions', $conditions );
}	

	public static function get_age_group() {
			$onbuy_age_group = array(
				'adult' => __('Adult', 'webtoffee-product-feed'),
				'kids' => __('Kids', 'webtoffee-product-feed'),
				'toddler' => __('Toddler', 'webtoffee-product-feed'),
				'infant' => __('Infant', 'webtoffee-product-feed'),
				'newborn' => __('Newborn', 'webtoffee-product-feed')
			);
			return apply_filters( 'wt_feed_onbuy_product_agegroup', $onbuy_age_group );

	}
		
		

	}

}

new Webtoffee_Product_Feed_Sync_OnBuy();

                        // FB Category dropdown caching
			if (!function_exists('wt_onbuy_category_dropdown')) {

				function wt_onbuy_category_dropdown($selected = '') {

					$category_dropdown = wp_cache_get('wt_onbuyfeed_dropdown_product_categories');

					if (false === $category_dropdown) {
						$categories = Webtoffee_Product_Feed_Sync_OnBuy::get_category_array();

						# Primary Attributes
						$category_dropdown = '';

						foreach ($categories as $key => $value) {
							$category_dropdown .= sprintf('<option value="%s">%s</option>', $key, $value);
						}

						wp_cache_set('wt_onbuyfeed_dropdown_product_categories', $category_dropdown, '', WEEK_IN_SECONDS);
					}


					if ($selected && strpos($category_dropdown, 'value="' . $selected . '"') !== false) {
						$category_dropdown = str_replace('value="' . $selected . '"', 'value="' . $selected . '"' . ' selected', $category_dropdown);
					}



					return $category_dropdown;
				}

			}