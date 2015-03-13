<?php
/**
 * 定义 VO_Search 搜索对外接口层
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

class VO_Search{
	
	/**
	 * 搜索引擎对象存储器
	 * @var array
	 */
	protected static $search_instances = array();
	
	/**
	 * 构造函数
	 */
	public function __construct(){}
	
	/**
	 * 获取搜索引擎实例
	 * @param Array  $config 
	 * @return VO_Search_Adapter_Abstract
	 */
	public static function getInstance(array $search_config=null){
		if(empty($search_config)){
			$search_config = C('search');
		}
		
		$key = md5(serialize($search_config));
		if(isset(self::$search_instances[$key]) && self::$search_instances[$key] instanceof VO_Search_Adapter_Abstract){
			return self::$search_instances[$key];
		}
		
		VO_Loader::import('Search.Adapter.Abstract');
		if(empty($search_config) ){
			$this->triggerError('搜索引擎配置参数不正确，请检查配置参数.');
			exit;
		}
		if(!is_array($search_config) && !is_object($search_config)){
			$this->triggerError('搜索引擎配置参数必须为数组或是对象.');
			exit;
		}
		
		$adapter = is_object($search_config) ? $search_config->adapter : $search_config['adapter'];
		if(!$adapter){
			$this->triggerError('搜索引擎配置适配器不正确，请确认VOPHP是否支持现有的数据适配器.');
			exit;
		}
		
		$db_adapter_namespace = 'VO_Search_Adapter';

		
		$adapter_config = $search_config[$adapter];
		
		//首字母大写
		$adapters = explode('_', $adapter);
		foreach($adapters as $k => $v){
			$adapters[$k] == ucfirst($v);
		}
		$adapter = implode('_', $adapters);
		$adapter = $db_adapter_namespace . '_' . $adapter;
		self::$search_instances[$key] = new $adapter( (array)$adapter_config );
		return self::$search_instances[$key];
	}
}