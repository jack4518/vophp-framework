<?php
/**
 * 定义 VO_Error_Storage错误存储器类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-06-01
 **/

defined('VOPHP') or die('Restricted access');

class VO_Error_Storage{	
	/**
	 * 错误信息
	 */ 
	private static $_errors = '';

	/**
	 * 构造函数
	 * @return void
	 */	
	public function __construct(){
	}

	/**
	 * 增加错误
	 * @param string  $error  错误信息
	 * @return  string 错误信息
	 */
	public static function addError($error=''){
		self::$_errors[] = $error;
		return $error;
	}

	/**
	 * 获取最后一次错误信息
	 * @return  string 最后一次错误信息
	 */
	public static function getError(){
		return array_pop(self::$_errors);
	}

	/**
	 * 获取所有错误
	 * @return  array  所有错误信息
	 */
	public static function getErrors(){
		return self::$_errors;
	}
}