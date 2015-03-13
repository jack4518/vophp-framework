<?php
/**
 * 定义  VO_Cache_Memory  内存缓存类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-09-07
 **/

defined('VOPHP') or die('Restricted access');

require_once(VO_LIB_DIR .  DS . 'Cache' . DS . 'Abstract.php');
class VO_Cache_Memory extends VO_Cache_Abstract{
	
	/**
	 * 缓存容器
	 * @var array
	 */
	private $_var = array();
	
	/**
	 * 构造函数
	 *
	 * @param array $option 缓存参数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Cache_Memory
	 */
	public static function getInstance(array $option = null){
		static $instance = null;
		if( !$instance instanceof VO_Cache_Memory ){
			$instance = new self($option);
		}
		return $instance;
	}
	

	/**
	 * 写入缓存
	 * @param string $name  缓存名称
	 * @param mixed $data   缓存值
	 */
	public function set($name, $data, $lift_time = null){
		$key = substr(md5($name),0,16);
		$this->_var[$key] = $data;
	}
	
	/**
	 * 读取缓存,读取失败或缓存失效返回false
	 * @param $name string  缓存名称
	 */
	public function get($name){
		$key = substr(md5($name),0,16);
        return isset($this->_var[$key]) ? $this->_var[$key] : false;
	}
	
	/**
	 * 清除指定缓存
	 * @param string $name 缓存名称
	 */
	public function remove($name){
		$key = substr(md5($name),0,16);
		unset($this->_var[md5($key)]);
	}
	
	/**
	 * 清空缓存
	 */
 	public function clean(){
         unset($this->_var);
    }
}