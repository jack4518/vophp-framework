<?php
/**
 * 定义VO_Language 多语言类
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

class VO_Language{
	
	/**
	 * 配置文件变量
	 * @var array
	 */
	protected $config = array();
	
	/**
	 * 语言提供器
	 * @var object
	 */
	protected $adapter = '';
	
	/**
	 * 系统目前支持的语言解析引擎
	 * @var array
	 */
	private $engines = array( 'Array', 'Ini', 'Gettext' );
	
	/**
	 * 语言的解析引擎
	 * @var string
	 */
	private $engine = null;
	
	/**
	 * 构造函数
	 */
	public function __construct($engine=null){
		$this->config = C();
		$this->checkEngine($engine);
		
		if( !empty($this->engine) ){
			include_once VO_LIBRARIES_PATH . DS . 'Language' . DS . $this->engine . '.php';
		}else{
			$this->engine = ucfirst( strtolower($this->config['language']['adapter']) );
		}
		
		$class = 'VO_Language_' . $this->engine;
		$this->adapter = new $class($this->engine);
	}
	
	/**
	 * 载入语言
	 * @param mixed $languageFile	语言文件(可以是数组或者是文件，如果是文件，则必须是完整的相对或绝对路径的文件)
	 * @param string $namespace	语言的命名空间
	 */
	public function load( $languageFile, $namespace='default' ){
		$this->adapter->load($languageFile, $namespace);
	}
	
	/**
	 * 增加语言
	 * @param mixed $languageFile	语言文件(可以是数组或者是文件，如果是文件，则必须是完整的相对或绝对路径的文件)
	 * @param string $namespace	语言的命名空间
	 * @return void
	 */
	public function add($languageFile, $namespace='default'){
		$this->adapter->load($languageFile, $namespace);
	}
	
	/**
	 * 检查语言解析引擎是否存在
	 * @param string $engine
	 */
	private function checkEngine($engine){
		if( !empty($engine) ){
			$engine = ucfirst($engine);
			if( in_array($engine, $this->engines) ){
				$this->engine = $engine;
			}else{
				$error = new VO_Error();
				$error->error( sprintf( T('VOPHP is not support "%s" engine', 'VOPHP'), $engine) );
			}
		}else{
			return true;
		}
	}
	
	/**
	 * 获取翻译后的语言字符串
	 * @param	string	$keyword	语言键值
	 * @return string 翻译后的结果
	 */
	public function _($keyword='', $namespace='default'){
		return $this->adapter->_($keyword, $namespace);
	}
}