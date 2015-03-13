<?php
return array(	
	/**
	 * 模板引擎
	 * @var string
	 */
	'engine'	=>	'smarty',

	/**
	 * 默认是否使用layout
	 * @var bool
	 */
	'allow_layout'	=>	true,

	'layout_file'	=> '_layout',

	/**
	 * 模板文件的扩展名
	 * @var bool
	 */
	'file_ext'	=> '.html',
	
	/**
	 * 视图的相关配置
	 */
	'config'	=>	array(
		'template_dir'	=>	'', //模板目录,为空则使用框架默认的视图结构
		'is_cache'	=>	false, //模板是否缓存		
		'cache_dir'	=>	'cache', //模板缓存目录,如果没有指定将使用全局缓存目录
		'cache_lifetime'	=>	300, //缓存时间,单位为秒,默认为300秒，即5分钟
	),
		
	'ext_view'	=> array(
		'adapter'	=> 'smarty',
		'compile_dir'	=>	'cache',		//模板编译目录,如果没有指定将使用全局缓存目录下的compile_tpl目录
		'left_delimiter'	=> '<%{',		//模板语法的左定界符
		'right_delimiter'	=> '}%>', 		//模板的右定界符
		'compile_check' => true,  //是否开启编译检查
		'force_compile'	=> true,  //是否强制编译
		'debugging' => false,  //是否开启smarty的调试
	)
);