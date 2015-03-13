<?php
/**
 * 定义  VO_Application_Dispatcher  分发器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-24
 **/

defined('VOPHP') or die('Restricted access');

class VO_Application_Dispatcher extends VO_Object{
		
	/**
	 * 构造方法
	 * @return VO_Application_Dispatcher
	 */
	public function __construct(){
	}
	
	/**
	 * 获取单一实例
	 * @param array $config
	 * @return VO_Application_Dispatcher
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Application_Dispatcher ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 检查路由器中的模块、命名空间、控制器、动作器名称是否合法
	 * @param array $route　 解析后的路由参数
	 * @return bool
	 */
	private function checkRoute($route){
		$errors = '';
		$rules = array(
			'app' => array(
				array('isAlpha', '应用名称只能为字母'),
				
			),
			'controller' => array(
				array('isAlpha', '控制器名称只能为字母'),
				
			),
			'action' => array(
				array('isAlpha', '动作器名称只能为字母'),
				
			),
		);
		$validator = VO_Validator::getInstance();
		$ret = $validator->validateRowsWithRules($route, $rules, $errors);
		if(!$ret){
			$error = array_shift($errors);
			$this->triggerError($error);
			exit;
		}
		return true;
	}
	
	/**
	 * 分发到指定的控制器和动作器
	 * @param array $route  解析后的路由参数
	 * @return void
	 */
	public function dispatch($route){
        $config = C();
		foreach ($route as $k => $v){
			if(empty($v) && array_key_exists($k, $config['app']['default_router'])){
				$route[$k] = $config['app']['default_router'][$k];
			}
		}
		$this->checkRoute($route);
		//获取当前模块的目录,及解析当前的控制器名称
		$class = '';
		$app_path = APP_DIR . DS . $route['app'] . DS ;
		$class .= ucfirst($route['controller']) . 'Controller';
		$controller_file =  $app_path . 'controllers' . DS . strtolower($route['controller']) . '.php';
		$controller_file = str_replace(array('//', '\\\\'), DS, $controller_file);
		if(is_dir($app_path)){
			if( file_exists( $controller_file ) ){
				include $controller_file; //包含控制器文件
				$controller = new $class();  //获取控制器实例
				$methods = get_class_methods($controller);
				$methodsLower = array();
				foreach($methods as $k => $method){
					$methodsLower[] = trim(strtolower($method));
				}			
				$key = array_search($route['action'].'action', $methodsLower);
				if($key !== false){
					//执行初始化函数
					$controller->init();
					
					$action = $methods[$key];
					$controller->$action();  //执行控制器中的动作器
				}else{
					if(array_search('error', $methodsLower)){
						$controller->errorAction(); //执行错误动作器
						exit;
					}else{
						$this->triggerError('无法在应用目录' . $route['app'] . '下的' . $route['controller'] . '控制器中找到名为' . $route['action'] . '动作器', E_USER_ERROR);
					}
				}
			}else{
				$this->triggerError('无法在' . $route['app'] . '应用中找到' . $route['controller'] . '控制器', E_USER_ERROR);
			}
		}else{
			$this->triggerError('应用目录' .  $route['app'] . '不存在', E_USER_ERROR);
		}
	} 
}