<?php
/**
 * 定义SQLite数据库驱动类
 * VO_Database_Adapter_Sqlite只支持SQLite2版本，暂不支持SQLite3版本；
 * 若需要连接SQLite3必须用VO_Database_Adapter_Pdo_Sqlite去连接
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-05
 **/

defined('VOPHP') or die('Restricted access');

VO_Loader::import('Database.Adapter.Abstract');

class VO_Database_Adapter_Sqlite extends VO_Database_Adapter_Abstract{

	//数据库连接参数
	protected $_options = null;
	
	/**
	 * 数据库连接资源
	 * @var SQLite
	 */
	protected $_resource = null;
	
	//数据库实例
	protected $_instance = null;
	
	//执行的SQL语句
	protected $_sql = null;
	
	//查询句柄
	protected $_cursor = null;
	
	//Debug调试
	protected $_debug = true;
	

	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_options = $config;
		self::connect();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Database_Adapter_Sqlite
	 */
	public function getInstance(){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}

	/**
	 * 打开数据库
	 * @param	string $database
	 * @return	Boolean 成功返回 true 否则返回 false
	 */
	public function selectDatabase($database=null)
	{
		//not support
		return true;
	}

	/**
	 * 检测 UTF 支持
	 * @return boolean 支持UTF返回　TRUE
	 */
	public function hasUTF()
	{
		//not support
		return true;
	}

	/**
	 * 设定UTF支持
	 */
	public function setUTF()
	{
		//not support
		return true;
	}	
	
