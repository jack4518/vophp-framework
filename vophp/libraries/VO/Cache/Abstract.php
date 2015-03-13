<?php
/**
 * 定义  VO_Cache_Abstract  缓存抽象类
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

class VO_Cache_Abstract extends VO_Object{
	/**
	 * VOPHP的配置文件
	 * @var array
	 */
	protected $_configs = array();
	
	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
	 * 写入缓存
	 * @param string $name  缓存名称
	 * @param mixed $data   缓存值
	 * @param int $lift_time  缓存时间
	 * @return boolean  成功返回True 失败返回False
	 */
	public function set($name, $data, $lift_time = null){
		
	}
	
	/**
	 * 读取缓存,读取失败或缓存失效返回false
	 * @param $name string  缓存名称
	 */
	public function get($name){
		
	}
	
	/**
	 * 清除指定缓存
	 * @param string $name 缓存名称
     * @return boolean
	 */
	public function remove($name){
		
	}
	
	/**
	 * 清除所有缓存
	 */
 	public function clean(){
 		
 	}
 	
 	/**
	 * 获取缓存的时效
	 * @return int $this->lifetime
	 */
 	public function getLifetime(){
 		
 	}
 	
 	/**
	 * 设置缓存的时效
	 * @param int $lifetime
	 */
 	public function setLifetime($lifetime=300){
 		
 	}
 	
 	/**
     * 获取缓存的信息
     * @return array  缓存的信息
     */
    public function getInfo(){
    	
    }

    /**
     * 解析Redis配置信息
     * @param array $config  配置信息
     * @return void
     */
    protected function _parseConfig($config, $type='file'){
	    if(empty($config)){
            return false;
        }
        switch(strtoupper($config['config_type'])){
	        case 'SERVER' :
	        	$redis_server_key = $config['server_key'];
	        	$server_configs = VO_Http::getServer($redis_server_key);
	        	$server_configs = trim($server_configs);
	        	if(!empty($server_configs)){
		        	$configs = explode(' ', $server_configs);
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
    
    
}