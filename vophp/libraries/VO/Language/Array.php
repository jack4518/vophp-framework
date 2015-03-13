<?php
/**
 * 定义VO_Language_Array 多语言类
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

VO_Loader::import('Language.Abstract');
class VO_Language_Array extends VO_Language_Abstract{
	
	/**
	 * 构造函数
	 * @return	VO_Language_Array
	 */
	public function __construct($engin = 'array'){
		parent::init($engin);
	}
	
	/**
	 * 载入语言
	 * @param string|array $languageFile	语言文件名称,不包含扩展名
	 * @param string $namespace	语言的命名空间
	 * @return array
	 */
	public function load( $languageFile, $namespace='default' ){
		if( $namespace === null  ){
			$namespace='default';
		}else{
			$namespace = (string) $namespace;
		}
		
		if( empty($languageFile) ){
			$languageFile = array();
		}
		
		if( is_array($languageFile) ){
			if( empty($this->_language[$namespace]) ){
				$this->_language[$namespace] = $languageFile;
			}else{
				$this->_language[$namespace] = array_merge($this->_language[$namespace], $languageFile);
			}
			return $this->_language;
		}
		
		$languageFile = $this->getFile($languageFile, 'array');
		if( file_exists($languageFile)){
			$language = include $languageFile;
			if( empty($this->_language[$namespace]) ){
				$this->_language[$namespace] = $language;
			}else{
				$this->_language[$namespace] = array_merge($this->_language[$namespace], $language);
			}
		}else{
			$error = new VO_Error();
			$error->error( sprintf( T('Language file "%s" is not exist', 'VOPHP'), $languageFile) );
		}
		return $this->_language;
	}
}