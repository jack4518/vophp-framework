<?php
/**
 * 定义 VO_Object对象基类
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

abstract class VO_Object{	
	/**
	 * 调试开关
	 */ 
	protected $_debug = true;
	
	/**
	 * 错误信息
	 */ 
	private $_error = '';
	/*
	public function __set($name, $value){
		if(property_exists($this, $name)){
			$this->$name = $value;
		}
	}
	
	/**
	 * 基类的__get魔术方法
	 * @param string $name
	 */
	/*
	public function __get($name) {
		return (isset($this->$name)) ? $this->$name : null;
	}
	*/

	public function __construct(){
		$this->_debug = C('error.debug');
	}

	/**
	 * 初始化加载器
	 * param  string  $class  类名(去掉VO_后的名称，如果是多级目录则可以使用.号进行分隔)
	 *                        　　例如:VO_Loader::loader('string.pinyin'); 加载VO_String_Pinyin类
	 *                        　　例如:VO_Loader::loader('model'); 加载VO_Model类
	 * @return bool | Object  返回对应的类实例对象，或者返回false
	 */	
	public function load($class, $xargs=array()){
		$xargs = func_get_args();
		$class = $xargs[0];
		unset($xargs[0]);
		$xargs = array_values($xargs);
		return VO_Loader::load($class, $xargs);
	}

	/**
	 * 字符化类
	 * @param string  $error  错误信息
	 * @return  string 错误信息
	 */
	public function addError($error=''){
		if(!empty($error)){
			VO_Error_Storage::addError($error);
			$this->_error = $error;
		}
		return $error;
	}

	/**
	 * 渲染错误
	 * @param string  $error  错误信息
	 * @param int   $type  错误级别
	 * @return  string 错误信息
	 */
	public function triggerError($error, $type=E_USER_ERROR){
		return trigger_error($error, $type);
	}

	/**
	 * 字符化类
	 * @return  string 错误信息
	 */
	public function getError(){
		return VO_Error_Storage::getError();
	}

	/**
	 * 字符化魔术方法
	 */
	public function __toString(){
		
	}
}