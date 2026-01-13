<?php
/**
 * XML writing section of the plugin
 *
 * @link           
 *
 * @package  Webtoffee_Product_Feed_Sync_Basic_Xmlwriter 
 */
if (!defined('ABSPATH')) {
    exit;
}
class Webtoffee_Product_Feed_Sync_Basic_Xmlwriter extends XMLWriter
{
	public $file_path='';
	public $data_ar='';
	public $to_export='item';
    public $export_data=array();
    public $head_data=array();
    public $to_export_channel = 'google';
    public $temp_file_path='';
    public $form_data=array();

	public function __construct($file_path)
	{
		$this->file_path=$file_path;
        $this->temp_file_path = $file_path . '.tmp';
	}
    public function write_to_file($export_data, $offset, $is_last_offset, $to_export)
    {       

        $to_export = apply_filters('wt_feed_xml_writer_items_node',$to_export);
        
        $item_key = 'item';
        
        if( 'google_product_reviews' === $to_export ){
            $item_key = 'review';
        }
        if( 'skroutz' === $to_export || 'vivino' === $to_export ){
            $item_key = 'product'; // skroutz
        } 
        if( 'fruugo' === $to_export ){
            $item_key = 'Product';
        } 
        if( 'heureka' === $to_export ){
            $item_key = 'SHOPITEM';
        }    
        if( 'yandex' === $to_export ){
            $item_key = 'dummyoffer';
        }        
        
        $this->to_export_channel = $to_export;        
	    $this->to_export = $item_key;
        $this->export_data=$export_data;
        $this->head_data=$export_data['head_data'];
        $file_path=$this->file_path;

        // Handle initial offset - create temporary file
        if($offset==0){
            // Clean up any existing temporary file
            if(file_exists($this->temp_file_path)){
                wp_delete_file($this->temp_file_path);
            }
        }

        $this->openMemory();
        $this->setIndent(TRUE);
        $xml_version = '1.0';
        $xml_encoding = 'UTF-8';
        //$xml_standalone = 'no';

        /* write array data to xml */
        if(!empty($export_data['body_data'])){
            $this->array_to_xml($this, $this->to_export, $export_data['body_data'], null);
        }

        if($is_last_offset)
        {
            $prev_body_xml_data = '';
            $body_xml_data=$this->outputMemory(); //taking current offset data
            $this->endDocument();
            
            /* need this checking because, if only single batch exists */
            if(file_exists($this->temp_file_path) && $offset!=0)
            {
                global $wp_filesystem;
                if (empty($wp_filesystem)) {
                    require_once ABSPATH . '/wp-admin/includes/file.php';
                    WP_Filesystem();
                }
                if ( $wp_filesystem && $wp_filesystem->exists( $this->temp_file_path ) ) {
                    $prev_body_xml_data = $wp_filesystem->get_contents( $this->temp_file_path );
                } else {
                    $prev_body_xml_data = '';
                }
            }
            
            /* create xml starting tag */
            $this->startDocument($xml_version, $xml_encoding /*, $xml_standalone*/);
            $doc_xml_data=$this->outputMemory(); //taking xml starting data
            $this->endDocument();
            
			$site_name = get_bloginfo('name');
			$site_url = get_site_url();
                        $feed_description = apply_filters( 'wt_feed_xml_head_description', 'WebToffee Product Feed - This product feed is generated with the WebToffee Product Feed. For support queries check out https://www.webtoffee.com/support or e-mail to: support@webtoffee.com' );
			$xml_start_data = '<rss xmlns:g="http://base.google.com/ns/1.0" xmlns:c="http://base.google.com/cns/1.0" version="2.0">
<channel>
<title>
<![CDATA[ '.$site_name.' ]]>
</title>
<link><![CDATA[ '.$site_url.'  ]]></link>
<description><![CDATA[ '.$feed_description.' ]]></description>
';
			$xml_end_data = '</channel></rss>';
                        
if ('review' === $this->to_export) {
    $fav_icon_url = get_site_icon_url();
                $xml_start_data = '<feed xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:noNamespaceSchemaLocation=
 "http://www.google.com/shopping/reviews/schema/product/2.3/product_reviews.xsd">
 <version>2.3</version>
    <aggregator>
        <name>Reviews Aggregator</name>
    </aggregator>
    <publisher>
        <name><![CDATA[ '.$site_name.' ]]></name>
        <favicon><![CDATA[ '.$fav_icon_url.'  ]]></favicon>
    </publisher>
<reviews>';
                $xml_end_data = '</reviews></feed>';
}


if ('product' === $this->to_export) {
    $fav_icon_url = get_site_icon_url();
                $xml_start_data = '<mywebstore>
   <created_at>'.gmdate('Y-m-d H:i').'</created_at>
<products>';
                $xml_end_data = '</products></mywebstore>';
}

            if ('fruugo' === $this->to_export_channel) {
                $xml_start_data = '<Products>';
                $xml_end_data = '</Products>';
            }
            if ('heureka' === $this->to_export_channel) {
                $xml_start_data = '<SHOP>';
                $xml_end_data = '</SHOP>';
            }            

            
            if ('yandex' === $this->to_export_channel) {
                
                if(!empty($this->form_data) && isset($this->form_data['post_type_form_data']['wt_pf_inc_exc_category'])){
                    $categories = array();
                    foreach ($this->form_data['post_type_form_data']['wt_pf_inc_exc_category'] as $key => $cat_slug){
                        $categories[] = get_term_by('slug', $cat_slug, 'product_cat');
                    }                   
                }else{

                    $args = array(
                        'taxonomy' => 'product_cat',
                        'get' => 'all'
                    );
                    $categories = get_categories($args);
                }
                
                $category_node = '';
                if(!empty($categories)){
                    foreach ($categories as $category) {
                        $category_node.='<category id="'.$category->term_id.'">'.$category->name.'</category>';
                    }
                }
                
                $currency = get_woocommerce_currency();
                $xml_start_data = '<yml_catalog date="2020-11-22T14:37:38+03:00">
    <shop>
        <name><![CDATA[ '.$site_name.' ]]></name>
        <company><![CDATA[ '.$site_name.' ]]></company>
        <url><![CDATA[ '.$site_url.'  ]]></url>
        <currencies>
            <currency id="'.$currency.'" rate="1"/>
        </currencies>
        <categories>
        '.$category_node.'
        </categories>
        <offers>';
                $xml_end_data = '</offers></shop></yml_catalog>';
            }            
            
            if ('vivino' === $this->to_export_channel) {
                $fav_icon_url = get_site_icon_url();
                            $xml_start_data = '<vivino-product-list>
<meta-data><feed-generation-date>'.gmdate('Y-m-d H:i').'</feed-generation-date>
</meta-data>';
                            $xml_end_data = '</vivino-product-list>';
            }
            /* creating xml doc data */
            $xml_data=$doc_xml_data.$xml_start_data.$prev_body_xml_data.$body_xml_data.$xml_end_data;

            $xml_data = str_replace( ['<dummyoffer>', '</dummyoffer>'],['', ''] , $xml_data);
            
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            // Write to temporary file first
            $wp_filesystem->put_contents($this->temp_file_path, $xml_data);  //writing the full xml data to temporary file
            
            // Replace original file with temporary file
            if($wp_filesystem->exists($this->temp_file_path)) {
                // Delete original file if it exists
                if($wp_filesystem->exists($file_path)) {
                    $wp_filesystem->delete($file_path);
                }
                // Move temp file to final location
                $wp_filesystem->move($this->temp_file_path, $file_path);
            }

        }else //append data to file
        {
            $xml_data=$this->outputMemory(); //taking xml starting data
            $this->endDocument();
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            if($offset==0)
            {
                $wp_filesystem->put_contents($this->temp_file_path, $xml_data);
            }else
            {
                $existing_content = $wp_filesystem->get_contents($this->temp_file_path);
                $wp_filesystem->put_contents($this->temp_file_path, $existing_content . $xml_data);
            }
        }
    }

