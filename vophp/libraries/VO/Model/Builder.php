<?php
/**
 * 定义VO_Model_Builder SQL解析处理器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen<cqq254@163.com>
 * @package VO
 * @since version 1.0
 * @date 2010-05-25
 **/

class VO_Model_Builder extends VO_Object
{
    /**
     * sql语句
     * @access public
     * @var string
     */
    public $sql = '';

    /**
     * 参数
     * @access public
     * @var array
     */
    public $_param = array();

    /**
     * 常用MySQL函数 
     * @access private
     * @var functions
     */
    private static $functions = array();

    private static $reserved = array();

    /**
     * insert/update条件绑定字段前缀
     * @var string
     */
    const VPREFIX = ':slv_';

    /**
     * where条件绑定字段前缀
     * @var string
     */
    const WPREFIX = ':slw_';

	/**
	 * 获取当前绑定参数
	 * @param	void
	 * @return	array  需要绑定的参数值
	 */
	public function getParam(){
		return $this->_param;
	}

    /**
     * 解析SQL的Select查找(Find)语法
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $table   查找表名
     * @param  object  $Conder  SQL查询对象
     * @return string  解析后的SQL语句
     */
    public function buildFind($Conder)
    {
        $this->init();
        $field = $Conder->getField();
        if(is_array($Conder->getField())){
            $field = $this->_buildField($Conder->getField());
		}elseif(is_string($field) && '*' != $field){
            $field = preg_replace('(\s+)', ' ', $field);
            $field = $this->_buildField(explode(',', $field));
        }

        $this->sql = $Conder->isDistinct() ? 'SELECT DISTINCT ' : 'SELECT ';
        $this->sql .= $Conder->isFoundRows() ? ' SQL_CALC_FOUND_ROWS' : ''; 
        $this->sql .=  ' ' . $field . ' FROM `' . $Conder->getFrom() . '`';
        $this->sql .= $Conder->getForceIndex() ? ' FORCE INDEX(' . $Conder->getForceIndex() . ')' : '';
      
        $this->sql = $this->_buildWhere($this->sql, $Conder->getWhere());
        $this->sql = $this->_buildGroup($this->sql, $Conder->getGroup());
        $this->sql = $this->_buildOrder($this->sql, $Conder->getOrder());
        $this->sql = $this->_buildLimit($this->sql, $Conder->getLimit(), $Conder->getOffset());
        return $this->sql;
    }

    /**
     * 更新
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $table  待更新的表名
     * @param  object  $Conder  更新条件对象
     * @param  array   $attr  更新数据
     * @return string  解析后的更新SQL语句
     */
    public function buildUpdate($table, $Conder, array $attr)
    {
        $this->init();
        $this->sql = "UPDATE `{$table}` SET ";
        foreach($attr as $k => $v){
			if(is_scalar($v)){
				$bkey = self::VPREFIX . $k;
				$this->sql .= '`' . $k . '`=' . $bkey . ',';
				$this->_param[$bkey] = $v;
			}
		}
        $this->sql = rtrim($this->sql, ', ');
        $this->sql = $this->_buildWhere($this->sql, $Conder->getWhere());
        return $this->sql;
    }

    /**
     * 构造insert语句
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $table  待插入的表名
     * @param  object  $attr   待插入的数据
     * @return string	解析后的insert SQL语句
     */
    public function buildInsert($table, array $attr)
    {
        $this->init();
        $this->sql = "INSERT INTO `{$table}` SET ";
        foreach($attr as $k => $v){
			if(is_scalar($v)){
				$bkey = self::VPREFIX . $k;
				$this->sql .= '`' . $k . '`=' . $bkey . ',';
			    $this->_param[$bkey] = $v;
			}
		}
        $this->sql = rtrim($this->sql, ', ');
        return $this->sql;
    }

    /**
     * 构造批量插入字段的SQL语句
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
	 * @param  string  $table   需要插入数据的目标表名
	 * @param  array   $attr    需要插入的数据 
	 * @param  array   Leb_Model   当前model
	 * @return string           insert语句
     */
    public function buildInsertAll($table, array $attr, $model)
    {
        $this->init();
        $keys= array();
		$fields = $model->getMeta()->getIndexColumn();
		if(!empty($fields)){
			$keys = $fields;
		}else{
			foreach($attr as $item){
				$keys = array_keys($item);
				break;
			}
		}

        $tmp = implode('`,`', $keys);
        $this->sql = "INSERT INTO `{$table}`(`{$tmp}`)values";
        $i = 0;
        foreach($attr as $key => $item){
            $sql = '(';
            foreach($keys as $k){
                $bkey = ':'.$k.'_'.$i;
                $sql .= $bkey.',';
				if(isset($item[$k]) && $item[$k] <> NULL){
					$this->_param[$bkey] = $item[$k];
				}else{
					//如果不存在此索引字段的值,则取当前索引表的默认值做为值,避免出现数据表字段为非空而插入一条null数据造成的sql错误
					$default_value = $model->getMeta()->getColumnDefault($k);
					$this->_param[$bkey] = $default_value;
				}
            }
            $i++;
            $this->sql .= rtrim($sql, ', ').'),';
        }
		$this->sql = rtrim($this->sql, ', ');
        return $this->sql;
    }

    /**
     * 解析删除SQL语句
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $table  表名
     * @param  object  $Conder  删除条件对象
     * @return string  解析后的delete SQL语句
     */
    public function buildDelete($table, $Conder)
    {
        $this->init();
        $this->sql = "DELETE FROM `{$table}`";
        $this->sql = $this->_buildWhere($this->sql, $Conder->getWhere());
        return $this->sql;
    }

    /**
     * 统计总数
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $Conder  查询条件对象
     * @return string  解析后的count SQL语句
     */
    public function buildCount($Conder){
        return $this->_buildFunc($Conder, 'count');
    }

    /**
     * 最小值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $Conder  查询条件对象
     * @return string  解析后的Min SQL语句
     * @return string
     */
    public function buildMin($Conder){
        return $this->_buildFunc($Conder, 'MIN');
    }

    /**
     * 最大值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $Conder  查询条件对象
     * @return string  解析后的Max SQL语句
     * @return string
     */
    public function buildMax($Conder){
        return $this->_buildFunc($Conder, 'MAX');
    }

    /**
     * 总数
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $Conder  查询条件对象
     * @return string  解析后的Sum SQL语句
     * @return string
     */
    public function buildSum($Conder){
        return $this->_buildFunc($Conder, 'SUM');
    }

    /**
     * 平均值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $Conder  查询条件对象
     * @return string  解析后的Avg SQL语句
     * @return string
     */
    public function buildAvg($Conder){
        return $this->_buildFunc($Conder, 'AVG');
    }

	/**
     * 单独解析where条件(从Where数组解析成where字符串)
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $where  SQL中的where条件
     * @return string 解析后的where字符串语句
     */
    public function buildWhere($Conder){
	    $where = $Conder->getWhere();
	    if(is_string($where) || empty($where)){
			return '';
		}

        $return = $this->_applyCondition($where);
		if(!empty($return)){
			$where = $return['where'];
			return trim($where, ' AND OR');
		}
		return '';
    }

	/**
     * 字符串绑定替换
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $string  由Build解析后的字符串
     * @return string 替换后的字符串语句
     */
    public function replace($string, $params=array()){
	    if(empty($string) || empty($params)){
		    return $string;
	    }
	    foreach ( $params as $field => $v ){
			$type = gettype( $v );
			if((string)$v == $v){
				$type = 'string';
			}elseif((int)$v == $v){
				$type = 'double';
			}elseif((boolean)$v == $v){
				$type = 'boolean';
			}
			switch($type){
				case 'string':	$v = addslashes($v); break;
				case 'double':	$v = str_replace( ',', '.', $v ); break;
				case 'boolean':	$v = $v ? true : false; break;
				case null:		$v = 'NULL'; break;
				default:		$v = $v; break;
			}
			$string = str_replace($field, $v, $string);
		}
		return $string;
    }

	/**
     * 替换字段检测替换
     * @access private 
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $column_name   字段名称
     * @param  string  $type   字段类型
     * @return string
     */
    public function replaceColumnCheck($column_name, $type){
	    $db_config = C('db');
	    if( $db_config['field_type_check'] ){
			if($type == 'integer') {
                $column_name = (int)$column_name;
            }elseif( $type == 'string' ){
                $column_name = (string)$column_name;
            }elseif( $type == 'float'){
                $column_name = (float)$column_name;
            }elseif( $type == 'double' ){
                $column_name = (double)$column_name;
            }elseif( $type == 'boolean' ){
                $column_name = (boolean)$column_name;
            }elseif( $type == 'time' ){
                $column_name = $column_name;
            }else{
            	$column_name = $column_name;
            }
		}
		return $column_name;
    }

