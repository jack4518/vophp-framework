<?php
/**
 * 定义 VO_Html_Table HTML表格类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-11-15
 **/
defined('VOPHP') or die('Restricted access');

include_once VO_LIB_DIR . DS . 'Html' . DS . 'Tag.php';
class VO_Html_Table extends VO_Html_Tag{
	
	/**
	 * 表格变量
	 * @var string
	 */
	private $_table = '';
	
	/**
	 * 表格表头变量
	 * @var string
	 */
	private $_thead = '';
	
	/**
	 * 表格表头标题变量
	 * @var string
	 */
	private $_caption = '';
	
	/**
	 * 表格表头变量
	 * @var string
	 */
	private $_tbody = '';
	
	/**
	 * 构造函数
	 * @param array $attributes
	 * @return VO_Html_Table
	 */
	public function __construct(array $attributes = array()){
		$this->start($attributes);
	}
	
	/**
	 * 生成表格头
	 * @param	array	$attributes  表格的属性
	 * @return	string	生成后的HTML标签
	 */
	private function start(array $attributes = array()){
		$attr = array(
			'name'		=>	'voTable',
			'id'		=>	'voTable',
			'border'	=>	'1',
			'cellpadding'	=>	1,
			'cellspacing'	=>	1,
			'width'		=>	'500',
			'height'	=>	'',
		);
		
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attributes = array_merge($attr, $attributes);
		
		$this->_table = self::createTag('table', '', $attributes);
	}
	
	/**
	 * 设置表格头
	 * @param array $ths  列数组
	 * @return void
	 */
	public function setHeader(){
		$ths  = func_get_args();
		if(isset($ths[0]) && is_array($ths[0])){
			$ths = $ths[0];
		}
		$tr = '<tr>';
		if( empty($ths) ){
			$tr .= '<th>&nbsp;</th>';
		}else{
			foreach($ths as $th){
				$tr .= '<th>' . $th . '</th>';
			}
		}
		$tr .= '</tr>';
		$this->_thead .= $tr;
	}

	/**
	 * 设置表格标题
	 * @param string $caption  表格标题
	 * @return void
	 */
	public function setCaption($caption){
		if( !empty($caption) ){
			$this->_caption = '<caption>' . $caption . '</caption>';
		}
	}
	
	/**
	 * 为表格增加一行(未定义参数，可以是一个一维数组，或者是多个参数，程序会自动将其转换)
	 * @return void
	 */
	public function addRow(){
		$cells  = func_get_args();
		if(isset($cells[0]) && is_array($cells[0])){
			$cells = $cells[0];
		}
		
		$tr = '<tr>';
		if( empty($cells) ){
			$tr .= '<td>&nbsp;</td>';
		}else{
			foreach($cells as $cell){
				$tr .= '<td>' . $cell . '</td>';
			}
		}
		$tr .= '</tr>';
		$this->_tbody .= $tr;
	}
	
	/**
	 * 生成表格尾
	 * @return void
	 */
	private function end(){
		$this->_table .= self::endTag('table');
	}
	
	/**
	 * 生成表格
	 * @param	$dataSet	表格数组，必须是一个二维数组，如果有此参数，则生成一个包括行和列的表格,一般用于从数据库取出结果集中直接生成
	 * @return	$string		完整的表格
	 */
	public function create($dataSet = array()){
		if( !empty($dataSet) ){
			$this->clear();
			foreach($dataSet as $k => $row){
				$this->addRow($row);
			}
		}
	
		$this->_table .= $this->_caption;
		$this->_table .= $this->_thead;
		//如果为空则加入一行空行
		if(!$this->_tbody){
			$this->addRow();
		}
		$this->_table .= $this->_tbody;
		$this->end();
		return $this->_table;
	}
	
	/**
	 * 根据一个一维数组，生成一个表格
	 * @param array	 $data	表格列数据(必须为数组，如果此参数为多维数组，该方法会将其转化为一维数组)
	 * @param int	 $row	表格的行数
	 * @param int	 $colnum	表格的列数
	 * @param bool	 $hasHeader	是否将第一行作为标题
	 * @return string	生成后的表格
	 */
	public function createTable($data, $row=null, $colnum=3, $hasHeader=false){
		$data = VO_Helper_Array::oneToTwo($data);
		$row = ($row===null) ? ceil(count($data)/$colnum) : $row;
		if( !is_array($data) ){
			$this->triggerError('The Frist argument must a array');
			exit;
		}
		if( count($data) < ($row * $colnum) ){
			$spaceNum = ($row * $colnum) - count($data);
			$tmp = array_fill(count($data), $spaceNum, '&nbsp;');
			$data = array_merge($data, $tmp); 
		}
		//清除当前表格存储器
		$this->clear();
		
		$arr = array();
		$i = 0;
		foreach($data as $k => $v){
			$arr[$i][] = $v;
			if( ($k+1) % $colnum ==0 ){
				$i++;
			}
		}
		
		$i = 1;
		foreach($arr as $r => $c){
			//是否将第一行作为标题头
			if($hasHeader == true && $r==0){
				$this->setHeader($c);
			}else{
				$this->addRow($c);
			}
			$i++;
			if($i>$row){
				break;
			}
		}
		return $this->create();
	}
	
	/**
	 * 清除创建的表格
	 * 
	 * @return void
	 */
	public function clear(){
		$this->start();
		$this->_thead = '';
		$this->_caption = '';
		$this->_tbody = '';
	}
}