    public function start_attr(&$xml_writer, $key)
    {       
        $xml_writer->startAttribute($key);
    }

    public function start_elm(&$xml_writer, $key)
    {   
		if('item' !== $key && 'review' !== $this->to_export && 'product' !== $this->to_export && 'label' !== $key && 'value' !== $key  && 'fruugo' !== $this->to_export_channel  && 'heureka' !== $this->to_export_channel && 'yandex' !== $this->to_export_channel && 'vivino' !== $this->to_export_channel ){
			$key = 'g:'.sanitize_title($key);
		}

        $xml_writer->startElement($key);
    }

    public function write_elm(&$xml_writer, $key, $value)
    {        
		// Check if google feed if needed - As of now facebook also uses the g: attr in the XML feed.
		if (strpos($key, 'wtimages_') !== false) {
			$key = 'additional_image_link';
		}
                if('review' === $this->to_export || 'product' === $this->to_export || 'label' === $key || 'value' === $key ){
                    $gkey = sanitize_title($key);
                }else{
                    $gkey = 'g:'.sanitize_title($key);
                }
                if( 'fruugo' === $this->to_export_channel || 'heureka' === $this->to_export_channel || 'pinterest_rss' === $this->to_export_channel || 'vivino' === $this->to_export_channel || 'yandex' === $this->to_export_channel  ){
                    $gkey = $key;
                }                
        $xml_writer->writeElement($gkey, $value);
    }