    /**
     * 解析SQL搜索字段 
     * @access private 
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array    $field   搜索字段,为切分开来的数组
     * @return string
     */
    private function _buildField($fields){
        $select = '';
		if(!is_array($fields)){
			$fields = (array)$fields;
		}
		//处理传一个参数的情况，如：field('`id`, `title`, `content`')
		if(count($fields) == 1){
			$fields[0] = str_replace('`', '', $fields[0]);
			$fields = explode(',', $fields[0]);
		}
        foreach($fields as $item){
            $item = trim($item, '` ');
            if(strpos($item, '(') || strpos($item, ')') || strpos($item, ' ')){
                $select .= $item . ',';
			}else{
				$select .= '`' . $item.'`,';
			}
        }
        $select = trim($select, ', ');
        return $select;
    }

    /**
     * 执行统计函数
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $Conder  查询条件对象
     * @param  string  $method  执行方法
     * @return string  SQL语句
     */
    private function _buildFunc($Conder, $method)
    {
	    $this->init();
        $field = $Conder->getField();
        if(is_array($Conder->getField())){
            $field = $this->_buildField($Conder->getField());
		}elseif(is_string($field) && '*' != $field){
            $field = preg_replace('(\s+)', ' ', $field);
            $field = $this->_buildField(explode(',', $field));
        }
        $this->sql = 'SELECT';
        $this->sql .= ' ' . $method . '(' . $field . ') FROM `' . $Conder->getFrom() . '`';
        $this->sql = $this->_buildWhere($this->sql, $Conder->getWhere());
        return $this->sql;
    }

    /**
     * 自定义表达式
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql
     * @param  array   $condition
     * @return string
     */
    private function applyExpress($sql, $condition)
    {
        //$this->parseExpress($condition['express']);
        return $sql.$condition['express'];
    }

    /**
     * 解析where条件
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql  待组装的SQL语句
     * @param  array   $where  SQL中的where条件
     * @return string 解析后的分组查询
     */
    private function _buildWhere($sql, $where)
    {
        if(is_string($where) || empty($where)){
			return $sql;
		}

        $return = $this->_applyCondition($where);
		if(!empty($return)){
			$where = $return['where'];
			//$sql .= ' WHERE ' . trim($where, ' AND OR ( )');
			$sql .= ' WHERE ' . trim($where, ' AND OR');
		}
		return $sql;
    }

    /**
     * 处理where条件(具体解析)
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql
     * @param  array   $condition
     * @return string
     */
    private function _applyCondition($condition, $sub_field='')
    {
        $logic = '=';
        $join = ' AND ';
        $last_join = ' AND ';
        $where = '';
		$temp = $condition;
		$is_group = false;


		//如果最后一个参数是SQL连接符'OR'和'AND'是,刚取出做为下一个条件的连接符
		$key = array_pop(array_keys($condition));
		$user_join = array_pop($condition);
		$is_join = $this->_isJoin($user_join);
		if($is_join === true){
			$last_join = $user_join;
		}else{
			//array_push($condition, $user_join);
			$condition[$key] = $user_join;
		}
		//处理条件
		foreach($condition as $field => $cond){
			$bkey = $this->_getBindKey($field);	
			//处理字段名称
			if(is_int($field)){
				/*
				//如果key是标题,不是字段名称,则表示此条件可能是带括号的子条件,或者是相同字段的多条件组合,
				//例如(多字段):'category' => array(
				                         array('first', '=', 'OR'),
							             array('recommend', '=', 'OR'),
						                 array('20', '='),
				                         'AND'
									 ),  
				//子条件:
						array(
							array('name' => 'jackchen'),
							array('title' => array('新浪乐居', '='),),
							'OR'
						), 
				 */
				$field = $sub_field;
				$is_group = true;
			}
			//$this->_checkKey();
			if(!is_array($cond)){
				//处理简单条件查询,如: array('id' => 10
				$logic = '=';
				$where .= sprintf("`%s`%s%s%s", $field, $logic, $bkey, $join);
				$this->_param[$bkey] = $cond;
			}else{
				//处理逻辑关系
				$logic = $this->_parseLogic($cond);
				switch($logic){
					case '=':
					case '>':
					case '<':
					case '<=':
					case '>=':
					case '!=':
					case '<>':
					case '<=>':
						$return = $this->_applyConditionBase($cond, $field);
						$where .= $return['where'];
						$join = $return['join'];
						$where .= ' ' . $join . ' ';
						break;

					case 'BETWEEN':
						$is_group = true;
						$return = $this->_applyConditionBetween($cond, $field);
						$where .= $return['where'];
						$join = $return['join'];
						$where .= ' ' . $join . ' ';
						break;

					case 'IN':
					case 'NOT IN':
						$return = $this->_applyConditionIn($cond, $field, $logic);
						$where .= $return['where'];
						$join = $return['join'];
						$where .= ' ' . $join . ' ';
						break;

					case 'LIKE':
					case 'NOT LIKE':
						$return = $this->_applyConditionLike($cond, $field, $logic);
						$where .= $return['where'];
						$join = $return['join'];
						$where .= ' ' . $join . ' ';
						break;

					default:
						$is_group = true;
						$return = $this->_applyCondition($cond, $field);
						$where .= $return['where'];
						$join = $return['join'];
						$where .= ' ' . $join . ' ';
				}
			}
        }

		if($where){
			$where = trim($where, ' AND OR');
			if($is_group){
				$where = '(' . $where . ')';
			}
		}
		$return = array(
			'where' => $where,
			'join'  => $last_join,
		);
        return $return;
    }
	
    /**
     * 处理条件数组中的类型,判断是哪些逻辑操作,例如:=,<,>,IN,BETWEEN等
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $cond  条件数组
     * @return string 大小的逻辑字符
     */
	private function _parseLogic($cond){
		array_shift($cond);
		$logic = array_shift($cond);
		if(!is_array($logic)){
			$logic = strtoupper(trim($logic));
		}else{
			$logic = 'Array';
		}
		return $logic;
	}


    /**
     * 处理where条件中的基本语句,例如:array('name' => array('leju', '<>', 'OR'))
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionBase($condition, $field)
    {
		$where= '';
		$bkey = $this->_getBindKey($field);	
		if(!isset($condition[0]) || is_array($condition[0]) || count($condition[0]) < 1){
			$where = '';
		}else{
			$logic = trim($condition[1]) ? trim($condition[1]) : '=';
			$where = sprintf("`%s`%s%s", $field, $logic, $bkey);
			$this->_param[$bkey] = $condition[0];
		}
		if($where){
			$where = trim($where, ' AND OR');
		}
		$return = array(
			'where' => $where,
			'join'  => isset($condition[2]) ? $condition[2] : ' AND ',
		);
        return $return;
    }

    /**
     * 处理where条件中的BETWEEN语句,例如:array('name' => array(array(100, 200), 'BETWEEN', 'OR'))
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionBetween($condition, $field)
    {
		$where= '';
		$bkey = $this->_getBindKey($field);	
		if(!isset($condition[0]) || !is_array($condition[0]) || count($condition) < 2){
			$where = '';
		}else{
			$bkey_start = $bkey . '_1';
			$bkey_end = $bkey . '_2';
			$where = sprintf("`%s` BETWEEN %s AND %s", $field, trim($bkey_start), $bkey_end);
			$this->_param[$bkey_start] = $condition[0][0];
			$this->_param[$bkey_end] = $condition[0][1];
		}
		if($where){
			$where = trim($where, ' AND OR');
			$where = '(' . $where . ')';
		}
		$return = array(
			'where' => $where,
			'join'  => isset($condition[2]) ? $condition[2] : ' AND ',
		);
        return $return;
    }

    /**
     * 处理where条件中的IN语句
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionIn($condition, $field, $logic='IN')
    {
		$where= '';
		$bkey = $this->_getBindKey($field);	
		if(!isset($condition[0]) || !is_array($condition[0]) || count($condition[0]) < 1){
			$where = sprintf("`%s` %s(%s)", $field, $logic, implode(',', ''));
		}else{
			$where = sprintf("`%s` %s(%s)", $field, $logic, implode(',', $condition[0]));
		}
		$return = array(
			'where' => $where,
			'join'  => isset($condition[2]) ? $condition[2] : ' AND ',
		);
        return $return;
    }

    /**
     * 处理where条件中的LIKE语句
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionLike($condition, $field, $logic='LIKE')
    {
		$where= '';
		$bkey = $this->_getBindKey($field);	
		if(!isset($condition[0]) || is_array($condition[0]) || count($condition) < 2){
			$where = '';
		}else{
			$where = sprintf("`%s` %s '%s'", $field, $logic, trim($condition[0]));
		}
		$return = array(
			'where' => $where,
			'join'  => isset($condition[2]) ? $condition[2] : ' AND ',
		);
        return $return;
    }
    
    /**
     * 解析分组查询
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string          $sql  待组装的SQL语句
     * @param  string | array  $group   GROUP信息
     * @return string 解析后的分组查询
     */
    private function _buildGroup($sql, $group)
	{
		if(is_array($group)){
			$group  = implode(',', $group);
		}
		$group = trim($group);
        if($group){
            $group = preg_replace('([\s+`])', '', $group);
            $group = explode(',', $group);
        }

		if($group){
			$sql .= ' GROUP BY `' . implode('`,`', $group) . '`';
		}
        return $sql;
    }

