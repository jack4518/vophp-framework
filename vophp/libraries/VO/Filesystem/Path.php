<?php
/**
 * 定义  Vo_Filesystem_Path 路径操作类
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

class Vo_Filesystem_Path{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return Vo_Filesystem_Path
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof Vo_Filesystem_Path ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 查询一个目录是否可以被改变权限
	 *
	 * @param	string	$path	需要检查的路径
	 * @return	boolean	如果可以改变权限，返回TRUE　否则返回False
	 */
	public static function isChmod($path){
		$perms = fileperms($path);
		if ($perms !== false)
		{
			if (@ chmod($path, $perms ^ 0001))
			{
				@chmod($path, $perms);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 改变指定路径下的目录、子目录及文件夹的权限
	 *
	 * @param	string	$path		要改变权限的目录 [不包含结尾的分隔符,例如"/"]
	 * @param	string	$filemode	文件的权限 [null为不改变]
	 * @param	string	$foldermode	目录的权限 [null为不改变]
	 * @return	boolean	成功返回TRUE [有一个操作失败，则意味着所有都失败]

	 */
	public static function setPermissions($path, $filemode = '0644', $foldermode = '0755') {

		// Initialize return value
		$ret = true;

		if (is_dir($path))
		{
			$dh = opendir($path);
			while ($file = readdir($dh))
			{
				if ($file != '.' && $file != '..') {
					$fullpath = $path.'/'.$file;
					if (is_dir($fullpath)) {
						if (!self::setPermissions($fullpath, $filemode, $foldermode)) {
							$ret = false;
						}
					} else {
						if (isset ($filemode)) {
							if (!@ chmod($fullpath, octdec($filemode))) {
								$ret = false;
							}
						}
					} // if
				} // if
			} // while
			closedir($dh);
			if (isset ($foldermode)) {
				if (!@ chmod($path, octdec($foldermode))) {
					$ret = false;
				}
			}
		}
		else
		{
			if (isset ($filemode)) {
				$ret = @ chmod($path, octdec($filemode));
			}
		} // if
		return $ret;
	}
	
	/**
	 * 获取目录或者文件的权限
	 *
	 * @param	string	$path	文件名称或者目录名称
	 * @return	string	文件权限
	 */
	public static function getPermissions($path){
		$path = self::clean($path);
		$mode = @ decoct(@ fileperms($path) & 0777);

		if (strlen($mode) < 3) {
			return '---------';
		}
		$parsed_mode = '';
		for ($i = 0; $i < 3; $i ++)
		{
			// read
			$parsed_mode .= ($mode { $i } & 04) ? "r" : "-";
			// write
			$parsed_mode .= ($mode { $i } & 02) ? "w" : "-";
			// execute
			$parsed_mode .= ($mode { $i } & 01) ? "x" : "-";
		}
		return $parsed_mode;
	}

	/**
	 * 去除目录中多余的/或者\
	 *
	 * @param	string	$path	路径名称
	 * @param	string	$ds		目录分隔符 
	 * @return	string	操作完后的路径

	 */
	public static function clean($path, $ds=DS)
	{
		$path = trim($path);

		if (empty($path)) {
			$path = JPATH_ROOT;
		} else {
			$path = preg_replace('#[/\\\\]+#', $ds, $path);
		}

		return $path;
	}
	
	/**
	 * 在指定的目录中查找指定的文件.
	 *
	 * @param	array|string	$path	一个目录或者一组目录(以数组形式)
	 * @param	string	$file	文件名称.
	 * @return	mixed	返回完整的路径包含文件名称,如果没有找到则返回False.

	 */
	public static function find($paths, $file){
		settype($paths, 'array');

		foreach ($paths as $path){
			$fullname = $path.DS.$file;

			if (strpos($path, '://') === false){
				$path = realpath($path);
				$fullname = realpath($fullname);
			}
			if (file_exists($fullname) && substr($fullname, 0, strlen($path)) == $path) {
				return $fullname;
			}
		}
		return false;
	}
}