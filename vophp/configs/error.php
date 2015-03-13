<?php
return array(	
	/**
	 * 是否显示打开调试模式
	 * @var bool
	 */
	'debug' => true,

	/**
	 * dump函数调试信息时的显示模式,可以为"page":页面正常显示, 'firephp':firePHP方式显示
	 */
	'dump_show_mode'	=>	'page',

	/**
	 * 是否友好显示错误
	 */
	'friendly_error'	=> true,
	
	/**
	 * 显示错误的报告级别
	 */
	'errorLevel'	=>	array(E_ERROR, E_USER_ERROR, E_NOTICE, E_WARNING, E_DEPRECATED, E_STRICT),
);