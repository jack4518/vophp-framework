<?php
/**
 * 翻译语言
 * @param $str  待翻译的语言字符串
 * @param $namespace 语言包的命名空间
 * @return string  翻译后的语言字符串
 */
function T($str, $namespace='application'){
	$language = VO_Factory::getLanguage();
	if($language instanceof  VO_Language){
		return $language->_($str, $namespace);
	}else{
		return $str;
	}
}

/**
 * 翻译语言，供smarty专用
 * @param $str  待翻译的语言字符串
 * @param $namespace 语言包的命名空间
 * @return string  翻译后的语言字符串
 */
function _T($lan){
	$lan = strval($lan['lan']);
	$language = VO_Factory::getLanguage();
	if($language instanceof  VO_Language){
		echo $language->_($lan, 'default');
	}else{
		echo $lan;
	}
}

/**
 * 获取配置信息中的数据
 * @param $key  配置信息数组标识符,$key可以使用"."作为分隔符加载多级配置信息,如：C('app.router.value')
 */
function C($key=null){
	//获取全部配置信息
	if( VO_Registry::isRegistered('config') ){
		$conf = VO_Registry::get('config');
	}else{
		$configer = VO_Config::getInstance();
		$conf = $configer->get($key);
	}
	//解析$key
	if(!empty($key)){
		$value = $conf;
		$keys = explode('.', $key);
		foreach($keys as $k => $key){
			if(isset($value[$key])){
				$value = $value[$key];
			}
		}
		return $value;
	}else{
		return $conf;
	}
}

/**
 * 注册全局变量
 * @param string $key	全局变量的变量名
 * @param mixed $value	全局变量的变量值
 * @param string $type	全局变量的命名空间
 */
function setGVar($key, $value, $namespace='')
{
    if( !empty($namespace) ){
    	$GLOBALS[$namespace][$key] = $value;
    }else{
        $GLOBALS[$key] = $value;
    }
}

/**
 * 获取全局变量
 * @param string $key	全局变量的变量名
 * @param string $type	全局变量的命名空间
 */
function getGVar($key, $namespace='')
{
	if( !empty($namespace) ){
    	return isset($GLOBALS[$namespace][$key])?
    	             $GLOBALS[$namespace][$key]:false;
    }else{
        return isset($GLOBALS[$key])?
                     $GLOBALS[$key]:false;
    }
}

/**
 * 生成URL
 * @param $action
 * @param $controller
 * @param $application
 * @param $param
 */
function buildUrl($action, $controller='', $application='', $params=array()){
	$router = VO_Application_Router::getInstance();
	return $router->reverseRoute($action, $controller, $application, $params);
}

/**
 * 传递给Smarty的注册函数，用于生成URL
 * @param $params
 */
function buildToUrl($params){
	$url = '';
	$urls = explode('/', $params['url']);
	$action 	= isset($urls[2]) ? $urls[2] : '';
	$controller = isset($urls[1]) ? $urls[1] : '';
	$module 	= isset($urls[0]) ? $urls[0] : '';
	unset($params['url']);
	echo buildUrl($action, $controller, $module, $params);
}

/**
 * 提交GET请求，curl方法
 * @param string  $url       请求url地址
 * @param mixed   $data      GET数据,数组或类似id=1&k1=v1
 * @param array   $header    头信息
 * @param int     $timeout   超时时间
 * @param int     $port      端口号
 * @return array             请求结果,
 *                            如果出错,返回结果为array('error'=>'','result'=>''),
 *                            未出错，返回结果为array('result'=>''),
 */
function curlGet($url, $data = array(), $header = array(), $timeout = 3, $port = 80){
	$ch = curl_init();
    if (!empty($data)) {
        $data = is_array($data)?http_build_query($data): $data;
        $url .= (strpos($url,'?')?  '&': "?") . $data;
    }

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_POST, 0);
    //curl_setopt($ch, CURLOPT_HEADER, true);          //显示文件头信息
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  //设定不跟随header发送的location
    //curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $result = array();
    
    $result['result'] = curl_exec($ch);
    if(0 != curl_errno($ch)) {
        $result['error']  = curl_error($ch);
        $result['status'] = false;
    }else{
        $result['error']  = '';
        $result['status'] = true;
    }
	curl_close($ch);
	return $result;
}


