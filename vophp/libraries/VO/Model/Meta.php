<?php
/**
 * 定义VO_Model_Meta 表结构信息处理器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen<cqq254@163.com>
 * @package VO
 * @since version 1.0
 * @date 2010-05-25
 **/
class VO_Model_Meta extends VO_Object
{
    /**
     * 内部对象前缀
     * @var string
     */
    const PREFIX = '_';

    /**
     * 字段对象KEY
     * @var string
     */
    const COLUMNS= 'columns';

    /**
     * 主键字段KEY
     * @var string
     */
    const PRIMARY= 'primaryKey';

    /**
     * 表绑定的Model对象
     * @access private
     * @var object
     */
    private $_model     = null;

    /**
     * 表结构字段数组
     * @access private
     * @var array
     */
    private $_schema    = null;

    /**
     * 自增字段
     * @access private
     * @var array
     */
    private $_autoinc   =array();

    /**
     * 自动更新字段
     * @access private
     * @var array
     */
    private $_update    = array();

    /**
     * 物理主键（多个主键用数组保存）
     * @access private
     * @var mixed
     */
    private $_primaryKey= null;

    //-------------- 以下为实时获取信息 --------------

    /**
     * 索引表信息
     * @access private
     * @var array
     */
    private $_status    = array();

    /**
     * 索引信息
     * @access private
     * @var array
     */
    private $_index     = array();

    /**
     * MySQL环境变量
     * @access private
     * @var array
     */
    private $_variables = array();

    /**
     * 初始化
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  object  $model
     * @return void
     */
    public function __construct($model){
        $this->_model = $model;
        $this->bind();
    }

    /**
     * 绑定到物理表上
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return bool  是否绑定成功
     */
    public function bind()
    {
	    if(empty($this->_schema)){
	        $db_config = $this->_model->getDbConfig('read');
	        $db_name = $db_config['database'];
	        $table = $this->_model->getTableName();
			//如果开启了缓存表字段设置并且Cache中有表结构信息,则从Cache中取信息
			if(C('db.field_cache') == true){
	            $cache_key = self::getCacheKey($db_name, $table);
	            $cache_fields   = VO_Loader::load('cache')->get($cache_key);
	            if($cache_fields){
					$table_schema = json_decode($cache_fields, true);
					if($table_schema){
						foreach($table_schema[self::COLUMNS] as $name => $item){
							$column_schema= VO_Loader::load('model.columnschema');
							foreach($item as $k => $v){
								//var_dump($item);
								if(property_exists($column_schema, $k)){
									$column_schema->$k = $v;
								}
							}
							$this->_schema[$name] = $column_schema->toArray();
							$column_schema->onUpdate && $this->_update[] = $column_schema->name;
							$column_schema->autoIncrement && $this->_autoinc[] = $column_schema->name;
						}
						$this->_primaryKey = $table_schema[self::PRIMARY];
						if(!empty($this->_schema)){
							return true;
						}
					}
	            }
	        }
       }else{
			return $this->_schema;
       }
       return $this->refresh();
    }

    /**
     * 从数据库取出表结构信息并刷新缓存
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return bool
     */
    public function refresh()
	{
		$Cacher = VO_Loader::load('cache');
		$name = $this->_model->getTableName();
        if(!$name){
			return false;
		}
        $sql = sprintf('SHOW FULL COLUMNS FROM `%s`', $name);
		$columns = $this->_model->getDao()->getSlaveConnect()->fetchAll($sql);
		//var_dump($this->_model->getDao()->getSlaveConnect()->getFields($name));
		//var_dump($columns);
        if(!$columns){
			$this->triggerError($name . ' table does not exists', E_USER_ERROR);
			exit;
		}else{
			//索引表结构信息
			$this->_schema = array();
			$this->setPrimaryKey(null);
			foreach($columns as $column){
				$column_schema = $this->createColumn($column);
				$name = $column_schema['name'];
				$this->_schema[$name] = $column_schema;
				if(!$column_schema['isPrimaryKey']){
					continue;
				}

				//设置当前主键
				if(!isset($this->_primaryKey) || null === $this->_primaryKey){
					$this->_primaryKey = $column_schema['name'];
				}elseif(is_string($this->_primaryKey)){
					$this->_primaryKey = array($this->_primaryKey, $column_schema['name']);
				}else{
					$this->_primaryKey[] = $column_schema['name'];
				}
			}

			//将表结构信息刷新到缓存
			if($Cacher && C('db.field_cache') == true){
				$table = array(
					self::COLUMNS => $this->_schema,
					self::PRIMARY => $this->_primaryKey,
				);
				if(C('db.field_cache') == true){
					$db_name    = $this->_model->getDbName();
					$table_name = $this->_model->getTableName();
					$cacheKey  = self::getCacheKey($db_name, $table_name);
					$Cacher->set($cacheKey, json_encode($table));
				}
			}

			if(null != $this->_schema){
				return true;
			}else{
				return false;
			}
		}
    }

