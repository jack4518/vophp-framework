<?php
/**
 * 定义 VO_Error 错误处理类
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

class VO_Error extends VO_Object{
	/**
	 * 错误处理句柄
	 * @var array
	 */
	protected $_provider = array(
		'error'		=>	'voError',
		'warning'	=>	'voWarning',
		'notice'	=>	'voNotice'
	);
		
	/**
	 * 构造方法
	 * @return VO_Error
	 */
	public function __construct(){
		$this->_debug = C('error.debug');
		if($this->_debug == false){
			ini_set('display_errors', false);
			error_reporting(0);
		}
	}
	
	/**
	 * 显示错误信息
	 * 
	 * @param	string	$message	错误信息
	 * @param	int		$code		错误代码
	 * @param	string	$file		错误所在文件
	 * @param	int		$line		错误所在行号
	 * @return	void
	 */
	public function error($message, $code=0, $file=null, $line=null, $trace=null){
		VO_Error_Storage::addError($message);
		if($this->_debug == false){
			return false;
		}
		$provider = $this->_provider['error'];
		if( strtolower($provider)<>'voerror'){
			$this->parseCallBack($provider);
		}else{
			$this->voError($message, $code, $file, $line, $trace);
		}
	}
	
	/**
	 * 显示警告信息
	 *
	 * @param	string	$message	警告信息
	 * @param	int		$code		警告代码
	 * @param	string	$file		警告所在文件
	 * @param	int		$line		警告所在行号
	 * @return	void
	 */
	public function warning($message, $code=0, $file=null, $line=null, $trace=null){
		VO_Error_Storage::addError($message);
		if($this->_debug == false){
			return false;
		}
		$provider = $this->_provider['warning'];
		if( strtolower($provider)<>'vowarning' ){
			$this->parseCallBack( $provider, array($message, $code, $file, $line, $trace) );
		}else{
			$this->voWarning($message, $code, $file, $line, $trace);
		}
		exit;
	}	
	
	/**
	 * 显示通知提示信息
	 *
	 * @param	string	$message	提示信息
	 * @param	int		$code		提示代码
	 * @param	string	$file		提示所在文件
	 * @param	int		$line		提示所在行号
	 * @return	void
	 */
	public function notice($message, $code=0, $file=null, $line=null, $trace=null){
		VO_Error_Storage::addError($message);
		if($this->_debug == false){
			return false;
		}
		$provider = $this->_provider['notice'];
		if( strtolower($provider)<>'vonotice' ){
			$this->parseCallBack( $provider, array($message, $code, $file, $line, $trace) );
		}else{
			$this->voNotice($message, $code, $file, $line, $trace);
		}
		exit;
	}	
	
	/**
	 * VOPHP框架默认错误信息提供器
	 *
	 * @param	string	$message	错误信息
	 * @param	int		$code		错误代码
	 * @param	string	$file		错误所在文件
	 * @param	int		$line		错误所在行号
	 * @return	void
	 */
	protected function voError($message, $code=0, $file=null, $line=null, $trace=null){
		$base_url = C('base.base_url');
		$type = $this->getErrorType($code);
		//var_dump(debug_backtrace());
		$array = $this->_getTraces($message, $code, $file, $line, $trace);
		include VO_LIB_DIR . DS . 'Error' . DS . 'Templates' . DS . 'Error.php';
		exit;
	}
	
	/**
	 * VOPHP框架默认警告信息提供器
	 *
	 * @param	string	$message	警告信息
	 * @param	int		$code		警告代码
	 * @param	string	$file		警告所在文件
	 * @param	int		$line		警告所在行号
	 * @return	void
	 */
	protected function voWarning($message, $code=0, $file=null, $line=null, $trace=null){
		$base_url = C('base.base_url');
		$type = $this->getErrorType($code);

		$array = $this->_getTraces($message, $code, $file, $line, $trace);
		include VO_LIB_DIR . DS . 'Error' . DS . 'Templates' . DS . 'Warn.php';
		exit;
	}	
	
	/**
	 * VOPHP框架默认通知信息提供器
	 *
	 * @param	string	$message	提示信息
	 * @param	int		$code		提示代码
	 * @param	string	$file		提示所在文件
	 * @param	int		$line		提示所在行号
	 * @return	void
	 */
	protected function voNotice($message, $code=0, $file=null, $line=null, $trace=null){
		$base_url = C('base.base_url');
		$type = $this->getErrorType($code);

		$array = $this->_getTraces($message, $code, $file, $line, $trace);
		@ob_end_clean();
		include VO_LIB_DIR . DS . 'Error' . DS . 'Templates' . DS . 'Notic.php';
		exit;
	}
	
	/**
	 * 设置错误处理句柄
	 * @param array $providers	错误处理句柄
	 * @return bool
	 */
	public function setProvider($providers){
		if(!is_array($providers)){
			return false;
		}
		foreach ($this->_provider as $key => $provider){
			if(isset($providers[$key])){
				$this->_provider[$key] = $providers[$key];
			}
		}
		return true;
	}
	
	/**
	 * 获取错误类型
	 * @param	int	$code	错误代码
	 * @return	string
	 */
	protected function getErrorType($code){
		switch( (int)$code ){
			case 404: 
				$type = '文件系统错误';
				break;
			case 403: 
				$type = '文件系统错误';
				break; 
			case 400:
				$type = 'WEB服务器错误';
				break;
			case 500:
				$type = '数据库服务器错误';
				break;
			case 501:
				$type = 'SQL语句执行错误';
				break;
			case 100:
				$type = '系统警告';
				break;
			case 200:
				$type = '系统通知';
				break;
			case 300:
			default: 
				$type = '系统错误';
				break;
		}
		return $type;
	}
	
	/**
	 * 解析回调函数
	 * @param	string	$fun	 用户自定义的方法	
	 * @param	array	$params	方法的参数，以数组形式传递
	 * @return	void
	 */
	protected function parseCallBack($fun, & $params=array()){
		$args = func_get_args();
		$args = $args[0];
		//如果是数组
		if(is_array($args)){
			if(count($args) == 1){
				//静态方法
				$arg = explode('::', $args[0]);
				if( count($arg) == 2 && class_exists($arg[0]) && !empty($arg[1]) ){
					$classname = $arg[0];
					$method = $arg[1];
					$obj = new $classname;
					if(method_exists($obj, $method)){
						call_user_func_array( array($obj, $method), $params );
					}else{
						$this->triggerError('自定义的方法"' . $classname . '::' . $method . '()"不存在');
						exit;
					}
				}else if( function_exists($arg[0] )){
					//普通函数
					call_user_func_array($arg[0], $params);
				}else{
					$this->triggerError('自定义的函数"' . $arg[0] . '()"不存在');
					exit;
				}
			}else{
				//类方法
				$classname = $args[0];
				$method = $args[1];
				$obj = new $classname;
					if(method_exists($obj, $method)){
						call_user_func_array( array($obj, $method), $params );
					}else{
						$this->triggerError('自定义的方法"' . $classname . '->' . $method . '()"不存在');
						exit;
					}
			}
		}else if( function_exists($args )){
			//普通函数
			if(function_exists($args)){
				call_user_func_array($args, $params);
			}
		}else{
			$this->triggerError('自定义的函数"' . $args . '()"不存在');
			exit;
		}
	}

	/**
     * 获取错误文件的源代码
     * @param string	$file 	错误文件
     * @param int		$line_number	错误行号
     * @param padding	$padding	跨越行(即取错误行的前后几行)
     * @return	array	错误文件内的行数组
     */
    private function _getSource($file, $line_number, $padding=3){
        $source = array();
        if(!$file || !is_readable($file) || !$fp = fopen($file, 'r'))
            return $source;

        $bline = $line_number - $padding;
        $eline = $line_number + $padding;
        $line = 0;
        while(($row = fgets($fp))){
            if(++$line > $eline)
                break;
            if($line < $bline)
                continue;
            $row = htmlspecialchars($row, ENT_NOQUOTES);
            $source[$line] = $row;
        }
        fclose($fp);
        return $source;
    }

	/**
     * 获取当前的错误回溯信息和服务器基本信息
     * @return array	回溯信息和服务器基本信息
     */
	private function _getTraces($message, $code=0, $file=null, $line=null, $trace=null){
		$skip = 20;
		$array = array();
        $array['exectime'] = microtime(true) - TIMESTAMP;
        $array['error']['file'] = $file;
        $array['error']['line'] = $line;
        $array['error']['message'] = $message;
        $array['error']['code'] = $code;
        $array['error']['source'] = $this->_getSource($file, $line);
        $array['ver'] = '1.0';
        $array['time'] = date('Y-m-d H:i:s');
        $array['server']= isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
        $array['phpver']=PHP_VERSION;
        $array['mmem'] = memory_get_peak_usage(true);
        $array['umem'] = memory_get_usage(true) - VO_MEMORY_USED;
        $array['addr'] = $_SERVER['SERVER_ADDR'];
        $array['from'] = VO_Http::getClientIp();
        $array['host'] = $_SERVER['HTTP_HOST'];
        $array['stack'] = array();
        if(empty($trace)){
     	   $trace = debug_backtrace();
    	}
        if(count($trace) > $skip){
            $trace = array_slice($trace, $skip);
        }

        $flag = true;
        foreach($trace as $key => $item){
            !isset($item['file']) && $item['file']='unknown';
            !isset($item['line']) && $item['line']=0;
            !isset($item['function']) && $item['function']='unknown';
            !isset($item['type']) && $item['type'] = '';
            !isset($item['class']) && $item['class'] = '';
            !isset($item['args']) && $item['args'] = array();
	        $item['is_caller'] = preg_match('/VO/', $item['file']);
            $item['source'] = $this->_getSource($item['file'], $item['line']);
            if($item['source']){
                $array['stacks'][] = $item;
            }
        }
		return $array;
	}    
}