	public function array_to_xml($xml_writer, $element_key, $element_value = array(), $xmlnsurl = NULL)
	{		
        if(!empty($xmlnsurl))
        {
            $my_root_tag = $element_key;
            $xml_writer->startElementNS(null, $element_key, $xmlnsurl);
        }else
        {
            $my_root_tag = '';
        }

        if(is_array($element_value))
        {
            //handle attributes
            if('@attributes' === $element_key)
            {
                foreach ($element_value as $attribute_key => $attribute_value)
                {
                    $this->start_attr($xml_writer, $attribute_key);
                    $xml_writer->text($attribute_value);
                    $xml_writer->endAttribute();
                }
                return;
            }

            //handle order elements
            if(is_numeric(key($element_value)))
            {
                foreach($element_value as $child_element_key => $child_element_value)
                {
                    if($element_key !== $my_root_tag)
                    {						
                        $this->start_elm($xml_writer, $element_key);
                    }
                    foreach ($child_element_value as $sibling_element_key => $sibling_element_value)
                    {
                        $this->array_to_xml($xml_writer, $sibling_element_key, $sibling_element_value);
                    }
                    $xml_writer->endElement();
                }
            }else
            {
                $element_key = apply_filters('wt_feed_alter_export_xml_tags', $element_key);              
                if($element_key !== $my_root_tag)
                {
                    $this->start_elm($xml_writer, $element_key);
                }
                foreach ($element_value as $child_element_key => $child_element_value)
                {
                    $this->array_to_xml($xml_writer, $child_element_key, $child_element_value);
                }
                $xml_writer->endElement();
            }
        }else
        {
            //handle single elements
            if('@value' == $element_key)
            {
                $xml_writer->text($element_value);
            }else
            {
                if(empty($element_value) || null === $element_value ){
                    if($element_key === 'VATRate') {
                        $element_value = '0'; // Ensure zero is written for empty VATRate
                    } else {
                        return;
                    }
                }
                //wrap element in CDATA tag if it contain illegal characters
                if( ( null !== $element_value && !empty($element_value) && false !== strpos($element_value, '<') || false !== strpos($element_value, '>') || apply_filters('wt_iew_xml_node_wrap_cdata', false, $element_value) ) &&  'ratings' !== $element_key )
                { 
                    $arr = explode(':', $element_key); 
                    if(isset($arr[1]))
                    {
                        $xml_writer->startElementNS($arr[0],$arr[1],$arr[0]);
                    }else
                    {
                        $this->start_elm($xml_writer, $element_key);
                    }                    
                    $xml_writer->writeCdata($element_value);
                    $xml_writer->endElement();
                    
                }else
                {
                    // Write full namespaced element tag using xmlns
                    $arr = explode(':', $element_key);
                    if(count($arr) > 1)
                    {
                      	$xml_writer->writeElementNS($arr[0], sanitize_title($arr[1]), $arr[0], $element_value);  
                    }else
                    {
                        $this->write_elm($xml_writer, $element_key, $element_value);                        
                    }
                }
            }
            return;
        }
    }
}