/**
 * 提交POST请求，curl方法
 * @param string  $url       请求url地址
 * @param mixed   $data      POST数据,数组或类似id=1&k1=v1
 * @param array   $header    头信息
 * @param int     $timeout   超时时间
 * @param int     $port      端口号
 * @return string            请求结果,
 *                            如果出错,返回结果为array('error'=>'','result'=>''),
 *                            未出错，返回结果为array('result'=>''),
 */
function curlPost($url, $data = array(), $header = array(), $timeout = 3, $port = 80){	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    //curl_setopt($ch, CURLOPT_HEADER, true);          //显示文件头信息
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  //设定不跟随header发送的location
    //curl_setopt($ch, CURLOPT_PORT, $port);
    !empty ($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $result = array();
    
    $result['result'] = curl_exec($ch);
    if(0 != curl_errno($ch)) {
        $result['error']  = curl_error($ch);
        $result['status'] = false;
    }else{
        $result['error']  = '';
        $result['status'] = true;
    }
	curl_close($ch);

    return $result;
}

/**
 * 输出变量的内容，通常用于调试
 * @param mixed $vars 要输出的变量
 * @param string $label 输出前加的文字标签
 * @param boolean $return
 */
 /*
function dump($vars, $label = '', $return = false){
    if(ini_get('html_errors')) {
        $content = "<pre>\n";
        if($label != ''){
            $content .= "<strong>{$label} :</strong>\n";
        }
        $content .= htmlspecialchars(print_r($vars, true));
    	if($vars === false){
        	$content .= 'false';
        }elseif(empty($vars)){
        	$content .= 'null';
        }
        $content .= "\n</pre>\n";
    }else{
        $content = $label . " :\n" . print_r($vars, true);
    }
    if($return){
    	 return $content; 
    }
    echo $content;
}
*/

/**
 * 输出页面信息,参数$function和$mode可以通过get参数传递
 * @param mixed	$str	待输出的信息
 * @param string	$function	输出所使用的函数,默认为"var_dump"
 * @param string	$mode	输出模式，默认输出为”页面方式“，目前可选值有:"page", "firephp"
 * @return void
 */
function dump($str, $function="var_dump", $mode=''){
	$function = isset($_GET['function']) ? $_GET['function'] : $function;
	$mode = isset($_GET['dump_show_mode']) ? $_GET['dump_show_mode'] : $mode;
	$mode = empty($mode) ? C('error.dump_show_mode') : 'page';
	switch(strtolower($mode)){
		case 'firephp' :
			require_once(VO_EXT_DIR . DS . 'firephp' . DS . 'FirePHP.class.php');
			$firePHP = FirePHP::getInstance(true);
			if(method_exists($firePHP, $function)){
				$firePHP->$function($str);
			}else{
				$firePHP->log($str);
			}
			break;
			
		case 'page' :
		default :
			if(function_exists($function)){
				$function($str);
			}else if($function == 'echo'){
				echo $str;
			}else{
				var_dump($str);
			}
			break;
	}
}

/**
 * 显示应用程序执行路径，通常用于调试
 * @return string
 */
function dumpTrace(){
    $debug = debug_backtrace();
    $lines = '';
    $index = 0;
    for($i=0; $i<count($debug); $i++){
        if ($i == 0) { continue; }
        $file = $debug[$i];
        if($file['file'] == ''){
        	continue;
        }
        $line = "#<strong>{$index} {$file['file']}({$file['line']}): </strong>";
        
        if(isset($file['class'])){
            $line .= "{$file['class']}{$file['type']}";
        }
        $line .= "{$file['function']}(";
        if(isset($file['args']) && count($file['args'])){
            foreach ($file['args'] as $arg){
                $line .= gettype($arg) . ', ';
            }
            $line = substr($line, 0, -2);
        }
        $line .= ')';
        $lines .= $line . "\n";
        $index++;
    }
    $lines .= "#{$index} {main}\n";

    if(ini_get('html_errors')){
        echo nl2br(str_replace(' ', '&nbsp;', $lines));
    }else{
        echo $lines;
    }
}