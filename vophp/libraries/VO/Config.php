<?php
/**
 * 定义VO_Config 配置文件处理类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-8-23
 **/

defined('VOPHP') or die('Restricted access');

class VO_Config {
	/**
	 * 框架配置变量
	 * @var array
	 */
	private $_config = array();
	
	/**
	 * 配置文件对应表
	 * @var array
	 */
	private $_config_files = array(
			'base'	=>	'base.php',
			'app'	=>	'app.php',
			'cache'	=>	'cache.php',
			'db'	=>	'db.php',
			'language'	=>	'language.php',
			'error'	=>	'error.php',
			'log'	=>	'log.php',
			'view'	=>	'view.php',
			'mail'	=>	'mail.php',
			'search'=>	'search.php',
		);
	
	/**
	 * 构造函数
	 */
	protected function __construct(){
		//$this->loadDefaultConfig();	
		$this->loadConfigs();
	}
	
	/**
	 * 获取单一实例
	 * @return	VO_Config
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Config ){
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * 获取配置信息
	 * @param string $key	配置信息的标识符,可以以"."号为分隔符取不同级别下的值，如：get('app.base_url.value')
	 * @return array	配置信息
	 */
	public function get($key = null){
		if(!empty($key)){
			$value = $this->_config;
			$keys = explode('.', $key);
			foreach($keys as $k => $key){
				if(isset($value[$key])){
					$value = $value[$key];
				}
			}
			return $value;
		}else{
			return $this->_config;
		}
	}
		
	
	/**
	 * 注册新的配置文件，并与当前的配置文件合并成一个新的配置信息数组返回
	 * @param string	$key	配置信息的键值
	 * @param string|array  $files 文件名称,数组或者是文件路径,若有多个文件，则合并后以$key为标识符存储
	 * @return array	当前文件解析后的配置信息
	 */
	public function registerFromFile($key, $files){
		if(empty($key)){
			$this->triggerError('必须为配置文件指定一个标识符');
			exit;
		}
		if(is_array($files)){
			foreach($files as $k => $file){
				$this->registerFromFile($key, $file);
			}
		}else{
			if(!file_exists($files)){
				$this->triggerError('配置文件：' . $files . ' 文件不存在。');
				exit;
			}else{
				$conf = '';
				$ext = strtoupper(substr($files, strrpos($files, '.')+1));
				switch( $ext ){
					case 'PHP' : $conf = $this->parsePhpConfig($files);
								 break;
					case 'INI' : $conf = $this->parseIniConfig($files);
								 break;
					case 'XML' : $conf = $this->parseXmlConfig($files);
								 break;
				}
				if(isset($this->_config[$key]) && is_array($conf)){
					$this->_config[$key] = array_merge($this->_config[$key], $conf);
				}else{
					$this->_config[$key] = $conf;
				}
			}
		}
		$this->flushRegister();
		return $this->_config[$key];
	}
	
	/**
	 * 载入/application/configs目录下的配置文件信息,文件在$this->_config->files下定义
	 * @return array 配置信息
	 */
	private function loadConfigs(){
		$config_files = VO_Filesystem_Folder::files(CONFIG_DIR);
		foreach($config_files as $k => $file){
			if(empty($file)){
				continue;
			}
			$key = substr($file, 0, strpos($file, '.'));
			$file = CONFIG_DIR . DS . $file;
			if(file_exists($file)){
				$conf = '';
				$ext = strtoupper(substr($file, strrpos($file, '.')+1));
				switch( $ext ){
					case 'PHP' : $conf = $this->parsePhpConfig($file);
								 break;
					case 'INI' : $conf = $this->parseIniConfig($file);
								 break;
					case 'XML' : $conf = $this->parseXmlConfig($file);
								 break;
				}
				if(isset($this->_configg[$key])){
					$this->_config[$key] = array_merge($this->_config[$key], $conf);
				}else{
					$this->_config[$key] = $conf;
				}
			}
		}
		$this->flushRegister();
		return $this->_config;
	}	
	
	/**
	 * 载入VOPHP框架默认的配置文件
	 * @return array 默认的配置信息
	 */
	private function loadDefaultConfig(){
		foreach($this->_config_files as $key => $file){
			$file = VO_CONFIG_DIR . DS . $file;
			if(file_exists($file)){
				$this->_config[$key] = include $file;
			}
		}
		$this->flushRegister();
		return $this->_config;
	}
	
	/**
	 * 解析PHP类型的文件
	 * @param string $file 配置文件的路径
	 */
	private function parsePhpConfig($file){
		if( !file_exists($file) ){
			return array();
		}
		return include_once($file);
	}
	
	/**
	 * 解析INI类型的配置文件
	 * @param string $file
	 * @return array
	 */
	private function parseIniConfig($file){
		//加载网站配置文件
		if( !file_exists($file) ){
			return array();
		}
		$config = VO_Parse_Ini::Parse($file, '', 'array');
		return $config;
	}
	
	/**
	 * 解析XML类型的配置文件
	 * @param string $file
	 * @return array
	 */
	private function parseXmlConfig($file){
		//加载网站配置文件
		if( !file_exists($file) ){
			return array();
		}
		$config = VO_Parse_Xml::Parse($file, '', 'array');
		return $config;
	}
	
	/**
	 * 刷新已注册的配置信息
	 * @return void
	 */
	private function flushRegister(){
		VO_Registry::set('config', $this->_config);
	}
}