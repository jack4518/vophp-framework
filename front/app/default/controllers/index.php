<?php
/**
 * 首页控制器
 * @author JackChen
 * @date 2010-07-08
 */
include(APP_DIR . DS . 'application.php');
class IndexController extends application
{	
	/**
	 * 构造方法
	 */
	public function __construct(){
		header('Content-type:text/html;charset=utf-8');
		$IndexModel = $this->loadModel('index');
		$ids = $IndexModel->getAll(); 
		//var_dump($ids);
	}
	
	/**
	 * 默认控制器
	 */
	public function indexAction(){
		$String = new VO_String_Pinyin();
		//var_dump($String);
		$a = $this->load('string')->countStr('asdfasdf asdfsdf');
		$this->load('http');
		//$a = VO_Loader::load('model')->loadModel('a');
		var_dump($a);
	}
}