<?php
/**
 * 功能：网站首页入口文件
 * 作者：JackChen
 * 修改时间：2010-04-19
 **/
define('DS', DIRECTORY_SEPARATOR);

define('ROOT_DIR', dirname(__FILE__));	//定义网站的根目录
define('SITE_DIR', ROOT_DIR . DS . 'front');	//定义网站的应用目录
define('VO_ROOT_DIR', ROOT_DIR . DS . 'vophp');

define('APP_DIR', SITE_DIR . DS . 'app');	//定义网站的模块目录
define('PLUGIN_DIR', SITE_DIR . DS . 'plugin');	//定义网站的模块目录
define('LIB_DIR', SITE_DIR . DS . 'lib');	//定义网站的公用库目录
define('CONFIG_DIR', SITE_DIR . DS . 'config');
define('ASSETS_DIR', SITE_DIR . DS . 'public');
//关闭PHP的MAGIC_QUOTES_GPC自动转义
if(PHP_VERSION < '5.3') {
	ini_set('magic_quotes_runtime', 0);
	define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc() ? true : false);
}


//加载VOPHP框架文件
include VO_ROOT_DIR . DS . 'vo.php';