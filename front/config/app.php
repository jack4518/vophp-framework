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
	'router_mode' =>	'REWRITE',

	/**
	 * WebServer 不支持PATH_INFO时,需要在设置path_info_var变量，并在重写规则里是以此为URL的QUERY_STRING变量名称
	 * 如(nginx)：rewrite ^/(.+)$ /index.php?path_info=/$1 last;
	 */
	'path_info_var'	=> 'path_info',
	
	/**
	 * 设置路由的模块、控制器、动作器的键名
	 * @var array
	 */
	'application_key' => array(
		'app' 	=> 	'app',
		'controller' => 'controller',
		'action' 	=> 	'action',
	),
	
	/**
	 * 默认路由
	 * @var array
	 */
	'default_router' =>	array(
		'app'	=>	'default',
		'controller'=>	'index',
		'action'	=>	'index',
	)
);