    /**
     * 解析SQL排序
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql  待组装SQL语句
     * @param  string  $orderby  待排序 
     * @return string  组装后的SQL
     */
    private function _buildOrder($sql, $orderby)
    {
		if(is_array($orderby)){
			$orderby  = implode(',', $orderby);
		}
		$orderby = trim($orderby);
        $orderby = preg_replace('(\s+)', ' ', $orderby);
        $list    = explode(',', $orderby);
        $orderby = '';
		if($list){
			foreach($list as $item){
				$item = trim($item);
				if(!$item){
					continue;
				}
				$tmp = explode(' ', $item);
				if( count($tmp) == 2 ){
					$orderby .= '`' . trim($tmp[0] , '` ') . '` ' . strtoupper($tmp[1]) . ',';
				}else{
					$orderby .= '`'.trim($tmp[0], '` ').'`,';
				}
			}
		}
        $orderby = trim($orderby, ', ');
		if(!empty($orderby)){
			$sql .= ' ORDER BY '.$orderby;
		}
        return $sql;
    }

    /**
     * 解析SQL分页
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql  待组装SQL语句
     * @param  int     $limit   获取记录的条数
     * @param  int     $offset  获取记录的偏移
     * @return string  组装后的SQL
     */
    private function _buildLimit($sql, $limit=1, $offset=0)
	{
		$limit = (int)$limit;
		$offset = (int)$offset;
        if($limit > 0){
			$sql .= ' LIMIT '.(int)$limit;
		}
        if($offset > 0){
			$sql .= ' OFFSET '.(int)$offset;
		}
        return $sql;
    }

    /**
     * 检查是否是SQL语句的OR和AND连接符
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $join   待判定的连接符
     * @return bool   是否为合格的SQL连接符
     */
	private function _isJoin($join){
		$joins = array('OR', 'AND');
		$join = strtoupper($join);
		if(in_array($join, $joins)){
			return true;
		}else{
			return false;
		}
	}

    /**
     * 判断是否为SQL的逻辑操作符,例如:=,<,>,IN,BETWEEN等
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string   $logic  待判断的SQL逻辑操作符
     * @return bool    是否为逻辑操作符
     */
	private function _isLogic($logic){
		$logics = array('=', '>', '<', '>=', '<=', '<>', '!=', '<=>', 'IN', 'BETWEEN', 'LIKE');
		$logic = strtoupper($logic);
		if(in_array($logic, $logics)){
			return true;
		}else{
			return false;
		}
	}

    /**
     * 初始化成员变量
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return void
     */
    private function init()
    {
        $this->sql = '';
        $this->_param = array();
    }

    /**
     * 生成唯一绑定值字段名
     * @access private
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $key   字段Key名
     * @return string  $bkey  生成唯一的绑定字段名
     */
    private function _getBindKey($key)
    {
        $bkey = self::WPREFIX . $key;
        if(!isset($this->_param[$bkey . '_0'])){
			$bkey .= '_0';
		}else{
			$suffix = 1;
			while(true){
				$temp = $bkey . '_' . $suffix;
				if(!isset($this->_param[$temp])){
					$bkey = $temp;
					break;
				}
				$suffix++;
			}
		}
        return $bkey;
    }

	/*********************************************************************************************/
    /**
     * 表达式解析
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $express
     * @return array   $queries
     */
    private function parseExpress($express)
    {
        $tokens = $this->splitToken($express);
        $queries = $this->processUnion($tokens);
        !$this->isUnion($queries) && $queries = $this->processSQL($queries[0]);
        return $queries;
    }

