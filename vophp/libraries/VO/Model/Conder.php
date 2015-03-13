<?php
/**
 * 定义VO_Model_Conder SQL查询条件处理器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen<cqq254@163.com>
 * @package VO
 * @since version 1.0
 * @date 2010-05-25
 **/

class VO_Model_Conder extends VO_Object{
    /**
     * 返回字段
     * @access private
     * @var string
     */
    private $_field = '*';

    /**
     * 是否返回查询记录总数
     * @access private
     * @var bool
     */
    private $_found_rows = false;

    /**
     * 排重
     * @access private
     * @var bool
     */
    private $_distinct = false;

    /**
     * 查询表名 
     * @access private
     * @var array
     */
    private $_tablename = '';    

    /**
     * 强制索引列 
     * @access private
     * @var string | array
     */
    private $_force_index = '';

    /**
     * 查询条件 
     * @access private
     * @var array
     */
    private $_where = null;

    /**
     * 结果集大小
     * @access private
     * @var int
     */
    private $_limit = null;

    /**
     * 结果集偏移值
     * @access private
     * @var int
     */
    private $_offset = 0;

    /**
     * 排序字段
     * @access private
     * @var string
     */
    private $_order = '';

    /**
     * 分组字段
     * @access private
     * @var string
     */
    private $_group = '';

    /**
     * having结构
     * @access private
     * @var string
     */
    private $_having = '';

    /**
     * 查询逻辑
     * 注意condition成员变量有特定格式请不要直接修改!!
     * @access public
     * @var array
     */
    public $condition = array();

	/**
     * 模型对象
     * @access private
     * @var VO_Model
     */
    public $_model = null;

	/**
	 * 构造函数
	 * @return VO_Model_Conder
	 */
    public function __construct($model){
	    $this->_model = $model;
    }

    /**
     * 设置是否返回查询总记录条数 
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return  Leb_Criteria
     */
	public function foundRows(){
		$this->_found_rows = true;
		return $this;
	}

    /**
     * 获取是否返回查询总记录条数 
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return bool   
     */
	public function isFoundRows(){
		return $this->_found_rows;
	}

    /**
     * 设置是否返回查询总记录条数 
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return  Leb_Criteria
     */
	public function forceIndex($indexs=''){
		if(is_array($indexs)){
			$this->_force_index = implode(',', $indexs);
		}else{
			$this->_force_index = $indexs;
		}
		return $this->_force_index;
	}

    /**
     * 获取是否返回查询总记录条数 
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return bool   
     */
	public function getForceIndex(){
		return $this->_force_index;
	}	

    /**
     * 设置是否去除重复记录
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return  Leb_Criteria
     */
	public function distinct(){
		$this->_distinct = true;
		return $this;
	}

    /**
     * 获取是否去除重复记录
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return bool 
     */
	public function isDistinct(){
		return $this->_distinct;
	}

    /**
     * 设置查询字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
	 * @param  string|array	 $field   需要查询的字段,可以是以逗号分隔的各字段名称字符串,也可以是一个字段数组
     * @return  Leb_Criteria
     */
	public function field($field='*'){
		$field = func_get_args();
		if(empty($field)){
			$this->_field = '*';
		}else{
			$this->_field = $field;
		}
		return $this;
	}

    /**
     * 返回查询字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return string 
     */
	public function getField(){
		return $this->_field;
	}

	/**
     * 设置查询表名
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
	 * @param  string  $table   待查询表名
     * @return string 
     */
	public function from($table){
		$this->_tablename = $this->_model->getTableName($table);
		return $this->_tablename;
	}

	/**
     * 返回查询表名
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return string 
     */
	public function getFrom(){
		return empty($this->_tablename) ? $this->_model->getTableName() : $this->_tablename;
	}	

	/**
	 * 构建Where语句
	 * 用法:
	 * $sql_cond = getConditions(
	 *		array(
	 *			'name' => 'leju',
	 *			'age' => array(18, '>='),
	 *			'step' => array(72, '>', 'OR'),
	 *			'content' => array('%房产%', 'LIKE'),
	 *		)	
	 *	);
	 * 返回: (  name = 'leju' AND age >= 18 OR step > 72 AND content LIKE '%房产%' )
	 * 
	 *	$where = array(
			'category'  => array(
								array('first', '=', 'OR'),
								array('recommend', '=', 'OR'),
							),
			'author'	=> array('admin', '=', 'OR'),
			'id'		=> array(array(1,2,3,4,5), 'IN', 'AND', false),
			'deleted'	=> 0
		);
		返回: ( ( category = 'first' OR category = 'recommend') OR author = 'admin' AND id IN (1,2,3,4,5) AND deleted = 0 )
     * @author Chen QiQing <qiqing@leju.com>
	 * @param array $where 查询条件
     * @return  Leb_Criteria
	 */
	public function where($where = array()){
		if(!is_array($where)){
			$this->_model->addError('', 'condition is not array');
			return $this;
		}
		if(empty($this->_where)){
			$this->_where = $where;
		}else{
			$this->_where[] = $where;
		}
		return $this;
	}