	/**
	 * 连接数据库
	 */
	public function connect(){
		$database	= array_key_exists('database',$this->_options)	? $this->_options['database']	: '';
		$prefix		= array_key_exists('prefix', $this->_options)	? $this->_options['prefix']	: 'vo_';
		
		$this->_table_prefix = $prefix;
		
		// 检查数据库连接是否可用
		$error = '';
		if( !extension_loaded( 'sqlite' ) && !extension_loaded( 'sqlite3' )){
			$this->triggerError('"SQLite" 数据库扩展不可用,请检查php.ini配置文件是否开启该扩展.', E_USER_ERROR);
		}
		
		if(!file_exists($database)){
			$this->triggerError('SQLite数据库文件:' . $database . '不存在,请确认文件路径是否正确.', E_USER_ERROR);
		}
		
		$this->_resource = @sqlite_open($database, 0666, $error);
		// 连接数据库
		if($error){
			$this->triggerError('无法连接到SQLite数据库.请检查相关配置(注:VO_Database_Adapter_Sqlite只支持SQLite2版本,暂不支持SQLite3版本;若需要连接SQLite3请使用VO_Database_Adapter_Pdo_Sqlite连接.).', E_USER_ERROR);
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
			$return = $this->close();
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
		return (extension_loaded( 'sqlite' ));
	}
	
/**
	 * 检测当前是连接状态
	 *
	 * @return	boolean
	 */
	public function isConnected()
	{
		if(is_resource($this->_resource)) {
			return true;
		}
		return false;
	}

	/**
	 * 描述
	 *
	 * @访问类型 public
	 * @return 返回MySQL版本号
	 */
	public function getVersion()
	{
		return sqlite_libversion();
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
		$result = sqlite_escape_string( $text );
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
	public function query($sql = ''){
		$config = VO_Factory::getConfig();
		if (!is_resource($this->_resource)) {
			$this->triggerError('无法连接到SQLite数据库服务器.');
			return false;
		}
		$this->_affactRowNum = null;
		
		$this->_sql	= $this->replacePrefix( $sql );
		
		//$this->_sql = $this->getEscaped($this->_sql);
		if($this->_sql){
			$start = VO_Date::getCurrentMicroSecond();
			$this->_cursor = sqlite_query($this->_sql, $this->_resource);
			$time = VO_Date::getCurrentMicroSecond() - $start;
			$time = round($time, 7);
			//记录日志
			if($config['logEnable']){
				$log = new VO_Log();
				$log->log( 'RunTime:' . $time .'s SQL= ' . $this->_sql, 'SQL');
			}
		}
		if (!$this->_cursor){
			$this->_errorMsg = $this->_resource->error ." SQL=$this->_sql";
			if ($this->_debug) {
				$message = 'SQL语句执行错误：' . $this->_errorMsg;
				$this->triggerError($message);
				exit;
			}
			return false;
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
	 * 获取最近查询的记录数
	 * @return	int
	 */
	function getNumRows()
	{
		if($this->_cursor){
			return sqlite_num_rows($this->_cursor);
		}
		return false;
	}
	
	/**
	 * 获取最近查询的记录数
	 * @return	int
	 */
	function getAffectedRows()
	{
		if($this->_cursor){
			return sqlite_changes($this->_resource);
		}
		return false;
	}	
	
	/**
	 * 取得第一行第一列的字段值
	 * @param String $sql
	 */
	function fetchOne($sql = ''){
		if($sql instanceof VO_Database_Select){
			$sql = $sql->getSql();
		}
		if( !($cur = $this->query($sql)) ){
			return null;
		}
		$row = sqlite_fetch_single($this->_cursor);
		$ret = $row[0];
		return $ret;
	}
	
	/**
	 * 取得一行数据
	 * @param String|VO_Database_Select $sql 查询语句
	 */
	function fetchRow($sql = ''){
		if($sql instanceof VO_Database_Select){
			$sql = $sql->getSql();
		}
		if( !($cur = $this->query($sql)) ){
			return null;
		}
		return sqlite_current($this->_cursor, SQLITE_ASSOC);
	}	
	
	/**
	 * 取得多行数据
	 * @param String|VO_Database_Select $sql  查询语句
	 */
	function fetchAll($sql = ''){
		$i = 0;
		if($sql instanceof VO_Database_Select){
			$sql = $sql->getSql();
		}
		if( !($cur = $this->query($sql)) ){
			return null;
		}
		$rows = sqlite_fetch_all($this->_cursor, SQLITE_ASSOC);
		return $rows;
	}
		
	/**
	 *基于一个数组，插入一条数据到一个表 
	 *
	 * @Param	string	$table		表名
	 * @Param	object	$array		和数据表字段名称对应的对象y
	 * @Param	string	$keyName	自增主键名称
	 */
	function insert( $table, &$array, $keyName = NULL )
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
		if ($keyName && $id) {
			$object->$keyName = $id;
		}else{
			$id = true;
		}
		return true;
	}
	
	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	function getInsertId()
	{
		if($this->_cursor){
			return sqlite_last_insert_rowid($this->_resource);
		}else{
			return false;
		}
	}
	
	/**
	 * 更新记录
	 * 
	 * @Param	string	$table		表名
	 * @Param	object	$array		和数据表字段名称对应的对象y
	 * @Param	string	$where	条件
	 * @param   Boolean $updateNulls 是否更新空值
	 */
	function update( $table, &$array, $where, $updateNulls=true )
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
	 * 
	 * @Param	string	$table		表名
	 * @Param	string	$where	           条件
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
		$sql = 'BEGIN TRANSACTION';
		return $this->query($sql);
	}
	
	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit(){
		$sql = 'COMMIT';
		return $this->query($sql);
	}
	
	/**
	 * 回滚事务
	 * @return mixed
	 */
	public function rollback(){
		$sql = 'ROLLBACK';
		return $this->query($sql);
	}	
	
	/**
	 * 诊断函数
	 * 
	 * @return	string
	 */
	function explain()
	{
		if(!$this->_sql){
			return false;
		}
		$temp = $this->_sql;
		$this->_sql = "EXPLAIN " . $this->_sql;

		if (!($cur = $this->query($this->_sql))) {
			return null;
		}
		$first = true;

		$buffer = '<table id="explain-sql" border="0">';
		$buffer .= '<thead><tr><td colspan="99">'.$this->_sql.'</td></tr>';
		$rows = $this->fetchAll();
		if ($first) {
			$buffer .= '<tr>';
			foreach ($rows[0] as $k=>$v) {
				$buffer .= '<th>'.$k.'</th>';
			}
			$buffer .= '</tr>';
			$first = false;
		}
		$buffer .= '</thead><tbody>';
		foreach ($rows as $k=>$v) {
			$buffer .= '<tr>';
			foreach($v as $i => $j){
				if($j == null){
					$buffer .= '<td>&nbsp;</td>';
				}else{
					$buffer .= '<td>'.$j.'</td>';
				}
			}
			$buffer .= '</tr>';
		}
		$buffer .= '</tbody></table>';

		$this->_sql = $temp;

		return $buffer;
	}
	
	/**
	 * 获取错误信息
	 */
	public function getErrorMessage(){
		return sqlite_error_string(sqlite_last_error($this->_resource));
	}	
	
	/**
	 * 获取错误号码
	 */
	public function getErrorNumber(){
		return sqlite_last_error($this->_resource);
	}
	
	/**
	 * 关闭SQLite连接
	 */
	public function close(){
		@sqlite_close($this->_resource);
		$this->_resource = null;
		$this->_cursor = null;
	}
	/**
	 * 构造一个Select查询对象
	 * @return VO_Database_Select
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