<?php
/**
 * 定义VO_View_Adapter_Votpl VOTPL模板引荐核心文件
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

require_once(VO_LIB_DIR . DS . 'View' . DS . 'Adapter' . DS . 'Abstract.php');
class VO_View_Adapter_Smarty extends VO_View_Adapter_Abstract{
	
	/**
	 * VOPHP的配置文件
	 * @var array
	 */
	var $_config = array();
	
	/**
	 * 函数存储器
	 * @var array
	 */
	var $_functions = array();
	
	/**
	 * 修饰器存储器
	 * @var array
	 */
	var $_modifiers = array();	
	
	/**
	 * 对象存储器
	 * @var object
	 */
	var $_objects = array();
	
	/**
	 * 变量存储器
	 * @var array
	 */
	var $_vars = array();
	
	/**
	 * 缓存对象
	 * @var VO_Cache_File
	 */
	var $_cache = null;
	
	/**
	 * 模板的配置信息
	 * @var array
	 */
	protected $view_config = array(
				'adapter'	=> 'smarty',
				'templateDir'	=>	'views',		//模板目录
				'compileDir'	=>	'views\templates_c',		//模板编译目录
				'cacheDir'	=>	'views\cache',		//模板缓存目录
				'isCache'	=>	false,		//模板是否缓存
				'cacheLifetime'	=>	300,		//缓存时间,单位为秒,默认为300秒，即5分钟
				'left_delimiter'	=> '{',		//模板语法的左定界符
				'right_delimiter'	=> '}', 		//模板的右定界符
				'isAllownPhpFunction'	=>	true,	//是否允许模板使用PHP内置函数，如果开启此选项，当模板使用PHP内置函数时，视图不需要手动注入函数，建议手动注入模板需要的方法
				'isForceCompile'	=>	true,		//是否强制编译
			);
	
	/**
	 * 构造方法
	 * @return VO_View_Adapter_Smarty
	 */
	public function __construct(array $view_config = null){

	}

	/**
	 * 初始化方法
	 * @return VO_View_Adapter_Smarty
	 */
	public static function _init(array $view_config=array()){
		VO_Registry::set('is_crumble', false);
	}
	
	/**
	 * 初始化视图
	 */
	public static function getInstance($view_config=array()){
		static $instance = null;
		self::_init();
		$config = VO_Registry::get('config');
		if (!is_object($instance)){
			$file = VO_EXT_DIR . DS . 'smarty' . DS . 'libs' . DS . "Smarty.class.php";
			require_once($file);
			$instance = new Smarty;
			$instance->addTemplateDir(APP_DIR . DS . $_GET['app'] . DS . 'views');
			$instance->addTemplateDir(APP_DIR . DS .'_global' . DS . 'views');
			$instance->setCompileDir(SITE_DIR . DS . $config['view']['ext_view']['compile_dir']);
			$instance->setCacheDir(SITE_DIR . DS . $config['view']['config']['cache_dir']);
			$instance->setCaching($config['view']['config']['is_cache']);
			
			$instance->left_delimiter = $config['view']['ext_view']['left_delimiter'];
			$instance->right_delimiter = $config['view']['ext_view']['right_delimiter'];
			$instance->setcache_lifetime($config['view']['config']['cache_dir']);
			$instance->setCompile_check($config['view']['ext_view']['compile_check']);
			$instance->setForce_compile($config['view']['ext_view']['force_compile']);
			$instance->debugging = $config['view']['ext_view']['debugging'];
			
			$global_plugin_dir = APP_DIR . DS . '_global' . DS  . 'plugins';
			$instance->addPluginsDir($global_plugin_dir);

			$instance->registerPlugin('function', 'build_url', 'buildToUrl');
		}
		return $instance;
	}
    
}