    /**
     * 表达式元素拆解
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql
     * @return array   $tokens
     */
    private function splitToken($sql)
    {
        $regex = <<<EOREGEX
/(`(?:[^`]|``)`|[@A-Za-z0-9_.`-]+)
|(\+|-|\*|\/|!=|>=|<=|<>|>|<|&&|\|\||=|\^|\(|\)|\\t|\\r\\n|\\n)
|('(?:[^']+|'')*'+)
|("(?:[^"]+|"")*"+)
|([^ ,]+)
/ix
EOREGEX
        ;

        $tokens = preg_split($regex, $sql, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $tokens = $this->balanceParenthesis($tokens);
        $tokens = $this->balanceBackticks($tokens, "`");
        $tokens = $this->balanceBackticks($tokens, "'");
        $tokens = $this->concatQuotedColReferences($tokens);
        return $tokens;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $inputArray
     * @return array  $queries
     */
    private function processUnion($inputArray)
    {
        $outputArray = array();
        $skipUntilToken = false;
        $unionType = false;
        $queries = array();
        foreach($inputArray as $key => $token)
        {
            $trim = trim($token);
            if($skipUntilToken)
            {
                if($trim === "")
                    continue;
                if(strtoupper($trim) === $skipUntilToken && !$skipUntilToken = false)
                    continue;
            }

            if(strtoupper($trim) !== "UNION")
            {
                $outputArray[] = $token;
                continue;
            }

            $unionType = "UNION";
            for($i = $key + 1; $i < count($inputArray); ++$i)
            {
                if(trim($inputArray[$i]) === "")
                    continue;
                if(strtoupper($inputArray[$i]) !== "ALL")
                    break;
                $skipUntilToken = "ALL";
                $unionType = "UNION ALL";
            }

            $queries[$unionType][] = $outputArray;
            $outputArray = array();
        }

        if(!empty($outputArray))
        {
            if($unionType)
                $queries[$unionType][] = $outputArray;
             else
                $queries[] = $outputArray;
        }

        return $this->processMySQLUnion($queries);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $queries
     * @return array  $queries
     */
    private function processMySQLUnion($queries)
    {
        $unionTypes = array('UNION', 'UNION ALL');
        foreach($unionTypes as $unionType)
        {
            if(empty($queries[$unionType]))
                continue;

            foreach($queries[$unionType] as $key => $tokenList)
            {
                foreach($tokenList as $z => $token)
                {
                    $token = trim($token);
                    if($token === "")
                        continue;

                    if(preg_match("/^\\(\\s*select\\s*/i", $token))
                    {
                        $queries[$unionType][$key] = $this->parseExpress($this->removeParenthesisFromStart($token));
                        break;
                    }

                    $queries[$unionType][$key] = $this->processSQL($queries[$unionType][$key]);
                    break;
                }
            }
        }
        return $queries;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $queries
     * @return bool
     */
    private function isUnion($queries)
    {
        $unionTypes = array('UNION', 'UNION ALL');
        foreach($unionTypes as $unionType)
        {
            if(!empty($queries[$unionType]))
                return true;
        }
        return false;
    }

    /**
     * 统计指定标签开始结束位置
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $token
     * @param  string  $char
     * @return int     $cnt
     */
    private function count_backtick($token, $char)
    {
        $len = strlen($token);
        $cnt = 0;
        $escaped = false;

        for($i = 0; $i < $len; ++$i)
        {
            if($token[$i] === "\\" && $escaped = true)
                continue;
            if(!$escaped && $token[$i] == $char)
                ++$cnt;
            $escaped = false;
        }
        return $cnt;
    }

    /**
     * 去除指定符号并重组元素
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $tokens
     * @param  string  $char
     * @return array   $tokens
     */
    private function balanceBackticks($tokens, $char)
    {
        $token_count = count($tokens);
        $i = 0;
        while($i < $token_count)
        {
            if($tokens[$i] === "")
            {
                $i++;
                continue;
            }

            $needed = $this->count_backtick($tokens[$i], $char) % 2;
            if($needed === 0)
            {
                $i++;
                continue;
            }

            for($n = $i + 1; $n < $token_count; $n++)
            {
                $needed = ($needed + $this->count_backtick($tokens[$n], $char)) % 2;
                $tokens[$i] .= $tokens[$n];
                unset($tokens[$n]);
                if($needed === 0)
                {
                    $n++;
                    break;
                }
            }
            $i = $n;
        }
        return array_values($tokens);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function concatQuotedColReferences($tokens)
    {
        $i = 0;
        $tokenCount = count($tokens);
        while($i < $tokenCount)
        {
            $trim = trim($tokens[$i]);
            if($trim === "")
            {
                $i++;
                continue;
            }

            if($trim[strlen($trim) - 1] !== "`")
            {
                $i++;
                continue;
            }

            $i++;
            if(!isset($tokens[$i]))
                continue;

            $trim = trim($tokens[$i]);
            if($trim === "")
            {
                $i++;
                continue;
            }

            if($trim[0] === ".")
            {
                $tokens[$i - 1] .= $tokens[$i];
                unset($tokens[$i]);
                $i++;
            }
        }

        return array_values($tokens);
    }

    /**
     * 去除指定符号并重组元素
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function balanceParenthesis($tokens)
    {
        $token_count = count($tokens);
        $i = 0;
        while($i < $token_count)
        {
            if($tokens[$i] !== '(')
            {
                $i++;
                continue;
            }
            $count = 1;
            for($n = $i + 1; $n < $token_count; $n++)
            {
                $token = $tokens[$n];
                $token === '(' && $count++;
                $token === ')' && $count--;
                $tokens[$i] .= $token;
                unset($tokens[$n]);
                if($count === 0)
                {
                    $n++;
                    break;
                }
            }
            $i = $n;
        }
        return array_values($tokens);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function processSQL(&$tokens)
    {
        $prev_category = "";
        $token_category = "";
        $skip_next = false;
        $out = false;

        $tokenCount = count($tokens);
        for($tokenNumber = 0; $tokenNumber < $tokenCount; ++$tokenNumber)
        {
            $token = $tokens[$tokenNumber];
            $trim = trim($token);
            if($trim !== "" && $trim[0] == "(" && $token_category == "")
                $token_category = 'SELECT';

            if($skip_next)
            {
                if($trim === "")
                {
                    if($token_category !== "")
                        $out[$token_category][] = $token;
                    continue;
                }
                $trim = "";
                $token = "";
                $skip_next = false;
            }

            $upper = strtoupper($trim);
            switch($upper)
            {
            case 'SELECT':
            case 'ORDER':
            case 'LIMIT':
            case 'SET':
            case 'DUPLICATE':
            case 'VALUES':
            case 'GROUP':
            case 'ORDER':
            case 'HAVING':
            case 'WHERE':
            case 'RENAME':
            case 'CALL':
            case 'PROCEDURE':
            case 'FUNCTION':
            case 'DATABASE':
            case 'SERVER':
            case 'LOGFILE':
            case 'DEFINER':
            case 'RETURNS':
            case 'EVENT':
            case 'TABLESPACE':
            case 'TRIGGER':
            case 'DATA':
            case 'DO':
            case 'PASSWORD':
            case 'PLUGIN':
            case 'FROM':
            case 'FLUSH':
            case 'KILL':
            case 'RESET':
            case 'START':
            case 'STOP':
            case 'PURGE':
            case 'EXECUTE':
            case 'PREPARE':
            case 'DEALLOCATE':
                $trim == 'DEALLOCATE' && $skip_next = true;
                if($token_category == 'PREPARE' && $upper == 'FROM')
                    continue 2;
                $token_category = $upper;
                break;

            case 'INTO':
                if($prev_category === 'LOAD')
                {
                    $out[$prev_category][] = $upper;
                    continue 2;
                }
                $token_category = $upper;
                break;

            case 'USER':
                if(in_array($prev_category, array('CREATE', 'RENAME', 'DROP'), true))
                    $token_category = $upper;
                break;

            case 'VIEW':
                if(in_array($prev_category, array('CREATE', 'ALTER', 'DROP'), true))
                    $token_category = $upper;
                break;

            case 'DELETE':
            case 'ALTER':
            case 'INSERT':
            case 'REPLACE':
            case 'TRUNCATE':
            case 'CREATE':
            case 'TRUNCATE':
            case 'OPTIMIZE':
            case 'GRANT':
            case 'REVOKE':
            case 'SHOW':
            case 'HANDLER':
            case 'LOAD':
            case 'ROLLBACK':
            case 'SAVEPOINT':
            case 'UNLOCK':
            case 'INSTALL':
            case 'UNINSTALL':
            case 'ANALZYE':
            case 'BACKUP':
            case 'CHECK':
            case 'CHECKSUM':
            case 'REPAIR':
            case 'RESTORE':
            case 'DESCRIBE':
            case 'EXPLAIN':
            case 'USE':
            case 'HELP':
                $token_category = $upper;
                $out[$upper][0] = $upper;
                continue 2;
                break;

            case 'CACHE':
                if(($prev_category === "") || (in_array($prev_category, array('RESET', 'FLUSH', 'LOAD'))))
                {
                    $token_category = $upper;
                    continue 2;
                }
                break;

            case 'LOCK':
                if($token_category == "")
                {
                    $token_category = $upper;
                    $out[$upper][0] = $upper;
                }
                else
                {
                    $trim = 'LOCK IN SHARE MODE';
                    $skip_next = true;
                    $out['OPTIONS'][] = $trim;
                }
                continue 2;
                break;

            case 'USING':
                if($token_category == 'EXECUTE')
                {
                    $token_category = $upper;
                    continue 2;
                }
                if($token_category == 'FROM' && !empty($out['DELETE']))
                {
                    $token_category = $upper;
                    continue 2;
                }
                break;

            case 'DROP':
                if($token_category != 'ALTER')
                {
                    $token_category = $upper;
                    $out[$upper][0] = $upper;
                    continue 2;
                }
                break;

            case 'FOR':
                $skip_next = true;
                $out['OPTIONS'][] = 'FOR UPDATE';
                continue 2;
                break;

            case 'UPDATE':
                if($token_category == "")
                {
                    $token_category = $upper;
                    continue 2;

                }
                if($token_category == 'DUPLICATE')
                    continue 2;
                break;

            case 'START':
                $trim = "BEGIN";
                $out[$upper][0] = $upper;
                $skip_next = true;
                break;

            case 'BY':
            case 'ALL':
            case 'SHARE':
            case 'MODE':
            case 'TO':
            case ';':
                continue 2;
                break;

            case 'KEY':
                if($token_category == 'DUPLICATE')
                    continue 2;
                break;

            case 'DISTINCTROW':
                $trim = 'DISTINCT';
            case 'DISTINCT':
            case 'HIGH_PRIORITY':
            case 'LOW_PRIORITY':
            case 'DELAYED':
            case 'IGNORE':
            case 'FORCE':
            case 'STRAIGHT_JOIN':
            case 'SQL_SMALL_RESULT':
            case 'SQL_BIG_RESULT':
            case 'QUICK':
            case 'SQL_BUFFER_RESULT':
            case 'SQL_CACHE':
            case 'SQL_NO_CACHE':
            case 'SQL_CALC_FOUND_ROWS':
                $out['OPTIONS'][] = $upper;
                continue 2;
                break;

            case 'WITH':
                if($token_category == 'GROUP')
                {
                    $skip_next = true;
                    $out['OPTIONS'][] = 'WITH ROLLUP';
                    continue 2;
                }
                break;

            case 'AS':
                break;

            case '':
            case ',':
            case ';':
                break;

            default:
                break;
            }

            if($token_category !== "" && ($prev_category === $token_category))
                $out[$token_category][] = $token;
            $prev_category = $token_category;
        }

        return $this->processSQLParts($out);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $out
     * @return array  $out
     */
    private function processSQLParts($out)
    {
        if (!$out)
            return false;
        !empty($out['SELECT']) && $out['SELECT'] = $this->process_select($out['SELECT']);
        !empty($out['FROM']) && $out['FROM'] = $this->process_from($out['FROM']);
        !empty($out['USING']) && $out['USING'] = $this->process_from($out['USING']);
        !empty($out['UPDATE']) && $out['UPDATE'] = $this->process_from($out['UPDATE']);
        !empty($out['GROUP']) && $out['GROUP'] = $this->process_group($out['GROUP'], $out['SELECT']);
        !empty($out['ORDER']) && $out['ORDER'] = $this->process_order($out['ORDER'], $out['SELECT']);
        !empty($out['LIMIT']) && $out['LIMIT'] = $this->process_limit($out['LIMIT']);
        !empty($out['WHERE']) && $out['WHERE'] = $this->process_expr_list($out['WHERE']);
        !empty($out['HAVING']) && $out['HAVING'] = $this->process_expr_list($out['HAVING']);
        !empty($out['SET']) && $out['SET'] = $this->process_set_list($out['SET']);
        if(!empty($out['DUPLICATE']))
        {
            $out['ON DUPLICATE KEY UPDATE'] = $this->process_set_list($out['DUPLICATE']);
            unset($out['DUPLICATE']);
        }

        !empty($out['INSERT']) && $out = $this->process_insert($out);
        !empty($out['REPLACE']) && $out = $this->process_insert($out, 'REPLACE');
        !empty($out['DELETE']) && $out = $this->process_delete($out);
        !empty($out['VALUES']) && $out = $this->process_values($out);
        !empty($out['INTO']) && $out = $this->process_into($out);
        return $out;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $base_expr
     * @return array
     */
    private function getColumn($base_expr)
    {
        $column = $this->process_expr_list($this->splitToken($base_expr));
        return array('expr_type' => 'expression', 'base_expr' => trim($base_expr), 'sub_tree' => $column);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function process_set_list($tokens)
    {
        $expr = array();
        $base_expr = "";

        foreach($tokens as $token)
        {
            $trim = trim($token);

            if($trim === ",")
            {
                $expr[] = $this->getColumn($base_expr);
                $base_expr = "";
                continue;
            }

            $base_expr .= $token;
        }

        if(trim($base_expr) !== "")
            $expr[] = $this->getColumn($base_expr);
        return $expr;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function process_limit($tokens)
    {
        $start = "";
        $end = "";
        $comma = -1;

        for($i = 0; $i < count($tokens); ++$i)
        {
            if(trim($tokens[$i]) === ",")
            {
                $comma = $i;
                break;
            }
        }

        for($i = 0; $i < $comma; ++$i)
        {
            $start .= $tokens[$i];
        }

        for($i = $comma + 1; $i < count($tokens); ++$i)
        {
            $end .= $tokens[$i];
        }

        return array('start' => trim($start), 'end' => trim($end));
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function process_select(&$tokens)
    {
        $expression = "";
        $expr = array();
        foreach($tokens as $token)
        {
            if(trim($token) === ',')
            {
                $expr[] = $this->process_select_expr(trim($expression));
                $expression = "";
            }
            else
                $expression .= $token;
        }
        if($expression)
            $expr[] = $this->process_select_expr(trim($expression));
        return $expr;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql
     * @return string
     */
    private function revokeEscaping($sql)
    {
        $sql = trim($sql);
        if(($sql[0] === '`') && ($sql[strlen($sql) - 1] === '`'))
            $sql = substr($sql, 1, -1);
        return str_replace('``', '`', $sql);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $expression
     * @return array
     */
    private function process_select_expr($expression)
    {
        $tokens = $this->splitToken($expression);
        $token_count = count($tokens);

        $base_expr = "";
        $stripped = array();
        $capture = false;
        $alias = false;
        $processed = false;
        for($i = 0; $i < $token_count; ++$i)
        {
            $token = strtoupper($tokens[$i]);
            if(trim($token) !== "")
                $stripped[] = $tokens[$i];

            if($token == 'AS')
            {
                $alias = array('as' => true, "name" => "", "base_expr" => $tokens[$i]);
                $tokens[$i] = "";
                array_pop($stripped); // remove it from the expression
                $capture = true;
                continue;
            }

            if($capture)
            {
                if(trim($token) !== "")
                {
                    $alias['name'] .= $tokens[$i];
                    array_pop($stripped);
                }
                $alias['base_expr'] .= $tokens[$i];
                $tokens[$i] = "";
                continue;
            }
            $base_expr .= $tokens[$i];
        }

        $stripped = $this->process_expr_list($stripped);

        $last = array_pop($stripped);
        if(!$alias && $last['expr_type'] == 'colref')
        {
            $prev = array_pop($stripped);
            if(isset($prev)
                    && ($prev['expr_type'] == 'reserved' || $prev['expr_type'] == 'const'
                            || $prev['expr_type'] == 'function' || $prev['expr_type'] == 'expression'
                            || $prev['expr_type'] == 'subquery' || $prev['expr_type'] == 'colref'))
            {

                $alias = array(
                    'as' => false,
                    'name' => trim($last['base_expr']),
                    'base_expr' => trim($last['base_expr'])
                );
                array_pop($tokens);
                $base_expr = join("", $tokens);
            }
        }

        if(!$alias)
            $base_expr = join("", $tokens);
        else
        {
            $alias['name'] = $this->revokeEscaping(trim($alias['name']));
            $alias['base_expr'] = trim($alias['base_expr']);
        }

        $processed = $this->process_expr_list($tokens);
        $type = 'expression';
        if(count($processed) == 1)
        {
            if($processed[0]['expr_type'] != 'subquery')
            {
                $type = $processed[0]['expr_type'];
                $base_expr = $processed[0]['base_expr'];
                $processed = $processed[0]['sub_tree'];
            }
        }

        return array(
            'expr_type' => $type,
            'alias' => $alias,
            'base_expr' => trim($base_expr),
            'sub_tree' => $processed
        );
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array  $expr
     */
    private function process_from(&$tokens)
    {
        $parseInfo = $this->initParseInfoForFrom();
        $expr = array();
        $skip_next = false;
        $i = 0;
        foreach ($tokens as $token)
        {
            $upper = strtoupper(trim($token));
            if($skip_next && $token !== "")
            {
                $parseInfo['token_count']++;
                $skip_next = false;
                continue;
            }
            elseif($skip_next)
                continue;

            switch($upper)
            {
            case 'OUTER':
            case 'LEFT':
            case 'RIGHT':
            case 'NATURAL':
            case 'CROSS':
            case ',':
            case 'JOIN':
            case 'INNER':
                break;

            default:
                $parseInfo['expression'] .= $token;
                $parseInfo['ref_type'] !== false && $parseInfo['ref_expr'] .= $token;
                break;
            }

            switch($upper)
            {
            case 'AS':
                $parseInfo['alias'] = array('as' => true, 'name' => "", 'base_expr' => $token);
                $parseInfo['token_count']++;
                $n = 1;
                $str = "";
                while($str == "")
                {
                    $parseInfo['alias']['base_expr'] .= ($tokens[$i + $n] === "" ? " " : $tokens[$i + $n]);
                    $str = trim($tokens[$i + $n]);
                    ++$n;
                }
                $parseInfo['alias']['name'] = $str;
                $parseInfo['alias']['base_expr'] = trim($parseInfo['alias']['base_expr']);
                continue;

            case 'INDEX':
                if($token_category == 'CREATE')
                {
                    $token_category = $upper;
                    continue 2;
                }
                break;

            case 'USING':
            case 'ON':
                $parseInfo['ref_type'] = $upper;
                $parseInfo['ref_expr'] = "";

            case 'CROSS':
            case 'USE':
            case 'FORCE':
            case 'IGNORE':
            case 'INNER':
            case 'OUTER':
                $parseInfo['token_count']++;
                continue;
                break;

            case 'FOR':
                $parseInfo['token_count']++;
                $skip_next = true;
                continue;
                break;

            case 'LEFT':
            case 'RIGHT':
            case 'STRAIGHT_JOIN':
                $parseInfo['next_join_type'] = $upper;
                break;

            case ',':
                $parseInfo['next_join_type'] = 'CROSS';

            case 'JOIN':
                if($parseInfo['subquery'])
                {
                    $parseInfo['sub_tree'] = $this->parse($this->removeParenthesisFromStart($parseInfo['subquery']));
                    $parseInfo['expression'] = $parseInfo['subquery'];
                }

                $expr[] = $this->processFromExpression($parseInfo);
                $parseInfo = $this->initParseInfoForFrom($parseInfo);
                break;

            default:
                if($upper === "")
                    continue;

                if($parseInfo['token_count'] === 0)
                {
                    $parseInfo['table'] === "" && $parseInfo['table'] = $token;
                }
                elseif($parseInfo['token_count'] === 1)
                    $parseInfo['alias'] = array('as' => false, 'name' => trim($token), 'base_expr' => trim($token));
                $parseInfo['token_count']++;
                break;
            }
            ++$i;
        }

        $expr[] = $this->processFromExpression($parseInfo);
        return $expr;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  bool  $parseInfo
     * @return array
     */
    private function initParseInfoForFrom($parseInfo = false)
    {
        $parseInfo === false && $parseInfo = array('join_type' => "", 'saved_join_type' => "JOIN");
        return array(
            'expression' => "",
            'token_count' => 0,
            'table' => "",
            'alias' => false,
            'join_type' => "",
            'next_join_type' => "",
            'saved_join_type' => $parseInfo['saved_join_type'],
            'ref_type' => false,
            'ref_expr' => false,
            'base_expr' => false,
            'sub_tree' => false,
            'subquery' => ""
        );
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $parseInfo
     * @return array
     */
    private function processFromExpression(&$parseInfo)
    {
        $res = array();
        $parseInfo['join_type'] = $parseInfo['saved_join_type'];
        $parseInfo['saved_join_type'] = ($parseInfo['next_join_type'] ? $parseInfo['next_join_type'] : 'JOIN');

        if($parseInfo['ref_expr'] !== false)
        {
            $unparsed = $this->splitToken($this->removeParenthesisFromStart($parseInfo['ref_expr']));
            foreach($unparsed as $k => $v)
            {
                trim($v) === ',' && $unparsed[$k] = "";
            }
            $parseInfo['ref_expr'] = $this->process_expr_list($unparsed);
        }

        if(substr(trim($parseInfo['table']), 0, 1) == '(')
        {
            $parseInfo['expression'] = $this->removeParenthesisFromStart($parseInfo['table']);
            if(preg_match("/^\\s*select/i", $parseInfo['expression']))
            {
                $parseInfo['sub_tree'] = $this->parse($parseInfo['expression']);
                $res['expr_type'] = 'subquery';
            }
            else
            {
                $tmp = $this->splitToken($parseInfo['expression']);
                $parseInfo['sub_tree'] = $this->process_from($tmp);
                $res['expr_type'] = 'table_expression';
            }
        }
        else
        {
            $res['expr_type'] = 'table';
            $res['table'] = $parseInfo['table'];
        }

        $res['alias'] = $parseInfo['alias'];
        $res['join_type'] = $parseInfo['join_type'];
        $res['ref_type'] = $parseInfo['ref_type'];
        $res['ref_clause'] = $parseInfo['ref_expr'];
        $res['base_expr'] = trim($parseInfo['expression']);
        $res['sub_tree'] = $parseInfo['sub_tree'];
        return $res;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $parseInfo
     * @param  array  $select
     * @return array
     */
    private function processOrderExpression(&$parseInfo, $select)
    {
        $parseInfo['expr'] = trim($parseInfo['expr']);
        if($parseInfo['expr'] === "")
            return false;
        $parseInfo['expr'] = trim($this->revokeEscaping($parseInfo['expr']));
        if(is_numeric($parseInfo['expr']))
        {
            $parseInfo['type'] = 'pos';
        }
        else
        {
            foreach($select as $clause)
            {
                if(!$clause['alias'])
                    continue;
                $clause['alias']['name'] === $parseInfo['expr'] && $parseInfo['type'] = 'alias';
            }

            !$parseInfo['type'] && $parseInfo['type'] = "expression";
        }

        return array(
            'type' => $parseInfo['type'],
            'base_expr' => $parseInfo['expr'],
            'direction' => $parseInfo['dir']
        );
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return array
     */
    private function initParseInfoForOrder()
    {
        return array('expr' => "", 'dir' => "ASC", 'type' => 'expression');
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @param  array  $select
     * @return array
     */
    private function process_order($tokens, $select)
    {
        $out = array();
        $parseInfo = $this->initParseInfoForOrder();

        if(!$tokens)
            return false;

        foreach($tokens as $token)
        {
            $upper = strtoupper(trim($token));
            switch($upper)
            {
            case ',':
                $out[] = $this->processOrderExpression($parseInfo, $select);
                $parseInfo = $this->initParseInfoForOrder();
                break;

            case 'DESC':
                $parseInfo['dir'] = "DESC";
                break;

            case 'ASC':
                $parseInfo['dir'] = "ASC";
                break;

            default:
                $parseInfo['expr'] .= $token;
            }
        }

        $out[] = $this->processOrderExpression($parseInfo, $select);
        return $out;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @param  array  $select
     * @return array
     */
    private function process_group(&$tokens, &$select)
    {
        $out = array();
        $parseInfo = $this->initParseInfoForOrder();

        if(!$tokens)
            return false;

        foreach($tokens as $token)
        {
            $trim = strtoupper(trim($token));
            switch ($trim)
            {
            case ',':
                $parsed = $this->processOrderExpression($parseInfo, $select);
                unset($parsed['direction']);

                $out[] = $parsed;
                $parseInfo = $this->initParseInfoForOrder();
                break;
            default:
                $parseInfo['expr'] .= $token;
            }
        }

        $parsed = $this->processOrderExpression($parseInfo, $select);
        unset($parsed['direction']);
        $out[] = $parsed;

        return $out;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $token
     * @return string
     */
    private function removeParenthesisFromStart($token)
    {
        $parenthesisRemoved = 0;
        $trim = trim($token);
        if($trim !== "" && $trim[0] === "(")
        {
            $parenthesisRemoved++;
            $trim[0] = " ";
            $trim = trim($trim);
        }

        $parenthesis = $parenthesisRemoved;
        $i = 0;
        $string = 0;
        while($i < strlen($trim))
        {
            if($trim[$i] === "\\")
            {
                $i += 2;
                continue;
            }

            in_array($trim[$i], array("'", '"')) && $string++;
            ($string % 2 === 0) && ($trim[$i] === "(") && $parenthesis++;
            if(($string % 2 === 0) && ($trim[$i] === ")"))
            {
                if($parenthesis == $parenthesisRemoved)
                {
                    $trim[$i] = " ";
                    $parenthesisRemoved--;
                }
                $parenthesis--;
            }
            $i++;
        }
        return trim($trim);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  bool  $parseInfo
     * @return array
     */
    private function initParseInfoExprList($parseInfo = false)
    {
        if($parseInfo === false)
        {
            return array(
                'processed' => false,
                'expr' => "",
                'key' => false,
                'token' => false,
                'tokenType' => "",
                'prevToken' => "",
                'prevTokenType' => "",
                'trim' => false,
                'upper' => false
            );
        }

        $expr = $parseInfo['expr'];
        $expr[] = array(
            'expr_type' => $parseInfo['tokenType'],
            'base_expr' => $parseInfo['token'],
            'sub_tree' => $parseInfo['processed']
        );

        return array(
            'processed' => false,
            'expr' => $expr,
            'key' => false,
            'token' => false,
            'tokenType' => "",
            'prevToken' => $parseInfo['upper'],
            'prevTokenType' => $parseInfo['tokenType'],
            'trim' => false,
            'upper' => false
        );
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array         $tokens
     * @return array | bool
     */
    private function process_expr_list($tokens)
    {
        $parseInfo = $this->initParseInfoExprList();
        $skip_next = false;
        foreach($tokens as $parseInfo['key'] => $parseInfo['token'])
        {
            $parseInfo['trim'] = trim($parseInfo['token']);
            if($parseInfo['trim'] === "")
                continue;

            if($skip_next && !$skip_next = false)
                continue;
            $parseInfo['upper'] = strtoupper($parseInfo['trim']);
            if(preg_match("/^\\(\\s*SELECT/i", $parseInfo['trim']))
            {
                $parseInfo['processed'] = $this->parse($this->removeParenthesisFromStart($parseInfo['trim']));
                $parseInfo['tokenType'] = 'subquery';
            }
            elseif($parseInfo['upper'][0] === '(' && substr($parseInfo['upper'], -1) === ')')
            {
                if(in_array($parseInfo['prevTokenType'], array('colref', 'function', 'aggregate_function')))
                {
                    $tmptokens = $this->splitToken($this->removeParenthesisFromStart($parseInfo['trim']));
                    foreach($tmptokens as $k => $v)
                    {
                        if(trim($v) == ',')
                            unset($tmptokens[$k]);
                    }

                    $tmptokens = array_values($tmptokens);
                    $parseInfo['processed'] = $this->process_expr_list($tmptokens);
                    $last = array_pop($parseInfo['expr']);
                    $parseInfo['token'] = $last['base_expr'];
                    $parseInfo['tokenType'] = ($parseInfo['prevTokenType'] === 'colref' ? 'function'
                            : $parseInfo['prevTokenType']);
                    $parseInfo['prevTokenType'] = $parseInfo['prevToken'] = "";
                }

                if($parseInfo['prevToken'] == 'IN')
                {
                    $tmptokens = $this->splitToken($this->removeParenthesisFromStart($parseInfo['trim']));
                    foreach($tmptokens as $k => $v)
                    {
                        if(trim($v) == ',')
                            unset($tmptokens[$k]);
                    }

                    $tmptokens = array_values($tmptokens);
                    $parseInfo['processed'] = $this->process_expr_list($tmptokens);
                    $parseInfo['prevTokenType'] = $parseInfo['prevToken'] = "";
                    $parseInfo['tokenType'] = "in-list";
                }

                if($parseInfo['prevToken'] == 'AGAINST')
                {
                    $tmptokens = $this->splitToken($this->removeParenthesisFromStart($parseInfo['trim']));
                    if(count($tmptokens) > 1)
                    {
                        $match_mode = implode('', array_slice($tmptokens, 1));
                        $parseInfo['processed'] = array($list[0], $match_mode);
                    }
                    else
                        $parseInfo['processed'] = $list[0];

                    $parseInfo['prevTokenType'] = $parseInfo['prevToken'] = "";
                    $parseInfo['tokenType'] = "match-arguments";
                }
            }
            else
            {
                switch($parseInfo['upper'])
                {
                case '*':
                    $parseInfo['processed'] = false;

                    if(!is_array($parseInfo['expr']))
                    {
                        $parseInfo['tokenType'] = "colref";
                        break;
                    }

                    $last = array_pop($parseInfo['expr']);
                    if(!in_array($last['expr_type'], array('colref', 'const', 'expression')))
                    {
                        $parseInfo['expr'][] = $last;
                        $parseInfo['tokenType'] = "colref";
                        break;
                    }

                    if($last['expr_type'] === 'colref' && substr($last['base_expr'], -1, 1) === ".")
                    {
                        $last['base_expr'] .= '*';
                        $parseInfo['expr'][] = $last;
                        continue 2;
                    }

                    $parseInfo['expr'][] = $last;
                    $parseInfo['tokenType'] = "operator";
                    break;

                case 'AND':
                case '&&':
                case 'BETWEEN':
                case 'AND':
                case 'BINARY':
                case '&':
                case '~':
                case '|':
                case '^':
                case 'DIV':
                case '/':
                case '<=>':
                case '=':
                case '>=':
                case '>':
                case 'IS':
                case 'NOT':
                case 'NULL':
                case '<<':
                case '<=':
                case '<':
                case 'LIKE':
                case '-':
                case '%':
                case '!=':
                case '<>':
                case 'REGEXP':
                case '!':
                case '||':
                case 'OR':
                case '+':
                case '>>':
                case 'RLIKE':
                case 'SOUNDS':
                case '-':
                case 'XOR':
                case 'IN':
                    $parseInfo['processed'] = false;
                    $parseInfo['tokenType'] = "operator";
                    break;

                default:
                    switch($parseInfo['token'][0])
                    {
                    case "'":
                    case '"':
                        $parseInfo['tokenType'] = 'const';
                        break;
                    case '`':
                        $parseInfo['tokenType'] = 'colref';
                        break;

                    default:
                        $parseInfo['tokenType'] = is_numeric($parseInfo['token']) ? 'const' : 'colref';
                        break;
                    }
                    $parseInfo['processed'] = false;
                }
            }

            if(!in_array($parseInfo['tokenType'], array('operator', 'in-list', 'function', 'aggregate_function'))
                && in_array($parseInfo['upper'], self::$reserved))
            {
                if(!in_array($parseInfo['upper'], self::$functions))
                {
                    $parseInfo['tokenType'] = 'reserved';
                }
                else
                {
                    switch($parseInfo['upper'])
                    {
                    case 'AVG':
                    case 'SUM':
                    case 'COUNT':
                    case 'MIN':
                    case 'MAX':
                    case 'STDDEV':
                    case 'STDDEV_SAMP':
                    case 'STDDEV_POP':
                    case 'VARIANCE':
                    case 'VAR_SAMP':
                    case 'VAR_POP':
                    case 'GROUP_CONCAT':
                    case 'BIT_AND':
                    case 'BIT_OR':
                    case 'BIT_XOR':
                        $parseInfo['tokenType'] = 'aggregate_function';
                        break;

                    default:
                        $parseInfo['tokenType'] = 'function';
                        break;
                    }
                }
            }

            if(!$parseInfo['tokenType'])
            {
                if($parseInfo['upper'][0] == '(')
                    $local_expr = $this->removeParenthesisFromStart($parseInfo['trim']);
                else
                    $local_expr = $parseInfo['trim'];
                $parseInfo['processed'] = $this->process_expr_list($this->splitToken($local_expr));
                $parseInfo['tokenType'] = 'expression';

                if(count($parseInfo['processed']) === 1)
                {
                    $parseInfo['tokenType'] = $parseInfo['processed'][0]['expr_type'];
                    $parseInfo['base_expr'] = $parseInfo['processed'][0]['base_expr'];
                    $parseInfo['processed'] = $parseInfo['processed'][0]['sub_tree'];
                }
            }

            $parseInfo = $this->initParseInfoExprList($parseInfo);
        }

        return (is_array($parseInfo['expr']) ? $parseInfo['expr'] : false);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array
     */
    private function process_delete($tokens)
    {
        $tables = array();
        $del = $tokens['DELETE'];

        foreach($tokens['DELETE'] as $expression)
        {
            if($expression != 'DELETE' && trim($expression, ' .*') != "" && $expression != ',')
                $tables[] = trim($expression, '.* ');
        }

        if(empty($tables))
        {
            foreach($tokens['FROM'] as $table)
                $tables[] = $table['table'];
        }

        $tokens['DELETE'] = array('TABLES' => $tables);
        return $tokens;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $tokens
     * @param  string  $token_category
     * @return array   $tokens
     */
    private function process_insert($tokens, $token_category = 'INSERT')
    {
        $table = "";
        $cols = array();

        $into = $tokens['INTO'];
        foreach($into as $token)
        {
            if(trim($token) === "")
                continue;
            if($table === "")
                $table = $token;
            elseif(empty($cols))
                $cols[] = $token;
        }

        if(empty($cols))
            $cols = false;
        else
        {
            $columns = explode(",", $this->removeParenthesisFromStart($cols[0]));
            $cols = array();
            foreach($columns as $k => $v)
                $cols[] = array('expr_type' => 'colref', 'base_expr' => trim($v));
        }

        unset($tokens['INTO']);
        $tokens[$token_category] = array('table' => $table, 'columns' => $cols, 'base_expr' => $table);
        return $tokens;
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string        $unparsed
     * @param  array         $select
     * @return array | bool
     */
    private function process_record($unparsed)
    {
        $unparsed = $this->removeParenthesisFromStart($unparsed);
        $values = $this->splitToken($unparsed);

        foreach($values as $k => $v)
        {
            trim($v) === "," && $values[$k] = "";
        }
        return $this->process_expr_list($values);
    }

    /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array  $tokens
     */
    private function process_values($tokens)
    {
        $unparsed = "";
        foreach($tokens['VALUES'] as $k => $v)
        {
            if(trim($v) === "")
                continue;
            $unparsed .= $v;
        }

        $values = $this->splitToken($unparsed);
        $parsed = array();
        foreach($values as $k => $v)
        {
            if(trim($v) === ",")
                unset($values[$k]);
             else
                $values[$k] = array('expr_type' => 'record', 'base_expr' => $v, 'data' => $this->process_record($v));
        }

        $tokens['VALUES'] = array_values($values);
        return $tokens;
    }

     /**
     * 未知
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $tokens
     * @return array  $tokens
     */
    private function process_into($tokens)
    {
        $unparsed = $tokens['INTO'];
        foreach($unparsed as $k => $token)
        {
            if((trim($token) === "") || (trim($token) === ","))
                unset($unparsed[$k]);
        }
        $tokens['INTO'] = array_values($unparsed);
        return $tokens;
    }

    /**
     * 初始化
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return void
     */
    public function __construct()
    {
        if(self::$functions){
			return;
		}

        $functions = array(
            'abs', 'acos', 'adddate', 'addtime', 'aes_encrypt', 'aes_decrypt', 'against',
            'ascii', 'asin', 'atan', 'avg', 'benchmark', 'bin', 'bit_and', 'bit_or',
            'bitcount', 'bitlength', 'cast', 'ceiling', 'char', 'char_length',
            'character_length', 'charset', 'coalesce', 'coercibility', 'collation',
            'compress', 'concat', 'concat_ws', 'conection_id', 'conv', 'convert',
            'convert_tz', 'cos', 'cot', 'count', 'crc32', 'curdate', 'current_user',
            'currval', 'curtime', 'database', 'date_add', 'date_diff', 'date_format',
            'date_sub', 'day', 'dayname', 'dayofmonth', 'dayofweek', 'dayofyear', 'decode',
            'default', 'degrees', 'des_decrypt', 'des_encrypt', 'elt', 'encode', 'encrypt',
            'exp', 'export_set', 'extract', 'field', 'find_in_set', 'floor', 'format',
            'found_rows', 'from_days', 'from_unixtime', 'get_format', 'get_lock',
            'group_concat', 'greatest', 'hex', 'hour', 'if', 'ifnull', 'in', 'inet_aton',
            'inet_ntoa', 'insert', 'instr', 'interval', 'is_free_lock', 'is_used_lock',
            'last_day', 'last_insert_id', 'lcase', 'least', 'left', 'length', 'ln',
            'load_file', 'localtime', 'localtimestamp', 'locate', 'log', 'log2', 'log10',
            'lower', 'lpad', 'ltrim', 'make_set', 'makedate', 'maketime', 'master_pos_wait',
            'match', 'max', 'md5', 'microsecond', 'mid', 'min', 'minute', 'mod', 'month',
            'monthname', 'nextval', 'now', 'nullif', 'oct', 'octet_length', 'old_password',
            'ord', 'password', 'period_add', 'period_diff', 'pi', 'position', 'pow', 'power',
            'quarter', 'quote', 'radians', 'rand', 'release_lock', 'repeat', 'replace',
            'reverse', 'right', 'round', 'row_count', 'rpad', 'rtrim', 'sec_to_time',
            'second', 'session_user', 'sha', 'sha1', 'sign', 'soundex', 'space', 'sqrt',
            'std', 'stddev', 'stddev_pop', 'stddev_samp', 'strcmp', 'str_to_date', 'subdate',
            'substring', 'substring_index', 'subtime', 'sum', 'sysdate', 'system_user', 'tan',
            'time', 'timediff', 'timestamp', 'timestampadd', 'timestampdiff', 'time_format',
            'time_to_sec', 'to_days', 'trim', 'truncate', 'ucase', 'uncompress',
            'uncompressed_length', 'unhex', 'unix_timestamp', 'upper', 'user', 'utc_date',
            'utc_time', 'utc_timestamp', 'uuid', 'var_pop', 'var_samp', 'variance', 'version',
            'week', 'weekday', 'weekofyear', 'year', 'yearweek');

        $reserved = array(
            'abs', 'acos', 'adddate', 'addtime', 'aes_encrypt', 'aes_decrypt', 'against',
            'ascii', 'asin', 'atan', 'avg', 'benchmark', 'bin', 'bit_and', 'bit_or',
            'bitcount', 'bitlength', 'cast', 'ceiling', 'char', 'char_length',
            'character_length', 'charset', 'coalesce', 'coercibility', 'collation', 'compress',
            'concat', 'concat_ws', 'conection_id', 'conv', 'convert', 'convert_tz', 'cos',
            'cot', 'count', 'crc32', 'curdate', 'current_user', 'currval', 'curtime',
            'database', 'date_add', 'date_diff', 'date_format', 'date_sub', 'day', 'dayname',
            'dayofmonth', 'dayofweek', 'dayofyear', 'decode', 'default', 'degrees',
            'des_decrypt', 'des_encrypt', 'elt', 'encode', 'encrypt', 'exp', 'export_set',
            'extract', 'field', 'find_in_set', 'floor', 'format', 'found_rows', 'from_days',
            'from_unixtime', 'get_format', 'get_lock', 'group_concat', 'greatest', 'hex',
            'hour', 'if', 'ifnull', 'in', 'inet_aton', 'inet_ntoa', 'insert', 'instr',
            'interval', 'is_free_lock', 'is_used_lock', 'last_day', 'last_insert_id', 'lcase',
            'least', 'left', 'length', 'ln', 'load_file', 'localtime', 'localtimestamp',
            'locate', 'log', 'log2', 'log10', 'lower', 'lpad', 'ltrim', 'make_set', 'makedate',
            'maketime', 'master_pos_wait', 'match', 'max', 'md5', 'microsecond', 'mid', 'min',
            'minute', 'mod', 'month', 'monthname', 'nextval', 'now', 'nullif', 'oct',
            'octet_length', 'old_password', 'ord', 'password', 'period_add', 'period_diff',
            'pi', 'position', 'pow', 'power', 'quarter', 'quote', 'radians', 'rand',
            'release_lock', 'repeat', 'replace', 'reverse', 'right', 'round', 'row_count',
            'rpad', 'rtrim', 'sec_to_time', 'second', 'session_user', 'sha', 'sha1', 'sign',
            'soundex', 'space', 'sqrt', 'std', 'stddev', 'stddev_pop', 'stddev_samp', 'strcmp',
            'str_to_date', 'subdate', 'substring', 'substring_index', 'subtime', 'sum',
            'sysdate', 'system_user', 'tan', 'time', 'timediff', 'timestamp', 'timestampadd',
            'timestampdiff', 'time_format', 'time_to_sec', 'to_days', 'trim', 'truncate',
            'ucase', 'uncompress', 'uncompressed_length', 'unhex', 'unix_timestamp', 'upper',
            'user', 'utc_date', 'utc_time', 'utc_timestamp', 'uuid', 'var_pop', 'var_samp',
            'variance', 'version', 'week', 'weekday', 'weekofyear', 'year', 'yearweek', 'add',
            'all', 'alter', 'analyze', 'and', 'as', 'asc', 'asensitive', 'auto_increment',
            'bdb', 'before', 'berkeleydb', 'between', 'bigint', 'binary', 'blob', 'both', 'by',
            'call', 'cascade', 'case', 'change', 'char', 'character', 'check', 'collate',
            'column', 'columns', 'condition', 'connection', 'constraint', 'continue', 'create',
            'cross', 'current_date', 'current_time', 'current_timestamp', 'cursor', 'database',
            'databases', 'day_hour', 'day_microsecond', 'day_minute', 'day_second', 'dec',
            'decimal', 'declare', 'default', 'delayed', 'delete', 'desc', 'describe',
            'deterministic', 'distinct', 'distinctrow', 'div', 'double', 'drop', 'else',
            'elseif', 'end', 'enclosed', 'escaped', 'exists', 'exit', 'explain', 'false',
            'fetch', 'fields', 'float', 'for', 'force', 'foreign', 'found', 'frac_second',
            'from', 'fulltext', 'grant', 'group', 'having', 'high_priority',
            'hour_microsecond', 'hour_minute', 'hour_second', 'if', 'ignore', 'in', 'index',
            'infile', 'inner', 'innodb', 'inout', 'insensitive', 'insert', 'int', 'integer',
            'interval', 'into', 'io_thread', 'is', 'iterate', 'join', 'key', 'keys', 'kill',
            'leading', 'leave', 'left', 'like', 'limit', 'lines', 'load', 'localtime',
            'localtimestamp', 'lock', 'long', 'longblob', 'longtext', 'loop', 'low_priority',
            'master_server_id', 'match', 'mediumblob', 'mediumint', 'mediumtext', 'middleint',
            'minute_microsecond', 'minute_second', 'mod', 'natural', 'not',
            'no_write_to_binlog', 'null', 'numeric', 'on', 'optimize', 'option', 'optionally',
            'or', 'order', 'out', 'outer', 'outfile', 'precision', 'primary', 'privileges',
            'procedure', 'purge', 'read', 'real', 'references', 'regexp', 'rename', 'repeat',
            'replace', 'require', 'restrict', 'return', 'revoke', 'right', 'rlike',
            'second_microsecond', 'select', 'sensitive', 'separator', 'set', 'show',
            'smallint', 'some', 'soname', 'spatial', 'specific', 'sql', 'sqlexception',
            'sqlstate', 'sqlwarning', 'sql_big_result', 'sql_calc_found_rows',
            'sql_small_result', 'sql_tsi_day', 'sql_tsi_frac_second', 'sql_tsi_hour',
            'sql_tsi_minute', 'sql_tsi_month', 'sql_tsi_quarter', 'sql_tsi_second',
            'sql_tsi_week', 'sql_tsi_year', 'ssl', 'starting', 'straight_join', 'striped',
            'table', 'tables', 'terminated', 'then', 'timestampadd', 'timestampdiff',
            'tinyblob', 'tinyint', 'tinytext', 'to', 'trailing', 'true', 'undo', 'union',
            'unique', 'unlock', 'unsigned', 'update', 'usage', 'use', 'user_resources',
            'using', 'utc_date', 'utc_time', 'utc_timestamp', 'values', 'varbinary', 'varchar',
            'varcharacter', 'varying', 'when', 'where', 'while', 'with', 'write', 'xor',
            'year_month', 'zerofill');

        self::$functions = $functions;
        self::$reserved = $reserved;
        $count = count($reserved);
        for($i = 0; $i < $count; $i++){
            self::$reserved[$i] = strtoupper(self::$reserved[$i]);
			if(!empty(self::$functions[$i])){
				self::$functions[$i] = strtoupper(self::$functions[$i]);
			}
        }
    }
}
