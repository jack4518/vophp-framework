<?php
/**
 * 定义 VO_Registry 对象注册类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-8-23
 **/

defined('VOPHP') or die('Restricted access');

class VO_Registry extends ArrayObject{
	
	/**
	 * VO_Registry实例
	 * @var VO_Registry
	 */
	static $instance = null;
	
	/**
	 * 构造函数
	 */
	public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }
	
	/**
	 * 获取单一实例
	 * @return VO_Registry
	 */
	public static function getInstance(){
		if( !self::$instance instanceof VO_Registry ){
			self::$instance = new self();
		}
		return self::$instance;
	}  
	
	/**
	 * 变量引用是否已经注册
	 * @param string   $index
	 * @return boolean
	 */
	public static function isRegistered($index=null){
		$instance = self::getInstance();
		return self::$instance->offsetExists($index);
	}
	
	/**
	 * 设置Registry属性元素
	 * @param  string  $index
	 * @param  string  $value 
	 */
	public static function set($index='default',$value='default'){
		$instance = self::getInstance();
        $instance->offsetSet($index, $value);
	}
	
	/**
	 * 根据索引获取变量属性值
	 * @param string $index
	 * @return mixed
	 */
	public static function get($index){
		if( empty($index) ){
			$this->triggerError('VO_Register::get参数不能为空.');
			exit;
		}
		
		$instance = self::getInstance();

        if (!$instance->isRegistered($index)) {
        	return false;
			//$this->triggerError( $index . '尚未注册.请通过VO_Registry::set()方法进行注册.');
			exit;
        }

        return $instance->offsetGet($index);
	}
	
	/**
	 * 注销已注册的变量
	 * @param string $index
	 * @return bool
	 */
	public static function remove($index){
		if( empty($index) ){
			$this->triggerError('VO_Register::remove()参数不能为空.');
			exit;
		}
		
		$instance = self::getInstance();

        if ($instance->offsetExists($index)) {
			$instance->offsetUnset($index);
			return true;
        }else{
        	return false;
        }
		
	}
	
}