    /**
     * 返回查询条件
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return array 
     */
	public function getWhere(){
		return $this->_where;
	}

    /**
     * 设置分组
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string|array  $group  分组信息,例如: group('create') 或者 group(array('id', 'name')) 
     * @return  Leb_Criteria
     */
	public function group($group){
		if(is_array($group)){
			$this->_group = implode(',', $group);
		}else{
			$this->_group = $group;
		}
		return $this;
	}

    /**
     * 返回查询组合
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return string 
     */
	public function getGroup(){
		return $this->_group;
	}

    /**
     * 设置分组后having过滤
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string|array  $having	分组过滤字段,例如: having('username') 或者 having(array('id', 'name')) 
     * @return  Leb_Criteria
     */
	public function having($having){
		if(is_array($having)){
			$this->_having = implode(',', $hving);
		}else{
			$this->_having = $having;
		}
		return $this;
	}

    /**
     * 返回查询过滤组合
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return string 
     */
	public function getHaving(){
		return $this->_having;
	}
    /**
     * 设置查询排序
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string|array  $order  排序信息,例如: order('create DESC')或者 order(array('id DESC', 'name ASC')) 
     * @return  Leb_Criteria
     */
	public function order($order){
		if(is_array($order)){
			$this->_order = implode(',', $order);
		}else{
			$this->_order = $order;
		}
		return $this;
	}

    /**
     * 返回查询排序组合
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return string 
     */
	public function getOrder(){
		return $this->_order;
	}

    /**
     * 设置查询条数
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  int  $limit  查询条数 
     * @return  Leb_Criteria
     */
	public function limit($limit = 10){
		$limit= (int)$limit;
		$this->_limit = $limit;
		return $this;
	}

    /**
     * 返回查询条数
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return int
     */
	public function getLimit(){
		return $this->_limit;
	}

    /**
     * 设置查询数据偏移
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  int  $offset  查询记录偏移
     * @return  Leb_Criteria
     */
	public function offset($offset= 10){
		$offset= (int)$offset;
		$this->_offset = $offset;
		return $this;
	}

    /**
     * 返回查询偏移
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return int
     */
	public function getOffset(){
		return $this->_offset;
	}

    /**
     * 设置查询页码
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
	 * @param  int  $page  查询页数
	 * @param  int  $limit  查询条数
     * @return  Leb_Criteria
     */
	public function page($page=1, $limit=10){
		$page = (int)$page;
		$limit= (int)$limit;
		if($page <= 0){
			$page = 1;
		}
		if($limit > 0){
			$this->limit($limit);
		}
		$offset = ($page-1) * $limit;
		$this->offset($offset);
		$this->_page = $page;
		return $this;
	}

    /**
     * 返回查询页码
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return int
     */
	public function getPage(){
		return $this->_page;
	}

	/**
     * 处理函数查询
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return int
     */
	public function func($func, $args){
		
	}

	/**
	 * 将当前的对象内容作为一个数组返回,供C端扩展参数使用
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
	 * @param	void
	 * @return	array SQL组装数组
	 * */
	public function getAsArray(){
		$criteria_array = array(
			'is_found_rows' => $this->isFoundRows(),
			'is_distinct' => $this->isDistinct(),
			'fields' => $this->getField(),
			'table' => $this->_tablename ? $this->_tablename : $this->_model->getTableName(),
			'where' => $this->getWhere(),
			'group' => $this->getGroup(),
			'having' => $this->getHaving(),
			'order' => $this->getOrder(),
			'limit' => $this->getLimit(),
			'offset' => $this->getOffset(),
		);
		return $criteria_array;
	}

    /**
     * 重置查询条件
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return int
     */
    public function clearCondition(){
	    $this->_field = '*';
	    $this->_found_rows = false;
	    $this->_distinct = false;
	    $this->_tablename = '';
	    $this->_where = null;
	    $this->_limit = null;
	    $this->_offset = 0;
	    $this->_order = '';
	    $this->_group = '';
	    $this->_having = '';
        return !$this->condition = array();
    }
}
