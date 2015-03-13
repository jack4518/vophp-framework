<?php
/**
 * 定义VO_View_Adapter_Abstract 视图文件抽象类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-12-25
 **/

defined('VOPHP') or die('Restricted access');

abstract class VO_View_Adapter_Abstract{
	/**
	 * VOPHP的配置文件
	 * @var array
	 */
	var $_config = array();
	
	/**
	 * 视图的渲染模式,当使用VOPHP框架自带的视图渲染引擎里，此选项有以下三种模式：
	 * 		'system':	从标准的系统目录中寻找模板
	 * 		'static':	从用户指定的templateDir目录中寻找模板
	 * 		'auto':		系统自动从查找标准目录中查找模板，如果不存在则从用户指定的templateDir目录中查找模板
	 * @var string
	 */
	protected $view_config = array();
	
	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
     * 获取视图目录
     */
    protected function _getViewFile($template){
    	$this->templateDir = str_replace(array('\\', '/'), array(DS,DS), $this->templateDir);
    	$template = str_replace(array('\\', '/'), array(DS,DS), $template);

    	if(substr($this->templateDir,-1) == DS){
    		$this->templateDir = substr($this->templateDir,-1);
    	}
    	//检测目录渲染模式
    	switch( $this->view_config['rendererMode'] ){
    		case 'static':
    			$templateFile = $this->_getStaticView($template);
    			break;
    		case 'system':
    			$templateFile = $this->_getSystemView($template);
    			break;
    		default:
    			$templateFile = $this->_getSystemView($template);
    			if( !file_exists($templateFile) ){
    				$templateFile = $this->_getStaticView($template);
    			}
    			
    	}
    	
    	return $templateFile;
    }
    
    /**
     * 获取定义视图目录及完整文件名
     * @param	string	$templateFile
     * @return	string
     */
    protected function _getStaticView($templateFile){
    	$route = VO_Http::getParam();
    	$this->view_config['templateDir'] = str_replace(array('/', '\\'), DS, $this->view_config['templateDir']);
    	$dir = APP_DIR . DS . $this->view_config['templateDir'];
    	
    	return $dir . DS . $templateFile;
    }
    
    /**
     * 获取系统视图文件目录及完整文件名
     * @param string $templateFile
     * @return string
     */
    protected function _getSystemView($templateFile){
    	$dir = '';
    	$route = VO_Http::getParam();
    	if( $route['module'] != 'default' ){
    		if(array_key_exists($route['module'], $this->_config['applicationFolder'])){
				$folder = $this->_config['applicationFolder'][$route['module']];
				$folder = str_replace(array('/', '\\'), array(DS, DS), $folder);
				$folder = trim($folder, DS);
				$dir .= DS . $folder . DS;
    		}else{
    			$dir .= DS . 'modules' . DS .$route['module'] . DS;
    		}
    	}else{
    		$dir = DS;
    	}
    	
    	$dir .= 'views' . DS;
    	
    	return APP_DIR . $dir . $templateFile;
    }
}