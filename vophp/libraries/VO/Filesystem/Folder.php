<?php
/**
 * 定义  VO_Filesystem_Folder 目录操作类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-28
 **/

defined('VOPHP') or die('Restricted access');
include(VO_LIB_DIR .  DS . 'Filesystem' . DS . 'Path.php');
class VO_Filesystem_Folder extends VO_Filesystem_Path{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Filesystem_Folder
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Filesystem_Folder ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 复制目录
	 *
	 * @param	string	$src	源目录
	 * @param	string	$dest	目标目录
	 * @param	boolean	$force	同名的文件或者目录是否覆盖
	 * @return	mixed	成功返回True，否则返回一个错误句柄
	 */
	public static function copy($src, $dest, $force = false){
		$src = parent::clean($src);
		$dest = parent::clean($dest);

		$src = rtrim($src, DS);
		$dest = rtrim($dest, DS);

		if (!self::exists($src)) {
			$this->triggerError('源目录不存在.');
			exit;
		}
		if (self::exists($dest) && !$force) {
			$this->triggerError('目标目录已经存在.');
			exit;
		}

		// Make sure the destination exists
		if (! self::create($dest)) {
			$this->triggerError('无法创建目标目录.');
			exit;
		}

		if(! ($dh = @opendir($src))) {
			$this->triggerError('无法打开目标目录.');
			exit;
		}
		while (($file = readdir($dh)) !== false) {
			$sfid = $src.DS.$file;
			$dfid = $dest.DS.$file;
			switch (filetype($sfid)) {
				case 'dir':
					if ($file != '.' && $file != '..') {
						$ret = self::copy($sfid, $dfid, null, $force);
						if ($ret !== true) {
							return $ret;
						}
					}
					break;

				case 'file':
					if (!@ copy($sfid, $dfid)) {
						$this->triggerError('复制失败.');
						exit;
					}
					break;
			}
		}
		return true;
	}

	/**
	 * 创建目录 
	 *
	 * @param string $path 需要创建文件的路径
	 * @param int $mode 创建后目录的权限
	 * @return boolean 成功返回TRUE 失败返回False
	 */
	public static function create($path = '', $mode = 0755){
		$nested = 0;
		$path = parent::clean($path);

		$parent = dirname($path);
		if (!self::exists($parent)) {
			$nested++;
			if (($nested > 20) || ($parent == $path)) {
				$nested--;
				$this->triggerError('程序进行无限循环，终止.');
				exit;
			}
			if (self::create($parent, $mode) !== true) {
				$nested--;
				return false;
			}
			$nested--;
		}

		if (self::exists($path)) {
			return true;
		}
		$obd = ini_get('open_basedir');

		if ($obd != null){
			if (JPATH_ISWIN) {
				$obdSeparator = ";";
			} else {
				$obdSeparator = ":";
			}
			$obdArray = explode($obdSeparator, $obd);
			$inOBD = false;
			foreach ($obdArray as $test) {
				$test = parent::clean($test);
				if (strpos($path, $test) === 0) {
					$obdpath = $test;
					$inOBD = true;
					break;
				}
			}
			if ($inOBD == false) {
				$this->triggerError('文件路径不在基本路径中.');
				exit;
			}
		}

		$origmask = @umask(0);
		if (!$ret = @mkdir($path, $mode)) {
			@umask($origmask);
			$this->triggerError('无法创建目录:'.$path);
			exit;
		}

		@ umask($origmask);
		return $ret;
	}

	/**
	 * 删除目录
	 *
	 * @param string $path 待删除的目录
	 * @return boolean 成功返回True,失败返回False
	 */
	public static function delete($path){
		if ( ! $path ) {
			$this->triggerError('试图删除基本目录失败');
			exit;
		}

		$path = parent::clean($path);
		
		if (!is_dir($path)) {
			$this->triggerError( '"' . $path . '"目录不存在.');
			exit;
		}

		$files = self::files($path, '.', false, true, array());
		if (count($files)) {
			if (VO_Filesystem_File::delete($files) !== true) {
				$this->triggerError( '"' .$path . '"目录下有文件无法删除.');
				exit;
			}
		}

		$folders = self::folders($path, '.', false, true, array());
		foreach ($folders as $folder) {
			if (self::delete($folder) !== true) {
				return false;
			}
		}
		if (@rmdir($path)) {
			$ret = true;
		}else {
			$this->triggerError( '无法删除"' . $path . '"目录.');
			exit;
		}

		return $ret;
	}

	/**
	 * 移动目录
	 *
	 * @param string $src 源文件夹路径
	 * @param string $dest 目标路径
	 * @return mixed 成功返回True，错误返回False
	 */
	public static function move($src, $dest){
		$src = parent::clean($src);
		$dest = parent::clean($dest);

		if (!self::exists($src) && !is_writable($src)) {
			$this->triggerError( '源目录"' . $src . '"不存在.');
			exit;
		}
		if (self::exists($dest)) {
			$this->triggerError( '目标目录"' . $dest . '"已经存在.');
			exit;
		}

		if (!@ rename($src, $dest)) {
			$this->triggerError( '重命名目录"' . $dest . '"失败.');
			exit;
		}
		$ret = true;
		return $ret;
	}

	/**
	 * 验证目录是否存在
	 *
	 * @param string $path 待检测的目录
	 * @return 如果为有效的目录则返回True，否则返回False
	 */
	public static function exists($path)
	{
		return is_dir(parent::clean($path));
	}

