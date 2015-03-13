<?php
/**
 * 定义VO_Application 应用程序实现类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-05-25
 **/

defined('VOPHP') or die('Restricted access');

class VO_Application extends VO_Object{
	
	/**
	 * 控制器的VO_Http实例
	 * @var	VO_Http
	 */
	public $_request = null;
	
	/**
	 * 构造函数
	 * @param	array	$config
	 */
	public function __construct(){
		parent::__construct();
		//设置系统时区
		if( C('base.date_timezone') ){
			date_default_timezone_set(C('base.date_timezone'));
		}
		//初始化HTTP对象
		$this->_request = VO_Http::getInstance();
		$this->_init();
	}
	
	/**
	 * 获取单一实例
	 * @return	VO_Application
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Application ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 初始化
	 * 
	 * @return void
	 */
	private function _init(){
		//友好显示用户信息,通过自定义错误和异常处理方法显示
		if( C('error.friendly_error') ){
			define('IS_DEBUG', true);
			//定义是否由于程序错误而导致的崩溃,在VO_Exception异常类的构造函数里定义为false,意指程序为正常终止
			VO_Registry::set('is_crumble', true);
			ini_set('display_errors', false);
			
			// 设定自定义错误和异常处理句柄
	        set_error_handler(array(&$this, "errorHandler"));
	        set_exception_handler(array(&$this, "exceptionHandler"));
			//注册脚本结束后的调用函数
			register_shutdown_function(array(&$this, 'catchError'));
		}else{
			ini_set('display_errors', true);
			$error_levels = C('error.errorLevel');
			$error_level = $error_levels[0];
			foreach($error_levels as $k => $level){
				if($k == 0) continue;
				$error_level ^= $level;
			}
			error_reporting($error_level);
		}
	}
	
	/**
     * 自定义异常处理
     * @param Exception $exception 异常对象
     * @return void
     */
    public static function exceptionHandler($exception){ 	
    	$traceStr = '';
		$code = $exception->getCode();
		$message = $exception->getMessage();
		$traces = $exception->getTrace();
		if($traces){
			foreach($traces as $key => $v){
				if( isset($v['file']) && !empty($v['file']) ){
					$traceStr .= $v['file'] . '　line ' . $v['line'] . '　';
				}
				if( isset($v['class']) && !empty($v['class']) ){
					$traceStr .= $v['class'] . '::';
				}
				if( isset($v['function']) && !empty($v['function']) ){
					$traceStr .= $v['function'] . '()<br />';
				}
			}
		}
		
		//记录日志
		$log = new VO_Log();
		$log->log($message, 'EXCEPTION');
		
		$error = new VO_Error();
		//$trace = debug_backtrace();
		$error->error($message, $code, null, null, $trace);
    }

    /**
     * 自定义错误处理
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    public function errorHandler($errno, $message, $file, $line=0){
		$params = func_get_args();
		if(VO_Registry::get('is_crumble') === false){
			//return false;
		}
		if(count($params) < 4){
			return false;
		}
		$errcontext = $params[4];
		/*
		var_dump($params);
		var_dump(C('error.errorLevel'));
		var_dump($errcontext['e']['type']);
		*/
		$error_type = !isset($errcontext['e']['type']) ? $params[0] : $errcontext['e']['type'];
		if(in_array($error_type, C('error.errorLevel') )){
			$method = 'error';
			if(!empty($errcontext) && isset($errcontext['e']) ){
				$err_info = $errcontext['e'];
				if(is_array($err_info)){
					$message = $err_info['message'];
					$line = $err_info['line'];
					$file = $err_info['file'];
					$code = $err_info['type'];
				}	
			}else{
				$message = $params[1];
				$file = $params[2];
				$line = $params[3];
				$code = '500';
			}
			switch ($errno) {
				case E_ALL:
				case E_ERROR:
				case E_USER_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_RECOVERABLE_ERROR:
					$method = 'error';
					break;
				
				case E_WARNING:
				case E_USER_WARNING:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
					$method = 'warning';
					break;

				case E_NOTICE:
				case E_USER_NOTICE:
				case E_STRICT:
					$method = 'notice';
					break;
				
				case E_DEPRECATED:
				case E_PARSE:
				default:
					$method = 'warning';
					break;
			}
			//var_dump($method,  C('error.errorLevel'));exit;
			$log = new VO_Log();
			$log_str = '[' . $errno . '] "' . $message . '" at ' . basename($file). ' line ' . $line . '';
			$log->log($log_str, strtoupper($method));
			
			//设置程序为正常终止，非系统错误导致的程序终止
			VO_Registry::set('is_crumble', false);
			$error = new VO_Error();
			$trace = debug_backtrace();
			$error->$method($message, $code, $file, $line, $trace);
		}
	}
    
	/**
	 * 捕获系统的最后一次错误，并以trigger_error()方法抛出，以供自定义的错误处理句柄方法捕获
	 * @return void
	 */
	public function catchError(){
		if(is_null($e = error_get_last()) === false){
			$e['is_user_trigger'] = true;
			$str = json_encode($e);
			if($e['type'] == 1){ 
				trigger_error($str, E_USER_ERROR); 
			}elseif($e['type'] == 8){ 
				trigger_error($str, E_USER_NOTICE); 
			}elseif($e['type'] == 2){
				trigger_error($str, E_USER_WARNING); 
			}else{
				trigger_error($str, E_USER_ERROR); 
			} 
		}
	}	

	/**
	 * 解析URL路由并分发到指定控制器
	 * @return null
	 */
	public function run(){
		$router = VO_Application_Router::getInstance();
		$route = $router->parse(); //获取路由
		//分发控制器的动作器
		$dispatcher = VO_Application_Dispatcher::getInstance();
		$dispatcher->dispatch($route);
	}
}