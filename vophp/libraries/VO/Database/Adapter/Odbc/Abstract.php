<?php
/**
 * 定义Odbc_Abstract 数据库抽象类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Odbc_Abstract extends VO_Database_Adapter_Abstract{

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
	 * @var VO_Database_Adapter_Odbc_Abstract
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
	protected $_dbType = 'Access';
	

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
		if(!extension_loaded( 'odbc' )){
			$this->triggerError('"ODBC" 数据库扩展不可用.');
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
		if (is_resource($this->_resource) ) {
			$this->close();
		}
		return true;
	}
	
	/**
	 * 测试数据库是否可用
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
	 * 返回数据库信息
	 * @return string
	 */
	public function getVersion()
	{
		return true;
	}
	
	/**
	 * 获取数据库转义后的特殊字符
	 * @param	string  $text   需要转义的字符串
	 * @param	boolean	$extra  可选项 扩展转义
	 * @return	String 转义后的字符串
	 */
	public function getEscaped( $text, $extra = false )
	{
		//对(%、'、_)进行转义
		$text = addcslashes( $text, '%_\'' );
		return $text;
	}
	
	/**
	 * 执行语句
	 * @Param String $sql
	 * @return mixed
	 */
	public function query($sql = '', $array = array()){
		$this->_sql	= $this->replacePrefix( $sql );
		if (!is_resource($this->_resource)) {
			$this->triggerError('无法连接到' . $this->_dbType . '数据库服务器.');
			return false;
		}
		$this->_sql = $this->_prepare($this->_sql, $array);
		$this->_cursor = odbc_exec($this->_resource, $this->_sql);
		if (!$this->_cursor){
			$this->_errorNum = odbc_error($this->_resource);
			$this->_errorMsg = odbc_errormsg($this->_resource) ." SQL=$this->_sql";
			if ($this->_debug) {
				$this->triggerError('SQL语句执行错误,' . $this->_resource->errno . ':' . $this->_resource->error);
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
	 * 返回最近查询所记录数
	 * @param result cursor
	 * @return int
	 */
	function getNumRows($cur=null)
	{
		if( !($cur = $this->query($this->_sql,$this->_bindValues)) ){
			return 0;
		}
		$num = odbc_num_rows($this->_cursor);
		if($num !== -1){
			return $num;
		}

		$num = 0;
		while ($row = odbc_fetch_array( $this->_cursor )) {
			$num++;
		}
		return $num;
	}
	
	/**
	 * 获取最近查询的记录数
	 * @return	int
	 */
	function getAffectedRows()
	{
		return odbc_num_rows($this->_cursor);
	}	
	
	/**
	 * 取得第一行第一列的字段值
	 * @param String $sql
	 * @return mixed
	 */
	function fetchOne($sql = '', $array=array()){
		$row = array();
		$this->_bindValues = $array;
		if( !($cur = $this->query($sql, $this->_bindValues)) ){
			return null;
		}
		odbc_fetch_into($this->_cursor, $row);
		return $row[0];
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
		$row = odbc_fetch_array($this->_cursor);
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
		if( !($cur = $this->query($sql, $this->_bindValues)) ){
			return null;
		}
		$array = array();
		while ($row = odbc_fetch_array( $this->_cursor )) {
			if ($key) {
				$array[$row[$key]] = $row;
			} else {
				$array[] = $row;
			}
		}
		odbc_free_result( $this->_cursor );
		return $array;
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
	 *基于一个数组，插入一条数据到一个表 
	 *
	 * @Param	string	$table			表名
	 * @Param	object	$array			和数据表字段名称对应的数据
	 * @Param	string	$primary_key	主键名称
	 */
	function insert( $table, &$data, $primary_key=NULL )
	{
		$fmtsql = 'INSERT INTO '.$this->nameQuote($table).' ( %s ) VALUES ( %s ) ';
		$fields = array();
		foreach ($data as $k => $v) {
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
	function getInsertId()
	{
		return true;
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
				$val = $this->isQuoted( $k ) ? $this->quote( $v, false ) : (int) $v;
			}
			$tmp[] = $this->nameQuote( $k ) . '=' . $val;
		}
		$sql = sprintf( $fmtsql, implode( ",", $tmp ) , $where );
		return $this->query($sql);
	}
	
	/**
	 * 启动事务
	 * @return mixed
	 */
	public function startTransaction(){
		odbc_autocommit($this->_resource, false);
	}
	
	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit(){
		odbc_commit($this->_resource);
		odbc_autocommit($this->_resource, true);
	}
	
	/**
	 * 回滚事务
	 * @return mixed
	 */
	public function rollback(){
		odbc_rollback($this->_resource);
		odbc_autocommit($this->_resource, true);
	}	
	
	/**
	 * 关闭ODBC连接
	 */
	public function close(){
		@odbc_free_result($this->_cursor);
		@odbc_close($this->_resource);
		$this->_resource = null;
		$this->_cursor = null;
		$this->_sql = '';
	}
	
	/**
	 * 获取错误信息
	 */
	public function getErrorMessage(){
		return odbc_error($this->_resource);
	}	
	
	/**
	 * 获取错误号码
	 */
	public function getErrorNumber(){
		return odbc_errormsg($this->_resource);
	}	
	
	/**
	 * 诊断函数
	 * @return	string
	 */
	function explain()
	{
		
	}
	/**
	 * 构造一个Select查询对象
	 * @return 返回一个Select对象
	 */
	
	public function getSelect(){
		return new VO_Database_Select($this);
	}
}