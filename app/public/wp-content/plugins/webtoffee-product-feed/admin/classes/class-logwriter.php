<?php
/**
 * Log writing section of the plugin
 *
 * @link       
 *
 * @package  Webtoffee_Product_Feed_Sync_Basic_Logwriter 
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Webtoffee_Product_Feed_Sync_Basic_Logwriter')){
class Webtoffee_Product_Feed_Sync_Basic_Logwriter extends Webtoffee_Product_Feed_Sync_Basic_Log
{
	private static $file_path='';
	private static $file_pointer=null;
	private static $mode='';
	public function __construct()
	{
		
	}
	public static function init($file_path, $mode="a+")
	{
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		self::$file_path=$file_path;
		self::$mode=$mode;
		// WordPress filesystem handles file operations automatically
		// No need to store file pointer
	}
	public static function write_row($text, $is_writing_finished=false)
	{
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		if(empty(self::$file_path))
		{
			return;
		}
		
		$existing_content = $wp_filesystem->get_contents(self::$file_path);
		$wp_filesystem->put_contents(self::$file_path, $existing_content . $text . PHP_EOL);
		
		if($is_writing_finished)
		{
			self::close_file_pointer();
		}
	}
	public static function close_file_pointer()
	{
		// WordPress filesystem handles file operations automatically
		// No need to manually close file pointers
	}
	
	/**
	*	Debug log writing function
	*	@param string 	$post_type 		post type
	*	@param string 	$action_type	action type
	*	@param mixed 	$data			array/string of data to write
	*/
	public static function write_log($post_type, $action_type, $data)
	{
		/**
		*	Checks log file created for the current day
		*/
		$old_file_name=self::check_log_exists_for_entry(self::$history_id);
		if(!$old_file_name) 
		{
			$file_name=self::generate_file_name($post_type, $action_type, self::$history_id);
		}else
		{
			$file_name=$old_file_name;
		}
		$file_path=self::get_file_path($file_name);
		self::init($file_path);
		$date_string=date_i18n('m-d-Y @ H:i:s');
		if(is_array($data))
		{
			foreach ($data as $value) 
			{
				self::write_row($date_string." - ".maybe_serialize($value));
			}
		}else
		{
			self::write_row($date_string." - ".$data);	
		}
	}

	/**
	*	Import response log
	*	@param array 	$data		array/string of import response data
	*	@param string 	$file_path	import log file
	*/
	public static function write_import_log($data_arr, $file_path)
	{
		self::init($file_path);
		foreach($data_arr as $key => $data)
		{
			self::write_row(maybe_serialize($data));
		}
		self::close_file_pointer();
	}
}
}