    /**
     * 获取索引信息
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array
     */
    public function getIndex()
    {
        $this->_index = null;
        $table_name = $this->getTableName();
        if(!$table_name){
			return false;
		}
        $sql = sprintf('SHOW INDEX FROM `%s`', $table_name);
		$columns = $this->_model->getDao()->getSlaveConnect()->fetchAll($sql);
        if(empty($columns)){
			$this->triggerError($table_name.' table does not exists');
		}else{
			return $this->_index = $columns;
		}
    }

    /**
     * 物理表状态信息
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array 当前表状态信息
     */
    public function getStatus()
    {
        $this->_status = null;
        $table_name = $this->getTableName();
        if(!$table_name){
			return false;
		}
        $sql = sprintf("SHOW TABLE STATUS WHERE NAME='%s'", $table_name);
		$columns = $this->_model->getDao()->getSlaveConnect()->fetchAll($sql);
        if(empty($columns)){
			$this->triggerError($table_name.' table does not exists');
		}else{
			return $this->_status = $columns[0];
		}
    }

    /**
     * MySQL环境变量
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array  MySQL服务的环境变量数组信息
     */
    public function getVariables()
    {
        $this->_variables = array();
		$table_name = $this->getTableName();
        if(!$table_name){
			return false;
		}
        $sql = 'SHOW VARIABLES';
		$columns = $this->_model->getDao()->getSlaveConnect()->fetchAll($sql);
        if($columns){
			foreach($columns as $item){
				$this->_variables[$item['Variable_name']] = $item['Value'];
			}
		}else{
			$this->triggerError("Can't get MySQL variables");
		}
        return $this->_variables;
    }

    /**
     * 生成表结构信息缓存Key
     * 格式：库名_表名_框架版本号
     *
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $db_name   数据名
     * @param  string  $table_name   表名
     * @return string
     */
    public static function getCacheKey($db_name, $table_name)
    {
        return sprintf('%s_%s_%s', $db_name, $table_name, VOPHP_VERSION);
    }

    /**
     * 删除表结构缓存
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $db_name  数组库名称
     * @param  string  $table_name 表名称
     * @return bool   是否删除成功
     */
    public static function flushTableSchema($db_name, $table_name)
    {
        $cacheKey = self::getCacheKey($db_name, $table_name);
		if(Slae::app()->cache){
			return  Slae::app()->cache->del($cacheKey);
		}else{
			return false;
		}
    }

    /**
     * 实例化字段对象
     * @access private
     * @author Chen QiQing <qiqing@leju.com>
     * @param  array   $column  字段数组信息
     * @return object  VO_ColumnSchema对象
     */
    private function createColumn($column)
    {
        $c = VO_Loader::load('model.columnschema');
        $c->name = $column['Field'];
        $c->allowNull = $column['Null']==='YES';
        $c->isPrimaryKey = false !== strpos($column['Key'],'PRI');
        $c->isForeignKey = false;
        $c->init($column['Type'], $column['Default']);
        $c->autoIncrement = false !== strpos(strtolower($column['Extra']),'auto_increment');
        $c->onUpdate = $column['Type']==='timestamp' && $column['Extra']==='on update CURRENT_TIMESTAMP';
        $c->comment=$column['Comment'];
        $c->onUpdate && $this->_update[] = $c->name;
        $c->autoIncrement && $this->_autoinc[] = $c->name;
        return $c->toArray();
    }

    /**
     * 返回是否存在指定字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $name
     * @return bool
     */
    public function hasColumn($name)
    {
        //暂时没有脚手架只能包含所有字段
        return isset($this->_schema[$name]);
    }

    /**
     * 索引表是否含有指定字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $name  字段名称 
     * @return bool
     */
    public function hasIndexColumn($name)
    {
        return $this->isIndexColumn($name);
    }

    /**
     * 返回是否为索引表字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $name
     * @return bool
     */
    public function isIndexColumn($name)
    {
        return isset($this->_schema[$name]) && $this->_schema[$name]->isIndexColumn();
    }

