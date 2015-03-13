<?php
return array(
	'/app:/controller/action' => array(
		'rule' => '/:app/:controller/:action',
		'router' => array(
			'app'	=>	'default',
			'controller'=>	'default',
			'action'	=>	'index',
		),
	),
);