<?php
return array(
	/**
	 * 配置文件的方式，可选值为:file, server
	 * file   : 代表读取当前的配置文件中的configs数组信息
	 * server : 代表从服务器的server变量中获取，并且当有多个Redis服务器时用空格分隔，主机和端口之间用':'分隔,例如:127.0.0.1:11211 127.0.0.2:11311
	 * @var array
	 */
	'config_type' => 'file',

	/**
	 * 当config_type为server时，可以自定义服务器配置的Redis环境变量的key名称,必须与服务器环境变量相一致，否则无法读取配置信息
	 */
	'server_key' => 'REDIS_CONFIGS',
	
	/**
	 * RedisRedis配置信息,多维数组代表可以使用多个Redis服务器
	 * @var array
	 */
	'configs' => array(
		array(
			'host'    	=> 	'127.0.0.1',  //Redis主机名
			'port' 		=> 	'6379',  //Redis连接端口
			'weight'	=> 	'1',  //Redis权重
	    ),
	    array(
			'host'    	=> 	'58.215.78.247',  //Redis主机名
			'port' 		=> 	'6379',  //Redis连接端口
			'weight'	=> 	'1',  //<Redis></Redis>权重
	    ),
	    //other redis configs
    ),
);