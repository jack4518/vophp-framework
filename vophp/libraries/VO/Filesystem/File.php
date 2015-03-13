<?php
/**
 * 定义  VO_Filesystem_File 文件处理类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-24
 **/

defined('VOPHP') or die('Restricted access');

require_once(VO_LIB_DIR .  DS . 'Filesystem' . DS . 'Folder.php');
class VO_Filesystem_File extends VO_Filesystem_Folder{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Filesystem_File
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Filesystem_File ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 取得文件扩展名
	 *
	 * @param   string  $file  文件名
	 * @return　string  文件的扩展名
	 */
	public static function getExt($file) {
		$dot = strrpos($file, '.') + 1;
		return substr($file, $dot);
	}

	/**
	 * 去除文件的扩展名
	 *
	 * @param   string  $file 文件名称
	 * @return　string  string 去除扩展名后的字符串
	 */
	public static function stripExt($file) {
		return preg_replace('#\.[^.]*$#', '', $file);
	}

	/**
	 * 设置安全的文件名称
	 *
	 * @param   string $file 文件名称 [不包括路径]
	 * @return　string string 处理后的文件名称
	 */
	public static function makeSafe($file) {
		$regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');
		return preg_replace($regex, '', $file);
	}

	/**
	 * 复制文件
	 *
	 * @param   string $src 文件源地址
	 * @param   string $dest 文件目标地址
	 * @param   string $path 如果源地址和目标地址在同一级目录下，此参数用于设置相同的那部分路径
	 * @return　boolean  成功返回 true 失败返回false
	 */
	public static function copy($src, $dest, $path = null)
	{

		// 检测路径
		if ($path) {
			$src = parent::clean($path.DS.$src);
			$dest = parent::clean($path.DS.$dest);
		}

		//检查源文件
		if (!is_readable($src)) {
			$this->triggerError('目录："' . $src . '"没有读取权限.');
			exit;
		}
		// 如果目标路径不存在，则创建一个目录
		if (!file_exists(dirname($dest))) {
			VO_Filesystem_Folder::create(dirname($dest));
		}
		if (!@ copy($src, $dest)) {
			$this->triggerError('复制失败.');
			exit;
		}
		$ret = true;
		return $ret;
	}

	/**
	 * 删除文件或者一组文件(以数组存放)
	 *
	 * @param   mixed $file 文件名称或者一组文件名称
	 * @return　 boolean  成功则返回TRUE，否则返回False
	 */
	public static function delete($file)
	{
		if (is_array($file)) {
			$files = $file;
		} else {
			$files[] = $file;
		}

		foreach ($files as $file)
		{
			$file = Vo_Filesystem_Path::clean($file);
			// 先设置文件为777权限，以便删除它
			@chmod($file, 0777);
			if (@unlink($file)) {

			} else {
				$filename	= basename($file);
				//$this->triggerError('删除"' . $filename . '"失败.');
				//exit;
			}
		}

		return true;
	}

	/**
	 * 移动文件
	 *
	 * @param   string $src 源文件
	 * @param   string $dest 目标文件
	 * @param   string $path 目标文件的路径
	 * @return　 boolean　成功返回True　否则返回False
	 */
	public static function move($src, $dest, $path = '')
	{
		if ($path) {
			$src = parent::clean($src);
			$dest = parent::clean($dest);
		}

		//检查文件路径
		if (!is_readable($src) && !is_writable($src)) {
			$this->triggerError('源文件"' . $src . '"不存在.');
			exit;
		}
		if (!@ rename($src, $dest)) {
			$this->triggerError('无法重命名文件夹.');
			exit;
		}
		return true;
	}

	/**
	 *读取文件内容
	 *
	 * @param   string $filename 完整的文件路径
	 * @param   boolean $incpath 使用包含路径
	 * @param   int $amount 读取的总字节数
	 * @param   int $chunksize 每次读取的字节数
	 * @param   int $offset 文件偏移(即从哪个地方开始读取)
	 * @return　 mixed 返回文件内容，失败返回False
	 */
	public static function read($filename, $incpath = false, $amount = 0, $chunksize = 8192, $offset = 0){
		$data = null;
		if($amount && $chunksize > $amount) { $chunksize = $amount; }
		if (false === $fh = fopen($filename, 'rb', $incpath)) {
			$this->triggerError('无法打开文件:"' . $filename .'"');
			exit;
		}
		clearstatcache();
		if($offset) fseek($fh, $offset);
		if ($fsize = @ filesize($filename)) {
			if($amount && $fsize > $amount) {
				$data = fread($fh, $amount);
			} else {
				$data = fread($fh, $fsize);
			}
		} else {
			$data = '';
			$x = 0;
			// 1: Not the end of the file AND
			// 2a: No Max Amount set OR
			// 2b: The length of the data is less than the max amount we want
			while (!feof($fh) && (!$amount || strlen($data) < $amount)) {
				$data .= fread($fh, $chunksize);
			}
		}
		fclose($fh);

		return $data;
	}

