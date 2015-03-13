<?php
/**
 * 定义 VO_Model 模型基类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-23
 **/

defined('VOPHP') or die('Restricted access');

class VO_Model extends VO_Object{
	/**
	 * 配置信息
	 * @var array
	 */
	protected $_config = array();

	/**
	 * 默认数据库句柄
	 * @var VO_Database_Adapter_Abstract
	 */
	protected $_db = null;
	
	/**
	 * 读数据库句柄
	 * @var VO_Database_Adapter_Abstract
	 */
	private $_read_db = null;
	
	/**
	 * 写数据库句柄
	 * @var VO_Database_Adapter_Abstract
	 */
	private $_write_db = null;
	
	/**
	 * 当前数据表的表名
	 * @var string
	 */
	protected $_name = '';
	
	/**
	 * 当前数据表的主键
	 * @var string
	 */
	protected $_key = '';
	
	/**
	 * 当前表的字段
	 * @var array
	 */
	protected $_fields = array();
	
	/**
	 * 方法及参数存储器
	 * @var array
	 */
	protected $_methods = array();
	
	protected $_sql_methods = array('field', 'distinct', 'foundrows', 'forceindex', 'from', 'where', 'order', 'limit', 'offset','page','having','group');
	/**
	 * 是否统计记录总数
	 */
	private $_is_found_rows = false;

	/**
	 * 查询条件生成器
	 */
	private $_Conder = null;
	
	/**
	 * 错误信息
	 * @var string
	 */
	protected $_error = '系统错误';

	/**
	 * ID的模式
	 * @var int
	 */
	private $_id_mode = ID_NONE;

	/**
	 * 数据存储模型
	 * @var int
	 */
	private $_dao_type = DAO_FAST;		

	/**
	 * 构造函数
	 * @return void 
	 */
	public function __construct($db_config=array()){
		$this->_config = C();
		$this->_Conder = VO_Loader::load('model.conder', $this);
		$this->init($db_config);
	}
	
	/**
	 * 初始化模型
	 */
	public function init($db_config = array()){
		if(!empty($db_config)){
			$this->_config['db']['write'] = $this->_config['db']['read'] = $db_config;
		}
		//如果没有定义表名，则取当前调用的model的类名作为表名
		if(empty($this->_name)){
			$this->_name = strtolower(get_class($this));
		}
		if(isset($this->_config['db']['write'])){
			$this->_write_db = VO_Database::getDb($this->_config['db']['write']);
		}
		if(isset($this->_config['db']['read'])){
			$this->_read_db = VO_Database::getDb($this->_config['db']['read']);
		}
		$this->_db = $this->_write_db;
		if(strtolower($this->_name) <> 'vo_model'){
			//缓存字段
			$this->cacheField();
		}
	}
	
	/**
	 * 获取助手对象
	 * @return VO_Helper_Abstract
	 */
	public function getHelper($name, $module='', $namespace=''){
		if(!empty($name)){
			return VO_Helper::getInstance()->getHelper($name, $module, $namespace);
		}else{
			throw new VO_Exception('助手类名称不能空。');
		}
	}

	/**
	 * 加载模型文件
	 * @param string  $name		模型名称
	 * @param string  $module	模块名称
	 * @return VO_Model		模型名称$name对应的模型对象
	 */
	public function loadModel($name, $module='', $db_config=array()){
		if(empty($module)){
			$module = strtolower(get_class($this));
		}
		return $this->getHelper('model')->loadModel($name, $module, $db_config);
	}	
	
	/**
	 * 获取数据库表名
	 * @param	string	$name	数据表名
	 * @return	string	数据库名
	 */
	public function getTableName($name=''){
		if(empty($name)){
			$name = $this->_name;
		}
		if(strpos($name, '#__') !== 0 ){
			$name = '#__' . $name;	
		}
		return $name;
	}
	
