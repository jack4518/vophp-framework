<?php
/**
 * 定义  VO_Cache_Xcache  XCache缓存类
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
class VO_Cache_Xcache extends VO_Cache_Abstract{
	
	protected $lifetime = 300;			//缓存过期时间(默认为5分钟)
	
	/**
	 * 构造函数
	 *
	 * @param array $option 缓存参数
	 */
	public function __construct(array $options = null){
		if (!extension_loaded('xcache')){
            $this->triggerError('无法载入Xcache扩展，请检查PHP是否支持Xcache扩展。');
        }
	        
        foreach($options as $k => $v){
			if(isset($this->$k)){
				$this->$k = $v;
			}
		}
		parent::__construct($options);
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Cache_Xcache
	 */
	public static function getInstance(array $option = null){
		static $instance = null;
		if( !$instance instanceof VO_Cache_Xcache ){
			$instance = new self($option);
		}
		return $instance;
	}

	/**
	 * 写入缓存
	 * @param string $name  缓存名称
	 * @param mixed $data   缓存值
	 * @param int $lift_time  缓存时间
	 * @return boolean  成功返回True 失败返回False
	 */
	public function set($name, $data, $lift_time = null){
		$lift_time = isset($lift_time) ? $lift_time : $this->lift_time;
		return xcache_set($name, $data, $lift_time);
	}
	
	/**
	 * 读取缓存,读取失败或缓存失效返回false
	 * @param $name string  缓存名称
	 */
	public function get($name){
		if(xcache_isset($name)) {
			return xcache_get($name);
		}
		return false;
	}
	
	/**
	 * 清除指定缓存
	 * @param string $name 缓存名称
     * @return boolean
	 */
	public function remove($name){
		return xcache_unset($name);
	}
	
	/**
	 * 获取缓存的时效
	 * @return int $this->lifetime
	 */
 	public function getLifetime(){
        return $this->lifetime;
    }
    
	/**
	 * 设置缓存的时效
	 * @param int $lifetime
	 */
 	public function setLifetime($lifetime=300){
 		$lifetime = (int)$lifetime;
        if ($lifetime != false){
            $this->lifetime = $lifetime;
            return true;
        }else{
        	return false;
        }
    }
}