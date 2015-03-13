<?php
/**
 * 定义 VO_Nosql_Redis Redis类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2014-07-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Nosql_Redis extends VO_Object{
    /**
     * 单一实例存储器
     * @var VO_Nosql_Redis
     */
    static protected  $_instance;

    /**
     * Redis服务器实例存储器
     * @var VO_Consistent_Redis
     */
    protected $_cacher;

    /**
     * 配置信息
     * var array
     */
    protected $_configs = array();

    /**
     * 构造函数
     * @param array $config  配置信息
     * @param array $options 附加信息
     * @return void
     */
    public function __construct($config=array(), $options=array()){
        if(!$this->_cacher){
            $this->_parseConfig($config);
            $instance = $this->addServer($this->_configs, $options);
            $this->_cacher = $instance;
        }

        if(!$this->_cacher){
            $this->triggerError("Can't make redis instance");
        }
    }

    /**
	 * 获取单一实例
	 * @return VO_Nosql_Redis
	 */
    static public function getInstance($config=array(), $options=array()){
        if(!self::$_instance){
		    self::$_instance = new self($config, $options);
		}
	    return self::$_instance;
    }

    /**
     * 添加server
     * @param array $servers 所有Redis服务器配置信息
     * @return VO_Consistent_Redis
     */
    public function addServer($servers){
        $instance = new VO_Consistent_Redis();
        foreach ($servers as $server) {
            $instance->addServer($server['host'], $server['port'], 0, $server['weight']);
        }
        return $instance;
    }

    /**
     * 取得该实例的server列表
     * @return array
     */
    public function getServer(){
        return $this->_configs;
    }

	/**
     * 魔术调用方法，间接调用VO_Consistent_Redis类的相关方法
     * @param array $config  配置信息
     * @param array $options 附加信息
     * @return void
     */
    public function __call($method, $args)
    {
        $method = strtolower($method);
        if(!extension_loaded('redis')){
	        $this->triggerError('无法载入Redis扩展，请通过php.ini确认phpRedis扩展是否安装成功');
        }
        if(!method_exists('Redis', $method)){
            $this->triggerError(get_class($this) . " Method '$method' does not exist for phpRedis");
        }

        static $nonkey_methods = array(
            'setoption', 'dbsize', 'randomkey', 'select',
            'mget', 'mset', 'msetnx', 'getmultiple',
            'exec', 'discard', 'unwatch', 'multi', 'flushall',
        );

        if(!in_array($method, $nonkey_methods) && (!isset($args[0]) || !is_string($args[0]))){
            $this->triggerError("Only methods with \$key as first parameter can be overloaded");
        }
        $status = call_user_func_array(array($this->_cacher, $method), $args);
        $this->_log($method, $args[0], $status);
        return $status;
    }

	/**
     * 日志记录器
     * @param string $method 信息
     * @param string $key    Redis Key
     * @param string $success  是否操作成功
     * @return void
     */
    private function _log($method, $key, $success=false){
        $message = 'Redis'.'【'.$method.'】'.$key;
        //记录日志
		$log = new VO_Log();
		if($success){
			$log->log($message, 'INFO');
		}else{
			$log->log($message, 'ERROR');
		}
    }

	/**
     * 解析Redis配置信息
     * @param array $config  配置信息
     * @return void
     */
    private function _parseConfig($config=array()){
	    if(empty($config)){
            $config = C('redis');
        }
        switch(strtoupper($config['config_type'])){
	        case 'SERVER' :
	        	$redis_server_key = $config['server_key'];
	        	$redis_configs = VO_Http::getServer($redis_server_key);
	        	$redis_configs = trim($redis_configs);
	        	if(!empty($redis_configs)){
		        	$configs = explode(' ', $redis_configs);
		        	$config = array();
		        	foreach($configs as $item){
			        	if(empty($item)){
				        	continue;
			       		}else{
				        	$tmp = explode(':', $item);
				        	$len = count($tmp);
				        	if($len == 2){
					        	$host = trim($item[0]);
					        	$port = trim($item[1]);
				        	}elseif($len == 1){
					        	$host = trim($item[0]);
					        	$port = 11211;
				        	}
				        	$config[] = array(
								'host'    	=> 	$host,  //Redis主机名
								'port' 		=> 	$port,  //Redis连接端口
								'weight'	=> 	0,  //Redis权重
								'lasting' 	=> 	0,  //Redis表前缀
								'lifetime' 	=> 	30	 //Redis连接生存时间
						    );
			        	}
		        	}
		        	$this->_configs = $config;
	        	}else{
		        	$this->triggerError('服务器不存在Key为:"' . $redis_server_key .'"的Redis配置信息,请确认');
	        	}
	        	break;
	        	
	        case 'FILE' :
	        default:
	        	$this->_configs = $config['configs'];
	        	break;
        }
    }    
};





