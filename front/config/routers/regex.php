<?php
return array(	
	'news' => array(
		'regex' => 'news/add/([\d]+)/([\d]+)',
		'match'	=> array(
			1	=> 'id',
			2	=> 'page',
			3	=> 'num'
		),
		'router' => array(
			'app'	=>	'default',
			'namespace'	=>	'default',
			'controller'=>	'news',
			'action'	=>	'add',
			'id'		=>	'0',
		),
	),
	
	'y' => array(
		'regex' => 'a/b/([\d]+)-([\d]+)/([\d]+)',
		'match'	=> array(
			1	=> 'id',
			2	=> 'page',
			3	=> 'num'
		),
		'router' => array(
			'app'	=>	'aa',
			'namespace'	=>	'default',
			'controller'=>	'b',
			'action'	=>	'index',
			'id'		=> '1234',
		),
	),

);