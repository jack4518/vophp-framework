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
	 * 当前数据表的表名
	 * @var string
	 */
	protected $_name = '';

	/**
	 * 当前数据表的表结构信息
	 * @var string
	 */
	protected $_meta = '';	
	
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

	/**
	 * 查询方法存储器
	 * @var array
	 */
	protected $_sql_methods = array('field', 'distinct', 'foundrows', 'forceindex', 'from', 'where', 'order', 'limit', 'offset','page','having','group');

	/**
	 * 聚合方法存储器
	 * @var array
	 */
	protected $_sql_func = array('count', 'max', 'min', 'sum', 'avg');
	/**
	 * 是否统计记录总数
	 */
	private $_is_found_rows = false;

	/**
	 * 查询条件生成器
	 */
	private $_conder = null;
	
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
	 * DAO对象
	 * @var VO_Database_DAO
	 */
	private $_dao = null;

	/**
	 * 数据库分片ID
	 * @var int
	 */
	protected $_shard_id = 0;

	/**
	 * COMDB是否开启
	 * @var int
	 */
	protected $_is_comdb_enable = false;

	/**
	 * 构造函数
	 * @return void 
	 */
	public function __construct(){
		$this->_config = C();
		$this->getConder();
		$this->getDao();
		//$this->cacheField();
		$this->init();
	}
	
	/**
	 * 初始化模型
	 */
	public function init($db_config = array()){
		$comdb_config = C('comdb');
		$this->_is_comdb_enable = $comdb_config['enable'];
	}
	
	/**
	 * 获取当前绑定的表名
	 * @return	string	当前绑定的数据表名
	 */
	public function getName(){
		if(empty($this->_name)) {
            $this->_name = get_class($this);
        }
        return $this->_name;
	}

	/**
     * 获取数据库名称
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return string  真实表名
     */
    public function getDbName(){
        $db_config = $this->getDbConfig(false);
		return $db_config['database'];
    }  	
	
	/**
	 * 获取数据库实际表名
	 * @param	string	$name	数据表名
	 * @return	string	数据库实际表名(带前缀和后缀)
	 */
	public function getTableName($name=''){
		if(empty($name)){
			$name = $this->getName();
		}
		if(strpos($name, '#__') !== 0 ){
			//$name = '#__' . $name;	
		}
		$db_config = $this->getDbConfig('read');
		$name = $db_config['prefix'] . $name . $db_config['suffix'];
		return $name;
	}

	/**
	 * 获取DAO数据类型
	 * @param	string	$dao_type	DAO数据类型
	 * @return	string	DAO数据类型
	 */
	public function setDaoType($dao_type){
		return $this->_dao_type = $dao_type;
	}

	/**
	 * 获取DAO数据类型
	 * @return	string	DAO数据类型
	 */
	public function getDaoType(){
		return $this->_dao_type;
	}

	/**
	 * 获取数据库配置信息
	 * @param  bool  $is_write	是否写库
	 * @return array  对应的数据库配置信息
	 */
	public function getDbConfig($is_write=false){
		return $this->getDao()->getDbConfig($is_write);
	}
	
	/**
	 * 获取助手对象
	 * @param string  $name	助手名称
	 * @param string  $app	应用名称
	 * @param string  $namespace  命名空间
	 * @return VO_Helper_Abstract
	 */
	public function getHelper($name='', $app='', $namespace=''){
		if(!empty($name)){
			return VO_Helper::getInstance()->getHelper($name, $app, $namespace);
		}else{
			$this->triggerError('助手类名称不能空。');
		}
	}

	/**
     * 获取数据查询分析对象
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return VO_Model_conder  数据查询解析Conder对象
     */
    public function getConder(){
        if(!$this->_conder){
			$this->_conder = VO_Loader::load('model.conder', $this);
		}
        return $this->_conder;
    } 	

	/**
     * 获取表结构信息对象
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return VO_Model_Meta
     */
    public function getMeta(){
        if(!$this->_meta){
			$this->_meta = VO_Loader::load('model.meta', $this);
		}
        return $this->_meta;
    }

	/**
     * 获取数据DAO层对象
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return VO_Database_DAO  数据DAO层对象
     */
    public function getDao(){
        if(!$this->_dao){
			$this->_dao = VO_Loader::load('database.dao', $this);
		}
        return $this->_dao;
    }    

	/**
	 * 加载模型文件
	 * @param string  $name		模型名称
	 * @param string  $app	模块名称
	 * @return VO_Model		模型名称$name对应的模型对象
	 */
	public function loadModel($name, $app='', $db_config=array()){
		if(empty($app)){
			$app = strtolower(get_class($this));
		}
		return $this->getHelper('model')->loadModel($name, $app, $db_config);
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
			call_user_func_array(array($this->getConder(), $method), $args);
			return $this;
		}elseif(in_array(strtolower($method), $this->_sql_func)){
			$params = array(
				'method' => $method,
				'args' => $args,
			);
			$result = call_user_func_array(array($this, '_func'), $params);
			return $result;
		}elseif( strtolower(substr(trim($method), 0, 5)) == 'getby' ){
	       $field = substr($method, 5);
	       if(!isset($args[0])){
				$this->triggerError( __CLASS__ . ':' . $method . ' 方法的参数不能为空', E_USER_ERROR);
	       }else{
		       $params = array(
					'field' => $field,
					'value' => $args,
				);
				$result = call_user_func_array(array($this, '_getByField'), $params);
				return $result;
	       }
	   }else{
	       $this->triggerError( __CLASS__ . ':' . $method . ' 方法不存在', E_USER_ERROR);
	   }
	}
		
	/**
	 * 查询一条记录 
	 * @param array $db_config 	数据库配置信息
	 * @return array 查询结果集
	 */
	public function find($db_config=array()){
		$Builder = VO_Loader::load('model.builder', $this);
		$this->getConder()->limit(1);
		$sql = $Builder->buildFind($this->getConder());
		$connect = $this->getDao()->getSlaveConnect($db_config);
		$row = $connect->fetchRow($sql, $Builder->getParam());
		$this->getConder()->clearCondition();
		if($row){
			//$comdb = VO_Database_Comdb::getInstance();
			return $row;
		}else{
			$this->_error = $connect->getErrorMessage();
			return array();
		}
	}
	
	/**
	 * 查询多条记录
	 * @param array $db_config 	数据库配置信息
	 * @return array 查询结果集
	 */
	public function select($db_config=array()){
		$Builder = VO_Loader::load('model.builder', $this);
		$sql = $Builder->buildFind($this->getConder());
		$connect = $this->getDao()->getSlaveConnect($db_config);
		$rows = $connect->fetchAll($sql, $Builder->getParam());
		$this->getConder()->clearCondition();
		if($rows){
			return $rows;
		}else{
			$this->_error = $connect->getErrorMessage();
			return array();
		}
	}

	/**
	 * 处理聚合函数数据
	 * @param string $method  执行方法
	 * @param array $args 	执行参数
	 * @return array 查询聚合结果
	 */
	private function _func($method, $args=array()){
		$Builder = VO_Loader::load('model.builder', $this);
		$build_method = 'build' . ucfirst($method);
		$args_length = count($args);
		switch($args_length){
			case 1:
				$field = $args[0];
				$db_config = array();
				break;
			case 2:
				if($args_length == 2 && is_array($args[1])){
					$field = $args[0];
					$db_config = $args[1];
				}
				break;
			default:
				$field = '*';
		}
		$this->getConder()->field($field);
		$sql = $Builder->$build_method($this->getConder());
		$row = $this->getDao()->getSlaveConnect()->fetchRow($sql, $Builder->getParam());
		$this->getConder()->clearCondition();
		if($row){
			return array_shift($row);
		}else{
			$this->_error = $this->getDao()->getSlaveConnect()->getErrorMessage();
			return false;
		}
	}

	/**
	 * 处理聚合函数数据
	 * @param string $ield  查询字段
	 * @param array $xargs 	查询值
	 * @return array 查询结果集
	 */
	private function _getByField($field, $xargs=''){
		$field = VO_String::underLineToHumpMode($field);
		$value = array_shift($xargs);		
		$where = array($field => $value);
		return $this->where($where)->select();
	}
	
	/**
     * 通过setIsFoundRows设置SQL_CALC_FOUND_ROWS获取执行Limit取得的总数据,通过此方法获取
     * @return int|false
     */
    public function getFoundRows(){
		$sql = 'SELECT FOUND_ROWS()';
		return $this->getDao()->getSlaveConnect()->fetchOne($sql);
    }

	/**
	 * 插入数据
	 * @param array $data	保存的数组
	 * @param array $db_config  数据库配置
	 * @return  int | bool 如果插入成功返回最后一次插入的id,如果插入失败，返回false
	 */
	public function insert($data, $db_config=array(), $flag=0){
		if(!is_array($data) || empty($data)){
			$ret = false;
		}else{
			//处理字段
			$primary_key = $this->getMeta()->getPrimaryKey();
			$Builder = VO_Loader::load('model.builder');
			$to_data = array();
			$fields_info = $this->getMeta()->getColumnInfo();
			if($fields_info){
				foreach($fields_info as $name => $field){
					if( isset($data[$name]) ){
						//字段类型检查
						if( $this->_config['db']['field_type_check'] ){
							$to_data[$name] = $Builder->replaceColumnCheck($data[$name], $field['type']);
						}else{
							$to_data[$name] = $data[$name];
						}
					}
				}
				if(isset($data[$primary_key])){
					$old_id = $data[$primary_key];
				}else{
					$old_id = 0;
				}
				$id = $this->getDao()->getPrimaryValue($this->_shard_id, $old_id, $flag);
				if($id){
					$to_data[$primary_key] = $id;
				}
			}
			if(!empty($to_data)){
				//$ret = $this->getDao()->getMasterConnect($db_config)->insert($this->getTableName(), $to_data, $primary_key);
				$ret = 10;
				if($ret && ($ret !== true) && $this->_is_comdb_enable && $this->getDaoType() <> DAO_FAST){
					$comdb = VO_Database_Comdb::getInstance($this);
					$com_ret = $comdb->set($ret, $data);
					if(!$com_ret){
						$where = array($primary_key => $data[$primary_key]);
						$this->delete($where);
					}
				}else{
					return $to_data[$primary_key];
				}
			}else{
				$ret = false;
			}
			return $ret;
		}
	}

	/**
	 * 更新数据
	 * @param array $data	保存的数组
	 * @param array $where  更新的where条件
	 * @param array $db_config  数据库配置
	 * @return  bool 更新成功返回true,失败返回false
	 */
	public function update($data, $where=array(), $db_config=array()){
		if(!is_array($data) || empty($data)){
			$ret = false;
		}else{
			//处理字段
			$Builder = VO_Loader::load('model.builder');
			$to_data = array();
			$fields_info = $this->getMeta()->getColumnInfo();
			if($fields_info){
				foreach($fields_info as $name => $field){
					if( isset($data[$name]) ){
						//字段类型检查
						if( $this->_config['db']['field_type_check'] ){
							$to_data[$name] = $Builder->replaceColumnCheck($data[$name], $field['type']);
						}else{
							$to_data[$name] = $data[$name];
						}
					}
				}
			}
			//var_dump($to_data);
			//处理条件
			$primart_key = $this->getMeta()->getPrimaryKey();
			if(empty($where)){
				if(isset($to_data[$primart_key])){
					$where[$primart_key] = $to_data[$primart_key];
				}else{
					$this->triggerError('更新失败，update语句缺少WHERE条件，请确认后再试', E_USER_ERROR);
				}
			}
			unset($to_data[$primart_key]);

			$Builder = VO_Loader::load('model.builder', $this);
			$Conder = $this->getConder()->where($where);
			$where = $Builder->replace($Builder->buildWhere($Conder), $Builder->getParam());
			$ret = $this->getDao()->getMasterConnect($db_config)->update($this->getTableName(), $to_data, $where);
			$this->getConder()->clearCondition();
			if($ret){
				
			}
		
		}
		return $ret;
	}
	
	/**
	 * 删除记录
	 * @param array | string $where  删除条件
	 * @param array $db_config  数据库配置
	 * @return  bool 删除成功返回true,失败返回false
	 */
	public function delete($where=array(), $db_config=array()){
		if(empty($where)){
			return false;
		}else{
			
			if(is_string($where)){
				$primary_key = $this->getMeta()->getPrimaryKey();
				$where = array(
					$primart_key => $where,
				);
			}
			$Builder = VO_Loader::load('model.builder', $this);
			$Conder = $this->getConder()->where($where);
			$cond_array = $Conder->getAsArray();
			$sql = $Builder->buildDelete($this->getTableName(), $Conder);
			$this->getConder()->clearCondition();
			//$ret = $this->getDao()->getMasterConnect($db_config)->delete($this->getTableName(), $this->_conder);
			$ret = $this->getDao()->getMasterConnect($db_config)->query($sql, $Builder->getParam());
			return $ret;
		}
	}
	
	/**
	 * 保存数据
	 * @param array $data	保存的数组
	 * @param string $where  当为更新时的where条件
	 */
	public function add($data, $db_config=array()){
		return $this->insert($data, $db_config);
	}	
	
	/**
	 * 保存数据
	 * @param array $data	保存的数组
	 * @param string $where  当为更新时的where条件
	 */
	public function save($data, $where=null, $db_config=array()){
		$this->getMeta()->refresh();
		$primary_key = $this->getMeta()->getPrimaryKey();
		if( (!empty($where)) || ($primary_key && array_key_exists($primary_key, $data) && !empty($data[$primary_key]) ) ){
			return $this->update($data, $where, $db_config);
		}else{
			return $this->insert($data, $db_config);
		}
	}

	/**
	 * 返回最后一次插入的记录ID
	 * @return int 	最后一次插入记录的ID;
	 */
	public function getInsertId($db_config=array()){
		return $this->getDao()->getMasterConnect($db_config)->getInsertId();
	}
	
	/**
	 * 返回当前数据对象的主键
	 * 
	 * @return string;
	 */
	public function getPrimaryKey(){
		return $this->getMeta()->getPrimaryKey();
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
		$this->getMeta()->setPrimaryKey($key);
	}	
	
	/**
	 * 获取最后一次操作的SQL语句
     * @param  array  $db_config  数据库配置
	 * @return string
	 */
	public function getSql($db_config=array()){
		return $this->getDao()->getSlaveConnect($db_config)->getSql();
	}
	
	/**
     * 启动事务
     * @param  array  $db_config  数据库配置
     * @return bool  是否开启成功
     */
    public function startTrans($db_config=array())
    {
        $this->commit();
        return $this->getDao()->getMasterConnect($db_config)->startTransaction();
    }

    /**
     * 提交事务
     * @param  array  $db_config  数据库配置
     * @return boolean  是否提交成功
     */
    public function commit($db_config=array())
    {
        return $this->getDao()->getMasterConnect($db_config)->commit();
    }

    /**
     * 事务回滚
     * @param  array  $db_config  数据库配置
     * @return boolean  是否回滚成功
     */
    public function rollback($db_config=array())
    {
        return $this->getDao()->getMasterConnect($db_config)->rollback();
    }

    /**
     * 创建数据库
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $name  待创建数据库名称
     * @param  array  $db_config  数据库配置
     * @return bool  是否创建成功
     */
    public function createDatabase($name, $db_config=array()){
	    $ret = $this->getDao()->getMasterConnect($db_config)->createDatabase($name);
    }

	/**
     * 删除数据库
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $name  待删除数据库名称
     * @param  array  $db_config  数据库配置
     * @return bool  是否删除成功
     */
    public function dropDatabase($name, $db_config=array()){
	    $ret = $this->getDao()->getMasterConnect($db_config)->dropDatabase($name);
	}

    /**
     * 创建数据表
     *
     * 格式如下：
     *
     *          array(
     *                  'id'  => array(
     *                                  'type'      => 'INT(10) UNSIGNED',
     *                                  'default'   => 'NOT NULL AUTO_INCREMENT',
     *                                  'comment'   => 'id',
     *                                ),
     *                  ...
     *              );
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table    表名
     * @param  array   $columns  字段名
     * @param  array   $options  额外属性
     * @param  array  $db_config  数据库配置
     * @return bool
     */
    public function createTable($table, array $columns, $options=null, $db_config=array()){
	    $table = $this->getTableName($table);
	    $ret = $this->getDao()->getMasterConnect($db_config)->createTable($table, $columns, $options);
       	return $ret;
    }

    /**
     * 删除表
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table    表名
     * @return bool  是否删除成功
     */
    public function dropTable($table, $db_config=array()){
	    $table = $this->getTableName($table);
	    $ret = $this->getDao()->getMasterConnect($db_config)->dropTable($table);
       	return $ret;
    }

	/**
     * 重命名表名
	 * @param  string  $tablename  原表名
	 * @param  string  $tablename_new  新表名
     * @param  array  $db_config  数据库配置
     * @return boolean  是否修改成功
     */
    public function renameTable($tablename, $tablename_new, $db_config=array()){
	    $tablename = $this->getTableName($tablename);
	    $tablename_new = $this->getTableName($tablename_new);
	    $ret = $this->getDao()->getMasterConnect($db_config)->renameTable($tablename, $tablename_new);
       	return $ret;
    }

	/**
     * 清空表数据重建
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
     * @param  string  $table    表名
     * @param  array  $db_config  数据库配置
     * @return bool
     */
    public function truncateTable($table, $db_config=array()){
	     $table = $this->getTableName($table);
	    $ret = $this->getDao()->getMasterConnect($db_config)->truncateTable($table);
       	return $ret;
	}    

    /**
     * 添加表字段
     * @access  public
     * @author  Liu Guangzhao <guangzhao@leju.com>
	 * @param  string  $table  表名
	 * @param  string  $column  列名
	 * @param  string  $type  字段类型
	 * @param  string  $default  字段默认值
	 * @param  string  $is_null  字段是否允许为空
	 * @param  string  $comment  字段注释
	 * @param  string  $after  在某个字段之后
     * @param  array   $db_config  数据库配置
	 * @return  bool  是否添加成功
     */
    public function addColumn($column, $type, $default, $is_null=true, $comment='', $after=null, $db_config=array()){
	    $table = $this->getTableName();
		$ret = $this->getDao()->getMasterConnect($db_config)->addColumn($table, $column, $type, $default, $is_null, $comment, $after);
       	$this->getMeta()->refresh();
       	return $ret;
    }

    /**
     * 删除字段
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string   $column  字段名
     * @param  array    $db_config  数据库配置
	 * @return bool 是否移除成功
     */
    public function dropColumn($column, $db_config=array()){
	    $table = $this->getTableName();
       	$ret = $this->getDao()->getMasterConnect($db_config)->dropColumn($table, $column);
       	$this->getMeta()->refresh();
       	return $ret;
    }

    /**
     * 重命名表字段名
     * @access public
     * @author Chen QiQIng<cqq254@163.com>
	 * @param  string  $column  列名
	 * @param  string  $column_new  新列名
     * @param  array   $db_config  数据库配置
	 * @return bool 是否重命名成功
     */
    public function renameColumn($column, $column_new, $db_config=array()){
	    return $this->alterColumn($column, $column_new, null,  null, null, null, $db_config);
    }

	/**
     * 重命名表字段
     * @access public
     * @author Chen QiQIng<cqq254@163.com>
	 * @param  string  $column  列名
	 * @param  string  $column_new  新列名
	 * @param  string  $type  字段类型
	 * @param  string  $default  字段默认值
	 * @param  string  $allow_null  字段是否允许为空
	 * @param  string  $comment  字段注释
     * @param  array   $db_config  数据库配置
	 * @return bool 是否重命名成功
     */
    public function alterColumn($column, $column_new, $type=null, $default=null, $allow_null=null, $comment=null, $db_config=array()){
	    $column_info = $this->getMeta()->getByColumnName($column);
	    if(empty($column_new)){
		    $this->triggerError('修改字段名"' . $column .'",但新字段名不能为空', E_USER_ERROR);
		    return false;
	    }
	    if(empty($column_info)){
		    $this->triggerError('数据库字段:"' . $column . '"不存在,请确认', E_USER_ERROR);
		    return false;
	    }else{
		    $type = is_null($type) ? $column_info['db_type'] : $type;
		    $default = is_null($default) ? $column_info['default'] : $default;
		    if(is_null($allow_null)){
			    $allow_null = is_null($column_info['allowNull']) ? true : false;
		    }
		    $comment = is_null($comment) ? $column_info['comment'] : $comment;
		    $table = $this->getTableName();
	       	$ret = $this->getDao()->getMasterConnect($db_config)->alterColumn($table, $column, $column_new, $type, $default, $allow_null, $comment);
	       	$this->getMeta()->refresh();
	       	return $ret;
	   	}
    }

    /**
     * 新增主键
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
     * @param   string  $table  表名
     * @param   array|strings   $column  列名,如果为字符串，刚以,分隔
     * @return  bool  是否添加成功
     */
    public function addPrimaryKey($name, $columns){
	    $table = $this->getTableName();
       	$ret = $this->getDao()->getMasterConnect($db_config)->addPrimaryKey($table, $name, $column);
       	$this->getMeta()->refresh();
       	return $ret;
    }

    /**
     * 删除主键
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param   string  $name   主键名称
     * @param   string  $table  表名
     * @return bool  是否删除成功
     */
    public function dropPrimaryKey($name){
	    $table = $this->getTableName();
       	$ret = $this->getDao()->getMasterConnect($db_config)->dropPrimaryKey($table, $name);
       	$this->getMeta()->refresh();
       	return $ret;
    }

    /**
     * 创建索引
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table   表名
     * @param  string  $index_name    索引名称
     * @param  array   $columns  索引列,如果多个为多列组合索引
     * @param  bool    $unique  是否唯一索引
     * @return bool    是否创建成功
     */
    public function createIndex($index_name, $columns, $unique=false){
	    $table = $this->getTableName();
       	$ret = $this->getDao()->getMasterConnect($db_config)->createIndex($table, $index_name, $columns, $unique);
       	$this->getMeta()->refresh();
       	return $ret;
    }

    /**
     * 删除索引
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $index_name  索引名称
     * @param  string  $table  索引所在表名
     * @return bool  是否删除成功
     */
    public function dropIndex($name){
	    $table = $this->getTableName();
       	$ret = $this->getDao()->getMasterConnect($db_config)->dropIndex($table, $name);
       	$this->getMeta()->refresh();
       	return $ret;
    }  
}
