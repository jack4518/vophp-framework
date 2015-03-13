<?php
/**
 * 定义 VO_Http HTTP请求类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Http{
	
	/**
	 * 用户参数保存器
	 * @var array
	 */
	private static $_userparams = array();
	
	/**
	 * 原始数据
	 * @var string
	 */
	protected static $_rawBody = '';
	
	/**
	 * 当前URI信息
	 * @var string
	 */
	protected static $_uri = '';

	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
	 * 获取单一实例
	 * @return VO_Http
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Http ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 是否有$_GET参数
	 * @return bool
	 */
	public static function isGet(){
		return strtoupper(self::getMethod()) == 'GET' ? true : false;
	}
	
	/**
	 * 是否有$_POST参数
	 * @return bool
	 */
	public static function isPost(){
		return strtoupper(self::getMethod()) == 'POST' ? true : false;
	}
	
	/**
	 * 是否有$_FILES参数
	 * @return bool
	 */
	public static function isFiles(){
		return $_FILES ? true : false;
	}	
	
	/**
	 * 获取指定的$_GET的值，若不存在，则返回$_GET数组
	 * @param String $key default null
	 * @param String $default 默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public static function getQuery($key = null, $default = null, $filter=null){
		$result = '';
		if(null === $key){
			return $_GET;
		}
		if(self::isGet() && array_key_exists($key,$_GET) && isset($_GET[$key])){
			$result = $_GET[$key];
		}else{
			$result = $default;
		}
		
		if($filter <> null){
			$result = VO_Http_Filter::clean($result, $filter);
		}
		return $result;
	}
	
	/**
	 * 获取指定的$_POST的信息，若不存在则返连回$_POST数组
	 * @param String $key
	 * @param String $default　默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public static function getPost($key = null, $default = null, $filter=null){
		$result = '';
		if(null === $key){
			return $_POST;
		}
		if(self::isPost() && array_key_exists($key,$_POST) && isset($_POST[$key])){
			$result = $_POST[$key];
		}else{
			$result = $default;
		}
		
		if($filter <> null){
			$result = VO_Http_Filter::clean($result, $filter);
		}
		return $result;
	}
	
	/**
	 * 获取指定的$_FILES的信息，若不存在则返连回$_FILES数组
	 * @param String $key
	 * @param String $default　默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public static function getFiles($key = null, $default = null){
		if(null === $key){
			return $_FILES;
		}
		if(self::isFiles() && array_key_exists($key,$_FILES) && isset($_FILES[$key])){
			return $_FILES[$key];
		}else{
			return $default;
		}
	}	
	
	/**
	 * 获取原始数据的提交
	 * @return mixed
	 */
    public static function getRawBody(){
        if (null === self::$_rawBody) {
            $body = file_get_contents('php://input');
            if (strlen(trim($body)) > 0) {
                self::$_rawBody = $body;
            } else {
                self::$_rawBody = false;
            }
        }
        return self::$_rawBody;
    }	
	
	/**
	 * 获取指定的$_Cookie的信息，若不存在则返连回$_Cookie数组
	 * @param String $key default null
	 * @param String $default　默认值
	 * @param string $filter 过滤标识
	 * @return string|array
	 */
	public static function getCookie($key = null, $default = null, $filter=null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        $result = (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    	if($filter <> null){
			$result = VO_Http_Filter::clean($result, $filter);
		}
		return $result;
    }
	
	/**
	 * 取得合并后的全部参数
	 * 合并 self::$_userparams $_GET $_POST,优先顺序为self::$_userparams $_GET $_POST
	 * @return array
	 */
	public static function getParams(){
		$_params = array();
		//取用户数据
		if(self::$_userparams){
			$_params = array_merge($_params,self::$_userparams);
		}
		//取$_GET数据
		if($_GET){
			$_params = array_merge($_params,self::getQuery());
		}
		
		//取$_POST数据
		if($_POST){
			$_params = array_merge($_params,self::getPost());
		}
		return $_params;
	}
	
	/**
	 * getParams方法的别名
	 */
	public static function getVars(){
		return self::getParams();
	}
	
	/**
	 * 根据提供的数组获取相应的参数
	 * @param array|string $arr
	 * @param string $filter 过滤标识
	 * @return array
	 */
	public static function getParamsByArray($arr = array(), $filter=null){
		if(empty($arr)){
			return false;
		}
		if(is_string($arr)){
			$result = array( $arr => self::getParam($arr) );
		}
		if(is_array($arr)){
			$result = array();
			foreach($arr as $key => $defaultValue){
				$result[$key] = self::getParam($key, $defaultValue, $filter);
			}
		}
		return $result;
	}
	
	/**
	 * 根据键值取得参数值,若不存在，则返回全部数组值
	 * @param String $key  default null
	 * @param String $default　默认值
	 * @param string $filter 过滤标识
	 * @param string|array
	 */
	public static function getParam($key=null, $default=null, $filter=null){
		$result = '';
		if(null === $key){
			return self::getParams();
		}
		$params = self::getParams();
		if(array_key_exists($key,$params) && isset($params[$key])){
			$result = $params[$key];
		}else{
			$result = $default;
		}
		
		if($filter <> null){
			$result = VO_Http_Filter::clean($result, $filter);
		}
		return $result;
	}
	
	/**
	 * getParam方法的别名
	 */
	public static function getVar($key=null, $default=null, $filter=null){
		return self::getParam($key, $default, $filter);
	}
	
	/**
	 * 获取指定的$_SERVER的信息，若不存在则返连回$_SERVER数组
	 * @param String $key default null
	 * @param String $default 默认值
	 * @return string|array
	 */
	public static function getServer($key = null, $default = null){
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }
    
    /**
	 * 获取指定的$_ENV的信息，若不存在则返连回$_ENV数组
	 * @param String $key default null
	 * @param String $default default null
	 * @return string|array
	 */
	public static function getEnv($key = null, $default = null){
        if (null === $key) {
            return $_ENV;
        }

        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }
    
    /**
     * 获取头信息
     * @param  string $header HTTP头信息名称
     * @return string|false  HTTP头信息, 当头信息不存在时返回false
     */
    public static function getHeader($header){
        if( empty($header) ){
           return '';
        }
        $header = strtoupper($header);
        if( substr($header, 0, 4) != 'HTTP' ){
        	$header = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        }
        if( !empty($_SERVER[$header]) ){
            return $_SERVER[$header];
        }
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if( !empty($headers[$header]) ){
                return $headers[$header];
            }
        }
        return false;
    }    
    
    /**
	 * 获取当前HTTP请求的提交方法
	 * @return string
	 */
	public static function getMethod(){
        return self::getServer('REQUEST_METHOD');
    }
    
	/**
	 * 获取当前是否为XmlHttpRequest请求的提交方法,为isXmlHttpRequest的同名方法
	 * @return bool
	 */
	public static function isAjax(){
		$isAjax = self::getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' ? true : false;
        return $isAjax;
    }
    
    /**
	 * 获取当前是否为XmlHttpRequest请求的提交方法
	 * @return bool
	 */
	public static function isXmlHttpRequest(){
		$isAjax = self::getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' ? true : false;
        return $isAjax;
    }
    
    /**
     * 获取当前是否为flash请求的提交方法
     * @return boolean
     */
    public static function isFlashRequest(){
        $header = strtolower(self::getServer('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }
    
    /**
     * 获取当前的位数(32位或者64位)
     * @return boolean
     */
    public static function getOsBit(){
      return (PHP_INT_SIZE===8) ? 64 : 32;
    }
    
	/**
     * 获取客户端的IP地址
     *
     * @param  boolean $checkProxy  是否检测代理
     * @return string
     */
    public static function getClientIp($checkProxy = true){
        if ($checkProxy && self::getServer('HTTP_CLIENT_IP') != null) {
            $ip = self::getServer('HTTP_CLIENT_IP');
        } else if ($checkProxy && self::getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = self::getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = self::getServer('REMOTE_ADDR');
        }

        return $ip;
    }

    /**
     * 获取服务端的IP地址
     *
     * @return string
     */
    public static function getServerIp(){
	    if( isset($_SERVER) ){
	        if($_SERVER['SERVER_ADDR']) {
	            $server_ip = $_SERVER['SERVER_ADDR'];
	        }else{
	            $server_ip = $_SERVER['LOCAL_ADDR'];
	        }
	    }else{
	        $server_ip = getenv('SERVER_ADDR');
	    }
	    return $server_ip;
    }
    
	/**
     * 获取HTTP请示协议
     * @return string
     */
    public function getScheme(){
        return self::getServer('HTTPS') == 'on' ? 'https' : 'http';
    }
	
	/**
	 * 将数组参数赋值给$_GET全局变量
	 * 
	 * @param String $key default null
	 * @param String $value default null
	 * @return bool
	 */
	public static function setQuery($key, $value = null){
		if(null === $key){
			return false;
		}
		if(is_array($key)){
			foreach($key as $k => $v){
				self::setQuery($k, $v);
			}
		}else{
			if(is_string($key)){
				$_GET[$key] = $value;
			}
		}
		return true;
	}
	
	/**
	 * 将数组参数赋值给$_GET全局变量
	 * 
	 * @param String $key default null
	 * @param String $value default null
	 * @return string|array
	 */
	public static function setPost($key, $value = null){
		if(null === $key){
			return false;
		}
		if((null === $value) && is_array($key)){
			foreach($key as $k => $v){
				self::setPost($k, $v);
			}
		}
		if(is_string($key)){
			$_POST[$key] = $value;
		}
		return self::getPost();
	}
	
	/**
	 * 设置用户参数
	 * @param String $key default null
	 * @param String $value default null
	 * @return string|array
	 */
	public static function setUserParam($key, $value = null){
		if(null === $key){
			return false;
		}
		if((null === $value) && is_array($key)){
			foreach($key as $k => $v){
				if(gettype($k) == 'string');
				self::setUserParam($k, $v);
			}
		}
		if(is_string($key)){
			self::$_userparams[(string)$key] = $value;
		}
		
		return self::getUserParam();
	}
	
	/**
	 * 根据键值返回用户参数,，若不存在，则返回默认值
	 * @param String $key default null
	 * @param String $default  default null
	 * @return string|array
	 */
	public static function getUserParam($key = null, $default = null, $filter = null){
		$result = '';
		if(null === $key){
			return self::$_userparams;
		}
		if(array_key_exists($key,self::$_userparams) && isset(self::$_userparams[$key])){
			$result = self::$_userparams[$key];
		}else{
			$result = $default;
		}
		
		if($filter <> null){
			$result = VO_Http_Filter::clean($result, $filter);
		}
		return $result;
	}
	
	/**
     * 设置URI参数
     * @param string $uri
     * @return string
     */
    public static function setUri($uri = null){
        if($uri === null){
            if(isset($_SERVER['HTTP_X_REWRITE_URL'])){
                $uri = $_SERVER['HTTP_X_REWRITE_URL'];
            }elseif(isset($_SERVER['REQUEST_URI'])){
                $uri = $_SERVER['REQUEST_URI'];
            } elseif(isset($_SERVER['ORIG_PATH_INFO'])){
                $uri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])){
                    $uri .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
        }elseif(!is_string($uri)){
            return '';
        }else{
            $_GET = array();
            if( ($pos = strpos($uri, '?')) !== false ){
                $query = substr($uri, $pos+1);
                parse_str($query, $vars);
                $_GET = $vars;
            }
        }
        self::$_uri = $uri;
        return $uri;
    }

	/**
     * 获得URI参数
     * @return string
     */
    public function getUri(){
        if (empty(self::$_uri)) {
            self::setUri();
        }
        return self::$_uri;
    }
	
	/**
     * 数据过滤
     *@access   public
     *@return   string
     **/ 
    private static function addslashes($str, $strip=false) {
        if(!get_magic_quotes_gpc()) {
        	if(is_array($str)) {
        		foreach ($str as $key => $value){
        			$str[$key] = self::addslashes($value);
        		}
        	}else{
        		$str = addslashes($strip ? stripslashes($str) : $str);
        	}
        }
        return $str;
    }
    
    /**
     *  检测INI文件中的GPC设置，并过滤 magic_quotes
     *  @return void
     */
    public static function filterGPC(){
    	if(PHP_VERSION < '6.0') {
			if( get_magic_quotes_gpc() ){
				$request = array( &$_GET, &$_POST, &$_COOKIE, &$_REQUEST, &$_SERVER, &$_FILES );
				while( list($k, $v) = each($request) ) {
					foreach($v as $key => $val) {
						if(!is_array($val)) {
							$request[$k][$key] = stripslashes($val);
							continue;
						}
						$request[] = &$request[$k][$key];
					}
				}
				unset($request);
			}
	    }
    }
}