	/**
	 * 写文件(将文件置为空，然后重新写入)
	 *
	 * @param   string $file 完整的文件路径
	 * @param   string $buffer 要写入的内容
	 * @return　 boolean 成功返回True，否则返回False
	 */
	public static function write($file, $buffer){
		// 若目标目录不存在则创建它
		if (!file_exists(dirname($file))) {
			VO_Filesystem_Folder::create(dirname($file));
		}
		$file = Vo_Filesystem_Path::clean($file);
		$ret = file_put_contents($file, $buffer);
		return $ret;
	}
	
	/**
	 * 向文件追加内容
	 *
	 * @param   string $file 完整的文件路径
	 * @param   string $buffer 要写入的内容
	 * @return　 boolean 成功返回True，否则返回False
	 */
	public static function append($file, $buffer, $mode=''){
		// 若目标目录不存在则创建它
		if (!file_exists(dirname($file))) {
			VO_Filesystem_Folder::create(dirname($file));
		}
		$file = Vo_Filesystem_Path::clean($file);
		$fp = @fopen($file, 'a+');
		if($fp){
			flock($fp, LOCK_EX);
			$ret = fwrite($fp, $buffer);
			flock($fp, LOCK_UN);
			fclose($fp);
		}else{
			$ret = false;
		}
		return $ret;
	}

	/**
	 * 上传一个文件
	 *
	 * @param   string $src 服务器PHP存放的临时文件
	 * @param   string $dest 最终存放文件的路径(包含文件名)
	 * @return　 boolean 成功返回True，否则返回False
	 */
	public static function upload($src, $dest, $filter=array()){
		self::filterUpload($src, $filter);
		$ret	= false;
		$dest	= Vo_Filesystem_Path::clean($dest);
		
		$src = $src['tmp_name'];

		// 若目标目录不存在则创建它
		$baseDir = dirname($dest);
		if (!file_exists($baseDir)) {
			VO_Filesystem_Folder::create($baseDir);
		}

		if(is_writeable($baseDir) && move_uploaded_file($src, $dest)) {
			if (Vo_Filesystem_Path::setPermissions($dest)) {
				$ret = true;
			}else{
				$this->triggerError('未知错误.');
				exit;
				
			}
		}else{
			$this->triggerError('未知错误.');
			exit;
		}
		return $ret;
	}

	/**
	 * 检测上传文件的过滤信息
	 * @param array $origin
	 * @param array $filter
	 * @return mixed
	 */
	public static function filterUpload($origin, $filter){
		if( isset($filter['type']) ){
			if(!is_array($filter['type'])){
				$allowType = explode(',', $filter['type']);
			}
			$type = VO_Filesystem_File::getExt($origin['name']);
			if( !in_array($type, $allowType) ){
				//$this->triggerError($origin['name']);
				$this->triggerError('非法的文件类型，只允许上传以' . $filter['type'] . '结尾的文件');
				exit;
			}
		}
		
		if( isset($filter['size']) ){
			$filesize = $origin['size'];
			$size = VO_String::toIntSize( $filter['size'] );
			if( $filesize > $size){
				$sizestr = VO_String::toBitSize( $size );
				$this->triggerError( '文件超过上传限制的大小，最大上传文件为:' . $sizestr );
				exit;
			}
		}
	}
	/**
	 * 判断文件是否存在
	 *
	 * @param   string $file 文件名称(包含路径)
	 * @return　 boolean 如果文件存在返回True，否则返回False
	 */
	public static function exists($file){
		return is_file(Vo_Filesystem_Path::clean($file));
	}

	/**
	 * 返回一个路径的文件名
	 *
	 * @param   string $file 包含路径的文件名
	 * @return　 string 文件名
	 */
	public static function getName($file){
		$slash = strrpos($file, DS) + 1;
		return substr($file, $slash);
	}

	/**
	 * 获取文件的mime类型
	 * @param   string $file 包含路径的文件名
	 * @return	string 文件的mime类型
	 */
	public static function getFileMimeType($file){
		if(!extension_loaded('fileinfo')){
			if(!dl('fileinfo.so')){
				return false;
			}
		}else{
			$fileinfo    = @finfo_open(FILEINFO_MIME);
			$mime_type = finfo_file($fileinfo, $file);
			finfo_close($fileinfo);
			return $mime_type;
		}
	}
}
