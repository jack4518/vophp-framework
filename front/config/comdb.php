<?php
return array(
	/**
	 * 是否开启ComDB功能
	 * @var bool
	 */
	'enable' => true,
	
	/**
	 * 缓存所使用的引擎
	 * @var string
	 */
	'cache' => 'memcache',

	/**
	 * DATA表的ID模式,可选值[64_BIT_GLOBAL, 64_BIT_COMPAT, TABLE_NAME_ID]
	 * AUTO_INCREASE : 数据库自增
	 * 64_BIT_GLOBAL : 64位全局ID，只适用于机器和操作系统为64位的平台
	 * 64_BIT_COMPAT : 64位兼容ID，只适用于机器和操作系统为64位的平台
	 * TABLE_NAME_ID : 表名+自增ID
	 * @var string
	 */
	'id_mode' => 'TABLE_NAME_ID',

	/**
	 * DATA表对应的数据库配置
	 * @var array
	 */
    'db_config'=>array(
    	'adapter' 	=> 	'mysql',  //数据库引擎
		'host'    	=> 	'127.0.0.1',  //数据库主机名
		'port' 		=> 	'3306',  //数据库连接端口
		'user' 		=> 	'jackchen',  //数据库用户名
		'password'	=> 	'840704',  //数据库密码
		'database'	=> 	'vophp_com',  //数据库库名
		'prefix' 	=> 	'vo_',  //数据库表前缀
		'suffix' 	=> 	'',   //数据库表后缀
        'charset'   => 'utf8',
        'persist'   => false,
    ),

    'table_name' => 'data',  //Data表名
    'key_name'   => 'key',   //数据主键名
    'value_name' => 'value', //数据内容字段名
    'version_name'   => 'version',   //数据版本号
    'createdate_name' => 'create_date', //数据迁移或创建时间戳
    'editdate_name' => 'edit_date', //数据最近更新时间戳
    'status_name'=> 'status',//数据有效状态，置空则物理删除否则逻辑标记删除
    'copies'    => 2,       //数据备份数，默认1没有备份
    'config_table' => 'info',  //Comdb配置信息存放表名
    'move_table'   => 'move',  //数据迁移日志表，置空则不记录
    'cache_table'  => 'cache', //刷新缓存日志表，置空则不记录
    'bucket'    => 'comdb', //Couchbase桶名
    'auto_move'  => true,    //是否自动迁移数据
    'gearman'   => array(), //异步写data表
    'async'     => true,  //是否打开异步写Data表
    'expire' => 0,  //数据过期时间，默认为永不过期
);
