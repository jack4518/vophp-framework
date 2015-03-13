<?php
/**
 * 定义 VO_Factory 工厂类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Factory{
	
	/**
	 * 数据库对象存储器
	 * @var array
	 */
	protected static $db_instances = array();
	
	/**
	 * 构造函数
	 */
	public function __construct(){}
	
	/**
	 * 获取数据库实例
	 * @return VO_Database_Adapter_Abstract
	 */
	public static function getDb($key=null){
		$config = self::getConfig();
		if(empty($key)){
			$key ='write';
		}
		$db_config = $config['db'][$key];
		
		//设置默认数据库链接
		if(array_key_exists($key, self::$db_instances)){
			return self::$db_instances[$key];
		}elseif( (array_key_exists($key, $config['db'])) ){
			self::setDb($db_config, $key);
			return self::$db_instances[$key];
		}else{
			$this->triggerError('数据库句柄"' . $key . '"尚未初始化，请在配置文件中的"db"其中定义键为"' . $key . '"的数据库配置信息或者使用VO_Factory::setDb()对数据库进行初始化.');
			exit;
		}
	}
	
	/**
	 * 获取数据库实例
	 * @param Array  $config 
	 * @return VO_Database_Adapter_Abstract
	 */
	public static function setDb(array $db_config=null, $key='default'){
		if(isset(self::$db_instances[$key]) && self::$db_instances[$key] instanceof VO_Database_Adapter_Abstract){
			return self::$db_instances[$key];
		}
		VO_Loader::import('Database.Adapter.Abstract');
		if(empty($db_config) ){
			$this->triggerError('数据库配置参数不正确，请检查配置参数.');
			exit;
		}
		if(!is_array($db_config) && !is_object($db_config)){
			$this->triggerError('数据库配置参数必须为数组或是对象.');
			exit;
		}
		
		$adapter = is_object($db_config) ? $config->adapter : $db_config['adapter'];
		if(!$adapter){
			$this->triggerError('数据库配置适配器不正确，请确认VOPHP是否支持现有的数据适配器.');
			exit;
		}
		
		$db_adapter_namespace = 'VO_Database_Adapter';
		
		//首字母大写
		$adapters = explode('_', $adapter);
		foreach($adapters as $k => $v){
			$adapters[$k] == ucfirst($v);
		}
		$adapter = implode('_', $adapters);
		$adapter = $db_adapter_namespace . '_' . $adapter;
		self::$db_instances[$key] = new $adapter( (array)$db_config );
		
		VO_Registry::set('db', self::$db_instances[$key]);
		return self::$db_instances[$key];
	}
	
	/**
	 * 获取配置文件信息
	 * @return array
	 */
	public static function getConfig($key=null){
		if( VO_Registry::isRegistered('config') ){
			$conf = VO_Registry::get('config');
		}else{
			$configer = VO_Config::getInstance();
			$conf = $configer->get($key);
		}
		if(!empty($key)){
			return $conf[$key];
		}else{
			return $conf;
		}
	}
	
	/**
	 * 获取默认的视图信息
	 * @return array
	 */
	public static function getView(){
		return VO_View::getEngine();
	}
	
	/**
	 * 获取默认的视图信息
	 * @return VO_Log
	 */
	public static function getLoger(){
		return VO_Log::getInstance();
	}	
	
	/**
	 * 获取配置文件信息
	 * @return VO_Language_Abstract
	 */
	public static function getLanguage(){
		static $instance = null;
		if( !$instance instanceof VO_Language){
			$language = new VO_Language();
			$instance = $language;
		}
		return $instance;
	}
	
}