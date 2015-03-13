<?php
/**
 * 定义 VO_Nosql_Mongodb MongoDB类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author  <qiqing>cqq254@163.com
 * @package VO
 * @since version 1.0
 * @date 2014-07-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Nosql_Mongodb extends VO_Object{
    /**
     * 单一实例存储器
     * @var VO_Nosql_Mongodb
     */
    static protected  $_instance;

	/**
     * MongoDB数据库对象,写库对象
     * @var mongo
     */
    private $_write_db = null;

	/**
     * MongoDB数据库对象,读库对象
     * @var mongo
     */
    private $_read_db = null;

	/**
     * Mongo实例化写对象
     * @var mongo
     */
    private $_write_mongodb = null;

	/**
     * Mongo实例化读对象
     * @var mongo
     */
    private $_read_mongodb = null;    

	/**
     * 写库集合
     * @var string
     */
    private $_write_collection = '';

	/**
     * 读库集合
     * @var string
     */
    private $_read_collection = '';    

    /**
     * 配置信息
     * var array
     */
    protected $_configs = array();

    /**
     * 附加信息
     * var array
     */
    protected $_options = array();

    /**
     * 返回的字段列表 
     * @access private
     * @var array
     */
    private $_fields = array();    

    /**
     * 查询条件 
     * @access private
     * @var array
     */
    private $_where = array();

    /**
     * 解析后的查询条件数组
     * @access private
     * @var array
     */
    private $_cond = array();

    /**
     * 结果集大小
     * @access private
     * @var int
     */
    private $_limit = 10;

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
    private $_order = array(); 

    /**
     * 构造函数
     * @param array $config  配置信息
     * @param array $options 附加信息
     * @return void
     */
    public function __construct($configs=array(), $options=array()){
        if(!extension_loaded('mongo')){
	        $this->triggerError('无法载入Mongodb扩展，请通过php.ini确认MongoDB扩展是否安装成功');
        }
        $this->_configs = $configs;
        $this->_options = $options;
        
    }

    /**
	 * 获取单一实例
	 * @return VO_Nosql_MongoDB
	 */
    static public function getInstance($config=array(), $options=array()){
        if(!self::$_instance){
		    self::$_instance = new self($config, $options);
		}
	    return self::$_instance;
    }

    /** 
     * 选择一个集合，相当于选择一个数据表 
     * @param string $collection 集合名称 
     */  
    public function selectCollection($collection){
	    $collection = $this->getTrueCollection($collection);
        $this->_write_collection = $this->getSlaveConnect()->selectCollection($collection);
        $this->_read_collection = $this->getSlaveConnect()->selectCollection($collection);  
    }

    /** 
     * 新增数据 
     * @param array $data 需要新增的数据 例如：array('title' => '1000', 'username' => 'xcxx') 
     * @param array $option 参数 
     */  
    public function insert($data, $option=array()) {  
        return $this->_write_collection->insert($data, $option);  
    }

    /** 
     * 批量新增数据 
     * @param array $data 需要新增的数据 例如：array(0=>array('title' => '1000', 'username' => 'xcxx')) 
     * @param array $option 参数 
     */  
    public function insertAll($data, $option=array()) {  
        return $this->_write_collection->batchInsert($data, $option);  
    }  
      
    /** 
     * 保存数据，如果已经存在在库中，则更新，不存在，则新增 
     * @param array $data 需要新增的数据 例如：array(0=>array('title' => '1000', 'username' => 'xcxx')) 
     * @param array $option 参数 
     */  
    public function save($data, $option=array()) {  
        return $this->_write_collection->save($data, $option);  
    }  
      
    /** 
     * 根据条件移除 
     * @param array $query  条件 例如：array(('title' => '1000')) 
     * @param array $option 参数 
     */  
    public function delete($query, $option=array()) {  
        return $this->remove($query, $option);  
    }

    /** 
     * 根据条件移除 
     * @param array $query  条件 例如：array(('title' => '1000')) 
     * @param array $option 参数 
     */  
    public function remove($query, $option=array()) {  
        return $this->_write_collection->remove($query, $option);  
    }

    /** 
     * 删除整个集合 
     * @param array $query  条件 例如：array(('title' => '1000')) 
     * @param array $option 参数 
     */  
    public function drop() {  
        return $this->_write_collection->drop();  
    }    
      
    /** 
     * 根据条件更新数据 
     * @param array $query  条件 例如：array(('title' => '1000')) 
     * @param array $data   需要更新的数据 例如：array(0=>array('title' => '1000', 'username' => 'xcxx')) 
     * @param array $option 参数 
     */  
    public function update($query, $data, $option=array()) {  
        return $this->_write_collection->update($query, $data, $option);  
    }  
      
    /** 
     * 根据条件查找一条数据 
     * @param array $query  条件 例如：array(('title' => '1000')) 
     * @param array $fields 参数 
     */  
    public function findOne($query, $fields=array()){
	    $field = $this->_applyCondition($this->_fields);
	    $where = $this->_applyCondition($this->_where);
	    if(!empty($query)){
		    $where = array_merge($where, $query);
	    }
	    if(!empty($fields)){
		    $field = array_merge($field, $fields);
	    }
	    return $this->_read_collection->findOne($where, $field);
    }  
      
    /** 
     * 根据条件查找多条数据 
     * @param array $query 查询条件 
     * @param array $order  排序条件 array('age' => -1, 'username' => 1) 
     * @param int   $offset 查询偏移 
     * @param int   $limit 查询到的数据条数 
     * @param array $fields返回的字段 
     */  
    public function find($query, $order=array(), $limit=0, $offset=0, $fields=array()){
	    $field = $this->_applyCondition($this->_fields);
	    $where = $this->_applyCondition($this->_where);
	    if(!empty($query)){
		    $where = array_merge($where, $query);
	    }
	    if(!empty($fields)){
		    $field = array_merge($field, $fields);
	    }
        $cursor = $this->_read_collection->find($where, $field); 
        if($order){
	        $order = array_merge($this->_order, $order);
	    }else{
		    $order = $this->_order;
	    }
	    var_dump($order);
	    if(!empty($order)){
		    $cursor->sort($order);
	    }

	    if(!$offset){
		    $offset = $this->_offset;
	    }
	    if(!empty($offset)){
		    $cursor->skip($offset);
	    }

	    if(!$limit){
		    $limit = $this->_limit;
	    }
         if(!empty($limit)){
        	$cursor->limit($limit);
    	}
        $results = iterator_to_array($cursor);
        $rows = array();
        if($results){
	    	foreach($results as $k => $item){
	        	$rows[] = $item;
        	}
	    }   
        return $rows;
    }

    /** 
     * Field 条件
     */  
    public function field($field){
	    $this->_fields = $field;
	    return $this;
    }

    /** 
     * Limit 条件
     */  
    public function limit($limit){
	    $this->_limit = $limit;
	    return $this;
    }

    /** 
     * Offset 篇移
     */  
    public function offset($offset){
	    $this->_offset = $offset;
	    return $this;
    }

    /** 
     * Order 排序
     */  
    public function order($order){
	    $order_tmp = array();
	    if(!is_array($order) && !empty($order)){
		    $orders = explode(',', $order);
		    foreach($orders as $k => $item){
			    if(empty($item)){
				    continue;
			    }
			    $temp = explode(' ', $item);
			    $field = trim($temp[0]);
			    if(isset($temp[1])){
				    switch( strtoupper($temp[1]) ){
					    case 'DESC' :
					    	$order_tmp[$field] = -1;
					    	break;

					    case 'ASC' :
					    default: 
					    	$order_tmp[$field] = 1;
					    	break;
				    }
			    }else{
					$order_tmp[$field] = 1;
			    }
		    }
	    }else{
		    foreach($order as $k => $item){
			    if(!is_array($item)){
				   switch( strtoupper($item) ){
					    case 'DESC' :
					    	$order_tmp[$k] = -1;
					    	break;

					    case 'ASC' :
					    	$order_tmp[$k] = 1;
					    	break;
					    default: 
					    	$order_tmp[$k] = $item;
					    	break;
				    }
			    }else{
				    
			    }
		    }
		    //var_dump($order_tmp);
	    }
	    $this->_order = $order_tmp + $this->_order;
	    return $this;
    }           

	/** 
     * Where 条件
     */  
    public function where($where){
	    $this->_where = $where;
	    return $this;
    }
      
    /** 
     * 数据统计 
     */  
    public function count(){
        return $this->getSlaveConnect()->count();  
    }

    /** 
     * 去重 
     */  
    public function distinct($query){
	    $where = $this->_applyCondition($this->_where);
	    if(!empty($query)){
		    $where = array_merge($where, $query);
	    }
        return $this->getSlaveConnect()->distinct($where);  
    }

 	/**
    * 创建索引：如索引已存在，则返回。
    * @param  string  $table_name  表名
    * @param  array   $index       索引  例如:array("id"=>1) 在id字段建立升序索引
    * @param  array   $params      其它条件  例如：是否唯一索引等
    * @return  bool   成功：true   失败：false
    */
    public function ensureIndex($table_name, $index, $params=array()){
        $dbname = $this->curr_db_name;
        $params['safe'] = 1;
        try{
            $this->getMasterConnect()->$table_name->ensureIndex($index, $params);
            return true;
        }catch(MongoCursorException $e){
            $this->triggerError($e->getMessage());
            return false;
        }
    }  

    /** 
     * 创建集合 
     */  
    public function createCollection($name){
        return $this->getMasterConnect()->createCollection($name);
    }     

    /** 
     * 删除数据库 
     */  
    public function dropDatabase($name){
        return $this->getMasterConnect()->dropDB($name);
    }  
    
    /** 
     * 获取所有数据库信息 
     */  
    public function getDatabases(){
        $rows = $this->_read_mongodb->listDbs(); 
        if(!empty($rows)){
	        return $rows['databases'];
        }
    }  

    /** 
     * 获取当前库的所有集合 
     */  
    public function getCollections(){
        return $this->getSlaveConnect()->getCollectionNames(); 
    }

	/** 
     * 获取真实的集合名称，即加上配置的表前缀和后缀 
     */ 
    public function getTrueCollection($collection){
	    $this->_parseConfig();
	    $prefix = $this->_configs['write']['prefix'];
	    $suffix = $this->_configs['write']['suffix'];
	    return $prefix . $collection . $suffix;
    }
    
    /** 
     * 数据统计 
     */  
    public function close(){
        $this->_read_mongodb->close();
        $this->_write_mongodb->close();  
    }    
      
    /** 
     * 错误信息 
     */  
    public function getError(){
        $error = $this->getMasterConnect()->lastError();
        if(empty($error)){
	        $error = $this->getSlaveConnect()->lastError();
        }
        return $error;
    }  
      
    /** 
     * 获取集合对象 
     */  
    public function getCollection(){
        return $this->_read_db;  
    }  
      
    /** 
     * 获取DB对象
     * @param  bool  $is_write  是否写库
     * @return MongoDB对象
     */  
    public function getDb($is_write=false) {  
        if($is_write){
	        return $this->getMasterConnect();
        }else{
	        return $this->getSlaveConnect();
        }
    }

    /**
     * 获取写库操作对象
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @return mixed 
     */
    public function getMasterConnect($config, $options){
	    if(!empty($this->_write_db)){
		    return $this->_write_db;
	    }else{
    	    $return = $this->_getDbConnect(true, $config, $options);
        	$this->_write_db =  $return['db_instance'];
        	$this->_write_mongodb = $return['mongodb_instance'];
        	return $return['db_instance'];
    	}
    }

    /**
     * 获取从库操作对象（vsid不传则返回最近使用的分库）
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  mixed  $vsid  Shard ID
     * @return mixed
     */
    public function getSlaveConnect(){
	    if(!empty($this->_read_db)){
		    return $this->_read_db;
	    }else{
        	$return = $this->_getDbConnect(false, $config, $options);
        	$this->_read_db =  $return['db_instance'];
        	$this->_read_mongodb = $return['mongodb_instance'];
        	return $return['db_instance'];
    	}
    }

    /**
     * 获取数据库操作对象
     * @access private 
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  bool   $is_write  是否连接写库
     * @param  mixed  $vsid  Shard ID
     * @return mixed
     */
    private function _getDbConnect($is_write=false, $config, $options){
	    if(empty($config)){
	        $db_config = $this->_parseConfig();
	        if(!$db_config){
				$this->triggerError('无法获取MongoDB数据库配置信息', E_USER_ERROR);
				return false;
			}
	        if($is_write){
		        $config = $db_config['write'];
	        }else{
		        $config = $db_config['read'];
	        }
		}

        if($config['host'] == ''){
	        $config['host'] = '127.0.0.1';
        }
        if($config['port'] == ''){
	        $config['port'] = '27017';
        }
        if(!$config['option']){
	        $config['option'] = array('connect' => true);  
        }
        $server = 'mongodb://' . $config['host'] . ':' . $config['port'];
        $mongo = new Mongo($server, $options);  
        if($config['database'] == ''){
	        $config['database'] = 'test';
	    }  
        $instance = $mongo->selectDB($config['database']);  
        if($config['user'] != '' && $config['password'] != ''){ 
            $instance->authenticate($config['user'], $config['password']);
        }
        $return  = array(
			'db_instance' => $instance,
			'mongodb_instance' => $mongo,
        );
        return $return;
    }

    /**
     * 解析MongoDB配置信息
     * @param array $config  配置信息
     * @return void
     */
    private function _parseConfig($config=array()){
	    if(empty($config)){
            $config = C('mongodb');
        }
        switch(strtoupper($config['config_type'])){
	        case 'SERVER' :
	        	$mongodb_server_key = $config['server_mongodb_key'];
	        	$mongodb_configs = VO_Http::getServer($mongodb_server_key);
	        	$mongodb_configs = trim($mongodb_configs);
	        	if(!empty($mongodb_configs)){
		        	$configs = explode(' ', $mongodb_configs);
		        	$config = array();
		        	foreach($configs as $item){
			        	if(empty($item)){
				        	continue;
			       		}else{
				        	$tmp = explode(':', $item);
				        	$len = count($tmp);
				        	if($len == 2){
					        	$host = trim($item[0]);
					        	$port = trim($item[1]);
				        	}elseif($len == 1){
					        	$host = trim($item[0]);
					        	$port = 11211;
				        	}
				        	$config[] = array(
								'host'    	=> 	$host,  //MongoDB主机名
								'port' 		=> 	$port,  //MongoDB连接端口
								'user' 		=> 	'',     //数据库用户名
							    'password'	=> 	'',     //数据库密码
							    'collection'	=> 	'', //数据库集合名称
						    );
			        	}
		        	}
		        	$this->_configs = $config;
	        	}else{
		        	$this->triggerError('服务器不存在Key为:"' . $mongodb_server_key .'"的MongoDB配置信息,请确认');
	        	}
	        	break;
	        	
	        case 'FILE' :
	        default:
	        	$this->_configs['write'] = $config['write'];
	        	$this->_configs['read'] = $config['read'];
	        	break;
        }
        return $this->_configs;
    }

    /**
     * 处理条件解析(具体解析)
     * 例如：
     * $where = array(
     * 		//或条件的解析
	 *		'username' => array(array('启', 'i'), 'regex', 'OR', 
	 *			array(
	 *				'age' => array(20, '='),
	 *				'hobby' => array(array('唱歌'), 'IN'),
	 *			),
	 *		),
	 *		'username' => array('李四', '='),
	 *		'age' => array(array(10, 20), 'IN'),
	 *		'age' => array(array(10, 0), 'MOD'),
	 *		'age' => array(array(10, 32), 'NOT IN'),
	 *		'age' => array(array(20, 30), 'ALL'),
	 *		'age' => array(true, 'EXISTS'),
	 *		'age' => array(
	 *			array( true, 'EXISTS', 'OR', array(20, 'IN') ),
	 *		),
	 *		'hobby' => array(
	 *			array(
	 *				'school' => array('跳舞', 'IN'),
	 *				'abc' => array('10', '>'),
	 *			),
	 *			'ELEMMATCH',
	 *		),
	 *		'age' => array(array(10, 30), 'BETWEEN'),
	 *		'username' => array(array('张', 'i'), 'REGEX'),
	 *	);
	 *
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $sql
     * @param  array   $condition
     * @return string
     */
    private function _applyCondition($condition, $sub_field=''){
        $logic = '';
        $join = '$and';
        $last_join = '$and';
        $where = array();
		$temp = $condition;
		$is_group = false;

		if(empty($condition)){
			return $where;
		}
		//处理条件
		$sub_storage = array();
		$or_storage = array();
		foreach($condition as $field => $cond){
			//处理字段名称
			if(is_int($field)){
				/*
				//如果key是标题,不是字段名称,则表示此条件可能是带括号的子条件,或者是相同字段的多条件组合,
				//例如(多字段):'category' => array(
			                        array('first', '=', 'OR'),
						            array('recommend', '=', 'OR'),
					                array('20', '='),
								 ), 
				 */
				$field = $sub_field;
				if(!empty($sub_field)){
					if( !is_array($cond) ){
						$cond = array(array($cond), 'IN');
					}elseif(isset($cond[1]) && trim($cond[1]) == '=' ){
						$cond = array($cond[0], 'IN');
					}elseif(count($cond)<=2 || isset($cond[1]) && trim($cond[1]) == '=' ){
						$cond[0] = (array)$cond[0];
						$cond = array($cond[0], 'IN');
					}
					
					//处理简单条件查询,如: array('id' => array(20, '='))
					$logic = $this->_parseLogic($cond);
					$sub_storage[$field][$logic] = $cond[0];
						
					$where = array_merge($where, $sub_storage);
					continue;
				}
			}
			if(!is_array($cond)){
				//处理简单条件查询,如: array('id' => 10)
				$logic = '';
				$where[$field] = $cond;
			}elseif(isset($cond[1]) && trim($cond[1]) == '=' ){
				//处理简单条件查询,如: array('id' => array(20, '='))
				$logic = '';
				$join = isset($cond[2]) && !empty($cond[2]) ? strtoupper(trim($cond[2])) : ''; //连接符
				$join_cond = isset($cond[3]) && !empty($cond[3]) ? $cond[3] : ''; //连接对象
				$joins = array('OR', 'AND');
				if( $join && in_array($join, $joins) && $join_cond ){
					$return = $this->_applyCondition($join_cond, $field);
					$mongo_join = '$' . strtolower($join);
					$where[$mongo_join] = array(
						array($field => $cond[0]),
						$return,
					);
				}else{
					$where[$field] = $cond[0];
				}
			}else{
				//处理逻辑关系
				$logic = $this->_parseLogic($cond);
				$logic = empty($logic) ? '' : $logic;
				switch($logic){
					case '$lt':
					case '$lte':
					case '$gt':
					case '$gte':
					case '$ne':
					case '$exists':
					case '$size':
						$return = $this->_applyConditionBase($cond, $field, $logic);
						$where = array_merge($where, $return);
						break;

					case '$between':
						$is_group = true;
						$return = $this->_applyConditionBetween($cond, $field);
						$where = array_merge($where, $return);
						break;

					case '$slice':
						$is_group = true;
						$return = $this->_applyConditionSlice($cond, $field);
						$where = array_merge($where, $return);
						break;

					case '$in':
					case '$nin':
					case '$all':
					case '$mod':
						$return = $this->_applyConditionIn($cond, $field, $logic);
						$where = array_merge($where, $return);
						break;

					case '$regex':
						$return = $this->_applyConditionRegex($cond, $field, $logic);
						$where = array_merge($where, $return);
						break;

					case '$elemMatch':
						$return = $this->_applyConditionElemMatch($cond, $field, $logic);
						$where = array_merge($where, $return);
						break;

					case '$orderby':
						
						$this->order(array($field => $cond[0]));
						break;
						
					default:
						$is_group = true;
						$return = $this->_applyCondition($cond, $field);
						$where = array_merge($where, $return);
				}
			}
        }

	/*
		if($where){
			$where = trim($where, ' AND OR');
			if($is_group){
				$where = '(' . $where . ')';
			}
		}
		*/
        return $where;
    }

    /**
     * 处理条件数组中的类型,判断是哪些逻辑操作,例如:=,<,>,IN,BETWEEN等
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $cond  条件数组
     * @return string 大小的逻辑字符
     */
	private function _parseLogic($cond){
		$logic_map = array(
			'='  => '$eq',
			'<'  => '$lt',
			'<=' => '$lte',
			'>'  => '$gt',
			'>=' => '$gte',
			'!=' => '$ne',
			'<>' => '$ne',
			'IN' => '$in',
			'NOT IN' => '$nin',
			'ALL' => '$all',
			'MOD' => '$mod',
			'EXISTS' => '$exists',
			'BETWEEN' => '$between',
			'SIZE' => '$size',
			'REGEX' => '$regex',
			'LIKE' => '$regex',
			'SLICE' => '$slice',
			'ELEMMATCH' => '$elemMatch',
			'ORDERBY' => '$orderby',
			'ORDER' => '$orderby',
		);
		array_shift($cond);
		$logic = strtoupper(trim(array_shift($cond)));
		if(!is_array($logic)){
			if(isset($logic_map[$logic])){
				$logic = $logic_map[$logic];
			}else{
				$logic = 'Array';
			}
		}else{
			$logic = 'Array';
		}
		return $logic;
	}


    /**
     * 处理where条件中的基本语句,
     * 例如:array('name' => array('leju', '<>', 'OR'))
     *     array('title' => 'vophp')
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionBase($condition, $field, $logic=''){
		if(!isset($condition[0]) || is_array($condition[0]) || count($condition[0]) < 1){
			$where = array();
		}else{
			$join = isset($condition[2]) && !empty($condition[2]) ? strtoupper(trim($condition[2])) : ''; //连接符
			$join_cond = isset($condition[3]) && !empty($condition[3]) ? $condition[3] : ''; //连接对象
			$joins = array('OR', 'AND');
			if( $join && in_array($join, $joins) && $join_cond ){
				$return = $this->_applyCondition($join_cond, $field);
				$mongo_join = '$' . strtolower($join);
				$where[$mongo_join] = array(
					array($field => $condition[0]),
					$return,
				);
			}else{
				$where = array( $field => array($logic => $condition[0]) );
			}
		}
        return $where;
    }

    /**
     * 处理where条件中的BETWEEN语句,例如:array('age' => array(array(100, 200), 'BETWEEN', 'OR'))
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionBetween($condition, $field){
		$where= array();
		if(!isset($condition[0]) || count($condition[0])<2 || !is_array($condition[0]) || count($condition) < 2){
			$where = array();
		}else{
			$gte = '$gte';
			$lte = '$lte';
			
			$join = isset($condition[2]) && !empty($condition[2]) ? strtoupper(trim($condition[2])) : ''; //连接符
			$join_cond = isset($condition[3]) && !empty($condition[3]) ? $condition[3] : ''; //连接对象
			$joins = array('OR', 'AND');
			if( $join && in_array($join, $joins) && $join_cond ){
				$return = $this->_applyCondition($join_cond, $field);
				$mongo_join = '$' . strtolower($join);
				$where[$mongo_join] = array(	
					array( $field => array($gte => $condition[0][0], $lte => $condition[0][1]) ),
					$return,
				);
			}else{
				$where = array( $field => array($gte => $condition[0][0], $lte => $condition[0][1]) );
			}
		}
        return $where;
    }

	/**
     * 处理fied字段中的slice语句,获取结果集中数组中的个数,例如:array('hobby' => array(array(1, 2), 'SLICE'))
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionSlice($condition, $field, $logic='$slice'){
	    $where= array();
		if(!isset($condition[0]) || count($condition) < 2){
			$where = array();
		}else{
			$item = array();
			$condition[0] = (array)$condition[0];
			if(count($condition)<2){
				$item[] = 0;
				$item[] = $condition[0];
			}else{
				$item = $condition[0];
			}
			$where = array( $field => array($logic => $item ));
		}
        return $where;
    }

    /**
     * 处理where条件中的IN和NOT IN语句,例如:array('age' => array(array(10, 20), 'IN/NOT IN', 'OR'))
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionIn($condition, $field, $logic='$in'){
	    $where = array();
	    $condition[0] = (array)$condition[0];
	    
		$join = isset($condition[2]) && !empty($condition[2]) ? strtoupper(trim($condition[2])) : ''; //连接符
		$join_cond = isset($condition[3]) && !empty($condition[3]) ? $condition[3] : ''; //连接对象
		$joins = array('OR', 'AND');
		if( $join && in_array($join, $joins) && $join_cond ){
			$return = $this->_applyCondition($join_cond, $field);
			$mongo_join = '$' . strtolower($join);
			$where[$mongo_join] = array(
				array( $field => array($logic => $condition[0]) ),
				$return,
			);
		}else{
			$where = array( $field => array($logic => $condition[0]) );
		}
        return $where;
    }

    /**
     * 处理where条件中的正则语句,例如:array('name' => array(array('正则表达式', '正则修饰符'), 'REGEX', 'OR'))
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionRegex($condition, $field, $logic='$regex'){
	    $where = array();
		if( isset($condition[0]) ){
			$condition[0] = (array)$condition[0];
			if(count($condition[0]) > 1){
				$regex = array($logic => $condition[0][0]);
				$options = array('$options' => $condition[0][1]);
				$item = array($logic => $condition[0][0], '$options' => $condition[0][1]);
			}else{
				$regex = array($logic => $condition[0][0]);
				$item = array($logic => $condition[0][0]);
			}
			
			$join = isset($condition[2]) && !empty($condition[2]) ? strtoupper(trim($condition[2])) : ''; //连接符
			$join_cond = isset($condition[3]) && !empty($condition[3]) ? $condition[3] : ''; //连接对象
			$joins = array('OR', 'AND');
			if( $join && in_array($join, $joins) && $join_cond ){
				$return = $this->_applyCondition($join_cond, $field);
				$mongo_join = '$' . strtolower($join);
				$where[$mongo_join] = array(
					array( $field => $item ),
					$return,
				);
			}else{
				$where = array( $field => $item );
			}
		}
        return $where;
    }

	/**
     * 处理where条件中的elemMatch语句, 例如:
     * array(
     * 	'hobby' => array(
     *	 	array(
	 *			'school' => array('跳舞', 'IN'),
	 *			'test' => array('10', '>'),
     *		), 
     *		'ELEMMATCH', 
     *		'OR',
     *	 )
     * )
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array   $condition  条件数组
     * @param  string  $field  字段名称
     * @return string  解析后的条件字符串
     */
    private function _applyConditionElemMatch($condition, $field, $logic='$elemMatch'){
	    $where = array();
	    $cond = $condition[0];
		$join = isset($condition[2]) && !empty($condition[2]) ? strtoupper(trim($condition[2])) : ''; //连接符
		$join_cond = isset($condition[3]) && !empty($condition[3]) ? $condition[3] : ''; //连接对象
		$joins = array('OR', 'AND');
		if( $join && in_array($join, $joins) && $join_cond ){
			$return = $this->_applyCondition($join_cond, $field);
			$mongo_join = '$' . strtolower($join);
			$where[$mongo_join] = array(
				array( $logic => $this->_applyCondition($cond, $field) ),
				$return,
			);
		}else{
			$where[$field] = array( $logic => $this->_applyCondition($cond, $field) );
		}
        return $where;
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

}