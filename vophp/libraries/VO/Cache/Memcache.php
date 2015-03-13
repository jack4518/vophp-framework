<?php
/**
 * 定义  VO_Cache_Memcache  MemCache缓存类
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
class VO_Cache_Memcache extends VO_Cache_Abstract{
	/**
	 * memcached连接句柄
	 * @var resource
	 */
	protected $conn = null;

	/**
	 * 缓存过期时间(默认为5分钟)
	 * @var int
	 */
	public $lifetime = 300;
	
	/**
	 * 缓存服务器配置,参看$default_server允许多个缓存服务器
	 * @var array
	 */
	protected $_configs = array(
		array(
			'host' => '127.0.0.1',		//MemCache缓存服务器地址或主机名
			'port' => '11211'			//MemCache缓存服务器端口
		)
	);
	
	/**
	 * 是否压缩缓存数据
	 * @var boolean
	 */
	protected $compressed = false;
	
	/**
	 * 是否使用持久连接
	 * @var boolean
	 */
	protected $pconnect = false;
	
	/**
	 * 构造函数
	 * @param array $option 缓存参数
	 */
	public function __construct($configs=array(), array $options = null){
		if(!extension_loaded('memcache')){
            $this->triggerError('无法载入Memcache扩展，请检查PHP是否支持Memcache扩展。');
            exit;
        }
        
        foreach($options as $k => $v){
			if(isset($this->$k)){
				$this->$k = $v;
			}
		}
    	
    	if(!empty($configs)){
    		$this->_configs = $configs;
    	}else{
	    	$config = C('memcache');
	    	if(!empty($config)){
		    	$this->_configs = $config;
	    	}
	    	$this->_parseConfig($this->_configs, $config['config_type']);
    	}
    	
    	$this->conn = new Memcache();
    	foreach($this->_configs as $server){
	    	$rhost = $server['host'] . ':' . $server['port'];
            $result = $this->conn->addServer($server['host'], $server['port'], $this->pconnect);
            if(!$result){
                $this->triggerError(sprintf('Connect memcached server [%s:%s] failed!', $server['host'], $server['port']));
            }else{
	            //var_dump($rhost);
            }
        }
		parent::__construct($options);
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Cache_Memcache
	 */
	public static function getInstance(array $option = null){
		static $instance = null;
		if( !$instance instanceof VO_Cache_Memcache ){
			$instance = new self($option);
		}
		return $instance;
	}

	/**
	 * 写入缓存
	 * @param string $name  缓存名称
	 * @param mixed $data   缓存值
	 * @param array $option  缓存参数
	 * @return boolean  成功返回True 失败返回False
	 */
	public function set($name, $data, $option = null){
		$compressed = isset($option['compressed']) ? $option['compressed'] : $this->compressed;
        $life_time = isset($option['life_time']) ? $option['life_time'] : $this->life_time;
        $is_compressed = $compressed ? MEMCACHE_COMPRESSED : 0;
        return $this->conn->set($name, $data, $is_compressed, $life_time);
	}
	
	/**
	 * 覆盖缓存
	 * @param string $name  缓存名称
	 * @param mixed $data   缓存值
	 * @param array $option  缓存参数
	 * @return boolean  成功返回True 失败返回False
	 */
	public function replace($name, $data, $option = null){
		$compressed = isset($option['compressed']) ? $option['compressed'] : $this->compressed;
        $life_time = isset($option['life_time']) ? $option['life_time'] : $this->life_time;
        $is_compressed = $compressed ? MEMCACHE_COMPRESSED : 0;
        return $this->conn->replace($name, $data, $is_compressed, $life_time);
	}
	
	/**
	 * 读取缓存,读取失败或缓存失效返回false
	 * @param $name string  缓存名称
	 */
	public function get($name){
		return $this->conn->get($name);
	}
	
	/**
	 * 清除指定缓存
	 * @param string $name 缓存名称
     * @return boolean
	 */
	public function remove($name){
		return $this->conn->delete($name);
	}
	
	/**
	 * 清空所有缓存
	 * @param string $name 缓存名称
     * @return boolean
	 */
	public function clean($name=null){
		return $this->conn->flush();
	}	
	
	/**
	 * 对某个缓存的值进行减法操作
	 * @param string $name  缓存名称
	 * @param int $value    减少的值
	 * @return int
	 */
	public function dec($name, $value=1){
		return $this->conn->decrement($name, $value);
	}

	/**
	 * 对某个缓存的值进行加法操作
	 * @param string $name   缓存名称
	 * @param int $value     增加的值
	 * @return int
	 */
	public function inc($name, $value=1){
		return $this->conn->increment($name, $value);
	}
	
	/**
	 * 获取MemCache的相关状态信息
	 */
	public function getState(){
		return $this->conn->getExtendedStats();
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