	/**
	 * 获取数据表字段信息
	 * @return array	数据表信息
	 */
	public function cacheField($is_forced_cache = false){
		if(empty($this->_fields) || $is_forced_cache){
			$fields = array(
				'_fields'	=> array(),
				'_key'		=>	$this->_key,
				'_autoinc'	=>	false
			);
			$field_cache_key = $this->_name . '_fields';
			$cacher = VO_Cache_File::getInstance();
			//缓存没有缓存字段或者处于调试状态，则不直接从数据库取字段信息;如果当前为非调试状态，则缓存字段信息
			if( ($this->_config['base']['debug'] == true) || ($cacher->get($field_cache_key) == false) ){
				$fields['_fields'] = $this->_read_db->getFields($this->getTableName());
				//取主键和自动增长列
				if(empty($this->_key)){
					foreach($fields['_fields'] as $k => $_field){
						if($_field['primary'] === true){
							$_field['_key'] = $k;
							$this->_key = $k;
						}
						
						if($_field['autoinc'] === true){
							$_field['_autoinc'] = true;
						}
						break;
					}
				}
				if( ($this->_config['base']['debug'] == false) ){
					$cacher->set($this->_name, $fields);
				}
			}else{
				//从缓存取字段信息
				$fields['_fields'] = $cacher->get($field_cache_key);
			}
			$this->_fields = $fields;
		}
	}
	
	/**
	 * 魔术方法
	 * @param string $method 方法名
	 * @param array $args 参数数组
	 * 
	 * @return VO_Model | array
	 */
	public function __call($method, $args){
		if(in_array(strtolower($method), $this->_sql_methods)){
			call_user_func_array(array($this->_Conder, $method), $args);
			return $this;
		}
		/*
		$method_arrays = array('field','table', 'from', 'forceindex', 'leftjoin', 'where','order', 'limit', 'offset','page','having','group','distinct', 'foundrows');
		$method = strtolower($method);
		if( in_array($method, $method_arrays, true) ){
			switch($method){
				case 'leftjoin':
					$this->_methods[$method][] =  array(
						'table' => $args[0],
						'on'	=> $args[1],
					);
					break;
				default:
					if(count($args) > 1){
						$args = implode(',', $args);
					}elseif(count($args) == 1){
						$args = $args[0];
					}else{
						$args = array();
					}
					if(!empty($args)){
						$this->_methods[$method] =  $args;
					}else{
						$this->_methods[$method] =  '';
					}
			}
            return $this;
	   }elseif( in_array($method, array('count','sum','min','max','avg'), true) ){
	        if($method == 'count'){
	        	$field =  isset($args[0]) ? $args[0] : '*';
	        }else{
	        	if(isset($args[0])){
	        		$field = $args[0];
	        	}else{
	        		throw new VO_Exception(__CLASS__ . ':' . $method . ' 方法必须要有一个字段作为统计参数');
	        	}
	        }
	        return $this->counter($method, $field);
	   }elseif( substr($method, 0, 5) == 'getby' ){
	       $field  =substr($method, 5);
	       if(!isset($args[0])){
				throw new VO_Exception( __CLASS__ . ':' . $method . ' 方法的参数不能为空');
	       }
	       $where = $field . "='" . $args[0] . "'";
	       $sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE ' . $where;
	       $row = $this->_read_db->fetchRow($sql);
	       if($row ==  false){
	       		$this->_error = $this->_read_db->getErrorMessage();
	       		return false;
	       }else{
	       		return $row;
	       }
	   }else{
	       throw new VO_Exception( __CLASS__ . ':' . $method . ' 方法不存在');
	   }
	   */
	}
	
