<?php
/**
 * 定义VO_Language_Abstract 多语言类抽象类
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

abstract class VO_Language_Abstract{
	
	/**
	 * 字典文件
	 * @var array
	 */
	protected $_language = array();
	
	/**
	 * 配置文件 变量
	 * @var array
	 */
	protected $config = array();
	
	/**
	 * 构造函数
	 * @return	VO_Language_Abstract
	 */
	public function __construct(){
		
	}
	
	/**
	 * 语言初始化
	 */
	public function init($engin = 'array'){
		$this->config = C('language');
		$this->_loadVoLanguage();
		$this->_loadAppLanguage($engin);
	}
		
	/**
	 * 载入VOPHP框架的语言
	 */
	private function _loadVoLanguage(){
		$file = VO_CONFIG_DIR . DS . 'language' . DS . strtolower($this->config['language']) . DS . 'VOPHP.php';
		
		if( !file_exists($file)){
			$file = VO_CONFIG_DIR . DS . 'language' . DS . 'zh-cn' . DS . 'VOPHP.php';
		}
		
		$language = include $file;

		if(empty($this->_language['VOPHP'])){
			$this->_language['VOPHP'] = $language;
		}else{
			$this->_language['VOPHP'] = array_merge($this->_language['VOPHP'], $language);
		}
	}
	
	/**
	 * 加载当前应用的语言文件,在配置文件语言目录下，每个app一个语言文件
	 * @param string $engin  语言解析引擎名称
	 * @return void
	 */
	private function _loadAppLanguage($engin){
		$app = VO_Http::getQuery('app');
		
		$dir = str_replace(array('/', '\\'), DS, C('language.dir'));
		$dir = (substr($dir, 0, 1) == DS) ? substr($dir, 1) : $dir;
		$dir = (substr($dir, -1) == DS) ? substr($dir, 0, -1) : $dir;
		
	
		//载入应用级的语言文件
		$base_language_file = SITE_DIR . DS . $dir . DS . C('language.language') . DS . 'application.' . strtolower($engin);
		if(file_exists($base_language_file)){
			$this->load('application');
		}
		
		//载入模块级的语言文件
		$app_language_file = SITE_DIR . DS . $dir . DS . C('language.language') . DS . $app . '.' . strtolower($engin);
                
		if(file_exists($app_language_file)){
			$this->load($app);
		}
	}
	
	/**
	 * 获取语言
	 * @param	string	$keyword	语言键值
	 */
	public function _($keyword='', $namespace='default'){
		if(!is_string($keyword)){
			return $keyword;
		}
		if( empty($keyword) || !isset($this->_language[$namespace][$keyword]) ){
			return $keyword;
		}else{
			return $this->_language[$namespace][$keyword];
		}
	}
	
	/**
	 * 获取语言包
	 */
	public function add( $languageFile, $namespace='default' ){
		$this->load($languageFile, $namespace);
	}
	
	/**
	 * 获取语言包文件的路径
	 * @param string $languageFile 语言文件
	 * @param string $engine 多语言的适配器
	 */
	protected function getFile($languageFile, $engine='array'){
		if( is_array($languageFile) ){
			return $languageFile;
		}else{
			if( !file_exists($languageFile) ){
				if( strrpos($languageFile, '.') !== false ){
					$filename = substr($languageFile, 0, strrpos($languageFile, '.'));
				}else{
					$filename = $languageFile;
				}
				$ext = strstr( $languageFile, '.' );
				if( empty($ext) ){
					switch($engine){
						case 'ini':		$ext = '.ini'; break;
						case 'gettext':	$ext = '.mo'; break;
						case 'array': 
						default:		$ext = '.php'; break;
					}
				}
				if( $this->config['language'] == 'auto' ){
					$langs = VO_Http::getServer('HTTP_ACCEPT_LANGUAGE', 'zh-cn');
					$langs = substr($langs, 0, strpos($langs, ';'));
					$langs = explode(',', $langs);
					foreach( $langs as $k => $lang ){
						$file = SITE_DIR . DS . C('language.dir') . DS . $lang . DS . $engine . DS . $filename . $ext;
						if( file_exists($file) ){
							return $file;
						}
					}
					$dir = str_replace(array('/', '\\'), DS, C('language.dir'));
					$dir = (substr($dir, 0, 1) == DS) ? substr($dir, 1) : $dir;
					$dir = (substr($dir, -1) == DS) ? substr($dir, 0, -1) : $dir;
					$file = SITE_DIR . DS . $dir . DS . 'zh-cn' . DS . $engine . DS . $filename . $ext;
					return $file;
				}else{
					$dir = str_replace(array('/', '\\'), DS, C('language.dir'));
					$dir = (substr($dir, 0, 1) == DS) ? substr($dir, 1) : $dir;
					$dir = (substr($dir, -1) == DS) ? substr($dir, 0, -1) : $dir;
					$file = SITE_DIR . DS . $dir . DS . C('language.language') . DS . $filename . $ext;
				}
				
				return $file;
			}else{
				return $languageFile;
			}
		}
	}
}