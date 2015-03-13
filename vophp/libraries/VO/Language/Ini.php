<?php
/**
 * 定义VO_Language_Ini 多语言类
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

class VO_Language_Ini extends VO_Language_Abstract{
	
	/**
	 * 构造函数
	 * @return	VO_Language_Ini
	 */
	public function __construct($engin = 'array'){
		parent::init($engin);
	}
	
	/**
	 * 载入语言文件
	 * @param string | array $languageFile	语言文件名称,不包含扩展名
	 * @param string $language	命名空间
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
			if(empty($this->_language[$namespace])){
				$this->_language[$namespace] = $languageFile;
			}else{
				$this->_language[$namespace] = array_merge($this->_language[$namespace], $languageFile);
			}
			return $this->_language;
		}
		$languageFile = $this->getFile($languageFile, 'ini');
		if( file_exists($languageFile)){
			$content = parse_ini_file($languageFile);
			if($content){
				if(empty($this->_language[$namespace])){
					$this->_language[$namespace] = $content;
				}else{
					$this->_language[$namespace] = array_merge($this->_language[$namespace], $content);
				}
			}
		}else{
			$error = new VO_Error();
			$error->error( sprintf( 'Language file "%s" is not exist', $languageFile) );
		}
		return $this->_language;
	}
}