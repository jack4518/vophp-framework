<?php
return array(
	/**
	 * 路由方式,一共有三种路由方式,分别为：
	 * STANDARD:   正常的URL参数，如:?module=blog&namespace=admin&controller=user&action=add&name=jackchen
	 * PATHINFO: 是PHP自身的URL解析模式，需要服务器开启PATHINFO支持，
	 * 			  格式为:index.php/module/blog/namespace/admin/controller/user/action/add/name/jackchen
	 * REWRITE:	 是通过服务器WEBSERVICE进行URL重写后的URL规则
	 * 			格式为：/blog/admin/user/add/jackchen
	 * @var string
	 */
	'route_mode' =>	'STANDARD',
	
	/**
	 * 定义路由文件,可定义多个路由文件，定义在最后的路由优先级最高
	 * @var array
	 */
	'routers'	=> array(
		'0'	=> 'configs/routers.config.php',
	),
	
	/**
	 * 设置每个应用的目录
	 * @var array
	 */
	'application_folder' => array(
		
	),
	
	/**
	 * 设置路由的模块、命名空间、控制器、动作器的键名
	 * @var array
	 */
	'application_key' => array(
		'app' 		=> 	'module',
		'controller'=> 'controller',
		'action' 	=> 	'action',
	),
	
	/**
	 * 默认路由
	 * @var array
	 */
	'default_router' =>	array(
		'app'		=>	'default',
		'controller'=>	'default',
		'action'	=>	'index',
	)
);