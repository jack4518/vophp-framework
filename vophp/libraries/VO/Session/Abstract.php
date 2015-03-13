<?php
/**
 * 定义  VO_Session_Abstract Session抽象类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-01
 **/

defined('VOPHP') or die('Restricted access');

class VO_Session_Abstract{	
	
	/**
	 * 构造器
	 */
	public function __construct(){
		
	}
	
	/**
	 * 启用Session
	 * 
	 * @return null
	 */
	public static function startSession(){
		
	}
	
	/**
	 * 获取Session ID
	 * 
	 * @return session_id
	 */
	public static function getId(){
		
	}
	
	/**
	 * 设置Session ID
	 * 
	 * @return session_name
	 */
	public static function setId($id){
		
	}
	
	/**
	 * 获取指定的$_SESSION的信息，若不存在则返连回$_SESSION数组
	 * @param String $key default null
	 * @param String $default default null
	 * @param $namespace session的命名空间
	 * 
	 * @return session的值
	 */
	public static function get($key = null, $default = null, $namespace='default'){
    	
    }
    
    /**
	 * 设置Session
	 * 
	 * @param String $key
	 * @param String $value default null
	 * @param $namespace session的命名空间
	 * 
	 * @return boolean true
	 */
	public static function set($key, $value=null, $namespace='default'){
		
	}
	
	/**
     * 判断指定Session是否存在
     * @param $key session键
     * @param $namespace session的命名空间
     */
	public static function isExist($key, $namespace='default'){
		
	}
	
	/**
	 * 创建Session ID
	 * @return string 新创建的Session Id
	 */
	private static function _createId(){
		
	}
	
	/**
     * 删除Session
     * @param string $key  要删除的session的名字
     * @param $namespace session的命名空间
     * 
     * @return string 删除前的session值
     */
	public static function clear($namespace='default'){
		return true;
	}
	/**
     * 清空Session
     */
	public static function remove($key, $namespace='default'){
		
	}
}	