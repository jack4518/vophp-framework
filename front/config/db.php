<?php
/**
 * 1、数据库的表信息，数据库默认提供为读写分离操作，可以分别为其配置，当只有一个库时，可以将读库的配置信息删除或者将读库配置成和写库一样的配置
 * 2、当只有一个数据库时，也可以把写库的配置信息删除，只保留读库的配置信息
 */
return array(
	/**
	 * 是否对表字段信息进行缓存
	 */
	'field_cache'	=>	true,

	/**
	 * 写数据时，表字段类型是否需要检查
	 */
	'field_type_check'	=>	true,

	/**
	 * 数据库表的ID模式,可选值[AUTO_INCREASE, 64_BIT_GLOBAL, 64_BIT_COMPAT]
	 * AUTO_INCREASE : 数据库自增
	 * 64_BIT_GLOBAL : 64位全局ID，只适用于机器和操作系统为64位的平台
	 * 64_BIT_COMPAT : 64位兼容ID，只适用于机器和操作系统为64位的平台
	 * @var string
	 */
	'id_mode' => 'AUTO_INCREASE',

   /**
	 * 'write'键为写库
	 * @var array
	 */
	'write'	 =>	array(
		'adapter' 	=> 	'mysql',  //数据库引擎
		'host'    	=> 	'127.0.0.1',  //数据库主机名
		'port' 		=> 	'3306',  //数据库连接端口
		'user' 		=> 	'jackchen',  //数据库用户名
		'password'	=> 	'840704',  //数据库密码
		'database'	=> 	'www_vocms_com',  //数据库库名
		'prefix' 	=> 	'vo_',  //数据库表前缀
		'suffix' 	=> 	''   //数据库表后缀
    ),
    
	/**
	 * 'read'键为读库
	 * @var array
	 */
	'read'	 =>	array(
		'adapter' 	=> 	'mysql',  //数据库引擎
		'host'    	=> 	'127.0.0.1',  //数据库主机名
		'port' 		=> 	'3306',  //数据库连接端口
		'user' 		=> 	'jackchen',  //数据库用户名
		'password'	=> 	'840704',  //数据库密码
		'database'	=> 	'www_vocms_com',  //数据库库名
		'prefix' 	=> 	'vo_',  //数据库表前缀
		'suffix' 	=> 	''   //数据库表后缀
    ),
   
    
);
