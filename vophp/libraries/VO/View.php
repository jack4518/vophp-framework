<?php
/**
 * 定义  VO_View 视图渲染类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-10
 **/

defined('VOPHP') or die('Restricted access');


class VO_View{
	
	/**
	 * 配置信息变量
	 * @var array
	 */
	protected static $_config = array(
		'engine'	=>	'php', //默认视图引擎
	);

	protected static $options = array();
	
	/**
	 * 视图引擎存储器
	 * @var array
	 */
	protected static $_adapter = array(
		'php' => 'VO_View_Adapter_Php',
		'votpl'	=> 'VO_View_Adapter_Votpl',
		'smarty'	=>	'VO_View_Adapter_Smarty',
	);
	
	/**
	 * 构造函数
	 */
	public function __construct($key='', array $options=null){
		self::getViewConfig($key, $options);
	}
	
	/**
	 * 获取配置信息
	 * @param array $options
	 */
	public static function getViewConfig($key, $view_config){
		self::$_config = C('view');
		//如果传入的视图不为空，但是没有设置key,那么就会覆盖默认的
		if( !empty($view_config) && empty($key) ){
			$key = self::$_config['engine'];
		}
		
		if(!empty($view_config)){
			$view_config = self::$_config['engine'][$key];
		}else{
			$view_config = array_merge(self::$options, $view_config);
		}
		return $view_config;
	}

	/**
	 * 获取模板引擎实例
	 * @return VO_View_Adapter_Abstract
	 */
	public static function getInstance($key = '', array $view_config=array()){
		self::getViewConfig($key, $view_config);
		$engine =  self::getEngine();
		return $engine::getInstance();
	}
	
	/**
	 * 设置模板引擎
	 */
	public static function setEngine($key='', $view_config=null ){
		//如果传入的视图不为空，但是没有设置key,那么就会覆盖默认的
		if(empty($key)){
			$this->triggerError('请设置视图句柄的名称');
		}

		if( empty($view_config) && (array_key_exists($key, self::$_config['engine'])) ){
			$view_config = self::$_config['engine'][$key];
		}elseif( empty($view_config) && (!array_key_exists($key, self::$_config['engine'])) ){
			$this->triggerError('视图引擎的配置参数错误，请检查');
		}
	
		$adapter = $view_config['adapter'];
		if(array_key_exists($adapter, self::$_adapter)){
			$adapter = self::$_adapter[$adapter];
			self::$_engines[$key] = new $adapter($view_config);
		}else{
			$this->triggerError('VOPHP尚不支持视图引擎"' . $key . '.');
		}
		return self::$_engines[$key];
	}

	/**
	 * 加载模板引擎
	 */
	public static function getEngine($key=''){
		if( empty($key) && isset(self::$_config['engine']) ){
			$key = self::$_config['engine'];
		}
		
		if(empty($key)){
			$this->triggerError('视图引擎尚未定义，请在配置文件中的"engine"  中定义视图配置信息，并为engine定义默认的视图引擎或者使用VO_View::setEngine($config, $key)对视图引擎进行初始化.');
			exit;
		}
		if(array_key_exists(strtolower($key), self::$_adapter)){
			return self::$_adapter[$key];  
		}elseif( (array_key_exists($key, self::$_config['engine'])) ){
			self::setEngine($key, self::$_config['engine'][$key]);
			return self::$_adapter[$key];
		}else{
			$this->triggerError('视图引擎"' . $key . '"尚未初始化，请在配置文件中的"views"  中定义键为"' . $key . '"的视图配置信息或者使用VO_View::setEngine($config, $key)对视图引擎进行初始化.');
			exit;
		}
	}
}	