	/**
	 * 统计器
	 * @param string $method	统计方法
	 * @param string $field		统计字段
	 */
	private function counter($method, $field){
		$sql = 'SELECT ';
		$sql .= strtoupper($method).'('.$field.') AS vo_' . $method;
		$sql .= isset($this->_methods['from']) 
					? ' FROM ' . '`' . $this->getTableName($this->_methods['from']) . '` AS `' . $this->_methods['from'] . '`'
					: ' FROM `' . $this->getTableName() . '` AS `' . $this->_name . '`';
        if( isset($this->_methods['forceindex']) ){
		      $sql .= ' FORCE INDEX(' . $this->_methods['forceindex'] . ')';
		}
        
		if(isset($this->_methods['leftjoin'])){
			foreach($this->_methods['leftjoin'] as $key => $leftjoin){
				if( preg_match('/(.*?)\sAS\s(.*+)/i', $leftjoin['table']) ){
					preg_match('/(.*?)\sAS\s(.*+)/i', $leftjoin['table'], $match);
					$sql .= ' LEFT JOIN `' . $this->getTableName($match[1]) . '`';
					$sql .= ' AS `' . $match[2] . '`';
					$sql .= ' ON ' . $leftjoin['on'];
				}else{
					$sql .= ' LEFT JOIN `' . $this->getTableName($leftjoin['table']) . '`';
					$sql .= ' AS `' . $leftjoin['table'] . '`';
					$sql .= ' ON ' . $leftjoin['on'];
				}		  
			}
		}
		
		//解析where
		if(isset($this->_methods['where']) && !empty($this->_methods['where'])){
			if( is_array($this->_methods['where']) ){
				$where = $this->_getConditions($this->_methods['where']);
				$sql .= ' WHERE ' . $where;
			}else{
				$sql .= ' WHERE ' . $this->_methods['where'];
			}
		}
		$ret = $this->_read_db->fetchOne( $sql );
		$this->_clearCallMetch();
		return $ret;
	}
	
	/**
	 * 解析__call方法中的未知方法
	 */
	private function _parseMethod(){
		$this->_is_found_rows = false;
		
		$sql = 'SELECT ';
		$sql .= isset($this->_methods['distinct']) ? ' DISTINCT ' : '';
		if(isset($this->_methods['foundrows'])){
			$sql .= ' SQL_CALC_FOUND_ROWS ';
			$this->_is_found_rows = true;
		}
		$sql .= isset($this->_methods['field']) ? $this->_methods['field'] : ' * ';
		$sql .= isset($this->_methods['from']) 
						? ' FROM ' . '`' . $this->getTableName($this->_methods['from']) . '`' 
						: ' FROM `' . $this->getTableName() . '` as `' . $this->_name . '`';
		if( isset($this->_methods['forceindex']) ){
		      $sql .= ' FORCE INDEX(' . $this->_methods['forceindex'] . ')';
		}
        
        if(isset($this->_methods['leftjoin'])){
			foreach($this->_methods['leftjoin'] as $key => $leftjoin){
				if( preg_match('/(.*?)\sas\s(.*+)/i', $leftjoin['table']) ){
					preg_match('/(.*?)\sas\s(.*+)/i', $leftjoin['table'], $match);
					$sql .= ' LEFT JOIN `' . $this->getTableName($match[1]) . '`';
					$sql .= ' AS `' . $match[2] . '`';
					$sql .= ' ON ' . $leftjoin['on'];
				}else{
					$sql .= ' LEFT JOIN `' . $this->getTableName($leftjoin['table']) . '`';
					$sql .= ' AS `' . $leftjoin['table'] . '`';
					$sql .= ' ON ' . $leftjoin['on'];
				}		  
			}
		}
		$sql .= isset($this->_methods['where']) && !empty($this->_methods['where']) ? ' WHERE ' . $this->_getConditions($this->_methods['where']) : '';
		$sql .= isset($this->_methods['group']) && !empty($this->_methods['group']) ? ' GROUP BY ' . $this->_methods['group'] : '';
		$sql .= isset($this->_methods['having']) && !empty($this->_methods['having']) ? ' HAVING BY ' . $this->_methods['having'] : '';
		$sql .= isset($this->_methods['order']) && !empty($this->_methods['order']) ? ' ORDER BY ' . $this->_methods['order'] : '';
		
		//解析limit
		if( isset($this->_methods['limit'])){
			$sql .= ' LIMIT ' . $this->_methods['limit'];
		}elseif( isset($this->_methods['page'])){
			$limit = ($this->_methods['page']-1) * 20;
			$sql .= ' LIMIT ' . $limit;
		}
		
		//解析limit
		if( isset($this->_methods['limit']) && isset($this->_methods['offset']) ){
			if(empty($this->_methods['offset'])){
				$this->_methods['offset'] = 0;
			}
			$sql .=  ' OFFSET ' . $this->_methods['offset'];
		}
		return $sql;
	}
	
