<?php
return array(	
	/**
	 * 模板引擎
	 * @var string
	 */
	'engine'	=>	'php',

	/**
	 * 是否使用layout
	 * @var bool
	 */
	'allowLayout'	=>	true,

	/**
	 * 模板文件的扩展名
	 * @var bool
	 */
	'ext'	=> '.html',
	
	/**
	 * 视图的相关配置
	 */
	'php'	=>	array(
		'adapter'	=> 'php',
		'template_dir'	=>	'', //模板目录,为空则使用框架默认的视图结构
		'cache_dir'	=>	'', //模板缓存目录,如果没有指定将使用全局缓存目录
		'is_cache'	=>	false, //模板是否缓存
		'cache_lifetime'	=>	300, //缓存时间,单位为秒,默认为300秒，即5分钟
	),
		
	'votpl'	=> array(
		'adapter'	=> 'votpl',
		'template_dir'	=>	'',		//模板目录,为空则使用框架默认的视图结构
		'compile_dir'	=>	'',		//模板编译目录,如果没有指定将使用全局缓存目录下的compile_tpl目录
		'cache_dir'	=>	'',			//模板缓存目录,如果没有指定将使用全局缓存目录
		'is_cache'	=>	false,		//模板是否缓存
		'cache_lifetime'	=>	300,		//缓存时间,单位为秒,默认为300秒，即5分钟
		'left_delimiter'	=> '{',		//模板语法的左定界符
		'right_delimiter'	=> '}', 		//模板的右定界符
		'is_allown_php_function'	=>	true,	//是否允许模板使用PHP内置函数，如果开启此选项，当模板使用PHP内置函数时，视图不需要手动注入函数，建议手动注入模板需要的方法
		'is_force_compile'	=>	true,		//是否强制编译
	)
);