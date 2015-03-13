<?php
return array(
	/**
	 * 配置文件的方式，可选值为:file, server
	 * file   : 代表读取当前的配置文件中的MongoDB_configs数组信息
	 * server : 代表从服务器的server变量中获取，并且当有多个MongoDB服务器时用空格分隔，主机和端口之间用':'分隔,例如:127.0.0.1:27017 127.0.0.2:27017
	 * @var array
	 */
	'config_type' => 'file',

	/**
	 * 当config_type为server时，可以自定义服务器配置的MongoDB环境变量的key名称,必须与服务器环境变量相一致，否则无法读取配置信息
	 */
	'server_mongodb_key' => 'MONGODB_CONFIGS',
	
	/**
	 * MongoDB配置信息
	 */

	/**
	 * 'write'键为写库
	 * @var array
	 */
	'write'	 =>	array(
		'host'    	=> 	'127.0.0.1',  //数据库主机名
		'port' 		=> 	'27017',  //数据库连接端口
		'user' 		=> 	'',  //数据库用户名
		'password'	=> 	'',  //数据库密码
		'database'	=> 	'www_vocms_com',  //数据库库名
		'prefix' 	=> 	'vo_',  //数据库表前缀
		'suffix' 	=> 	''   //数据库表后缀
    ),
    
	/**
	 * 'read'键为读库
	 * @var array
	 */
	'read'	 =>	array(
		'host'    	=> 	'127.0.0.1',  //数据库主机名
		'port' 		=> 	'27017',  //数据库连接端口
		'user' 		=> 	'',  //数据库用户名
		'password'	=> 	'',  //数据库密码
		'database'	=> 	'www_vocms_com',  //数据库库名
		'prefix' 	=> 	'vo_',  //数据库表前缀
		'suffix' 	=> 	''   //数据库表后缀
    ),
);