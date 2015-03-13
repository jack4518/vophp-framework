<?php
/**
 * 定义 VO_Helper_Model 模型助手类
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

require_once(VO_LIB_DIR .  DS . 'Helper' . DS . 'Abstract.php');
class VO_Helper_Model extends VO_Helper_Abstract{
	/**
	 * 模型存储器
	 * @var array
	 */
	public $_models = array();	

	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 加载模型文件
	 * @param string  $name		模型名称
	 * @param string  $module	模块名称
	 * @param string  $namespace	命名空间的名称
	 * @return VO_Model		模型名称$name对应的模型对象
	 */
	public function loadModel($model_name, $app, $db_config=array()){
		$model_name = strtolower($model_name);
		$hash = $model_name;
		if(isset($this->_models[$hash])){
			return $this->_models[$hash];
		}
		
		$app_path = $this->getModulePath($app);
		
		//当命名空间下的models文件夹不存在以$name命名的模型文件时就会检测模块下的模型空间里是否有此模型
		$model_file = $app_path . DS . 'models' . DS . $model_name . '.php';
		if(!file_exists($model_file)){
			//载入通用模型目录(即application/models)下的$name模型
			$model_file = APP_DIR . DS . '_global' . DS . 'models' . DS . $model_name . '.php';
			if(file_exists($model_file)){
				include($model_file);
				$this->_models[$hash] = new $model_name($db_config);
			}else{
				return new VO_Model($db_config);
			}
		}else{
			//载入$module对应模块下的$name模型
			require_once($model_file);
			$this->_models[$hash] = new $model_name($db_config);
		}
		return $this->_models[$hash];
	}
}