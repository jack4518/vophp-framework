<?php
/**
 * 定义VO_Controller 控制器基类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-25
 **/


defined('VOPHP') or die('Restricted access');

class VO_Controller extends VO_Object{
	
	/**
	 * 当前的应用名称
	 * @var string
	 */
	protected $_app = null;
	
	/**
	 * 当前的命名空间名称
	 * @var string
	 */
	protected $_namespace = null;
	
	/**
	 * 当前的控制器名称
	 * @var string
	 */
	protected $_controller = null;
	
	/**
	 * 当前的动作器名称
	 * @var string
	 */
	protected $_action = null;
	
	/**
	 * HTTP请求对象
	 * @var VO_HTTP
	 */
	protected $_request = null;
	
	/**
	 * 验证器对象
	 * @var VO_Validator
	 */
	protected $_validator = null;
	
	/**
	 * ACL权限对象
	 * @var VO_ACL
	 */
	protected $_auth = null;
	
	/**
	 * 助手管理类
	 * @var VO_Helper
	 */
	protected $_helper = null; 
	
	/**
	 * 配置文件信息
	 * @var array
	 */
	protected $_config = array();
	
	/**
	 * 视图对象
	 * @var VO_View
	 */
	protected $_view = null;
	
	/**
	 * 是否需要Layout
	 * @var bool
	 */
	protected $_is_need_layout = true;
	
	/**
	 * Layout文件名
	 * @var string
	 */
	protected $_layout_file = '';
	
	/**
	 * 构造器
	 * @return VO_Controller
	 */
	public function __construct(){ }
	
	/**
	 * 初始化方法
	 */
	public function init(){
		$this->_config = C();
		$router = $this->getCurrentRoute();
		$this->_app = $router['app'];
		$this->_controller = $router['controller'];
		$this->_action = $router['action'];
		$this->getRequest();
	}
	
	/**
	 * 获取HTTP对象
	 * @return VO_Http
	 */
	public function getRequest(){
		if(is_null($this->_request)){
			$this->_request = VO_Http::getInstance();
		}
		return $this->_request;
		
	}
	
	/**
	 * 获取指定的$_GET的值，若不存在，则返回$_GET数组
	 * @param String $key default null
	 * @param String $default 默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public function getVar($key = null, $default = null, $filter=null){
		return $this->getRequest()->getQuery($key, $default, $filter);
	}
	
	/**
	 * 获取指定的$_POST的值，若不存在，则返回$_POST数组
	 * @param String $key default null
	 * @param String $default 默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public function postVar($key = null, $default = null, $filter=null){
		return $this->getRequest()->getPost($key, $default, $filter);
	}
	
	/**
	 * 获取全部的请求数据,包括$_GET、$_POST等
	 * @param String $key default null
	 * @param String $default 默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public function getParams($key = null, $default = null, $filter=null){
		return $this->getRequest()->getParam($key, $default, $filter);
	}
	
	
	public function getViewer(){
		$instance = VO_View::getInstance();
		//var_dump($instance);
		return $instance;
	}
	
	public function display($tpl = ''){
		error_reporting(0);
		
		if(empty($tpl)){
			$tpl = $this->_controller . '.' . $this->_action;
		}
		$tpl .= C('view.file_ext');
		$viewer = $this->getViewer();
		if($this->_is_need_layout){
			$output = $viewer->fetch($tpl);
			$layout_file = $this->getLayoutName() .C('view.file_ext');
			$viewer->assign('content', $output);
			$output = $viewer->fetch($layout_file);
			echo $output;
		}else{
			$viewer->display($tpl);
		}
	}
	
	public function fetch($tpl = ''){
		error_reporting(E_ALL ^ E_NOTICE);
		if(empty($tpl)){
			$tpl = $this->_controller . '.' . $this->_action;
		}
		$tpl .= C('view.file_ext');
		$viewer = $this->getViewer();
		if($this->_is_need_layout){
			$output = $viewer->fetch($tpl);
			$layout_file = $this->getLayoutName() .C('view.file_ext');
			$viewer->assign('content', $output);
			$output = $viewer->fetch($layout_file);
			return $output;
		}else{
			return $viewer->fetch($tpl);
		}
	}
	
	public function assign($var, $value=''){
		$viewer = $this->getViewer();
		$viewer->assign($var, $value);
	}
	
	public function isNeedLayout($is_need=true){
		$this->_is_need_layout = $is_need;
	}
	
	public function setLayoutName($filename=''){
		if(!empty($filename)){
			$this->_layout_file = APP_DIR . DS . '_global' . DS . 'layouts' .DS . $filename;
		}
	}
	
	public function getLayoutName(){
		if(empty($this->_layout_file)){
			$layout_file = C('view.layout_file');
			$this->_layout_file = APP_DIR . DS . '_global' . DS . 'layouts' .DS . $layout_file;
		}
		return $this->_layout_file;
	}
	
	/**
	 * 设置网站的title标题
	 * @param string $title
	 */
	public function setPageTitle($title){
		$viewer = $this->getViewer();
		$viewer->assign('page_title', $title);
	}

