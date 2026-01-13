<?php
/**
 * Log reading section of the plugin
 *
 * @link       
 *
 * @package  Webtoffee_Product_Feed_Sync_Basic_Logreader 
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Webtoffee_Product_Feed_Sync_Basic_Logreader')){
class Webtoffee_Product_Feed_Sync_Basic_Logreader
{
	private $file_path='';
	private $file_pointer=null;
	private $mode='';
	public function __construct()
	{
		
	}
	public function init($file_path, $mode="r")
	{
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		$this->file_path=$file_path;
		$this->mode=$mode;
		// WordPress filesystem handles file operations automatically
		// No need to store file pointer
	}
	public function close_file_pointer()
	{
		// WordPress filesystem handles file operations automatically
		// No need to manually close file pointers
	}

	public function get_full_data($file_path)
	{
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		$out=array(
			'response'=>false,
			'data_str'=>'',
		);
		$this->init($file_path);
		
		if(!$wp_filesystem->exists($file_path))
		{
			return $out;
		}
		
		$data = $wp_filesystem->get_contents($file_path);
		$this->close_file_pointer();

		$out=array(
			'response'=>true,
			'data_str'=>$data,
		);
		return $out;
	}
	
	/**
	*	Read log file as batch
	*	@param 		string  	path of file to read
	*	@param 		int  		offset in bytes. default 0
	*	@param 		int  		total row in a batch. default 50
	*	@return 	array  		response, next offset, data array, finished or not flag
	*/
	public function get_data_as_batch($file_path, $offset=0, $batch_count=50)
	{
		global $wp_filesystem;
		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		$out=array(
			'response'=>false,
			'offset'=>$offset,
			'data_arr'=>array(),
			'finished'=>false, //end of file reached or not
		);
		$this->init($file_path);
		
		if(!$wp_filesystem->exists($file_path))
		{
			return $out;
		}

		$file_content = $wp_filesystem->get_contents($file_path);
		$lines = explode("\n", $file_content);
		
		$row_count=0;
		$next_offset=$offset;
		$finished=false;
		$data_arr=array();
		
		// Calculate starting line based on offset
		$start_line = 0;
		$current_offset = 0;
		foreach($lines as $line_num => $line) {
			$line_length = strlen($line) + 1; // +1 for newline
			if($current_offset + $line_length > $offset) {
				$start_line = $line_num;
				break;
			}
			$current_offset += $line_length;
		}
		
		for($i = $start_line; $i < count($lines); $i++) {
			$data = $lines[$i];
			$data = Webtoffee_Product_Feed_Sync_Common_Helper::wt_decode_data($data);
			if(is_array($data))
			{
				$data_arr[]=$data;
				$row_count++;
				$next_offset += strlen($lines[$i]) + 1; // +1 for newline
			}
			if($row_count==$batch_count)
			{
				break;
			}
		}
		
		if($next_offset >= strlen($file_content))
		{
			$finished=true;
		}
		$this->close_file_pointer();

		$out=array(
			'response'=>true,
			'offset'=>$next_offset,
			'data_arr'=>$data_arr,
			'finished'=>$finished,
		);
		return $out;
	}
}
}