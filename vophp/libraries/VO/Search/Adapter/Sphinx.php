<?php
/*
	$s = new SphinxClient;
	$s->setServer("localhost", 9312);
	$s->setMatchMode(SPH_MATCH_ANY);
	$s->setMaxQueryTime(3);
	$s->SetArrayResult ( true );
	$result = $s->query("test");
	$error = $s->getLastError();
	var_dump($error);  
	var_dump($result);
	print_r($result['matches']);
	
	$str = "中国";
	var_dump($str);
	$result = $s->query($str);
	var_dump($result);
	print_r($result['matches']);
*/
/**
 * 类说明：Sphinx搜索类
 * 作者：  JackChen
 * 时间：  2010-05-25
*/

defined('VOPHP') or die('Restricted access');

class VO_Search_Adapter_Sphinx{
	/**
	 * 配置信息
	 * @var array
	 */
	protected $_config = array();

	/*
	 * Sphinx客户端句柄
	 * @var SphinxClient
	 */
	 private $_sphinx = null;

	/*
	 * VO_Search_Adapter_Sphinx句柄
	 * @var VO_Search_Adapter_Sphinx
	 */
	 static private $_instance = null;
	
	/**
	 * 构造函数
	 */
	public function __construct($config = array()){
		//$this->_config = VO_Registry::get('config');
		error_reporting(E_ERROR);
		$this->_config = $config;
		$this->init($config);
		return self::$_instance;
	}

	/**
	 * 获取单一实例
	 * @return VO_Search_Adapter_Sphinx
	 */
	public function getInstance($config = array()){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self($config);
		}
		
		$this->_config = $config;
		self::$_instance->init($config);
		return self::$_instance;
	}

	/**
	 * 初始化
	 * @return void
	 */
	public function init($config = array()){
		if(!extension_loaded('sphinx')){
			include_once  VO_EXT_DIR . DS . 'sphinx' . DS . 'sphinxapi.php';
		}
		$this->_sphinx =  new SphinxClient();
		$this->setServer($this->_config['host'], $this->_config['port']);
		$this->setMatchMode($this->_config['match_mode']);
		$this->setArrayResult(true);
	}

	/**
	 * 魔术方法
	 * @param string $method 方法名
	 * @param array $args 参数数组
	 * @return VO_Model | array
	 */
	public function __call($method, $args){
		$this->_sphinx->$method($args);
	}

	/**
	 * 设置sphinx服务器
	 * @param	string	$host	sphinx主机名或者IP地址
	 * @param	string	$port	sphinx端口
	 * @return boolean
	 */
	public function setServer($host='127.0.0.1', $port=3312){
		return $this->_sphinx->setServer($host, $port);
	}
	/**
	 * 设置全文查询的匹配模式
	 * @param	int	$mode	匹配模式
	 * @return boolean
	 */
	public function setMatchMode($mode){
		return $this->_sphinx->setMatchMode($mode);
	}
	
	/**
	 * 设置全文查询的排序模式
	 * @param	int	$mode	排序模式
	 * @return	boolean
	 */
	public function setSortMode($mode){
		return $this->_sphinx->setSortMode($mode);
	}

	/**
	 * 控制搜索结果集的返回格式
	 * @param	boolean	$return_array	返回格式,true为数组，false为hash
	 * @return	boolean true
	 */
	public function setArrayResult($return_array = true){
		return $this->_sphinx->setArrayResult($return_array);
	}

	/**
	 * 执行搜索查询
	 * @param	string	$word	查询字符串
	 * @param	string	$index	索引名称 (可以为多个，使用逗号分割，或者为“*”表示全部索引)
	 * @return	boolean/hash 	
	 */
	public function query($word, $index='*'){
		return $this->_sphinx->query($word, $index);
	}
	
	/**
	 * 获取指定应用的目录路径
	 * @param string $module   模块名称
	 * @return string 模块所对应的目录路径
	 */
	public function getModulePath($module=''){
		if(empty($module)){
			$module = VO_Http::getParam('app');
		}
		$module = strtolower($module);
		$path = APP_DIR . DS . $module;

		if( C('app.application_folder') ){
			foreach(C('app.application_folder') as $name => $folder){
				if($name == $module){
					$folder = str_replace(array('/', '\\', '//'), DS, $folder);
					$folder = trim($folder, DS);
					$path = APP_DIR . DS .$folder;
					$path = str_replace(array('/', '\\', '//'), DS, $path);
					break;
				}
			}
		}
		return $path;
	}
	
	/**
	 * 析构函数
	 *
	 * @return  boolean
	 */
	public function __destruct(){
		
	}	
}