	/**
	 * 查询一条记录 
	 * @return array 查询结果集
	 */
	public function find(){
		$Builder = VO_Loader::load('model.builder', $this);
		$this->_Conder->limit(1);
		$sql = $Builder->buildFind($this->_Conder);
		$row = $this->_read_db->fetchRow($sql, $Builder->getParam());
		$this->_Conder->clearCondition();
		$this->_clearCallMetch();
		return $row;
	}
	
	/**
	 * 查询多条记录
	 * @return array 查询结果集
	 */
	public function select(){
		$Builder = VO_Loader::load('model.builder', $this);
		$sql = $Builder->buildFind($this->_Conder);
		$rows = $this->_read_db->fetchAll($sql, $Builder->getParam());
		$this->_Conder->clearCondition();
		$this->_clearCallMetch();
		if($rows){
			return $rows;
		}else{
			$this->_error = $this->_read_db->getErrorMessage();
			return false;
		}
	}
	
	/**
     * 通过setIsFoundRows设置SQL_CALC_FOUND_ROWS获取执行Limit取得的总数据,通过此方法获取
     * @return int|false
     */
    public function getFoundRows(){
    	if($this->_is_found_rows == false){
    		return false;
    	}else{
    		$sql = 'SELECT FOUND_ROWS()';
    		return $this->_read_db->fetchOne($sql);
    	}
    }
	
	/**
	 * 删除记录
	 * @param array | string $where  删除条件
	 */
	public function delete($where){
		if(is_array($where)){
			$where = $this->_getConditions($where);
		}
		return $this->_write_db->delete($this->getTableName(), $where);
	}
	
/**
	 * 保存数据
	 * @param array $data	保存的数组
	 * @param string $where  当为更新时的where条件
	 */
	public function add($data, $where=null){
		if(!is_array($data) || empty($data)){
			$ret = false;
		}else{
			//处理字段
			$to_data = array();
			if($this->_fields){
				foreach($this->_fields['_fields'] as $k => $field){
					$field_name =$field['name'];
					if( isset($data[$field_name]) ){
						//字段类型检查
						if( $this->_config['db']['field_type_check'] ){
							$field_type = $field['type'];
							if(strpos($field_type,'int') !== false) {
	                            $to_data[$field_name] = (int)$data[$field_name];
	                        }elseif( (strpos($field_type,'float') !== false) || (strpos($field_type,'double') !== false) ){
	                            $to_data[$field_name] = (float)$data[$field_name];
	                        }elseif( (strpos($field_type,'string') !== false) || (strpos($field_type,'text') !== false)  || (strpos($field_type,'char') !== false) ){
	                            $to_data[$field_name] = (string)$data[$field_name];
	                        }else{
	                        	$to_data[$field_name] = $data[$field_name];
	                        }
						}else{
							$to_data[$field_name] = $data[$field_name];
						}
					}
				}
			}
			$ret = $this->_write_db->insert($this->getTableName(), $to_data);
			if($ret === 0){
				return true;
			}else{
				return $ret;
			}
		}
	}	
	
