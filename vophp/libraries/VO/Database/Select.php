<?php
/**
 * 定义  VO_Database_Select  数据库选择器类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-24
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Select{
	
	protected $_instance;
	
	private $_select = null;
	
	private $_is_found_rows = false;
	
	private $_adapter = null;
	
	/**
	 * 构造函数
	 */
	public function __construct($adapter){
		if($adapter instanceof VO_Database_Adapter_Abstract){
			$this->_adapter = $adapter;
		}else{
			return false;
		}
		$this->init();
	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Database_Select
	 */
	public static function getInstance($adapter){
		static $instance = null;
		if( !$instance instanceof VO_Database_Select ){
			$instance = new self($adapter);
		}
		return $instance;
	}
	
	/**
	 * 初始化SELECT语句
	 */
	public function init(){
		$this->_select = 'SELECT ';
	}
	
	/**
	 * 设置是否去除重复数据
	 * @param Boolean $flag
	 */
	public function distinct($flag = true){
		$this->_select .= $flag?' DISTINCT ' : '';
		return $this;
	}
	
	public function field($field = '*'){
		$this->_select .= $field ? $field : '';
		return $this;
	}
	
	/**
	 * 设置是否通过设置'SQL_CALC_FOUND_ROWS'返回Limit前返回的总数据
	 * @param Boolean $flag
	 */
	public function setIsFoundRows($flag = true){
		$this->_is_found_rows = $flag;
		if($this->_is_found_rows == false){
			$this->_select .= '';
		}else{
			$this->_select .= ' SQL_CALC_FOUND_ROWS';
		}
		return $this;
	}	
	
	/**
	 * 构建From语句
	 * @param string $table
	 * @param string $result
	 */
	public function from($table=''){
		if(!$table){
			throw new VO_Exception('数据库表名不能为空.');
		}
		$this->_select .= $result . ' FROM ' . $table;
		return $this;
	}
	
	/**
	 * 构建全连接语句
	 * @param string $table
	 * @param string $on
	 */
	public function join($table='',$on = ''){
		return $this->innerJoin($table,$on);
	}
	
	/**
	 * 构建全连接语句
	 * @param string $table
	 * @param string $on
	 */
	public function innerJoin($table='',$on=''){
		if(empty($table) || empty($on)){
			return $this;
		}
		$this->_select .= ' INNER JOIN ' . $table . ' ON ' . $on;
		return $this;
	}
	
	/**
	 * 构建左连接语句
	 * @param string $table
	 * @param string $on
	 */
	public function leftJoin($table='',$on=''){
		if(empty($table) || empty($on)){
			return $this;
		}
		$this->_select .= ' LEFT JOIN ' . $table . ' ON ' . $on;
		return $this;
	}
	
	/**
	 * 构建右连接语句
	 * @param string $table
	 * @param string $on
	 */
	public function rightJoin($table='',$on=''){
		if(empty($table) || empty($on)){
			return $this;
		}
		$this->_select .= ' RIGHT JOIN ' . $table . ' ON ' . $on;
		return $this;
	}
	
	/**
	 * 构建自然连接语句
	 * @param string $table
	 * @param string $on
	 */
	public function naturalJoin($table='',$on=''){
		if(empty($table) || empty($on)){
			return $this;
		}
		$this->_select .= ' NATURAL JOIN ' . $table . ' ON ' . $on;
		return $this;
	}

	/**
	 * 构建Where语句
	 * @param string||array $where
	 */
	public function where($where = '1'){
		if(is_array($where)){
			$where = implode(' AND ', $where);
		}
		$where = '(' . $where . ')';
		if(!strpos($this->_select,'WHERE')){
			$this->_select .= ' WHERE ';
			$this->_select .= $where;
		}else{
			$this->_select .= ' AND ' . $where;
		}
		return $this;
	}

	/**
	 * 构建Where语句
	 * @param string||array $where
	 */
	public function orWhere($where = '1'){
		if(is_array($where)){
			$where = implode(' OR ', $where);
		}
		$where = '(' . $where . ')';
		if(!strpos($this->_select,'WHERE')){
			$this->_select .= ' WHERE ';
			$this->_select .= $where;
		}else{
			$this->_select .= ' OR ' . $where;
		}
		return $this;
	}

	/**
	 * 构建GROUP BY语句
	 * @param string||array $group
	 */
	public function group($group = 'id'){
		$tmp = '';
		if(is_array($group)){
			foreach($group as $k => $v){
				$this->_select .= ' GROUP BY ' . $v;
			}
		}else{
			$this->_select .= ' GROUP BY ' . $group;
		}
		return $this;
	}
	
	/**
	 * 构建 Having 语句
	 * @param int $having
	 */
	public function having($having = '1'){
		if(is_array($having)){
			$having = implode(' AND ', $having);
		}
		$having = '(' . $having . ')';
		if(!strpos($this->_select,'HAVING')){
			$this->_select .= ' HAVING ';
			$this->_select .= $having;
		}else{
			$this->_select .= ' AND ' . $having;
		}
		return $this;
	}
	
	/**
	 * 构建 Having 语句
	 * @param int $having
	 */
	public function orHaving($having = '1'){
		if(is_array($having)){
			$having = implode(' OR ', $having);
		}
		$having = '(' . $having . ')';
		if(!strpos($this->_select,'HAVING')){
			$this->_select .= ' HAVING ';
			$this->_select .= $having;
		}else{
			$this->_select .= ' OR ' . $having;
		}
		return $this;
	}
	
	/**
	 * 构建ORDER BY语句
	 * @param string||array $order
	 * @param string $type
	 */
	public function order($order = 'id', $type='ASC'){
		$tmp = '';
		if(is_array($order)){
			foreach($order as $k => $v){
				if(!strpos($this->_select,'ORDER')){
					$this->_select .= ' ORDER BY ' . $v . ' ' . $type;
				}else{
					$this->_select .= ', ' . $v . ' ' . $type;
				}
			}
		}else{
			if(!strpos($this->_select,'ORDER')){
					$this->_select .= ' ORDER BY ' . $order . ' ' . $type;
				}else{
					$this->_select .= ', ' . $order . ' ' . $type;
				}
		}
		return $this;
	}
	
	/**
	 * 构建 LIMIT 语句
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit($limit=1,$offset=1){
		$limit     = ($limit > 0)     ? $limit     : 1;
        $offset = ($offset > 0) ? $offset : 1;
		$this->_select .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
		return $this;
	}
	
	/**
	 * 构建 LIMIT 语句
	 * @param int $limit
	 * @param int $offset
	 */
 	public function limitPage($page=0, $rowCount=0)
    {
        $page     = ($page > 0)     ? $page     : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        
        $offset = $page * $rowCount;
        $this->_select .= ' LIMIT ' . $rowCount . ' OFFSET ' . $offset;
        return $this;
    }
    
    /**
     * 通过setIsFoundRows设置SQL_CALC_FOUND_ROWS获取执行Limit取得的总数据,通过此方法获取
     * @param $db
     */
    public function getFoundRows(){
    	if($this->_is_found_rows == false){
    		return false;
    	}else{
    		$sql = 'SELECT FOUND_ROWS()';
    		return $this->_adapter->fetchOne($sql);
    	}
    }
    
    /**
	 * 重置select对象的SQL语句
	 */
    public function reset(){
    	$this->_select = '';
    	return $this;
    }
	
	/**
	 * 返回构建的SQl语句
	 */
	public function getSql(){
		return $this->_select;
	}
	
	/**
	 * __toString时返回构建的SQl语句
	 */
	public function __toString(){
		return $this->_select;
	}
}