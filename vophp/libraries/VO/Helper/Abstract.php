<?php
/**
 * 类说明：助手抽象类
 * 作者：  JackChen
 * 时间：  2010-05-25
*/

defined('VOPHP') or die('Restricted access');

abstract class VO_Helper_Abstract{
	/**
	 * 配置文件信息
	 * @var array
	 */
	protected $_config = array();
	/**
	 * 构造函数
	 */
	public function __construct(){
		$this->_config = VO_Registry::get('config');
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