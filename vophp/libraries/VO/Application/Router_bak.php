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

Class VO_Application_Router{
	
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
	protected $_static_rules = array();
	
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
	
	private $_url = '/';
	
	private $_uri = array();
	
	private $_query_string = '';
	
	/**
	 * 构造函数
	 * @param array $config
	 * @return VO_Application_Router
	 */
	public function __construct(){
		$this->_config = C();
		
		$static_router = VO_Router_Standard::getInstance();
		$regex_router = VO_Router_Rewrite::getInstance();
		$this->_static_rules = $static_router->loadRouterConfig();
		$this->_regex_rules = $regex_router->loadRouterConfig();
		var_dump($this->_regex_rules);
	}
	
	/**
	 * 获取单一实例
	 * @param array $config
	 * @return VO_Application_Router
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Application_Router ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 获取URL的URI参数
	 */
	private function getUri(){
		$uri = '';

		$url = $this->getPathInfo();
		if(empty($url)){
			$route = $this->getRouteByStandard();
		}else{
			$static_route = $this->matchStaticRoute($uri);
			if( !$static_route ){ //匹配静态路由
				if( ! $this->matchRegxRoute($uri) ){ //匹配泛路由
					if( ! $this->matchStandardRoute($uri)){ //匹配标准路由
						
					}
				}
			}
			$this->_route = $this->fillDefaultRoute($this->_route); //填充标准路由
		}

		$this->_uri =$this->parseUri();
		var_dump($this->_route);
		//通过标准的URL获取Query字符串
		
		$uri = '';
	}
	
	/**
	 * 以PATH_INFO的形式获取URL的值
	 * @return string  pathinfo字符串
	 */
	private function getPathInfo(){
		$url = '';
		if(!empty($_SERVER['PATH_INFO'])){
            $pathinfo = $_SERVER['PATH_INFO'];
            if(strpos($pathinfo,$_SERVER['SCRIPT_NAME']) === 0){
                $url = substr($pathinfo, strlen($_SERVER['SCRIPT_NAME']));
            }else{
                $url = $pathinfo;
            }
        }elseif(!empty($_SERVER['ORIG_PATH_INFO'])) {
            $pathinfo = $_SERVER['ORIG_PATH_INFO'];
            if(strpos($pathinfo, $_SERVER['SCRIPT_NAME']) === 0){
                $url = substr($pathinfo, strlen($_SERVER['SCRIPT_NAME']));
            }else{
                $url = $pathinfo;
            }
        }elseif (!empty($_SERVER['REDIRECT_PATH_INFO'])){
            $url = $_SERVER['REDIRECT_PATH_INFO'];
        }elseif(!empty($_SERVER["REDIRECT_Url"])){
            $url = $_SERVER["REDIRECT_Url"];
            if(empty($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] == $_SERVER["REDIRECT_QUERY_STRING"]){
                $parsed_url = parse_url($_SERVER["REQUEST_URI"]);
                if(!empty($parsed_url['query'])) {
                    $_SERVER['QUERY_STRING'] = $parsed_url['query'];
                    parse_str($parsed_url['query'], $GET);
                    $_GET = array_merge($_GET, $GET);
                    reset($_GET);
                }else {
                    unset($_SERVER['QUERY_STRING']);
                }
                reset($_SERVER);
            }
        }
        $_SERVER['PATH_INFO'] = empty($url) ? '/' : $url;
        return $url;
	}
	
	public function getRouteByStandard(){
		$module_key = $this->_config['app']['application_key']['module'];
		$controller_key = $this->_config['app']['application_key']['controller'];
		$action_key = $this->_config['app']['application_key']['action'];
		$query = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		if( !empty($query) ){
			$querys = VO_Http::getQuery();
			//取得模块值
			if( isset($querys[$module_key]) ){
				$this->_route[$module_key] = $querys[$module_key];
			}
			//取得控制器值
			if( isset($querys[$controller_key]) ){
				$this->_route[$controller_key] = $querys[$controller_key];
			}
			//取得动作器值
			if( isset($querys[$action_key]) ){
				$this->_route[$action_key] = $querys[$action_key];
			}
			unset($_GET[$module_key]);
			unset($_GET[$controller_key]);
			unset($_GET[$action_key]);
			
			return $this->_route;
		}
	}
	
	public function parseUri(){
		$query = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		var_dump($query);
	}
	
	/**
	 * 匹配路由
	 * @param string $route URI字符串，如：'/blog/user/add/id/7'
	 * @return array
	 */
	private function _matchRoute( $uri, $rules ){
		$ret = false;
		$uris = explode('/', $uri); //url参数
		foreach($rules as $k => $rule){
			$match = array();  //匹配规则
			$replace = array();  //替换规则(基于":"的参数)
			if(substr($rule['rule'], 0, 1) != '/'){
				$rule['rule'] = '/' . $rule['rule'];
			}
			preg_match_all('/\/([\w]+)/', $rule['rule'], $match);
			preg_match_all('/:([\w]+)\/?/', $rule['rule'], $replace);
			if(empty($replace)){
				continue;
			}
			$match = $match[1];
			$replace = $replace[1];
			$return = $rule['router'];  //匹配规则中定义的返回规则
			$isMacth = true;
			$temp_match = $match;
			$querys = $uris;
			foreach ($match as $key => $val){
				if(!isset($uris[$key])){
					break;
				}
				if( $val == $uris[$key]){
					array_shift($querys);
					$isMacth = true;
				}else{
					$isMacth = false;
				}
				array_shift($temp_match);
			}
			if( $isMacth === true && empty($temp_match) ){
				$result = array();
				foreach($replace as $k => $v){
					$result[$v] = array_shift($querys);
				}
				//合并其它参数
				$param = $this->parseParams($querys);
				$this->_route = array_merge($return, $result);
				$this->_route = array_merge($this->_route, $param);
				$ret = true; 
				break;
			}
		}
		return $ret;
	}
	
	/**
	 * 匹配标准路由
	 */
	public function matchStandardRoute($uri){
		$ret = false; //是否解析成功
		$standardRouteFile = VO_CONFIG_DIR . DS . 'routers.standard.php';
		if(!file_exists($standardRouteFile)){
			$this->_route = $this->_config['defaultrouter'];
			$ret = true;
		}else{
			$this->_standard_rules = include $standardRouteFile;
			if(empty($this->_standard_rules)){
				$this->_route = $this->_config['defaultrouter'];
				$ret = true;
			}else{
				$ret = $this->_matchRoute($uri, $this->_standard_rules);
			}
			return $ret;
		}
	}
	
	/**
	 * 匹配静态路由
	 * @param array $uris 分解后的URL参数
	 */
	public function matchStaticRoute($uri){
		$ret = false; //是否解析成功
		if(empty($this->_static_rules)){
			return $ret;
		}
		$ret = $this->_matchRoute($uri, $this->_static_rules);
		return $ret;
	}
	
	/**
	 * 匹配泛路由
	 * @param array $uris 分解后的URL参数
	 */
	public function matchRegxRoute($uri){
		$ret = false; //是否解析成功
		if(empty($this->_regex_rules)){
			return $ret;
		}
		foreach($this->_regex_rules as $k => $rule){
			$matchResult = '';  //匹配规则
			$regx = str_replace('/', '\/', $rule['regex']);
			$regx = '/^' . $regx . '/';
			preg_match_all($regx, $uri, $matchResult);
			if( empty($matchResult[0]) ){
				continue;
			}
			foreach($matchResult as $k => $v){
				$matchValue[$k] = $v[0];
			}
			$return = $rule['router'];  //匹配规则中定义的返回规则
			$match = $rule['match']; //匹配规则中定义的匹配项
			
			$result = array();
			foreach($match as $k => $v){
				if(isset($matchValue[$k])){
					$result[$v] = $matchValue[$k];
				}else if(isset($return[$k])){
					$result[$v] = $return[$k];
				}else{
					$result[$v] = '';
				}
			}

			$this->_route = array_merge($return, $result);
			$ret = true;
			break;
		}
		return $ret;
	}
	
	/**
	 * 填充默认路由
	 * @param array $route
	 * @return array
	 */
	public function fillDefaultRoute($route){
		$default = $this->_config['app']['default_router'];
		foreach ($route as $k => $v){
			if( $v == '' && isset($default[$k]) ){
				$route[$k] = $default[$k];
			}
		}
		return $route;
	}

	/**
	 * 解析参数,将
	 * 	array(
	 * 		'page'	=>	'',
	 * 		'1'	=> '',
	 * 		'title'	=>	'',
	 * 		'abc'	=> '',
	 * 	)
	 * 
	 * 解析成 
	 * array(
	 * 		'page'	=>1,
	 * 		'title'	=> 'abc',
	 * )
	 * @param array $array
	 */
	public function parseParams($array=array()){
		$params = array();
		for($i=0; $i<count($array); $i=$i+2){
			if( !is_numeric($array[$i]) && ($array[$i]<>'') ){
				if(isset($array[$i+1])){     
					$params[$array[$i]] = $array[$i + 1];
				}else{
					$params[$array[$i]] = '';
				}     
			}
		}
		return $params;
	}
	
	/**
	 * 反向解析路由
	 */
	public function reverseRoute(array $route){
		$base_url = $this->_config['base_url'];
		switch( strtoupper($this->_config['route_mode']) ){
			case 'PATHINFO' : 
				$url = $base_url . '/index.php/' . $route['module'] . '/' . $route['namespace'] . '/' . $route['controller'] . '/' . $route['action'];
				break;
				
			case 'REWRITE':
				$url = $base_url . '/' . $route['module'] . '/' . $route['namespace'] . '/' . $route['controller'] . '/' . $route['action'];
				break;
				
			case 'NORMAL':
			default:
				$url = $base_url . '/index.php?' . $this->_config['application_key']['moduleKey'] . '=' . $route['module'] . '&' . $this->_config['application_key']['namespaceKey'] . '=' . $route['namespace'] . '&' . $this->_config['application_key']['controllerKey'] . '=' . $route['controller'] . '&' . $this->_config['application_key']['actionKey'] . '=' . $route['action'];
				break;
		}
		$url = preg_replace('/[\/]+/', '/', $url);
		return $url;
	}
	
	/**
	 * 功能：获取解析后的URL路由
	 * 参数： 无
	 * 返回值：null
	 */
	public function parse(){
		$uri = $this->getUri();
		VO_Http::setUserParam($this->_route);
		return $this->_route;
	}
}