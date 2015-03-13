<?php
/**
 * 定义VO_Application_Router URL路由器类
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

Class VO_Router_Pathinfo{
	
	/**
	 * 解析后的路由存储器
	 * @var array
	 */
	protected $_route = array();
	
	/**
	 * 框架配置存储器
	 * @var array
	 */
	protected $_config = array();
	
	/**
	 * 框架静态路由规则
	 * @var array
	 */
	protected $_rules = array();
	
	/**
	 * 框架正则路由规则
	 * @var array
	 */
	protected $_regex_rules = array();
	
	/**
	 * 框架标准路由规则
	 * @var array
	 */
	protected $_standard_rules = array();
	
	/**
	 * 构造函数
	 * @param array $config
	 * @return VO_Router_Pathinfo
	 */
	public function __construct(){
		$this->_config = VO_Factory::getConfig();
		$this->_loadRoute();
	}
	
	/**
	 * 获取单一实例
	 * @param array $config
	 * @return VO_Router_Pathinfo
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Application_Router ){
			$instance = new self();
		}
		return $instance;
	}
	
	public function loadRouterConfig(){
		$file = CONFIG_DIR . DS . 'routers' . DS . 'standard.php';
		if( !file_exists($file) ){
			throw new VO_Exception('路由文件:'. $file  .'不存在', 404);
			exit;
		}
		$rules = @include( $file );
		
		if( !is_array($rules) ){
			return array();
		}
		foreach( $rules as $k => $rule ){
			$this->_rules[] = $rule;
		}
	}
}