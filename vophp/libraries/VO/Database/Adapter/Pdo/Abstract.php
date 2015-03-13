<?php
/**
 * 定义Pdo_Abstract PDO数据库抽象类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Pdo_Abstract extends VO_Database_Adapter_Abstract{

	/**
	 * 数据库连接参数
	 * @var array
	 */
	protected $_options = null;
	
	/**
	 * 数据库连接资源
	 * @var PDO
	 */
	protected $_resource = null;
	
	/**
	 * 数据库实例
	 * @var VO_Database_Adapter_Pdo_Abstract
	 */
	protected $_instance = null;
	
	/**
	 * 执行的SQL语句
	 * @var string
	 */
	protected $_sql = null;
	
	/**
	 * 查询句柄
	 * @var PDOStatement
	 */
	protected $_cursor = null;
	
	/**
	 * 存储最后一次绑定的值
	 * @var array
	 */
	protected $_bindValues = array();
	
	/**
	 *Debug调试模式
	 *@var boolean
	 */
	protected $_debug = true;
	
	/**
	 * 定义当前的数据库类型
	 * @var string
	 */
	protected $_dbType = 'MySQL';
	

	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_options = $config;
		self::connect();
		$this->setUTF();
	}
	
	/**
	 * 连接数据库
	 * @return boolean
	 */
	public function connect(){		
		// 检查数据库连接是否可用
		if(!extension_loaded( 'pdo' )){
			$this->triggerError('"PDO" 数据库扩展不可用.');
			exit;
		}
		return true;
	}
	
	/**
	 * 数据库析构函数
	 * @return  boolean
	 */
	public function __destruct()
	{
		$return = false;
		if ($this->_resource instanceof PDO ) {
			$this->_resource = null;
			$this->_cursor = null;
			$this->_sql = '';
		}
		return true;
	}
	
	/**
	 * 测试Mysql数据库是否可用
	 * @return  boolean  成功返回 true, 否则返回 false
	 */
	public function test()
	{
		return (method_exists($this->_resource, 'query' ));
	}
	
	/**
	 * 检测当前的连接状态
	 * @return	boolean
	 */
	public function isConnected()
	{
		if(($this->_resource instanceof PDO) ) {
			return true;
		}
		return false;
	}

	/**
	 * 检测 UTF 支持
	 * @return boolean 支持UTF返回　TRUE
	 */
	public function hasUTF()
	{
		return false;
	}

	/**
	 * 设定UTF支持
	 * @return boolean
	 */
	public function setUTF()
	{
		return true;
	}
	
	/**
	 * 返回MySQL信息
	 * @return string
	 */
	public function getVersion()
	{
		return true;
	}
	
	/**
	 * 获取MySQL转义后的特殊字符
	 * @param	string  $text   需要转义的字符串
	 * @param	boolean	$extra  可选项 扩展转义
	 * @return	String 转义后的字符串
	 */
	public function getEscaped( $text, $extra = false )
	{
		$result = $this->_resource->quote($text);
		if ($extra) {
			//对%和_进行转义
			$result = addcslashes( $result, '%_' );
		}
		return $result;
	}
	
	/**
	 * 执行MySQL语句
	 * @Param String $sql
	 * @return mixed
	 */
	public function query($sql = '', $array = array()){
		$config = VO_Factory::getConfig();
		$this->_sql	= $this->replacePrefix( $sql );
		if (!$this->_resource instanceof PDO) {
			$this->triggerError('无法连接到' . $this->_dbType . '数据库服务器.');
			return false;
		}
		$start = VO_Date::getCurrentMicroSecond();
		if(count($array)>0){ //如果为绑定SQL语句
			$this->_cursor = $this->_resource->prepare( $this->_sql );
			$this->_cursor->execute($array);
		}else{
			$this->_cursor = $this->_resource->query( $this->_sql );
			$this->_cursor->execute();
		}
		
		$time = VO_Date::getCurrentMicroSecond() - $start;
		$time = round($time, 7);
		//记录日志
		if($config['logEnable']){
			$log = new VO_Log();
			$log->log( 'RunTime:' . $time .'s SQL= ' . $this->_sql, 'SQL');
		}
		
		if (!$this->_cursor){
			$this->_errorNum = $this->_resource->errno;
			$this->_errorMsg = $this->_resource->error ."　SQL=$this->_sql";
			if ($this->_debug) {
				$message = 'SQL语句执行错误，错误号：' . $this->_errorNum . ':' . $this->_errorMsg;
				$this->triggerError($message);
				exit;
			}
			return false;
		}
		return $this->_cursor;
	}
	
	/**
	 * 执行SQL语句
	 * @param int $sql SQL语句
	 * @return int 执行结果的影响行数
	 */
	protected function execute($sql){
		$this->_sql	= $this->replacePrefix( $sql );
		if (!$this->_resource instanceof PDO) {
			$this->triggerError('无法连接到' . $this->_dbType . '数据库服务器.');
			return false;
		}
		$ret = $this->_resource->exec($this->_sql);
		return $ret;
	}
	
	/**
	 * 获取最近一次执行的SQL语句
	 * @return string;
	 */
	public function getSql(){
		return $this->_sql;
	}		
	
	/**
	 * 返回最近查询所记录数
	 * @return int
	 */
	function getNumRows()
	{
		if( !($cur = $this->query($this->_sql,$this->_bindValues)) ){
			return 0;
		}
		$rows = $this->_cursor->fetchAll(PDO::FETCH_NUM);
		return count($rows);
	}
	
	/**
	 * 获取最近查询的记录数
	 * @return	int
	 */
	function getAffectedRows()
	{
		return $this->_cursor->rowCount();
	}	
	
	/**
	 * 取得第一行第一列的字段值
	 * @param String $sql
	 * @return mixed
	 */
	function fetchOne($sql = '', $array=array()){
		$this->_bindValues = $array;
		if( !($cur = $this->query($sql, $this->_bindValues)) ){
			return null;
		}
		$row = $cur->fetch(PDO::FETCH_NUM);
		$ret = $row[0];
		return $ret;
	}
	
	/**
	 * 取得一行数据
	 * @param String $sql
	 * @return array result set
	 */
	function fetchRow($sql = '', $array=array()){
		$this->_bindValues = $array;
		if( !($cur = $this->query($sql, $this->_bindValues)) ){
			return null;
		}
		$row = $this->_cursor->fetch(PDO::FETCH_ASSOC);
		return $row;
	}	
	
	/**
	 * 取得多行数据
	 * @param String $sql
	 * @return array result set
	 */
	function fetchAll($sql = '', $array=array()){
		$this->_bindValues = $array;
		if($sql instanceof VO_Database_Select){
			$sql = $sql->getSql();
		}
		if( !($this->_cursor = $this->query($sql, $this->_bindValues)) ){
			return null;
		}
		$rows = $this->_cursor->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
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
		$this->_sql = $sql;
		return $this->execute($sql);
	}

	/**
	 * 清空数据库
	 * @param string $table
	 */
	public function truncate($table){
		return $this->delete($table);
	}	
	
	/**
	 *基于一个数组，插入一条数据到一个表 
	 *
	 * @Param	string	$table			表名
	 * @Param	object	$array			和数据表字段名称对应的数据
	 * @Param	string	$primary_key	主键名称
	 */
	function insert( $table, &$array, $primary_key=NULL )
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
			$values[] = $this->isQuoted( $k ) ? $this->quote( $v, false ) : (int) $v;
		}

		$sql = sprintf( $fmtsql, implode( ',', $fields ) ,  implode( ',', $values ) );
		if (!$this->execute($sql)) {
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
	function getInsertId()
	{
		return (int) $this->_resource->lastInsertId();
	}
	
	/**
	 * 更新记录
	 * 
	 * @Param	string	$table		表名
	 * @Param	object	$array		和数据表字段名称对应的对象y
	 * @Param	string	$where	条件
	 * @param   Boolean $updateNulls 是否更新空值
	 */
	function update( $table, &$array, $where=null, $updateNulls=true )
	{
		if(!empty($where)){
			$fmtsql = 'UPDATE '.$this->nameQuote($table).' SET %s WHERE %s';
		}else{
			$fmtsql = 'UPDATE '.$this->nameQuote($table).' SET %s';
		}
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
				$val = $this->isQuoted( $k ) ? $this->quote( $v, false ) : (int) $v;
			}
			$tmp[] = $this->nameQuote( $k ) . '=' . $val;
		}
		$sql = sprintf( $fmtsql, implode( ",", $tmp ) , $where );
		return $this->execute($sql);
	}
	
	/**
	 * 启动事务
	 * @return mixed
	 */
	public function startTransaction(){
		$this->_resource->beginTransaction();
	}
	
	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit(){
		$this->_resource->commit();
	}
	
	/**
	 * 回滚事务
	 * @return mixed
	 */
	public function rollback(){
		$this->_resource->rollBack();
	}	
	
	/**
	 * 关闭MySQL连接
	 */
	public function close(){
		$this->_resource = null;
		$this->_cursor = null;
		$this->_sql = '';
	}
	
	/**
	 * 获取错误信息
	 */
	public function getErrorMessage(){
		return $this->_resource->errorInfo();
	}	
	
	/**
	 * 获取错误号码
	 */
	public function getErrorNumber(){
		return $this->_resource->errorCode();
	}	
	
	/**
	 * 诊断函数
	 * 
	 * @return	string
	 */
	function explain()
	{
		$temp = $this->_sql;
		$this->_sql = "EXPLAIN " . $this->_sql;

		if (!($cur = $this->query($this->_sql, $this->_bindValues))) {
			return null;
		}
		$first = true;
		$buffer = '<table id="explain-sql" border="1" >';
		$buffer .= '<thead><tr><td colspan="99">'.$this->_sql.'</td></tr>';
		$rows = $this->fetchAll( $this->_sql, $this->_bindValues );
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