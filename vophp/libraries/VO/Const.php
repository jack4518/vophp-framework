<?php
/**
 * 定义常量
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO 
 * @since version 1.0
 * @date 2010-07-05
 **/
defined('VOPHP') or exit("You don't have the access to execution VOPHP Framework");

/**
 * 普通ID
 */
if(!defined('ID_NONE')){
	define('ID_NONE', 0);
}

/**
 * 全局ID
 */
if(!defined('ID_GLOBAL')){
	define('ID_GLOBAL', 1);
}

/**
 * 兼容ID
 */
if(!defined('ID_COMPAT')){
	define('ID_COMPAT', 2);
}

/**
 * 只存数据库快表
 */
if(!defined('DAO_FAST')){
	define('DAO_FAST', 0);
}

/**
 * 存数据库快表和缓存
 */
if(!defined('DAO_FAST_CACHE')){
	define('DAO_FAST_CACHE', 1);
}

/**
 * 存数据库快表和缓存和Data表
 */
if(!defined('DAO_ALL')){
	define('DAO_ALL', 2);
}

/**
 * 存数据库快表和Data表
 */
if(!defined('DAO_FAST_DATA')){
	define('DAO_FAST_DATA', 3);
}