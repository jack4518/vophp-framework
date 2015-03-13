<?php
/**
 * 基础控制器
 * @author JackChen
 * @date 2011-01-08
 */
class application extends VO_Controller{
	
	/**
	 * 已登录的用户信息
	 * @var array
	 */
	protected $_user = array();
	
	/**
	 * 分页样式
	 * @var array
	 */
	protected $_pager_style = array();
		
	/**
	 * 网站基本配置信息数据模型
	 * @var Base
	 */
	protected $_baseModel = null;
	
	/**
	 * 广告数据模型
	 * @var Adv
	 */
	protected $_advModel = null;	
	
	/**
	 * 网站后台设置基本信息
	 * @var array
	 */
	protected $_cnf;
	
	
	/**
	 * 是否调试阶段
	 * @var bool
	 */
	private $_is_test = true;
	
	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
	 * 所有控制器的初始化方法
	 */
	public function init(){
	/*
		if($this->_is_test == true){
			$is_test_login = $this->getRequest()->getCookie('is_test_login', false);
			if($is_test_login === false){
				$this->_redirect(buildUrl('login', 'login', 'user'));
			}
		}
		*/
		if(VO_Session::get('user_is_login') == true){
	        $this->_user = VO_Session::get('userinfo');
	        if(!defined('_USER_ID')){
	        	define('_USER_ID', $this->_user['id']);
	        }
			if(!defined('_USER_NAME')){
	        	define('_USER_NAME', $this->_user['username']);
	        }
		}
		
		parent::init();
		$this->initView();
		$this->loadCommon();
		
		//分页样式
		$this->_pager_style = C('base.pager_style');
		
		if((VO_Session::get('test_login')<> true)){
			//$this->_redirect(buildUrl('index', 'login', 'login'));
		}
	}
	
	/**
	 * 初始化为视图变量
	 * @return void
	 */
	public function initView(){
	    $this->assign('upload_url', 'http://' . $_SERVER['SERVER_NAME'] . '/upload');
		$this->assign('theme_url', C('base.base_url') . '/' . C('base.app_name') .'/public');
	    $this->assign('base_url', C('base.base_url'));
	    $this->assign('layout_dir', APP_DIR . DS . '_global' . DS . 'layouts');
	}
	
	/**
	 * 载入公共的信息
	 * @return void
	 */
	public function loadCommon(){

	}
}
