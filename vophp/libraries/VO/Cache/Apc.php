<?php
/**
 * 定义  VO_Cache_Apc  APC缓存类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-30
 **/

defined('VOPHP') or die('Restricted access');

require_once(VO_LIB_DIR .  DS . 'Cache' . DS . 'Abstract.php');
class VO_Cache_Apc extends VO_Cache_Abstract{
	
	public $lifttime = 300;			//缓存过期时间,单位为秒(默认为5分钟)
	
	/**
	 * 用户缓存
	 * @var string
	 */
	const MODE_USER = 'user';
	
	/**
	 * 用户缓存
	 * @var string
	 */
	const MOD_SYSTEM = 'system';
	
	/**
	 * 构造函数
	 *
	 * @param array $option 缓存参数
	 */
	public function __construct(array $options = array()){
        if(!extension_loaded('apc')) {
            $this->triggerError('无法载入APC缓存扩展，请检查PHP是否支持APC扩展.');
            exit;
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
	 * @return VO_Cache_Apc
	 */
	public static function getInstance(array $option = null){
		static $instance = null;
		if( !$instance instanceof VO_Cache_Apc ){
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
		$lift_time = isset($lift_time) ? $lift_time : $this->getLifetime();
		return apc_store($name, $data, $lift_time);
	}
	
	/**
	 * 读取缓存,读取失败或缓存失效返回false
	 * @param $name string  缓存名称
	 */
	public function get($name){
		$tmp = apc_fetch($name);
        if (is_array($tmp)) {
            return $tmp[0];
        }else{
        	return $tmp;
        }
	}
	
	/**
	 * 清除指定缓存
	 * @param string $name 缓存名称
     * @return boolean
	 */
	public function remove($name){
		return apc_delete($name);
	}
	
	/**
	 * 清除所有缓存
	 * @param string $mode  清除缓存的模式
	 */
 	public function clean($mode = self::MOD_ALL){
        switch ($mode) {
            case self::MODE_USER:
                return apc_clear_cache('user');
                break;
            case self::MOD_SYSTEM:
                return apc_clear_cache();
                break;
            default:
                return apc_clear_cache();
                break;
        }
    }
	
	/**
	 * 获取缓存的时效
	 * @return int 缓存时间
	 */
 	public function getLifetime(){
        return $this->lifetime;
    }
    
	/**
	 * 设置缓存的时效
	 * @param int $lifetime   缓存的时效性
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
    
    /**
     * 获取缓存的信息
     * @return array  缓存的信息
     */
    public function getInfo(){
        $res = array();
        $array = apc_cache_info('user', false);
        $records = $array['cache_list'];
        foreach ($records as $record) {
            $res[] = $record['info'];
        }
        return $res;
    }
}