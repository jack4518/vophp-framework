<?php
/**
 * VO_Log 日志处理类
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

class VO_Log{
	
	/**
	 * 是否开启日志记录功能
	 * @var string
	 */
	protected $enable = false;
	
	/**
	 * 日志存放目录
	 * @var string
	 */
	protected $logPath = '/log';
	
	/**
	 * 日志名称
	 * @var string
	 */
	protected $logFileName = 'access.log';
	
	/**
	 * 日志最大文件大小，当超这个大小时重新生成一个文件名
	 * @var string
	 */
	protected $logFileMaxSize = 4096;
	
	/**
	 * 日志日期格式
	 * @var string
	 */
	protected $logDateFormat = 'Y-m-d H:i:s';
	
	/**
	 * 日志记录级别
	 * @var string
	 */
	protected $logLevel = array( 'NOTIC', 'INFO', 'DEBUG', 'WARNING', 'ERROR', 'EXCEPTION', 'LOG','SQL' );
	
	/**
	 * 日志内容
	 * @var string
	 */
	protected $content = '';
	
	/**
	 * 日志的记录引擎,可以为：'FILE','DB', FIREPHP, SYSTEM
	 * @var string
	 */
	protected $engine = 'FILE';
	
	/**
	 * 日志对应的数据表参数，当配置参数$engine="DB"的时候生效
	 * @var array
	 */
	protected $logDbOption = array(
		'table'	=>	'logs',		//日志表名
		'fields'	=>array(	//日志表字段名,值为字段名称
			'level'	=>	'level',
			'message'	=>	'message',
			'logdate'	=>	'logdate',
		),
	);

	protected $_provider = 'voLog';
	
	/**
	 * 构造方法
	 * @return VO_Log
	 */
	public function __construct(){
		$this->enable = C('log.enable');
		$this->engine = C('log.engine');
		if( $this->engine != '' ){
			$this->engine = C('log.engine');
			if( (strtoupper($this->engine) == 'DB') && ($this->engine != '') ){
				$this->logDbOption = C('log.table_info');
			}
		}
		if( C('log.file_name') != '' ){
			$this->logFileName = C('log.file_name');
		}
		$maxsize = C('log.file_max_size');
		if( $maxsize && $maxsize>2 ){
			$this->logFileMaxSize = $maxsize;
		}

		if( C('log.level') != '' ){
			$level = C('log.level');
			if(!is_array($level) || count($level)<=1){
				$level = (array)$level;
				$level = explode(',', $level[0]);
			}
			foreach($level as $k => $v){
				$level[$k] = strtoupper(trim($v));
			}
		}
		if(in_array('ALL', $level)){
			$this->logLevel = array( 'NOTIC', 'INFO', 'DEBUG', 'WARNING', 'ERROR', 'EXCEPTION', 'LOG','SQL' );
		}else{
			$this->logLevel = $level;
		}
		if( C('log.file_path') != '' ){
			$logPath = str_replace(array('/', '\\'), array(DS, DS), C('log.file_path') );
			//加上前缀'/'
			$ds = substr($logPath, 0, 1);
			$logPath = ($ds == DS) ? $logPath : DS . $logPath;
			//加上后缀'/'
			$ds = substr($logPath, -1, 1);
			$logPath = ($ds == DS) ? $logPath : $logPath . DS;
			$this->logPath = SITE_DIR . $logPath;
		}else{
			$this->logPath = SITE_DIR . $this->logPath;
		}
		if( !is_dir($this->logPath) && !is_writeable($this->logPath)){
			return false;
			/**
			$error = new VO_Error();
			$error->showError('日志存储目录不可写');
			exit;
			*/
		}
	}
	
	/**
	 * 获取单一实例
	 * @return	VO_Log
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Log ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 记录日志
	 * @param	string	$message	日志信息
	 * @param	string	$level		日志级别
	 * @return void
	 */
	public function log($message, $level='info'){
		if( !$this->checkLogLevel($level) ){
			return false;
		}
		if( isset($this->_provider) && strtolower($this->_provider)<>'volog' ){
			//调用自定义日志提供器
			$this->parseCallBack($this->_provider);
		}else{
			//调用VOPHP框架默认日志提供器
			$this->voLog($message, $level);
		}
	}
	
	/**
	 * VOPHP框架默认的日志记录提供器
	 * @param	string	$message	日志信息
	 * @param	string	$level		日志级别
	 * @return void
	 */
	private function voLog($message, $level){
		if( !C('log.enable') ){
			return false;
		}
		
		//调用不同的日志记录引擎
		switch(strtoupper($this->engine)){
			case 'FILE':
				$this->logToFile($message, $level);
				break;
			case 'DATABASE':
				$this->logToDatabase($message, $level);	
				break;
			case 'FIREPHP':
				$this->firePHP($message, $level);	
				break;
			case 'SYSTEM':
				error_log($message, $level);	
				break;
		}
	}
	
	/**
	 * 将日志记录到文件中
	 * 
	 * @param	string	$message	日志信息
	 * @param	string	$level		日志级别
	 * @return void
	 */
	private function logToFile($message, $level='info'){		
		$level = strtoupper($level);
		if( !in_array($level, $this->logLevel) ){
			return false;
		}
		$time = date($this->logDateFormat);
		$this->content = '[ ' . $time . ' ]　　(' . $level . ')　' .$message . PHP_EOL ;

		//判断文件目录是否存在，并且是否可写
		if( !is_dir($this->logPath) && !is_writeable($this->logPath)){
			return false;
		}

		$filename = $this->logPath . $this->logFileName;
		//判断文件大小
		if( !file_exists($filename) ) {
			$filesize = 0;
		}else{
			$filesize = @filesize($filename);
			if( C('log.is_compress') == true && extension_loaded('zip') && (ceil($filesize/1204) > $this->logFileMaxSize)  ){
				$zipFileName = dirname($filename) . DS . VO_Filesystem_File::stripExt($this->logFileName) . '-' . date('Ymd-His') . '.zip';
				$zip = VO_Zip::getInstance();
				$zip->addFile($filename);
				$zip->compress($zipFileName);
				VO_Filesystem_File::delete($filename);
			}
		}
		//将日志写入文件
		VO_Filesystem_File::append($filename, $this->content);
	}
	
	/**
	 * 将日志记录到数据库中
	 * @param string $message
	 * @param string $level
	 * @return void
	 */
	private function logToDatabase($message, $level='info'){
		$level = strtoupper($level);
		if( !in_array($level, $this->logLevel) ){
			return false;
		}
		
		$time = date($this->logDateFormat);
		$this->content = $time . '　　(' . $level . ')　' .$message . PHP_EOL ;
		
		$db = VO_Registry::get('db');
		$table = trim($this->logDbOption['table']);
		$data = array(
			$this->logDbOption['fields']['level']	=>	$level,
			$this->logDbOption['fields']['message']	=>	$message,
			$this->logDbOption['fields']['logdate']	=>	$time,
		);
		$db->insert( $table, $data );
	}
	
	private function checkLogLevel($level){
		if( $this->enable && in_array(strtoupper($level), $this->logLevel) ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 将日志输出到FirePHP
	 * @param string $message
	 * @param string $level
	 * @return void
	 */
	public function firePHP($message, $level='log'){
		include_once  VO_EXT_DIR . DS . 'firephp' . DS . 'FirePHP.class.php';
		$firephp = FirePHP::getInstance(true);
		$level = strtoupper($level);
		switch($level){
			case 'INFO': 		$firephp->info($message, $level); break;
			case 'ERROR': 		$firephp->error($message, $level); break;
			case 'WARNING': 	$firephp->warn($message, $level); break;
			case 'EXCEPTION': 	$firephp->error($message, $level); break;
			case 'NOTIC': 		$firephp->info($message, $level); break;
			case 'DEBUG': 		$firephp->log($message, $level); break;
			case 'LOG':
			default:			$firephp->log($message, $level); break;
		}
	}
	
	/**
	 * 解析回调函数
	 * @param　string	$fun	 用户自定义的方法	
	 * @param  array	$params	方法的参数，以数组形式传递
	 * @return void
	 */
	private function parseCallBack($fun, & $params=array()){
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
}