	/**
	 * 获取HTTP对象
	 * @return VO_View
	 */
	/*
	public function getViewer($key = ''){
		static $instances = array(); 
		if(empty($key)){
			$key = $this->_config['view']['config'];
		}
		if(!isset($instances[$key])){
			$this->_view = VO_View::getInstance($key);
			$instances[$key] = $this->_view;
		}
		return $instances[$key];
	}
	*/
	
	/**
	 * 获取验证器对象
	 * @return VO_Validator
	 */
	public function getValidator(){
		if(is_null($this->_validator)){
			$this->_validator = VO_Validator::getInstance();
		}
		return $this->_validator;
	}
	
	/**
	 * 获取ACL对象
	 * @return VO_ACL
	 */
	public function getAuth(){
		if(is_null($this->_auth)){
			$this->_auth = VO_ACL::getInstance();
		}
		return $this->_auth;
	}
	
	/**
	 * 获取当前URL路由
	 * @return array
	 */
	public function getCurrentRoute(){
		$params = $this->getRequest()->getParams();
		$routers = array(
			'app'		=>	$params['app'],
			'controller'	=>	$params['controller'],
			'action'		=>	$params['action']
		);
		return $routers;
	}
	
	/**
	 * 获取当前的模块名称
	 * @return string 当前模块
	 */
	public function getApp(){
		$route = $this->getCurrentRoute();
		return $route['app'];
	}
	
	/**
	 * 获取当前的控制器名称
	 * @return string 当前模块
	 */
	public function getController(){
		$route = $this->getCurrentRoute();
		return $route['controller'];
	}
	
	/**
	 * 获取当前的动作器名称
	 * @return string 当前模块
	 */
	public function getAction(){
		$route = $this->getCurrentRoute();
		return $route['action'];
	}
	
	/**
	 * 获取助手对象
	 * @return VO_Helper_Abstract
	 */
	public function getHelper($name, $module=''){
		if(!empty($name)){
			return VO_Helper::getInstance()->getHelper($name, $module);
		}else{
			$this->triggerError('助手类名称不能空。');
		}
	}	
	
	/**
	 * 加载模型文件
	 * @param string  $name		模型名称
	 * @param string  $module	模块名称
	 * @param string  $namespace	命名空间的名称
	 * @return VO_Model		模型名称$name对应的模型对象
	 */
	public function loadModel($name, $app=''){
		if(empty($app)){
			$app = $this->_app;
		}
		return $this->getHelper('model')->loadModel($name, $app);
	}
	
	/**
	 * 错误控制器
	 */
	public function errorAction(){
		$error = new VO_Error();
		$error->error('无法找到动作器');
	}
	
	/**
	 * 重定向到指定URL
	 * @param string $url
	 */
	public function _redirect($url, $isJs = false){
		if( substr($url, 0, 1) == '/' ){
			$url = substr($url, 1);
		}
				
		if(substr($url, 0, 7) != 'http://'){
			$url = C('base.base_url'). '/' . $url;
		}
		if($isJs){
			echo '<script language="javascript">window.location.href="' . $url . '";</script>';
			exit;
		}else{
			header('Location:' . $url);
			exit;
		}
	}
	
	/**
	 * 转发到其它路由器
	 * @param string $action   动作器
	 * @param string $controller  控制器
	 * @param string $module   模块名
	 * @param string $namespace  命名空间
	 * @param string $params 附带参数
	 */
	public function _forword($action, $controller, $module='', $namespace='', $params=array()){
		$defaultRouter = $this->_config['defaultrouter'];
		$uri = array();
		
		$uri['action'] = $action;
		$uri['controller'] = $controller;
		$uri['module'] = $module;
		$uri['namespace'] = $namespace;
		
		$this->getHelper('array')->removeEmpty($uri);
		if(!empty($params)){
			$params_arr = array();
			if(!is_array($params)){
				$this->triggerError('URL参数必须是一个关联数组。');
			}else{
				$params_arr = $params;
				foreach($params as $k => $v){
					if(!is_string($k)){
						$this->triggerError('URL参数必须是一个关联数组。');
					}
				}
			}
			VO_Http::setQuery($params_arr);
		}
		$uri = array_merge( $defaultRouter, $uri );
		$Dispathcher = VO_Application_Dispatcher::getInstance();
		$Dispathcher->dispatch($uri);

	}
}