/**
 * 定义 VO_Consistent_Redis Redis类
 * 模拟memcache的addserver，并通过一致性哈稀来生成多个虚拟的Redis Server节点
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author <qiqing>cqq254@163.com
 * @package VO
 * @since version 1.0
 * @date 2014-07-06
 **/
class VO_Consistent_Redis extends VO_Object{
	/**
	 * Redis对象连接池
	 * @var array
	 */
    protected $_pool = array(); // 'host:port' => $redis

    /**
	 * 哈稀对象存储器
	 * @var VO_Utils_Hash
	 */
    protected $_hash = null;

	/**
     * 构造函数
     * @return void
     */
    public function __construct(){
        $this->_hash = new VO_Utils_Hash(null, 16);
    }


	/**
     * 添加server
     * @param string $host  Redis主机IP
     * @param string $port  端口
     * @param string $host  Redis主机IP
     * @param string $host  Redis主机IP
     * @return bool 是否连接成功
     */
    public function addServer($host='127.0.0.1', $port=6379, $db_index=0, $weight=1){
        if(!extension_loaded('redis')){
	        $this->triggerError('无法载入Redis扩展，请通过php.ini确认phpRedis扩展是否安装成功');
        }
        $redis = new Redis();
        $rhost = $host . ':' . $port;
        $connection_string = $rhost . '/' . $db_index;

        $redis_ext_refc = new ReflectionExtension('redis');
        if(version_compare($redis_ext_refc->getVersion(), '2.1.0') <= 0){
            $bret = $redis->pconnect($host, $port, 0);
        }else{
            $bret = $redis->pconnect($host, $port, 0, $connection_string);
        }
        
        if($bret){
            // $redis->setOption(Redis::OPT_READ_TIMEOUT, 5); // need phpredis >= 2.2.3
            // $redis->select($db_index); // redis-proxy and high redis not support this anymore
            $this->_hash->addTarget($rhost);
            $this->_pool[$rhost] = $redis;
            return true;
        }else{
	        $this->triggerError('无法连接到Redis服务器：' . $host . ':' . $port);
	        
        }
        unset($redis);
        return false;
    }

	/**
     * 魔术调用方法，间接调用Redis扩展的相关方法
     * @param array $config  配置信息
     * @param array $options 附加信息
     * @return void
     */
    public function __call($method, $args){
        if(!method_exists('Redis', $method))
            $this->triggerError("Method '$method' does not exist for phpredis");

        if(!isset($args[0]) || !is_string($args[0]))
            $this->triggerError("Only methods with \$key as first parameter can be overloaded");
        if(empty($this->_pool))
            return false;
            
        $redis = $this->_pool[$this->_hash->lookup($args[0])];
        // 无参数方法
        static $trans_methods = array('exec', 'discard', 'unwatch', 'multi');
        if(in_array($method, $trans_methods)) {
            return call_user_func_array(array($redis, $method), array());
        }else{
            return call_user_func_array(array($redis, $method), $args);
        }
    }

	/**
     * 添加Redis的附加参数
     * @param  string $name  参数名称
     * @param  string $value  值
     * @return array
     */
    public function setOption($name, $value){
        $bret = true;
        if(!empty($this->_pool)) {
            foreach ($this->_pool as $pn => $pc) {
                $breta = $pc->setOption($name, $value);
                $bret = $bret || $breta;
            }
        }else{
            return false;
        }
        return $bret;
    }

    /**
     * 清除所有redis缓存数据
     * @param null
     * @return bool 是否清除成功
     */
    public function flush(){
        $bret = true;
        if(!empty($this->_pool)) {
            foreach ($this->_pool as $pn => $pc) {
                $breta = $pc->flushAll();
                $bret = $bret || $breta;
            }
        }else{
            return false;
        }
        return $bret;
    }
}
