<?php
/**
 * 定义  VO_Http_Cookie Cookie操作类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-31
 **/

defined('VOPHP') or die('Restricted access');

class VO_Http_Cookie{
	
	//Cookie的有效时间
	var $_expire = 3600;
	
	//Cookie的存储路径
	var $_path = '/';
	
	//Cookie的域
	var $_domain = '';
	
	//Cookie是否通过secure来传输
	var $_secure = false;
	
	
	/**
	 * 构造器
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Http_Cookie
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Http_Cookie ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 是否有$_COOKIE参数
	 */
	public function isCookie(){
		return $_COOKIE?true:false;
	}
	
	/**
	 * 获取指定的$_Cookie的信息，若不存在则返连回$_Cookie数组
	 * @param String $key default null
	 * @param String $default default null
	 */
	public function get($key = null, $default = null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }
	
	/**
	 * 设置Cookie
	 * 
	 * @param String $key
	 * @param String $value default null
	 */
	public function set($key,$value=null, $expire=null, $path=null, $domain=null, $secure=false){
		if(null === $key){
			return false;
		}
		if(isset($expire)){
			$this->_expire = $expire;
		}
		if(isset($path)){
			$this->_path = $path;
		}
		if(isset($domain)){
			$this->_domain = $domain;
		}
		if(isset($secure)){
			$this->_secure = $secure;
		}
		if((null === $value) && is_array($key)){
			foreach($key as $k => $v){
				$this->set($k, $v, $this->_expire, $this->_path, $this->_domain, $this->_secure);
			}
		}
		$_COOKIE[(string)$key] = $value;
		return true;
	}
	
	/**
	 * 设置Cookie的存储路径
	 * @param int $cookie_path  Cookie的存储路径
	 */
	public function setPath($cookie_path){
		$this->$_path = $cookie_path;
	}
	
	/**
	 * 设置Cookie的域
	 * @param int $cookie_domain  Cookie的域
	 */
	public function setDomain($cookie_domain){
		$this->$_domain = $cookie_domain;
	}
	
	/**
	 * 设置Cookie的有效时限
	 * @param int $cookie_expire  Cookie的有效时间，以秒为单位
	 */
	public function setExpire($cookie_expire){
		$this->$_expire = $cookie_expire;
	}
	
	/**
	 * 设置Cookie是否通过HTTPS传输
	 * @param boolean $cookie_secure  Cookie的传输方式
	 */
	public function setIsSecure($cookie_secure){
		$this->$_secure = $cookie_secure;
	}
	
	/**
     * 删除指定名称的cookie
     * @param string $name  要删除的cookie的名字
     */
	public function remove($name){
		$this->set($name, '', time()-3600);
	}
}