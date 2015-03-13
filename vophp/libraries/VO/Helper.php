<?php
/**
 * 定义 VO_Helper 助手类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-09-15
 **/

defined('VOPHP') or die('Restricted access');

include(VO_LIB_DIR .  DS . 'Helper' . DS . 'Abstract.php');
class VO_Helper extends VO_Helper_Abstract{
	
	/**
	 * 助手类的命名空间
	 * @var array
	 */
	public $nameSpace = array('VO_Helper');
	
	/**
	 * 助手类实例存储器
	 * @var array
	 */
	private $_helps = array();
	
	/**
	 * 构造方法
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Helper
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Helper){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 取得助手类
	 * @param string $name
	 * @return VO_Helper_Abstract
	 */
	public function getHelper($helper_name, $app=''){
		$hash = md5( $app . $helper_name);
		if(isset($this->_helps[$hash])){
			return $this->_helps[$hash];
		}
		
		$app_path = $this->getModulePath($app);
		
		//当命名空间下的helpers文件夹不存在以$name命名的助手类文件时就会检测模块下的助手空间里是否有此助手类
		$helper_file = $app_path . DS . 'helpers' . DS . $helper_name . '.php';

		if(!file_exists($helper_file)){
			//载入通用助手目录(即application/helpers)下的$name助手
			$helper_file = APP_DIR . DS . 'helpers' . DS . $helper_name . '.php';
			if(file_exists($helper_file)){
				include($helper_file);
				$this->_helps[$hash] = new $helper_name();
			}else{
				$this->_helps[$hash] = self::loadRegistedHelper($helper_name);
			}
		}else{
			//载入$app对应模块下的$name模型
			include($helper_file);
			$this->_helps[$hash] = new $helper_name();
		}
		return $this->_helps[$hash];
	}
	
	/**
	 * 取得助手类
	 * @param string $name
	 * @return VO_Helper_Abstract
	 */
	public function loadRegistedHelper($name){
		$helperClassName = $this->getHelpClass($name);
		if($helperClassName !== false){
			$instance = new $helperClassName;
			return $instance;
		}
	}
	
	/**
	 * 获取助手类的类名
	 * @param string $name
	 */
	private function getHelpClass($name){
		if(empty($name)){
			return false;
		}
		foreach ($this->nameSpace as $k => $v){
			$class = $v . '_' . ucfirst($name);
			if(@class_exists($class)){
				return $class;
			}
		}
		return false;
	}
	
	/**
	 * 返回当前已注册的命名空间
	 */
	public function getNameSpace(){
		return $this->nameSpace;
	}
	
	/**
	 * 注册助手类的命名空间(用户新注册的命名空间会被最先搜索，系统默认命名空间会最后被搜索到)
	 * @param mixed $namespace
	 */
	public function registerNameSpace($namespace=null){
		if(null == $namespace || empty($namespace) ){
			return false;
		}
		if(!is_array($namespace)){
			$namespace = (array) $namespace;
		}
		foreach($namespace as $k => $v){
			if(false === array_search($v, $this->nameSpace)){
				array_unshift($this->nameSpace, $v);
			}
		}
	}
	
	/**
	 * 移除助手类的命名空间
	 * @param string $namespace
	 */
	public function removeNameSpace($namespace=null){
		if(null == $namespace || empty($namespace) ){
			return false;
		}
		foreach($this->nameSpace as $k => $v){
			if($v == $namespace){
				unset($this->nameSpace[$k]);
			}
		}
	}
	
	/**
	 * 重置为默认命名空间(移除所有注册的命名空间)
	 */
	public function setDefaultNameSpace(){
		$this->nameSpace = array('VO_Helper');
	}
}