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
	 * 标准路由规则
	 * @var array
	 */
	protected $_default_rules = array();	
	
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
    
    /**
     * PATHINFO变量
     */
    const PHTHINFO = 'PATHINFO';
    
    /**
     * REWRITE变量
     */
    const REWRITE = 'REWRITE';
    
    /**
     * STANDARD变量
     */
    const STANDARD = 'STANDARD';
	
	/**
	 * 构造函数
	 * @param array $config
	 * @return VO_Application_Router
	 */
	public function __construct(){
		$this->_loadRoute();
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
		$router_mode = C('app.router_mode');
        switch(strtoupper($router_mode)){
            case 'PATHINFO' : 
                return $this->parsePathInfo();
                break;
            
            case 'REWRITE' :
                return  $this->parseRewrite();
                break;
            
            case 'STANDARD':
            default :
                return $this->parseQueryString();
                break;
        }
	}
    
    /**
     * 通过URL重写解析URL
     * @return  array   路由数组
     */
    public function parsePathInfo(){	    
        $path_info = '';
        $path_info_var = C('app.path_info_var');
        
        if(isset($_SERVER['PATH_INFO']) && !empty($_SERVER["PATH_INFO"])){
            $path_info = $_SERVER['PATH_INFO'];
        }elseif( isset($_SERVER['ORIG_PATH_INFO']) && !empty($_SERVER['ORIG_PATH_INFO']) ){
            $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
        }elseif(isset($_SERVER["REDIRECT_URL"]) && !empty($_SERVER["REDIRECT_URL"]) ){
			$_SERVER['PATH_INFO'] = $_SERVER["REDIRECT_URL"];
		}elseif(isset($_SERVER["REDIRECT_QUERY_STRING"]) && !empty($_SERVER["REDIRECT_QUERY_STRING"]) ){
			$_SERVER['PATH_INFO'] = $_SERVER["REDIRECT_QUERY_STRING"];
		}elseif(isset($_SERVER["REQUEST_URI"]) && !empty($_SERVER["REQUEST_URI"]) ){
			$_SERVER['PATH_INFO'] = $_SERVER["REQUEST_URI"];
		}elseif(isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) ){
			$_SERVER['PATH_INFO'] = $_SERVER["QUERY_STRING"];
		}elseif( isset($_GET[$path_info_var]) && !empty($_GET[$path_info_var]) ){
            //WebServer 不支持PATH_INFO时,需要在设置app配置文件里的path_info_var变量，并在重写时以此为URL　QUERY_STRING的变量名称
            $_SERVER['PATH_INFO'] = $_GET[C('app.path_info_var')];  // 兼容PATHINFO 参数
    		unset($_GET[$path_info_var]);
  		}else{
			// 在FastCGI模式下面 $_SERVER["PATH_INFO"] 为空
			$_SERVER['PATH_INFO'] = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
        }
        $path_info = $_SERVER['PATH_INFO'];
        $uri = trim(substr($path_info, 1));
        if($uri){
            $uri = preg_replace('/(.*\.php)/i', '', $uri);
            if(substr($uri, 0, 1) == '/'){
    			$uri = substr($uri, 1);
    		}
        }        
		return $uri;
    }
    
    /**
     * 通过URL重写解析URL
     * @return  array   路由数组
     */
    public function parseRewrite(){
	    if(isset($_SERVER["REQUEST_URI"]) && !empty($_SERVER["REQUEST_URI"]) ){
			$uri = $_SERVER["REQUEST_URI"];
		}elseif(isset($_SERVER["REDIRECT_URL"]) && !empty($_SERVER["REDIRECT_URL"]) ){
			$uri = $_SERVER["REDIRECT_URL"];
		}elseif(isset($_SERVER["REDIRECT_QUERY_STRING"]) && !empty($_SERVER["REDIRECT_QUERY_STRING"]) ){
			$uri = $_SERVER["REDIRECT_QUERY_STRING"];
		}elseif(isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) ){
			$uri = $_SERVER["QUERY_STRING"];
		}else{
			$uri = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
		}
        $uri = preg_replace('/(.*\.php)/i', '', $uri);
        if(substr($uri, 0, 1) == '/'){
			$uri = substr($uri, 1);
		}
        return $uri;
    }
    
    /**
     * 通过正常的URL形式解析URL
     * @return  array   路由数组
     */
    public function parseQueryString(){
        $query = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		if( !empty($query) ){
			$route = array();
			$app_key = C('app.application_key.app');
			$controller_key = C('app.application_key.controller');
			$action_key = C('app.application_key.action');
			
			$querys = VO_Http::getQuery();
			//取得模块值
			if( isset($querys[$app_key]) ){
				$route[$app_key] = $querys[$app_key];
			}
			//取得控制器值
			if( isset($querys[$controller_key]) ){
				$route[$controller_key] = $querys[$controller_key];
			}
			//取得动作器值
			if( isset($querys[$action_key]) ){
				$route[$action_key] = $querys[$action_key];
			}
			unset($_GET[$app_key]);
			unset($_GET[$controller_key]);
			unset($_GET[$action_key]);
			$route = array_merge(C('app.default_router'), $route);
			return $route;
		//}
		}
    }
	
	/**
	 * 载入配置文件中定义的路由规则文件中的内容
	 */
	private function _loadRoute(){		
		$standard_router = include(CONFIG_DIR . DS . 'routers' . DS . 'standard.php');
		$this->_assignRoute($standard_router);
		
		$regex_router = include(CONFIG_DIR . DS . 'routers' . DS . 'regex.php');
		$this->_assignRoute($regex_router);
	}
	
	/**
	 * 分配路由(根据不同的路由规则，分配到不同的路由存储器)
	 * @param array $rules 路由规则
	 * @return void
	 */
	private function _assignRoute($rules){
		if( !is_array($rules) ){
			return array();
		}
		foreach( $rules as $k => $rule ){
			if(isset($rule['rule'])){
				$this->_static_rules[] = $rule;
			}else if(isset($rule['regex'])){
				$this->_regex_rules[] = $rule;
			}
		}
	}
	
	/**
	 * 匹配路由
	 * @param string $route URI字符串，如：'/blog/user/add/id/7'
	 * @return array
	 */
	private function _matchRoute( $uri, $rules ){
		$ret = false;
		$uri = str_replace(array('?', '&', '='), '/', $uri);
		$uri = trim($uri, '/');
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
				$params = $this->parseParams($querys);
				if(isset($return['params'])){
					$params = array_merge($return['params'], $params);
					unset($return['params']);
				}
				//合并其它参数
				$this->_route = array_merge($return, $result);
				$this->_route = array_merge($this->_route, $params);
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
			$this->_route = C('app.default_router');
			$ret = true;
		}else{
			$this->_standard_rules = include $standardRouteFile;
			if(empty($this->_standard_rules)){
				$this->_route = C('app.default_router');
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
		$default = C('app.default_router');
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
     * 从一个URL中提取路由信息
     * @param   string  $url    URL地址
     * @param   string  $type   URL模式(若为空则默认取app配置文件中的router_mode变量)
     * @return  array   解析后的路由信息
     */
    public function getRouteFromUrl($url, $type=null){
        $type = empty($type) ? C('app.router_mode') : $type;
        $route = $default_router = C('app.default_router');
        $router_mode = C('app.router_mode');
        
        if(empty($url)){
            return $route;
        }else{
            switch( strtoupper($type) ){
                case 'PATHINFO' :
                    $uri = preg_replace('/(.*\.php)/i', '', $url);
                    if(substr($uri, 0, 1) == '/'){
            			$uri = substr($uri, 1);
            		}
                    if(!empty($uri)){
                        $tmp = explode('/', $uri);
                        $route['app'] = isset($tmp[0]) ? $tmp[0] : $default_router['app'];
                        $route['controller'] = isset($tmp[1]) ? $tmp[1] : $default_router['controller'];
                        $route['action'] = isset($tmp[2]) ? $tmp[2] : $default_router['action'];
                        if(count($tmp) > 3){
                            for($i=3; $i<count($tmp); $i++){
                                if($i%2 <> 0){
                                    $key = $tmp[$i];
                                }
                                $route[$key] = $tmp[$i];
                            }
                        }
                    }
                    break;
                    
                case 'REWRITE' :
                    $base_url = C('base.base_url');
                    $uri = str_replace($base_url, '', $url);
                    if(substr($uri, 0, 1) == '/'){
            			$uri = substr($uri, 1);
            		}
                    if(!empty($uri)){
                        $tmp = explode('/', $uri);
                        $route['app'] = isset($tmp[0]) ? $tmp[0] : $default_router['app'];
                        $route['controller'] = isset($tmp[1]) ? $tmp[1] : $default_router['controller'];
                        $route['action'] = isset($tmp[2]) ? $tmp[2] : $default_router['action'];
                        if(count($tmp) > 3){
                            for($i=3; $i<count($tmp); $i++){
                                if($i%2 <> 0){
                                    $key = $tmp[$i];
                                }
                                $route[$key] = $tmp[$i];
                            }
                        }
                    }
                    break;
                    
                case 'STANDARD':
                default:
                    $url_array = parse_url($url);
                    if(isset($url_array['query'])){
                        parse_str($url_array['query'], $route);
                    }
                    $route = array_merge($default_router, $route);
                    break;
            }
        }
        return $route;
    }
	
	/**
	 * 反向解析路由
     * @param   string  $action 动作器
     * @param   string  $controller 控制器
     * @param   string  $app    应用
     * @param   array   $param  参数
     * @param   string  $type   URL模式(若为空则默认取app配置文件中的router_mode变量)
	 */
	public function reverseRoute($action, $controller, $app, $params=array(), $type=null){
	    $type = empty($type) ? C('app.router_mode') : $type;
		$router = VO_Registry::get('router');
		if(empty($action)){
			$action = $router['action'];
		}
		if(empty($controller)){
			$controller = $router['controller'];
		}
		if(empty($action)){
			$app = $router['app'];
		}
		if(!is_array($params)){
			$params = array($params);
		}
		$base_url = C('base.base_url');
        $index_page = C('base.index_page');
		switch( strtoupper($type) ){
			case 'PATHINFO' : 
				$url = $this->_reverseRegexRoute($app, $controller, $action, $params);
				if($url <> false){
					$url = $base_url . '/' . $index_page . '/' . $url;
					
				}elseif( $url = $this->_reverseStaticRoute($app, $controller, $action, $params) ){
					$url = $base_url . '/' . $index_page . '/' . $url;
				}else{
					$url = $base_url . '/' . $index_page . '/' . $app. '/' . $controller . '/' . $action;
					if(!empty($params)){
						foreach($params as $key => $value){
							$url .= '/' .$key . '/' .$value;
						}
						$url = rtrim($url, '/');
					}
				}
				break;
				
			case 'REWRITE':
				$url = $this->_reverseRegexRoute($app, $controller, $action, $params);
				if($url <> false){
					$url = $base_url . '/' . $url;
					
				}elseif( $url = $this->_reverseStaticRoute($app, $controller, $action, $params) ){
					$url = $base_url . '/' . $url;
				}else{
					$url = $base_url . '/' . $app . '/' . $controller . '/' . $action;
					if(!empty($params)){
						foreach($params as $key => $value){
							$url .= '/' .$key . '/' .$value;
						}
					}
				}
				$url = str_replace('\/\/', '/', $url);
				break;
				
			case 'STANDARD':
			default:
				if( $url = $this->_reverseStaticRoute($app, $controller, $action, $params) ){
					$url = $base_url . '/' . $index_page . '?' . $url;
				}else{
					$url = $base_url . '/' . $index_page . '?' . C('app.application_key.app') . '=' . $app . '&' . C('app.application_key.controller') . '=' . $controller . '&' . C('app.application_key.action') . '=' . $action;
					if(!empty($params)){
						foreach($params as $key => $value){
							$url .= '&' . $key . '=' .$value;
						}
					}
				}
				break;
		}
        
		return $url;
	}

	/**
	 * 反向解析路由(普通路由解析)
     * @param   string  $app    应用
     * @param   string  $controller 控制器
     * @param   string  $action 动作器
     * @param   array   $param  参数
     * @return  string | false  解析后的URL，如果没有解析到规则，则返回false
	 */
	private function _reverseStaticRoute($app, $controller, $action, $params=array()){
		$ret = false; //是否解析成功
		if(empty($this->_regex_rules)){
			return $ret;
		}
		foreach($this->_regex_rules as $k => $rule){
			$router = $rule['router'];  //匹配规则中定义的返回规则
			$router = array_merge($router, $params);
			$match = $rule['match']; //匹配规则中定义的匹配项
			$params_arr = array();
			if(!empty($router) && is_array($router)){
				if($router['app'] == $app && $router['controller'] == $controller && $router['action'] == $action){
					$rule = $rule['rule'];
					preg_match_all('/:(.*?)\//i', $rule, $m);
					if(!empty($m)){
						foreach($m[0] as $k => $replace){
							$rule = str_replace( ':' . $replace, $router[$replace], $rule);
						}
					}
					return $rule;
				}
			}
		}
		return false;
	}

	/**
	 * 反向解析路由(正则泛路由解析)
     * @param   string  $app    应用
     * @param   string  $controller 控制器
     * @param   string  $action 动作器
     * @param   array   $param  参数
     * @return  string | false  解析后的URL，如果没有解析到规则，则返回false
	 */
	private function _reverseRegexRoute($app, $controller, $action, $params=array()){
		$ret = false; //是否解析成功
		if(empty($this->_regex_rules)){
			return $ret;
		}
		foreach($this->_regex_rules as $k => $rule){
			$router = $rule['router'];  //匹配规则中定义的返回规则
			$match = $rule['match']; //匹配规则中定义的匹配项
			$params_arr = array();
			if(!empty($router) && is_array($router)){
				if($router['app'] == $app && $router['controller'] == $controller && $router['action'] == $action){
					$regex = $rule['regex'];
					if(!empty($match) && is_array($match)){
						foreach($match as $k => $query_key){
							foreach($params as $key => $query_value){
								if($query_key == $key){
									$params_arr[] = $query_value;
								}
							}
						}
					}
					preg_match_all('/\(.*?\)/i', $rule['regex'], $m);
					if(!empty($m)){
						foreach($m[0] as $k => $replace){
							$pos = strpos($regex, $replace);
							if( $pos !== false ){ 
							    $len = strlen($replace); 
							    $regex = substr_replace($regex, $params_arr[$k], $pos, $len); 
							} 
						}
					}
					return $regex;
				}
			}
		}
		return false;
	}
    
    /**
     * 将URL从一种模式转换成另外一种模式
     * @param   string  $url    URL地址
     * @param   string  $to     转换成的目标地址
     * @param   string  $from   URL地址的源模式
     * @return  string  转换后的URL地址
     */
    public function convertUrl($url, $to='STANDARD', $from=null){
        $index_page = C('base.index_page');
        if(empty($url)){
            return $url;
        }
        
        $url = trim($url, '/');
        
        if($from == null){
            //自动检测$url类型
            if(strstr($url, $index_page) === false){
                $from = 'REWRITE';
            }else{
                if(preg_match('/[?&]/i', $url)){
                    $from = 'STANDARD';
                }else{
                    $from = 'PATHINFO';
                }
            }
        }
        
        if($from == $to){
            return $url;
        }
        
        $route = $this->getRouteFromUrl($url, $from);
        
        $app = $route['app'];
        $controller = $route['controller'];
        $action = $route['action'];
        unset($route['app']);
        unset($route['controller']);
        unset($route['action']);
        
        $url = $this->reverseRoute($action, $controller, $app, $route, $to);
        
        return $url;
        
    }
	
	/**
	 * 功能：获取解析后的URL路由
	 * 参数： 无
	 * 返回值：null
	 */
	public function parse(){
		$uri = $this->getUri();
		if( is_array($uri) ){
			$this->_route = $uri;
		}else{
			if( !$this->matchStaticRoute($uri) ){ //匹配静态路由
				if( !$this->matchRegxRoute($uri) ){ //匹配泛路由
					if( ! $this->matchStandardRoute($uri)){ //匹配标准路由
						
					}
				}
			}
			
			$this->_route = $this->fillDefaultRoute($this->_route); //填充标准路由
		}
		VO_Http::setQuery($this->_route);
		VO_Registry::set('router', $this->_route);
		//var_dump($this->_route);exit;
		return $this->_route;
	}
}
