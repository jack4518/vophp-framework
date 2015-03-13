<?php
/**
 * 定义Mysql数据库驱动类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-08
 **/

defined('VOPHP') or die('Restricted access');

VO_Loader::import('Database.Adapter.Abstract');

require_once(VO_LIB_DIR . DS . 'Database'. DS . 'Adapter' . DS . 'Abstract.php');
class VO_Database_Adapter_Mysql extends VO_Database_Adapter_Abstract{

	//数据库连接参数
	protected $_options = null;
	
	//数据库连接资源
	protected $_resource = null;
	
	//数据库实例
	protected $_instance = null;
	
	//执行的SQL语句
	protected $_sql = null;
	
	//查询句柄
	protected $_cursor = null;
	

	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		parent::__construct();
		$this->_options = $config;
		self::connect();
		$this->setUTF();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Database_Adapter_Mysql
	 */
	public function getInstance($config){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self($config);
		}
		
		return self::$_instance;
	}
	
	/**
	 * 连接数据库
	 */
	public function connect(){
		$host		= array_key_exists('host', $this->_options)	? $this->_options['host']		: 'localhost';
		$port		= array_key_exists('port', $this->_options)	? $this->_options['port']		: '3306';
		$user		= array_key_exists('user', $this->_options)	? $this->_options['user']		: '';
		$password	= array_key_exists('password',$this->_options)	? $this->_options['password']	: '';
		$database	= array_key_exists('database',$this->_options)	? $this->_options['database']	: '';
		$prefix		= array_key_exists('prefix', $this->_options)	? $this->_options['prefix']	: 'vo_';
		$select		= array_key_exists('select', $this->_options)	? $this->_options['select']	: true;
		
		$host = $host . ':' . $port;
		
		$this->_table_prefix = $prefix;
		
		if (!($this->_resource = mysql_connect( $host, $user, $password, true ))) {
			$this->triggerError('无法连接到MySQL数据库！请检查配置或者MySQL数据库服务器是否启动.', E_USER_ERROR);
		}

		// 选择数据库
		if ( $select ) {
			$this->selectDatabase($database);
		}
	}
	
	/**
	 * 数据库析构函数
	 *
	 * @return  boolean
	 */
	public function __destruct()
	{
		$return = false;
		if (is_resource($this->_resource)) {
			$return = mysql_close($this->_resource);
		}
		return $return;
	}
	
	/**
	 * 测试Mysql数据库是否可用
	 *
	 * @return  boolean  成功返回 true, 否则返回 false
	 */
	public function test()
	{
		return (function_exists( 'mysql_connect' ));
	}
	
	/**
	 * 检测当前是连接状态
	 *
	 * @return	boolean
	 */
	public function isConnected()
	{
		if(is_resource($this->_resource)) {
			return mysql_ping($this->_resource);
		}
		return false;
	}

	/**
	 * 打开数据库
	 *
	 * @param	string $database
	 * @return	Boolean 成功返回 true 否则返回 false
	 */
	public function selectDatabase($database=null)
	{
		if ( ! $database )
		{
			return false;
		}

		if ( !mysql_select_db( $database, $this->_resource )) {
			$this->triggerError('无法连接到指定数据库.');
			exit;
		}

		// 如果数据库服务器为 mysql 5, 设置 sql-mode为mysql40 - 从而解决Mysql严格的SQL模式的问题
		
		if ( strpos( $this->getVersion(), '5' ) === 0 ) {
			//$this->query( "SET sql_mode = 'MYSQL40'" );
		}
		return true;
	}

	/**
	 * 检测 UTF 支持
	 *
	 * @访问类型	public
	 * @return boolean 支持UTF返回　TRUE
	 */
	public function hasUTF()
	{
		$verParts = explode( '.', $this->getVersion() );
		return ($verParts[0] == 5 || ($verParts[0] == 4 && $verParts[1] == 1 && (int)$verParts[2] >= 2));
	}

	/**
	 * 设定UTF支持
	 *
	 * @访问类型	public
	 */
	public function setUTF()
	{
		mysql_query( "SET NAMES 'utf8'", $this->_resource );
	}
	
	/**
	 * 描述
	 *
	 * @访问类型 public
	 * @return 返回MySQL版本号
	 */
	public function getVersion()
	{
		return mysql_get_server_info( $this->_resource );
	}
	
	/**
	 * 获取MySQL转义后的特殊字符
	 *
	 * @param	string  $text   需要转义的字符串
	 * @param	boolean	$extra  可选项 扩展转义
	 * @return	String 转义后的字符串
	 */
	public function getEscaped( $text, $extra = false )
	{
		$result = mysql_real_escape_string( $text, $this->_resource );
		if ($extra) {
			//对%和_进行转义
			$result = addcslashes( $result, '%_' );
		}
		return $result;
	}
	
	/**
	 * 执行MySQL语句
	 * @Param String $sql
	 * 
	 * return 查询句柄
	 */
	public function query($sql = '', $array = array()){
		$config = VO_Factory::getConfig();
		$this->_sql	= $this->replacePrefix( $sql );
		if (!is_resource($this->_resource)) {
			$this->triggerError('无法连接到数据库服务器');
			return false;
		}

		//$this->_sql = $this->getEscaped($this->_sql);
		if(count($array)>0){ //如果为绑定SQL语句
			$this->_sql = $this->_prepare( $this->_sql, $array );
		}
		
		$start = VO_Date::getCurrentMicroSecond();
		$this->_cursor = mysql_query( $this->_sql, $this->_resource );
		$time = VO_Date::getCurrentMicroSecond() - $start;
		$time = round($time, 7);
		//记录日志
		if($config['log']['enable']){
			$log = new VO_Log();
			$log->log( 'RunTime:' . $time .'s SQL= ' . $this->_sql, 'SQL');
		}
		
		if (!$this->_cursor){
			$this->_errorNum = mysql_errno( $this->_resource );
			$this->_errorMsg = mysql_error( $this->_resource ) . "　SQL=$this->_sql";
			$message = 'SQL语句执行错误，错误号：' . $this->_errorNum . ':' . $this->_errorMsg;
			$this->triggerError($message, E_USER_ERROR);
		}
		return $this->_cursor;
	}
	
	/**
	 * 获取最近一次执行的SQL语句
	 * @return string;
	 */
	public function getSql(){
		return $this->_sql;
	}	
	
	/**
	 * @访问类型	public
	 * @return	返回最近查询所记录数
	 */
	public function getNumRows( $cur=null )
	{
		return mysql_num_rows( $cur ? $cur : $this->_cursor );
	}
	
	/**
	 * 获取最近查询的记录数
	 * @return	int
	 */
	function getAffectedRows()
	{
		return mysql_affected_rows($this->_resource);
	}	
	
	/**
	 * 取得第一行第一列的字段值
	 * @param String $sql
	 */
	public function fetchOne($sql = '', $array = array()){
		if( !($cur = $this->query($sql, $array)) ){
			return null;
		}
		$row = mysql_fetch_array($cur);
		$ret = $row[0];
		//mysql_free_result( $cur );
		return $ret;
	}
	
	/**
	 * 取得一行数据
	 * @param String $sql
	 */
	public function fetchRow($sql = '', $array = array()){
		if( !($cur = $this->query($sql, $array)) ){
			return null;
		}
		$row = mysql_fetch_assoc($cur);
		//mysql_free_result( $cur );
		return $row;
	}	
	
	/**
	 * 取得多行数据
	 * @param String $sql
	 */
	public function fetchAll($sql = '', $array = array()){
		$i = 0;
		$rows = array();
		if($sql instanceof VO_Database_Select){
			$sql = $sql->getSql();
		}
		if( !($cur = $this->query($sql, $array)) ){
			return null;
		}
		while($result = mysql_fetch_assoc($cur)){
			$rows[] = $result;
		}
		//mysql_free_result( $cur );
		return $rows;
	}
	
	/**
	 * 获取数据表字段信息
	 * @param string $table	数据表名
	 * @return array	数据表字段信息
	 */
	public function getFields($table){
		$sql = 'SHOW FULL COLUMNS FROM ' . $table;
		$rows = $this->fetchAll($sql);
        $fields = array();
        if($rows) {
            foreach ($rows as $k => $v) {
                $fields[$v['Field']] = array(
                    'name'    => $v['Field'],
                    'type'    => $v['Type'],
                    'notnull' => (bool) ($v['Null'] === ''),
                    'default' => $v['Default'],
                    'primary' => (strtolower($v['Key']) == 'pri'),
                    'autoinc' => (strtolower($v['Extra']) == 'auto_increment'),
                );
            }
        }
        return $fields;
	}
	
	/**
	 * 取得数据库表信息
	 * @param string $dbname	数据库名称
	 * @return array	数据库表
	 */	
	public function getTables($dbname = null){
		if(!empty($dbname)) {
           $sql = 'SHOW TABLES FROM ' . $dbname;
        }else{
           $sql = 'SHOW TABLES ';
        }
        $rows = $this->fetchAll($sql);
        $tables = array();
        if($rows){
	        foreach($rows as $k => $v) {
	            $tables[$k] = current($v);
	        }
        }
        return $tables;
	}
	
	/**
	 *基于一个数组，插入一条数据到一个表 
	 *
	 * @Param	string	$table			表名
	 * @Param	object	$array			和数据表字段名称对应的数据
	 * @Param	string	$primary_key	主键名称
	 */
	public function insert( $table, &$array, $primary_key=NULL )
	{
		$fmtsql = 'INSERT INTO '.$this->nameQuote($table).' ( %s ) VALUES ( %s ) ';
		$fields = array();
		foreach ($array as $k => $v) {
			if (is_array($v) or is_object($v) or $v === NULL) {
				continue;
			}
			if ($k[0] == '_') { // internal field
				continue;
			}
			$fields[] = $this->nameQuote( $k );
			$values[] = $this->isQuoted( $k ) ? $this->quote( $v ) : (int) $v;
		}
		$sql = sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) );
		if (!$this->query($sql)) {
			return false;
		}
		$id = $this->getInsertId();
		if($id){
			return $id;
		}elseif($primary_key && !$id && isset($data[$primary_key])){
			return $data[$primary_key];
		}else{
			return true;
		}
	}
	
	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	public function getInsertId()
	{
		return mysql_insert_id( $this->_resource );
	}
	
	/**
	 * 更新记录
	 * 
	 * @Param	string	$table		表名
	 * @Param	object	$array		和数据表字段名称对应的对象y
	 * @Param	string	$where	条件
	 * @param   Boolean $updateNulls 是否更新空值
	 */
	public function update( $table, &$array, $where=null, $updateNulls=true )
	{
		$fmtsql = 'UPDATE '.$this->nameQuote($table).' SET %s WHERE %s';
		$tmp = array();
		foreach ($array as $k => $v)
		{
			if( is_array($v) or is_object($v) or $k[0] == '_' ) { // internal or NA field
				continue;
			}
			if ($v === null)
			{
				if ($updateNulls) {
					$val = 'NULL';
				} else {
					continue;
				}
			} else {
				$val = $this->isQuoted( $k ) ? $this->quote( $v ) : (int) $v;
			}
			$tmp[] = $this->nameQuote( $k ) . '=' . $val;
		}
		$sql = sprintf( $fmtsql, implode( ",", $tmp ) , $where );
		return $this->query($sql);
	}
	
	/**
	 * 删除记录
	 * @Param	string	$table		表名
	 * @Param	string	$where	           条件
	 * @return mixed
	 */
	function delete( $table, $where=null)
	{
		if(!empty($where)){
			$fmtsql = 'DELETE FROM '.$this->nameQuote($table).' WHERE %s';
		}else{
			$fmtsql = 'DELETE FROM '.$this->nameQuote($table);
		}
		$sql = sprintf( $fmtsql, $where );
		return $this->query($sql);
	}

	/**
	 * 清空数据库
	 * @param string $table
	 */
	public function truncate($table){
		return $this->delete($table);
	}
		
	/**
	 * 启动事务
	 * @return mixed
	 */
	public function startTransaction(){
		$sql = 'SET AUTOCOMMIT=0';
		$this->query($sql);
		$sql = 'START TRANSACTION';
		return $this->query($sql);
	}
	
	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit(){
		$sql = 'COMMIT';
		$this->query($sql);
		$sql = 'SET AUTOCOMMIT=1';
		return $this->query($sql);
	}
	
	/**
	 * 回滚事务
	 * @return mixed
	 */
	public function rollback(){
		$sql = 'ROLLBACK';
		$this->query($sql);
		$sql = 'SET AUTOCOMMIT=1';
		return $this->query($sql);
	}

	/**
	 * 获取错误信息
	 */
	public function getErrorMessage(){
		return mysql_error($this->_resource);
	}	
	
	/**
	 * 获取错误号码
	 */
	public function getErrorNumber(){
		return mysql_errno($this->_resource);
	}		
	
	/**
	 * 关闭MySQL连接
	 */
	public function close(){
		mysql_close($this->_resource);
		$this->_resource = null;
		$this->_cursor = null;
	}
		
	/**
	 * 诊断函数
	 * @return	string
	 */
	public function explain()
	{
		$temp = $this->_sql;
		$this->_sql = "EXPLAIN " . $this->_sql;

		if (!($cur = $this->query($this->_sql))) {
			return null;
		}
		$first = true;

		$buffer = '<table id="explain-sql">';
		$buffer .= '<thead><tr><td colspan="99">'.$this->_sql.'</td></tr>';
		while ($row = mysql_fetch_assoc( $cur )) {
			if ($first) {
				$buffer .= '<tr>';
				foreach ($row as $k=>$v) {
					$buffer .= '<th>'.$k.'</th>';
				}
				$buffer .= '</tr>';
				$first = false;
			}
			$buffer .= '</thead><tbody><tr>';
			foreach ($row as $k=>$v) {
				$buffer .= '<td>'.$v.'</td>';
			}
			$buffer .= '</tr>';
		}
		$buffer .= '</tbody></table>';
		mysql_free_result( $cur );

		$this->_sql = $temp;

		return $buffer;
	}
	/**
	 * 构造一个Select查询对象
	 * @return 返回一个Select对象
	 */
	
	public function getSelect(){
		return new VO_Database_Select($this);
	}

	/**
     * 创建数据库
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $name  待创建数据库名称
     * @return bool  是否创建成功
     */
    public function createDatabase($name){
	    $sql = 'CREATE DATABASE ' . $this->nameQuote($name);
        return $this->query($sql);
    }

	/**
     * 删除数据库
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $name  待删除数据库名称
     * @return bool  是否删除成功
     */
    public function dropDatabase($name){
	    $sql = 'DROP DATABASE ' . $this->nameQuote($name);
        return $this->query($sql);
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
     * @param  array   $columns  字段相关数组
     * @param  array   $options  额外属性
     * @return bool
     */
    public function createTable($table, array $columns, $options=null){
	    $cols=array();
        foreach($columns as $name => $type){
            if(is_string($name))
                $cols[] = "\t" . $this->nameQuote($name) . ' ' . $type;
            elseif(is_array($type)){

            }else{
                $cols[]="\t" . $type;
            }
        }

        $sql = 'CREATE TABLE ' . $this->nameQuote($table) . " (\n" . implode(",\n", $cols) . "\n)";
        if(null !== $options){
	        $sql .= ' ' . $options;
        }
        return $this->query($sql);
    }

	/**
     * 删除表
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table    表名
     * @return bool  是否删除成功
     */
    public function dropTable($table){
        $sql = 'DROP TABLE ' . $this->nameQuote($table);
		return $this->query($sql);
    }

	/**
	 * 重命名表名
	 * @param string $tablename  原表名
	 * @param string $tablename_new 新表名
	 * @return bool 是否重命名成功
	 */
	public function renameTable($tablename, $tablename_new){
		$sql = 'ALTER TABLE ' . $this->nameQuote($tablename) . ' RENAME ' . $this->nameQuote($tablename_new);
		return $this->query($sql);
	}	

	/**
     * 清空表数据重建
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
     * @param  string  $table    表名
     * @return bool
     */
    public function truncateTable($table){
	    $sql = 'TRUNCATE TABLE ' . $this->nameQuote($tablename);
		return $this->query($sql);
	}	


	/**
	 * 添加表字段
	 * @param string $table  表名
	 * @param string $column  列名
	 * @param string $type  字段类型
	 * @param string $default  字段默认值
	 * @param string $is_null  字段是否允许为空
	 * @param string $comment  字段注释
	 * @param string $after  在某个字段之后
	 * @return bool 是否添加成功
	 */
	public function addColumn($table, $column, $type, $default=null, $is_null=true, $comment='', $after=null){
		$type = strtolower($type);
		if(!in_array($type, $this->_column_types)){
			$pos = stripos($type, '(');
			if($pos != false){
				$t = substr($type, 0, $pos);
				if(!in_array($t, $this->_column_types)){
					$this->triggerError('字段"' . $column .'"的类型"' . $type . '不正确', E_USER_ERROR);
				}
			}
		}
		$sql = 'ALTER TABLE ' . $this->nameQuote($table) . ' ADD COLUMN ' . $this->nameQuote($column) . ' ' . $type;
		if($is_null == false){
			$sql .= ' NOT NULL';
		}
		if($default !== null){
			$sql .= ' DEFAULT ' . $default;
		}
		if(!empty($comment)){
			$comment = 
			$sql .= ' COMMENT ' . $this->quote($comment);
		}
		if(!empty($after)){
			$sql .= ' AFTER ' . $this->nameQuote($after);
		}
		return $this->query($sql);
	}

	/**
	 * 删除表字段
	 * @param string $table  表名
	 * @param string $column 列名
	 * @return bool 是否删除成功
	 */
	public function dropColumn($table, $column=null){
		$sql = 'ALTER TABLE ' . $this->nameQuote($table) . ' DROP COLUMN ' . $this->nameQuote($column);
		return $this->query($sql);
	}
	
	/**
	 * 重命名表字段
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
	 * @param  string  $table  表名
	 * @param  string  $column  列名
	 * @param  string  $column_new  新列名
	 * @param  string  $type  字段类型
	 * @param  string  $default  字段默认值
	 * @param  string  $allow_null  字段是否允许为空
	 * @param  string  $comment  字段注释
	 * @return bool 是否重命名成功
	 */
	public function alterColumn($table, $column, $column_new, $type, $default=null, $allow_null=true, $comment='', $after=null){
		$type = strtolower($type);
		if(!in_array($type, $this->_column_types)){
			$pos = stripos($type, '(');
			if($pos != false){
				$t = substr($type, 0, $pos);
				if(!in_array($t, $this->_column_types)){
					$this->triggerError('字段"' . $column .'"的类型"' . $type . '不正确', E_USER_ERROR);
					return false;
				}
			}
		}
		$sql = 'ALTER TABLE ' . $this->nameQuote($table) . ' CHANGE COLUMN ' . $this->nameQuote($column) . ' ' . $this->nameQuote($column_new) . ' ' . $type;
		if($allow_null == false){
			$sql .= ' NOT NULL';
		}
		if(is_null($default)){
		    if( $default === 0 ){
			    $default = 0;
		    }elseif( $default === '' ){
			    $default = '';
		    }elseif( $default === false ){
			    $default = false;
		    }
	    }
	    if(is_null($allow_null)){
		    $allow_null = is_null($column_info['allowNull']) ? true : false;
	    }
	    
		if($default !== null){
			if( $default === 0 ){
			    $sql .= ' DEFAULT 0';
		    }elseif( $default === '' ){
			    $sql .= ' DEFAULT ""';
		    }elseif( $default === false ){
			    $sql .= ' DEFAULT false';
		    }else{
				$sql .= ' DEFAULT ' . $default;
			}
		}
		if(!empty($comment)){
			$comment = 
			$sql .= ' COMMENT ' . $this->quote($comment);
		}
		if(!empty($after)){
			$sql .= ' AFTER ' . $this->nameQuote($after);
		}
		return $this->query($sql);
	}	

	/**
     * 新增主键
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
     * @param   string  $table  表名
     * @param   string  $name  主键名
     * @param   array   $column  列名
     * @return  bool  是否添加成功
     */
    public function addPrimaryKey($table, $name, $columns){
	    if(is_string($columns)){
	    	$columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
    	}
        foreach($columns as $i=>$col){
            $columns[$i] = $this->nameQuote($col);
        }
        $return = $this->query('ALTER TABLE ' . $this->nameQuote($table) . ' ADD CONSTRAINT '
            . $this->nameQuote($name) . '  PRIMARY KEY ('
            . implode(', ', $columns). ' )'
        );
        return $return;
    }

    /**
     * 删除主键
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param   string  $name   主键名称
     * @param   string  $table  表名
     * @return bool  是否删除成功
     */
    public function dropPrimaryKey($table, $name){
        $sql = 'ALTER TABLE ' . $this->nameQuote($table) . ' DROP CONSTRAINT ' . $this->nameQuote($name);
        $return = $this->query($sql);
        return $return;
    }

    /**
     * 创建索引
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table   表名
     * @param  string  $index_name    索引名称
     * @param  array|string   $columns  索引列,如果多个为多列组合索引
     * @param  bool    $unique  是否唯一索引
     * @return bool    是否创建成功
     */
    public function createIndex($table, $index_name, $columns, $unique=false){
	    $cols=array();
	    if(is_string($columns)){
	    	$columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
    	}
        foreach($columns as $col){
            if(strpos($col,'(')!==false){
                $cols[]=$col;
            }else{
                $cols[]=$this->nameQuote($col);
            }
        }
        $sql = $unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ' . $this->nameQuote($index_name) . ' ON ' . $this->nameQuote($table) . ' (' . implode(', ',$cols) . ')';
        return $this->query($sql);
    }

    /**
     * 删除索引
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table  索引所在表名
     * @param  string  $name  索引名称
     * @return bool  是否删除成功
     */
    public function dropIndex($table, $name){
	    $sql = 'DROP INDEX ' . $this->nameQuote($name) . ' ON ' . $this->nameQuote($table);
	    return $this->query($sql);
    }
}