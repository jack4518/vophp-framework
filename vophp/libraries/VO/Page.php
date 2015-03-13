<?php
/**
 * 定义 VO_Page分页类 
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Page {
	
	private $pageBarStyle = array(
 				'1'	=>	array(
					'nextPage'	=>	'next',
					'previousPage'	=> 'previous',
					'firstPage' => 'first',
					'lastPage'  => 'last',
					'leftDelimiter' => '[',
					'rightDelimiter' => ']',
					'isShowFirst'	=>	false,
					'isShowLast'	=>	false,
					'isShowNext'	=>	true,
					'isSHowPrevious'=>	true,
 					'isShowCurrent'	=>	false,
					'isShowPageNumberBar'	=> true,
					'isShowSelect'	=> true,
				),
				
				'2'	=>	array(
					'nextPage'	=>	'next',
					'previousPage'	=> 'previous',
					'firstPage' => 'first',
					'lastPage'  => 'last',
					'leftDelimiter' => '[',
					'rightDelimiter' => ']',
					'isShowFirst'	=>	true,
					'isShowLast'	=>	true,
					'isShowNext'	=>	true,
					'isSHowPrevious'=>	true,
 					'isShowCurrent'	=>	true,
					'isShowPageNumberBar'	=> false,
					'isShowSelect'	=> true,
				),
				
				'3'	=>	array(
					'nextPage'	=>	'next',
					'previousPage'	=> 'previous',
					'firstPage' => 'first',
					'lastPage'  => 'last',
					'leftDelimiter' => '[',
					'rightDelimiter' => ']',
					'isShowFirst'	=>	true,
					'isShowLast'	=>	true,
					'isShowNext'	=>	true,
					'isSHowPrevious'=>	true,
 					'isShowCurrent'	=>	false,
					'isShowPageNumberBar'	=> false,
					'isShowSelect'	=> false,
				),
				
				'4'	=>	array(
					'nextPage'	=>	'next',
					'previousPage'	=> 'previous',
					'firstPage' => 'first',
					'lastPage'  => 'last',
					'leftDelimiter' => '[',
					'rightDelimiter' => ']',
					'isShowFirst'	=>	false,
					'isShowLast'	=>	false,
					'isShowNext'	=>	true,
					'isSHowPrevious'=>	true,
 					'isShowCurrent'	=>	false,
					'isShowPageNumberBar'	=> true,
					'isShowSelect'	=> false,
				),
				
				'5'	=>	array(
					'nextPage'	=>	'>',
					'previousPage'	=> '<',
					'firstPage' => '<<',
					'lastPage'  => '>>',
					'leftDelimiter' => '[',
					'rightDelimiter' => ']',
					'isShowFirst'	=>	true,
					'isShowLast'	=>	true,
					'isShowNext'	=>	true,
					'isSHowPrevious'=>	true,
 					'isShowCurrent'	=>	false,
					'isShowPageNumberBar'	=> true,
					'isShowSelect'	=> false,
				),
				
				'6'	=>	array(
					'nextPage'	=>	'next',
					'previousPage'	=> 'previous',
					'firstPage' => '[1]',
					'lastPage'  => '',
					'leftDelimiter' => '[',
					'rightDelimiter' => ']',
					'isShowFirst'	=>	true,
					'isShowLast'	=>	true,
					'isShowNext'	=>	true,
					'isSHowPrevious'=>	true,
 					'isShowCurrent'	=>	false,
					'isShowPageNumberBar'	=> true,
					'isShowSelect'	=> false,
				),
		);
	/**
	 * 分页参数的键名
	 * @var string
	 */
	public $pageName = "page";
 
	/**
	 * 控制记录条的个数
	 * @var int
	 */
	public $pageBarNum = 5;
	
	/**
	 * 记录总数
	 * @var int
	 */
	public $total = 0;
	
	/**
	 * 总页数
	 * @var int
	 */
	public $totalPage = 0;
	
	/**
	 * 当前页
	 * @var int
	 */
	public $currentPage = 1;
	
	/**
	 * 分页链接地址
	 * @var string
	 */
	public $url = '';
	
	/**
	 * 当前选择的分类栏风格模式
	 * @var mixed
	 */
	private $currentMode = 1;
	
	/**
	 * 翻译语言
	 * @var VO_Language_Abstract
	 */
	private $lang = '';
	
	/**
	 * 默认翻译语言命名空间
	 * @var string
	 */
	private $lang_namespace = 'VOPHP';

	/**
	 * 默认的翻译语言
	 * @var string
	 */
	private $lang_language = 'zh';

	/**
	 * constructor构造函数
	 *
	 * @param total    	总记录数
	 * @param perpage  	每页显示的记录数 ，默认为每页显示10条
	 * @param pageBarNum　	分页栏显示的进度个数
	 * @param nowindex 	当前页   ，默认为第一页
	 * @param url      	转向的URL，默认空为转向本页
	 * @return void
	 */
	public function __construct($total=0, $perPage=10, $currentPage=null, $pageBarNum=5, $url=null, $pageName="page"){
		$this->total	= (int)$total;
		$this->perPage	= (int)$perPage>0 ? (int)$perPage : 10;
		$this->url		= $url;
		$this->pageBarNum = (int)$pageBarNum>0 ? (int)$pageBarNum : 5;
		$this->pageName  = $pageName ? $pageName : $this->pageName;
		  
		if( $this->total<0 ){
			$error = new VO_Error();
			$error->error( sprintf(T_System('Total page %s is not a positive integer'), $this->total) );
		}
		$this->totalPage = ceil($total/$perPage); //计算总页数
		
		$this->setCurrentPage($currentPage);  //设置当前页
		$this->setUrl($url);//设置链接地址
		
		/**
		 * 设置默认翻译语言
		 */
		$this->setLanguage();
	}
	
	/**
	 * 增加或者替换分页样式
	 * @param array $style 样式参数数组
	 * @param string $key 样式标记
	 * @param bool $isEnforce 是否强制增加或者替换
	 * @return void
	 */
	public function addStyle($style, $key, $isEnforce=false){
		if( !is_array($style) ){
			$error = new VO_Error();
			$error->error(  sprintf($this->lang->_( '%s is not a vaild style', $this->lang_namespace), $style) );
		}
		
		$default = array(
					'nextPage'	=>	'next',
					'previousPage'	=> 'previous',
					'firstPage' => 'first',
					'lastPage'  => 'last',
					'leftDelimiter' => '',
					'rightDelimiter' => '',
					'isShowFirst'	=>	false,
					'isShowLast'	=>	false,
					'isShowNext'	=>	false,
					'isSHowPrevious'=>	false,
 					'isShowCurrent'	=>	false,
					'isShowPageNumberBar'	=> false,
					'isShowSelect'	=> false,
				);
		if( empty($key) ){
			$error = new VO_Error();
			$error->error( sprintf($this->lang->_( 'Page Style key %s is not a vaild', $this->lang_namespace ), $key) );
		}
		
		if( array_key_exists($key, $this->pageBarStyle) && $isEnforce==false ){
			$error = new VO_Error();
			$error->error( sprintf($this->lang->_( 'Page Style key %s is exist', $this->lang_namespace ), $key) );
		}
		
		$style = array_merge( $default, $style );
		$this->pageBarStyle[$key] = $style;		
	}
	
	/**
	 * 设置分页语言信息
	 * @param VO_Language_Abstract $language
	 */
	public function setLanguage(&$language=array(), $namespace='VOPHP', $lang=null){
		$this->lang_namespace = $namespace;
		if( !empty($lang) ){
			$this->lang_language = $lang;
		}else{
			$config = C();
			$this->lang_language = C('language.language');
		}
		
		if( $language instanceof VO_Language ){
			$this->lang = $language;
		}elseif( is_array($language) ){
			$this->lang = new VO_Language();
			$this->lang->load($language, $namespace, $lang);
		}else{
			$this->lang = new VO_Language();
			$this->lang->load(array(), $namespace, $lang);
		}
	}
 
	/**
	 * 获取显示"下一页"的代码
  	 * @return string
	 */
	protected function getNextPage(){
		$nextPage = $this->lang->_( $this->pageBarStyle[$this->currentMode]['nextPage'], $this->lang_namespace );
		if($this->currentPage < $this->totalPage){
			return $this->getLink($this->getUrl($this->currentPage+1), $nextPage, 'vo_next_page');
		}
		return '<span class="vo_next_page_nolink">' . $nextPage . '</span>';
	}
 
	/**
	 * 获取显示“上一页”的代码
	 * @return string
	 */
	protected function getPreviousPage(){
		$previousPage = $this->lang->_( $this->pageBarStyle[$this->currentMode]['previousPage'], $this->lang_namespace );
		if($this->currentPage > 1){
			return $this->getLink($this->getUrl($this->currentPage-1), $previousPage, 'vo_previous_page');
		}
		return '<span class="vo_previous_page_nolink">' . $previousPage . '</span>';
	}
 
	/**
	 * 获取显示“首页”的代码
	 * @return string
	 */
	protected function getFirstPage(){
		$firstPage = $this->lang->_( $this->pageBarStyle[$this->currentMode]['firstPage'], $this->lang_namespace );
		$firstPage = !empty($firstPage) ? $firstPage : $this->getText($this->totalPage);
		if($this->currentPage == 1){
			return '<span class="vo_first_page_nolink">' . $firstPage . '</span>';
		}
		return $this->getLink($this->getUrl(1), $firstPage, 'vo_first_page');
	}
 
	/**
	 * 获取显示“最后一页”的代码
	 * @return string
	 */
	protected function getLastPage(){
		$lastPage = $this->lang->_( $this->pageBarStyle[$this->currentMode]['lastPage'], $this->lang_namespace );
		$lastPage = !empty($lastPage) ? $lastPage : $this->getText($this->totalPage);
		if($this->currentPage == $this->totalPage){
			return '<span class="vo_last_page_nolink">' . $lastPage . '</span>';
		}
		return $this->getLink($this->getUrl($this->totalPage), $lastPage, 'vo_last_page');
	}
 
	/**
	 * 显示页码栏
	 * $return string
	 */
 	protected function showNumberBar(){
		$index = ceil($this->pageBarNum/2);
		if($this->pageBarNum - $index + $this->currentPage > $this->totalPage){
			$index = $this->pageBarNum - $this->totalPage + $this->currentPage;
		}
		$start = $this->currentPage - $index + 1;
		$start = ($start>1) ? $start : 1;
		$bar = '';
		for( $i=$start; $i<$start+$this->pageBarNum; $i++ ){
			if( $i <= $this->totalPage ){
				if( $i != $this->currentPage ){
					$bar .= $this->getText( $this->getLink($this->getUrl($i), $i, 'vo_page_item') );
				}else{ 
					$bar .= $this->getText('<span class="vo_current_page">' . $i . '</span>');
				}
			}else{
				break;
			}
			$bar .= "\n";
		}
		return $bar;
	}
	
	/**
	 * 获取显示跳转下拉列表框的HTML代码
  	 * @return string
 	 */
	protected function getSelect(){
		$indexof = strrpos($this->url, '&');
		if($indexof){
			$url = substr($this->url, 0, $indexof+1) . $this->pageName . '=';
		}else{
			$url = $this->url;
		}

		$select = '<select name="vo_pagebar_select" id="vo_pagebar_select" onChange="location=\'' . $url . '\'+this.value">';
		for( $i=1; $i<=$this->totalPage; $i++){
			if( $i==$this->currentPage ){
				$select .= '<option value="' . $i . '" selected>' . $i . '</option>';
			}else{
				$select .= '<option value="'.$i.'">'.$i.'</option>';
			}
		}
		$select .= '</select>';
	  
		return $select;
	}
 
	/**
	 * 分页显示风格
	 *
	 * @param int $mode
  	 * @return string
  	 */
 	public function show($mode=1){
 		$this->currentMode = $mode;
		if( isset($this->pageBarStyle[$this->currentMode]) ){
			$style = $this->pageBarStyle[$this->currentMode];
		}else{
			$error = new VO_Error();
			$error->error( sprintf( $this->lang->_( 'Page Style %s is not exist', $this->lang_namespace ), $this->currentMode) );
		}
		
		$bar = '';
		if( $style['isShowFirst'] ){
			$bar .= $this->getFirstPage();
		}
		
 		if( $style['isSHowPrevious'] ){
			$bar .= $this->getPreviousPage();
		}
		
 		if( $style['isShowCurrent'] ){
			$bar .= $this->getText($this->currentPage);
		}
		
 		if( $style['isShowPageNumberBar'] ){
			$bar .= $this->showNumberBar();
		}
		
 		if( $style['isShowNext'] ){
			$bar .= $this->getNextPage();
		}
		
 		if( $style['isShowLast'] ){
			$bar .= $this->getLastPage();
		}
		
 		if( $style['isShowSelect'] ){
			$bar .= $this->lang->_( 'goto', $this->lang_namespace ) . $this->getSelect();
		}
 		return $bar;
 	} 
		
	/**
	 * 设置url地址
	 * @param string $url（转向URL，转默认为空则设置为当前页）
  	 * @return bool
	 */
	private function setUrl($url=''){
		if( !empty($url) ){
			$this->url = $url . ((stristr($url, '?')) ? '&' : '?') . $this->pageName . "=";
		}else{
			if( empty($_SERVER['QUERY_STRING']) ){
				$this->url = $_SERVER['REQUEST_URI'] . "?" . $this->pageName . "=";
			}else{
				if( stristr($_SERVER['QUERY_STRING'], $this->pageName.'=') ){
					$this->url = preg_replace('/'.$this->pageName.'=\d/', '', $_SERVER['REQUEST_URI']);
					$last = $this->url[strlen($this->url)-1];
					if( $last=='?' || $last=='&' ){
						$this->url .= $this->pageName . "=";					  
					}else{
						$this->url .= '&' . $this->pageName . "=";
					}
				}elseif( stristr($_SERVER['REQUEST_URI'], $this->pageName.'=') ){
					$this->url = preg_replace('/'.$this->pageName.'=\d/', '', $_SERVER['REQUEST_URI']);
					$last = $this->url[strlen($this->url)-1];
					if( $last=='?' || $last=='&' ){
						$this->url .= $this->pageName . "=";					  
					}else{
						$this->url .= '&' . $this->pageName . "=";
					}
				}else{
					$this->url = $_SERVER['REQUEST_URI'] . '?' . $this->pageName . '=';
				} 
			}
		}
	}
 
	/**
	 * 设置当前页面,如果参数为空则从URL中获取
	 * @param int $page  当前页号
	 * @return void
	 */
	private function setCurrentPage($page){
		if( !empty($page) ){
			$this->currentPage = (int) $page;
		}elseif( isset($_GET[$this->pageName]) ){
			$this->currentPage = (int) $_GET[$this->pageName];
		}else{
			$this->currentPage = 1;
		}
	}
  
	/**
	 * 为指定的页面返回地址值
	 * @param int $page 页号
	 * @return string 转向URL
	 */
	private function getUrl($page=1){
		return $this->url . $page;
	}
 
	/**
	 * 获取分页显示文字
	 * @param String $str  页表示符
 	 * @return string
	 */ 
	private function getText($str){
		return $this->pageBarStyle[$this->currentMode]['leftDelimiter'] . $str . $this->pageBarStyle[$this->currentMode]['rightDelimiter'];
	}
 
	/**
	 * 获取链接HTML代码
	 * @param String $text     链接文本
	 * @param String $url      转向地址
	 * @param String $classname	样式   
	 */
 	private function getLink($url, $text, $classname=''){
		if( empty($classname) ){
			return '<a href="' . $url . '">' . $text. '</a>';
		}else{
			return '<a href="' . $url . '" class="' . $classname . '">' . $text. '</a>';
		}
	}
}