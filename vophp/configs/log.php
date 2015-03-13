<?php
return array(	
	/**
	 * 是否开启日志记录
	 * @var bool
	 */
	'enable'	=>	true,
		
	/**
	 * 日志记录引擎(值为'FILE'、'DATABASE'、SYSTEM、FIREPHP)
	 * @var string
	 */
	'engine'	=>	'FILE',

	/**
	 * 当开启此选项，才会进行压缩，并且file_max_size选项才会生效
	 * @var bool
	 */
	'is_compress'	=> true,
	
	/**
     * 指当日志文件超过多少 KB 时，自动创建新的日志文件，单位是 KB，不能小于 512KB,服务器必须支持ZIP压缩
     * @var int
     */
	'file_max_size'	=>	512,
	
	/**
     * 日志文件名
     */
	'file_name'	=>	'access.log',
	
	/**
     * 日志存放目录路径
     * @var string
     */
	'file_path'	=>	'/logs',
	
	/**
	 * 日志记录的时间格式
	 * @var string
	 */
	'date_format'	=>	'Y-m-d H:i:s',
	
	/**
     * 指示哪些级别的日志要保存到日志中,以逗号分隔
     * 可选值为：notice,info,debug,warning,error,exception,log,sql
     * @var string
     */
    'level'  => 'notice,info,debug,warning,error,exception,log,sql',
	
	/**
	 * 日志对应的数据表参数，当配置参数'engine'=DB的时候生效
	 * @var array
	 */
	'table_info' =>	array(
		'table'	=>	'logs',		//日志表名
		'fields'	=>array(	//日志表字段名,值为字段名称
			'level'	=>	'level',
			'message'	=>	'message',
			'log_date'	=>	'createdate',
		),
	)
);