	/**
	 * 在一个目录中读取文件列表
	 *
	 * @param	string	$path		需要读取文件的目录
	 * @param	string	$filter		需要过滤的文件名
	 * @param	mixed	$recurse	设置为True则会搜索子目录, 若提供一个整型则为搜索的最大深度
	 * @param	boolean	$fullpath	设置为True则为目录返回全路径
	 * @param	array	$exclude	设置一个数组用于过滤哪些目录需要过滤
	 * @return	array	返回文件名列表
	 */
	public static function files($path, $filter = '.', $recurse = false, $fullpath = false, $exclude = array('.svn', 'CVS')){
		$arr = array ();

		$path = parent::clean($path);

		if (!is_dir($path)) {
			$this->triggerError('"' . $path . '"不是有效的目录.');
			exit;
		}
		
		$handle = opendir($path);
		while (($file = readdir($handle)) !== false){			
			$dir = $path.DS.$file;
			$isDir = is_dir($dir);
			if (($file != '.') && ($file != '..') && (!in_array($file, $exclude))) {
				if ($isDir) {
					if ($recurse) {
						if (is_integer($recurse)) {
							$recurse--;
						}
						$arr2 = self::files($dir, $filter, $recurse, $fullpath);
						$arr = array_merge($arr, $arr2);
					}
				} else {
					if (preg_match("/$filter/", $file)) {
						if ($fullpath) {
							$arr[] = $path.DS.$file;
						} else {
							$arr[] = $file;
						}
					}
				}
			}
		}
		closedir($handle);

		asort($arr);
		return $arr;
	}

	/**
	 * 列出指定路径下的文件夹列表
	 *
	 * @param	string	$path		需要读取的路径
	 * @param	string	$filter		需要过滤的文件夹列表
	 * @param	mixed	$recurse	设置为True则会搜索子目录, 若提供一个整型则为搜索的最大深度
	 * @param	boolean	$fullpath	设置为True则为目录返回全路径
	 * @param	array	$exclude	设置一个数组用于过滤哪些目录需要过滤
	 * @return	array	返回目录列表
	 */
	public static function folders($path, $filter = '.', $recurse = false, $fullpath = false, $exclude = array('.svn', 'CVS')){
		$arr = array ();
		$path = parent::clean($path);

		if (!is_dir($path)) {
			$this->triggerError('"' . $path . '"不是有效的目录.');
			exit;
		}

		$handle = opendir($path);
		while (($file = readdir($handle)) !== false){
			$dir = $path.DS.$file;
			$isDir = is_dir($dir);
			if (($file != '.') && ($file != '..') && (!in_array($file, $exclude)) && $isDir) {
				// 过滤SVN目录
				if (preg_match("/$filter/", $file)) {
					if ($fullpath) {
						$arr[] = $dir;
					} else {
						$arr[] = $file;
					}
				}
				if ($recurse) {
					if (is_integer($recurse)) {
						$recurse--;
					}
					$arr2 = self::folders($dir, $filter, $recurse, $fullpath);
					$arr = array_merge($arr, $arr2);
				}
			}
		}
		closedir($handle);

		asort($arr);
		return $arr;
	}

	/**
	 * 以树型目录的形式列出目录结构
	 *
	 * @param	string	$path		需要读取的目录
	 * @param	string	$filter		需要过滤的目录(以什么开头的字符)
	 * @param	integer	$maxLevel	读取目录的深度, 默认为 3
	 * @param	integer	$level		当前的级别,可自定义
	 * @param	integer	$parent     父结点
	 * @return	array	目录列表

	 */
	public static function listFolderTree($path, $filter='.', $maxLevel = 3, $level = 0, $parent = 0)
	{
		$dirs = array ();
		if ($level == 0) {
			$GLOBALS['_self_folder_tree_index'] = 0;
		}
		if ($level < $maxLevel) {
			$folders = self::folders($path, $filter);
			// first path, index foldernames
			for ($i = 0, $n = count($folders); $i < $n; $i++) {
				$id = ++ $GLOBALS['_self_folder_tree_index'];
				$name = $folders[$i];
				$fullName = parent::clean($path.DS.$name);
				$dirs[] = array ('id' => $id, 'parent' => $parent, 'name' => $name, 'fullname' => $fullName, 'relname' => str_replace(APP_DIR, '', $fullName));
				$dirs2 = self::listFolderTree($fullName, $filter, $maxLevel, $level+1, $id);
				$dirs = array_merge($dirs, $dirs2);
			}
		}
		return $dirs;
	}

	/**
	 * 过滤路径中的非法字符
	 *
	 * @param	string $path 全路径
	 * @return	string 返回过滤后的字符串
	 */
	public static function makeSafe($path)
	{
		$ds	   = ( DS == '\\' ) ? '\\' . DS : DS;
		$regex = array('#[^A-Za-z0-9:\_\-' . $ds . ' ]#');
		return preg_replace($regex, '', $path);
	}

  /**
  * 判断目录是否可写
  *
  * @param    string    $file 待测试的文件或者目录
  * @return   boolean    是否可写
  */
  public function isWritable($file){
    //如果服务器为Unix或者Linux系列
    if (DIRECTORY_SEPARATOR == '/' && @ini_get("safe_mode") == FALSE){
        return is_writable($file);
    }

    //如果是windows服务器并且safe_mode设置为"on" ，就尝试创建一个文件，然后查看返回结果来判断目录是否可写
    if (is_dir($file)){
        $file = rtrim($file, DS) . DS . md5(rand(10,100));

        if (($fp = @fopen($file, 'ab')) === FALSE){
            return FALSE;
        }

        fclose($fp);
        @chmod($file, DIR_WRITE_MODE);
        @unlink($file);
        return TRUE;
    }elseif (($fp = @fopen($file, 'ab')) === FALSE){
      return FALSE;
    }

    fclose($fp);
    return TRUE;
    }
}
