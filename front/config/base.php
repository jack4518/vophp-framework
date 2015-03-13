<?php
return array(
	/**
	 * VOPHP版本信息
	 * @var string
	 */
	'version'	=> '2.0',

	/**
	 * 应用名称
	 * @var string
	 */
	'app_name'	=>	'front',

	/**
	 * 入口文件名称
	 * @var string
	 */
	'index_page'	=>	'index.php',

	/**
	 * 网站的URL地址
	 * @var string
	 */
	'base_url' => 'http://' . $_SERVER['SERVER_NAME'],

	/**
	 * 网站的URL地址
	 * @var string
	 */
	'upload_url' => 'http://' . $_SERVER['SERVER_NAME'] . '/upload',

	/**
	 * 设置系统的时区
	 */
	'date_timezone'	=>	'Asia/Shanghai',
	
	/**
	 * 附件上传地址
	 * @var string
	 */
	'upload_path' => 'upload',
	
	/**
	 * 列表显示条数
	 */
	'list_num' => 20,
);