	/**
	 * 保存数据
	 * @param array $data	保存的数组
	 * @param string $where  当为更新时的where条件
	 */
	public function save($data, $where=null){
		if(!is_array($data) || empty($data)){
			$ret = false;
		}else{
			//处理字段
			$to_data = array();
			if($this->_fields){
				foreach($this->_fields['_fields'] as $k => $field){
					$field_name =$field['name'];
					if( isset($data[$field_name]) ){
						//字段类型检查
						if( $this->_config['db']['field_type_check'] ){
							$field_type = $field['type'];
							if(strpos($field_type,'int') !== false) {
	                            $to_data[$field_name] = (int)$data[$field_name];
	                        }elseif( (strpos($field_type,'float') !== false) || (strpos($field_type,'double') !== false) ){
	                            $to_data[$field_name] = (float)$data[$field_name];
	                        }elseif( (strpos($field_type,'string') !== false) || (strpos($field_type,'text') !== false)  || (strpos($field_type,'char') !== false) ){
	                            $to_data[$field_name] = (string)$data[$field_name];
	                        }else{
	                        	$to_data[$field_name] = $data[$field_name];
	                        }
						}else{
							$to_data[$field_name] = $data[$field_name];
						}
					}
				}
			}
			//更新
			if( (!empty($where)) || ($this->_key && array_key_exists($this->_key, $to_data) && !empty($to_data[$this->_key]) ) ){
				//处理条件
				if(empty($where)){
					$where = '`' . $this->_key . '`="' . $to_data[$this->_key] . '"';
				}
				if(is_array($where)){
					$where = $this->_getConditions($where);
				}
				unset($to_data[$this->_key]);
				$ret = $this->_write_db->update($this->getTableName(), $to_data, $where);
			}else{
				//添加
				$ret = $this->_write_db->insert($this->getTableName(), $to_data);
				if($ret === 0){ //由于insert返回的是lastinsertid，所以如果没有自增ID则会返回0
					$ret = true;
				}
			}
		}
		return $ret;
	}

	/**
	 * 返回最后一次插入的记录ID
	 * @return int 	最后一次插入记录的ID;
	 */
	public function getInsertId(){
		return $this->_write_db->getInsertId();
	}
	
	/**
	 * 返回当前数据对象的主键
	 * 
	 * @return string;
	 */
	public function getPrimaryKey(){
		return $this->_key;
	}
	
	/**
	 * 设置当前要操作的模型
	 * @param string  $table  表名
	 * @return void
	 */
	public function setTableName($table){
		$this->_name = $table;
		$this->cacheField(true);  //强制重新表字段缓存
	}
	
	/**
	 * 设置数据对象的主键
	 * 
	 * @param string $key 主键
	 * @return void
	 */
	public function setPrimaryKey($key){
		$this->_key = $key;
	}
	
	
	/**
     * 得到当前的数据模型对象的名称
     *
     * @return string
     */
    public function getModelName()
    {
        if(empty($this->_name)) {
            $this->_name = get_class($this);
        }
        return $this->_name;
    }	
	
	/**
	 * 获取最后一次操作的SQL语句
	 * 
	 * @return string
	 */
	public function getSql(){
		return $this->_read_db->getSql();
	}
	
	/**
     * 启动事务
     *
     * @return void
     */
    public function startTrans()
    {
        $this->commit();
        $this->_write_db->startTransaction();
        return ;
    }

    /**
     * 提交事务
     *
     * @return boolean
     */
    public function commit()
    {
        return $this->_write_db->commit();
    }

    /**
     * 事务回滚
     *
     * @return boolean
     */
    public function rollback()
    {
        return $this->_write_db->rollback();
    }
	
    /**
     * 清除所有保存的方法
     */
    private function _clearCallMetch(){
    	$this->_methods = array();
    }

	/**
	 * 获取错误信息
	 * @return	错误信息
	 */
	public function getError(){
		return $this->_error;
	}

	/**
	 * 设置错误信息
	 * @return	错误信息
	 */
	public function setError($error){
		return $this->_error = $error;
	}	
    