    /**
     * 返回是否为Data表字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $name  是否为Data表字段
     * @return bool
     */
    public function isDataColumn($name)
    {
        return !$this->isIndexColumn($name);
        //return isset($this->_schema[$name]) && $this->_schema[$name]->isDataColumn();
    }

    /**
     * 返回该表是否使用InnoDB引擎
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return bool 是否为InnoDB引擎
     */
    public function isInnoDB()
    {
        return !strcasecmp('InnoDB', $this->Engine);
    }

    /**
     * 返回该表是否使用MyISAM引擎
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return bool  是否为MyISAM引擎
     */
    public function isMyISAM()
    {
        return !strcasecmp('MyISAM', $this->Engine);
    }

    /**
     * 获取索引表字段结构信息
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array 包含索引表的所有字段结构信息
     */
    public function getIndexSchema()
    {
        $array = array();
        foreach($this->_schema as $col => $item){
			if($item->isIndexColumn()){
				$array[$col] = $item;
			}
        }

        return $array;
    }

    /**
     * 返回索引表字段数组
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array 索引表字段名称数组
     */
    public function getIndexColumn()
    {
        $array = array();
        foreach($this->_schema as $item){
			if($item->isIndexColumn()){
				$array[] = $item->name;
			}
        }

        return $array;
    }

    /**
     * 返回Data表字段数组
     * 注意：非索引表字段信息无法获取
     *
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array  包含所有Data表字段的名称数组
     */
    public function getDataColumn()
    {
        $array = array();
        foreach($this->_schema as $item){
			if($item->isDataColumn()){
				$array[] = $item->name;
			}
        }
        return $array;
    }

    /**
     * 返回所有字段名称数组
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array 所以字段名称数组
     */
    public function getAllColumnName()
    {
        return array_keys($this->_schema);
    }

    /**
     * 返回指定字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $name  字段名称
     * @return array 所以字段名称数组
     */
    public function getByColumnName($name)
    {
	    if(isset($this->_schema[$name])){
        	return $this->_schema[$name];
    	}else{
	    	return array();
    	}
    }

    public function getColumnInfo(){
	    return $this->_schema;
    }

    /**
     * 返回字段默认值数组
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string  $name  字段名称
     * @return string|array   字段默认值
     */
    public function getColumnDefault($name=null)
    {
        if(empty($name)){
            $array = array();
            foreach($this->_schema as $item){
				$array[$item->name] = $item->default;
			}
            return $array;
        }else{
			if(isset($this->_schema[$name])){
				return $this->_schema[$name]->default;
			}else{
				return null;
			}
        }
    }

    /**
     * 返回当前时间戳字段名
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return mixed
     */
    public function getCurTimeColumn()
    {
        foreach($this->_schema as $item){
            if('timestamp' == $item->db_type && null == $item->default){
				return $item->name;
			}
        }

        return false;
    }

    /**
     * 返回更新当前时间戳字段名
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return mixed
     */
    public function getOnCurTimeColumn()
    {
        foreach($this->_schema as $item){
            if('timestamp' == $item->db_type && null == $item->default && $item->onUpdate){
				return $item->name;
			}
        }

        return false;
    }

    /**
     * 返回更新字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array
     */
    public function getUpdateColumn()
    {
        return $this->_update;
    }

    /**
     * 返回表自增字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return string 当前表自增字段名称 
     */
    public function getAutoIncColumn()
    {
        return $this->_autoinc;
    }

    /**
     * 返回所有允许为空字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array 所有允许为空的字段数组
     */
    public function getAllowNull()
    {
        $columns = array();
        foreach($this->_schema as $col){
			if($col->allowNull){
				$columns[] = $col->name;
			}
        }

        return $columns;
    }

    /**
     * 返回所有禁止为空的字段
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return array 所有禁止为空的字段数组
     */
    public function getDenyNull()
    {
        $columns = array();
        foreach($this->_schema as $col){
			if(!$col->allowNull){
				$columns[] = $col->name;
			}
        }

        return $columns;
    }

    /**
     * 返回主键
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  void
     * @return mixed 当前表的主键名称
     */
    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }

    /**
     * 设置主键
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @param  string $primary_key  主键 
     * @return mixed  设置后的主键
     */
    public function setPrimaryKey($primary_key = null)
    {
		$this->_primaryKey = $primary_key;
		return $this->_primaryKey;
	}
}
