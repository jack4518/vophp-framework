<?php
/**
 * 定义 VO_Session类
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

VO_Loader::import('Session.Abstract');

class VO_Session extends VO_Session_Abstract{	
	
	/**
	 * Session是否开启
	 * @var bool
	 */
	static $_is_started = false;
	
	/**
	 * 构造器
	 */
	public function __construct(){
		self::start();
	}
	
	/**
	 * 启用Session
	 * 
	 * @return null
	 */
	public static function start(){
		if(!isset($_SESSION) && self::$_is_started == false){
			session_start();
			self::$_is_started = true;
			/*
			session_id(self::_createId());
			session_cache_limiter('none');
			Send modified header for IE 6.0 Security Policy
			header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
			*/
		}
		self::$_is_started = true;
		return false;
	}
	
	/**
	 * 获取Session ID
	 * 
	 * @return session_id
	 */
	public static function getId(){
		return session_id();
	}
	
	/**
	 * 设置Session ID
	 * 
	 * @return session_id
	 */
	public static function setId($id){
		session_id($id);
	}	
	
	/**
	 * 获取Session名称
	 * 
	 * @return session_name
	 */
	public static function getName(){
		return session_name();
	}
	
/**
	 * 设置Session名称
	 * 
	 * @return session_name
	 */
	public static function setName($name){
		session_name($name);
	}
	
	/**
	 * 获取指定的$_SESSION的信息，若不存在则返连回$_SESSION数组
	 * @param String $key default null
	 * @param String $default default null
	 * @param $namespace session的命名空间
	 * 
	 * @return session的值
	 */
	public static function get($key = null, $default = null, $namespace=null)
    {
    	self::start();
        if (null === $key && @$_SESSION) {
            return $_SESSION;
        }
        
        if( ($namespace == null) && isset($_SESSION[$key]) ){
        	return $_SESSION[$key];
        }elseif( isset($_SESSION[$namespace][$key]) ){
        	return $_SESSION[$namespace][$key];
        }else{
        	return $default;
        }
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
	public static function set($key, $value=null, $namespace=null){
		self::start();
		if(null === $key){
			return false;
		}
		if((null === $value) && is_array($key)){
			foreach($key as $k => $v){
				self::set($k, $v, $namespace);
			}
		}
		
		if( empty($namespace) ){
			$_SESSION[(string)$key] = $value;
		}else{
			$_SESSION[$namespace][(string)$key] = $value;
		}
		return true;
	}
	
	/**
     * 判断指定Session是否存在
     * @param $key session键
     * @param $namespace session的命名空间
     */
	public static function isExist($key, $namespace=null){
		self::start();
		if( !empty($namespace) && isset($_SESSION[$namespace][$key]) ){
			return true;
		}elseif( isset($_SESSION[$key]) ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 创建Session ID
	 * @return string 新创建的Session Id
	 */
	private static function _createId(){
		$id = 0;
		while (strlen($id) < 32)  {
			$id .= mt_rand(0, mt_getrandmax());
		}

		$id	= md5( uniqid($id, true));
		return $id;
	}
	
	/**
     * 删除Session
     * @param string $key  要删除的session的名字
     * @param $namespace session的命名空间
     * 
     * @return string 删除前的session值
     */
	public static function remove($key, $namespace=null){
		self::start();
		if(!self::isExist($key,$namespace)){
			return true;
		}
		$ret = self::get($key);
		if( !empty($namespace) ){
			unset($_SESSION[$namespace][$key]);
		}else{
			unset($_SESSION[$key]);
		}
		
		return $ret;		
	}
	
	/**
     * 清空Session
     */
	public static function clear($namespace=null){
		self::start();
		if( !empty($namespace) && @$_SESSION[$namespace]){
			unset($_SESSION[$namespace]);
		}else{
			unset($_SESSION);
		}
		
	}	
}