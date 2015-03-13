<?php
return array(
	'test' => array(
		'rule' => '/test',
		'router' => array(
			'app'	=>	'default',
			'controller'=>	'index',
			'action'	=>	'test',
		),
	),
	
	'/admin/*' => array(
		'rule' => '/admin/:app/:controller/:action',
		'router' => array(
			'app'	=>	'default',
			'controller'=>	'index',
			'action'	=>	'index',
		),
	),
	
	
	'offshore' => array(
		'rule' => '/offshore',
		'router' => array(
			'app'	=>	'product',
			'controller'=>	'service',
			'action'	=>	'display',
			'params' 	=>	array(
						'id' =>	13,
						'service_type_id' =>	8,			  
				),
		),				
	),
		
	'controller/action' => array(
		'rule' => '/:app/:controller/:action',
		'router' => array(
			'app'	=>	'default',
			'controller'=>	'default',
			'action'	=>	'index',
		),
	),

	
	'blog/admin' => array(
		'rule' => '/blog/admin/:controller/:action',
		'router' => array(
			'app'	=>	'blog',
			'controller'=>	'index',
			'action'	=>	'index',
		),
	),
	
	'blog/default' => array(
		'rule' => '/blog/:controller/:action',
		'router' => array(
			'app'	=>	'blog',
			'controller'=>	'index',
			'action'	=>	'index',
		),
	),
	
	'blog' => array(
		'rule' => '/blog/:controller/:action',
		'router' => array(
			'app'	=>	'blog',
			'controller'=>	'index',
			'action'	=>	'index',
			
		),
	),
	

);