	/**
	 * 返回复合条件字符串
	 * 
	 * 	用法:
	 *	$sql_cond = getConditions(
	 *		array(
	 *			'site_name' => 'VOWEBER' ,
	 *			'age' => array(18,'>=') ,
	 *			'grade' => array(72,'>','OR') ,
	 *			'framework_name' => array('%key%','LIKE') ,
	 *		)			
	 *	);
	 * 返回: (  site_name = 'VOWEBER' AND age >= 18 OR grade > 72 AND framework_name LIKE '%VOPHP%' )
	 * 
	 *	$where = array(
			'category'		=> array(
									array('first', '=', 'OR'),
									array('recommend', '=', 'OR'),
								),
			'author'		=> array('管理员', '=', 'OR'),
			'id'		=> array(array(1,2,3,4,5), 'IN', 'and', false),
			'is_allow_reply'	=> 1,
			'is_deleted'	=> 0
		);
		返回: ( ( category = 'first' OR category = 'recommend') OR author = '管理员' and id IN (1,2,3,4,5) AND is_allow_reply = 1 AND is_deleted = 0 )
	 *  
	 * @param mixed $condition 条件
	 * @return string
	 */
	protected function _getConditions($condition){
		$defaultCompare = '='; //缺省的比较符
		$defaultJoin = 'AND'; //缺省的连接符
		$defaultIsQuote = true;
		
		if( empty($condition) ){
			return '';
		}
		if( is_string($condition )){
			return "( {$condition} )" ;
		}
		if( is_array($condition) ){
			$where = '' ; 
			$join = '' ;
			foreach( $condition as $field => $cond ){
				if( is_object($cond) ){
					continue ;
				}
				$fields = explode('.', $field); //防止有字段表限定，例如news.id
				if(count($fields) > 1){
					$field = $fields[0] . '.`' . $fields[1] . '`';
				}else{
					$field = '`' . $field . '`';
				}
				$noQuote = false ; //不进行转义字符操作 
				if(is_array($cond)){ //array(:value,:compare,:join,:noQuote)
					if(!isset($cond[2]) || empty($cond[2])){
						$cond[2] = $defaultJoin;
					}
					if( is_array($cond[0])){
					    if(strtoupper($cond[1]) == 'IN' ||  strtoupper($cond[1]) == 'NOT IN'){
						    if(!empty($where)){
							    $where .= ' ' . $cond[2] . ' ';
						    }
					        $where .= $field . ' ' . strtoupper($cond[1]) . ' (';
                            $where .= implode(',', $cond[0]);
            	            $where .= ')';
					    }elseif(strtoupper($cond[1]) == 'BETWEEN'){
						   if(!empty($where)){
							    $where .= ' ' . $cond[2] . ' ';
						    }
					        $where .= $field . ' ' . strtoupper($cond[1]) . ' ' . $cond[0][0] . ' AND ' . $cond[0][1];
						}else{
    						$where .= '(';
    						foreach($cond as $i => $item){
    							$this->_parseWhereArray($field, $item, $where) ;
    						}
    						$where .= ')';
                        }
					}else{
						$this->_parseWhereArray($field, $cond, $where) ;
					}
				}else{
					if(empty($cond)){
						//continue;
					}
					if( !empty($where) ){
						$join = $defaultJoin;
					}
					$where .= " {$join} {$field} {$defaultCompare} \"{$cond}\"" ;
				}
			}
			return "( {$where} )" ;
		}else{
			return '' ;
		}	
	}
	
	/**
	 * 用于解析where中的数组条件
	 * @param	string	$field	字段名称
	 * @param	string|array	$cond	条件类型(当条件为IN操作的时候有可能为数组)
	 * @param   string	$where	拼接后的字符串，以引用传递过来，
	 * @return  void	由于以引用返回，所以没有返回值
	 */
	private function _parseWhereArray($field, $cond, &$where){
		$defaultCompare = '='; //缺省的比较符
		$defaultJoin = 'AND'; //缺省的连接符
		$defaultIsQuote = true;

		$value = array_shift($cond);
		if (is_object($value)){
			return false ;
		}
		
		$compare = array_shift($cond);
		$compare = $compare ? $compare : $defaultCompare;
		
		$join = array_shift($cond);
		$join = $join ? strtoUpper($join) : $defaultJoin;
		if( empty($where) || $where == '('){
			$join = '';
		}
		
		$isQuote = array_shift($cond);
		$isQuote = isset($isQuote) ? $isQuote : $defaultIsQuote;
		if(!$isQuote) {
			$value = is_array($value) ? '(' . implode(',',$value) . ')' : $value ;
			$item = " {$join} {$field} {$compare} {$value}" ;
		} else {
			$value = is_array($value) ? '(' . implode(',',$value) . ')' : $value ;
			// 参数占位符
			$value = $this->_db->quote($value);
			$item = " {$join} {$field} {$compare} {$value}" ;
		}
		
		$where .= $item;
	}
}
