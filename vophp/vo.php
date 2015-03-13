<?php
/**
 * VOPHP Framework 入口文件
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-05-19
 * */

define('VOPHP', 1);

if(!defined('VOPHP_VERSION')){
	define('VOPHP_VERSION', '1.0');
}

if(!defined('DS')){
	define('DS', DIRECTORY_SEPARATOR);
}

if(!defined('VO_ROOT_DIR')){
	define('VO_ROOT_DIR', dirname(__FILE__));	//定义网站的根目录
}
if(!defined('SITE_DIR')){
	define('SITE_DIR', ROOT_DIR);	//定义网站的根目录
}
if(!defined('VO_LIB_DIR')){
	define('VO_LIB_DIR', VO_ROOT_DIR . DS . 'libraries' . DS . 'VO');
}

if(!defined('VO_EXT_DIR')){
	define('VO_EXT_DIR', VO_ROOT_DIR . DS . 'libraries' . DS . 'Ext');
}

/**
 * 定义VOPHP框架默认的配置文件
 */
if(!defined('VO_CONFIG_DIR')){
	define('VO_CONFIG_DIR', VO_ROOT_DIR . DS . 'configs');
}

if(!defined('TIMESTAMP')){
	define('TIMESTAMP', time());
}
if(!defined('VO_MEMORY_USED')){
	define('VO_MEMORY_USED', memory_get_usage());
}

// 目录设置和类装载
set_include_path( PATH_SEPARATOR . dirname(VO_LIB_DIR)
				. PATH_SEPARATOR . VO_EXT_DIR
				. PATH_SEPARATOR . APP_DIR
                . PATH_SEPARATOR . get_include_path()
				);

@ob_start();
//加载自动加载类
require_once  VO_LIB_DIR . DS . 'Loader.php';

//初始化加载器
VO_Loader::init();

//加载常用的库文件
VO_Loader::import( 'Const' );
VO_Loader::import( 'Object' );
VO_Loader::import( 'Exception' );
VO_Loader::import( 'Application' );
VO_Loader::import( 'Http' );
VO_Loader::import( 'Registry' );
VO_Loader::import( 'Session' );
VO_Loader::import( 'Factory' );
VO_Loader::import( 'Language' );
VO_Loader::import( 'Error' );
VO_Loader::import( 'Model' );
VO_Loader::import( 'Controller' );
VO_Loader::import( 'Common' );
VO_Loader::import( 'Application.Router' );
VO_Loader::import( 'Application.Dispatcher' );

//加载应用控制器
$vo = VO_Application::getInstance();

//运行VOPHP框架
$vo->run();
