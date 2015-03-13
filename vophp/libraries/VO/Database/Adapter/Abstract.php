<?php
/**
 * 类说明：数据库抽象类
 * 作者：  JackChen
 * 时间：  2010-05-25
*/

defined('VOPHP') or die('Restricted access');

abstract class VO_Database_Adapter_Abstract extends VO_Object{
	/**
	 * The fields that are to be quote
	 *
	 * @var array
	 */
	protected $_quoted	= array();

	/**
	 *  是否需要过滤
	 * @var bool
	 */
	protected $_hasQuoted	= null;

	/**
	 * 字段过滤符
	 * var string
	 */	
	protected $_nameQuote = "`";

	/**
	 * 表前缀
	 * var string
	 */
	protected $_table_prefix = '#__';

	/**
	 * 全部字段类型
	 * var array
	 */
	protected $_column_types = array('int', 'bigint', 'binary', 'bit', 'blob', 'bool', 'boolean', 'char', 'date', 'time', 'datetime', 'decimal', 'double', 'enum', 'float', 'longblob', 'longtext', 'mediumblob', 'mediumint', 'mediumtext', 'numeric', 'real', 'set', 'smallint', 'text', 'time', 'timestamp', 'tinyblob', 'tinying', 'tinytext', 'varbinary', 'varchar', 'year');	
	
	

	/**
	 * 构造函数
	 */
	public function __construct(){
	}
	
	/**
	 * 数据库析构函数
	 *
	 * @return  boolean
	 */
	public function __destruct()
	{
		
	}	
	
	/**
	 * 连接数据库
	 */
	public function connect(){
		
	}	
	
	/**
	 * 测试Mysql数据库是否可用
	 * @return  boolean  成功返回 true, 否则返回 false
	 */
	public function test()
	{

	}
	
	/**
	 * 检测当前是连接状态
	 *
	 * @return	boolean
	 */
	public function isConnected()
	{
		
	}

	/**
	 * 描述
	 *
	 * @访问类型 public
	 * @return 返回MySQL版本号
	 */
	public function getVersion()
	{

	}
	
	/**
	 * 打开数据库
	 * @param	string $database
	 * @return	Boolean 成功返回 true 否则返回 false
	 */
	public function selectDatabase($database=null)
	{
		return true;
	}	
	
	/**
	 * 替换表前缀
	 * @param String $sql
	 * @param String $prefix
	 */
	public function replacePrefix( $sql, $prefix='#__' )
	{
		$sql = trim( $sql );

		$escaped = false;
		$quoteChar = '';

		$n = strlen( $sql );

		$startPos = 0;
		$literal = '';
		while ($startPos < $n) {
			$ip = strpos($sql, $prefix, $startPos);
			if ($ip === false) {
				break;
			}

			$j = strpos( $sql, "'", $startPos );
			$k = strpos( $sql, '"', $startPos );
			if (($k !== FALSE) && (($k < $j) || ($j === FALSE))) {
				$quoteChar	= '"';
				$j			= $k;
			} else {
				$quoteChar	= "'";
			}

			if ($j === false) {
				$j = $n;
			}
			$literal .= str_replace( $prefix, $this->_table_prefix,substr( $sql, $startPos, $j - $startPos ) );
			$startPos = $j;

			$j = $startPos + 1;

			if ($j >= $n) {
				break;
			}

			// quote comes first, find end of quote
			while (TRUE) {
				$k = strpos( $sql, $quoteChar, $j );
				$escaped = false;
				if ($k === false) {
					break;
				}
				$l = $k - 1;
				while ($l >= 0 && $sql{$l} == '\\') {
					$l--;
					$escaped = !$escaped;
				}
				if ($escaped) {
					$j	= $k+1;
					continue;
				}
				break;
			}
			if ($k === FALSE) {
				// error in the query - no end quote; ignore it
				break;
			}
			$literal .= substr( $sql, $startPos, $k - $startPos + 1 );
			$startPos = $k+1;
		}
		if ($startPos < $n) {
			$literal .= substr( $sql, $startPos, $n - $startPos );
		}
		return $literal;
	}

	/**
	 * 模拟PDO的prepare语句
	 * @param string $sql
	 * @param array $array
	 * @return string
	 */
	protected function _prepare( $sql, array $array )
	{
		$i = 0;
		foreach ( $array as $field => $v ){
			$type = gettype( $v );
			if((string)$v == $v){
				$type = 'string';
			}elseif((int)$v == $v){
				$type = 'double';
			}elseif((boolean)$v == $v){
				$type = 'boolean';
			}
			switch($type){
				case 'string':	$v = $this->quote( $v ); break;
				case 'double':	$v = str_replace( ',', '.', $v ); break;
				case 'boolean':	$v = $v ? true : false; break;
				case null:		$v = 'NULL'; break;
				default:		$v = $v; break;
			}
			$sql = str_replace($field, $v, $sql);
		}
		return $sql;
	}	
	
	/**
	 * 为
	 * @param $s
	 */
	public function nameQuote( $s )
	{
		// Only quote if the name is not using dot-notation
		if (strpos( '.', $s ) === false)
		{
			$q = $this->_nameQuote;
			if (strlen( $q ) == 1) {
				return $q . $s . $q;
			} else {
				return $q{0} . $s . $q{1};
			}
		}
		else {
			return $s;
		}
	}
	
	/**
	 * 检测字段是否需要转义
	 * @param string $fieldName 字段名
	 * @return bool
	 */
	function isQuoted( $fieldName )
	{
		if ($this->_hasQuoted) {
			return in_array( $fieldName, $this->_quoted );
		} else {
			return true;
		}
	}
	
	/**
	* 对字段值加上引号，并且也可以对其进行转义
	*
	* @param	string	$test
	* @param	boolean	$escaped 是否需要转义
	* @return	string
	* @access public
	*/
	function quote( $text, $escaped = true )
	{
		array_push($this->_quoted, $text);
		return '\''.($escaped ? $this->getEscaped( $text ) : $text).'\'';
	}
	
	/**
	 * 获取MySQL转义后的特殊字符
	 *
	 * @param	string  $text   需要转义的字符串
	 * @param	boolean	$extra  可选项 扩展转义
	 */
	public function getEscaped( $text, $extra = false )
	{
		return addslashes($text);
	}
	
	/**
	 * 执行MySQL语句
	 * @Param String $sql
	 * 
	 * return 查询句柄
	 */
	public function query($sql = ''){
	
	}
	
	/**
	 * 获取查询的记录数
	 * @param db resource $cur 
	 */
	function getNumRows( $cur=null )
	{
		
	}
	
	/**
	 * 获取最近查询的记录数
	 * @return	int
	 */
	function getAffectedRows()
	{

	}		
	
	/**
	 * 取得第一行第一列的字段值
	 * @param String $sql
	 */
	function fetchOne($sql = ''){
		
	}
	
	/**
	 * 取得一行数据
	 * @param String $sql
	 */
	function fetchRow($sql = ''){
		
	}
	
	/**
	 * 取得多行数据
	 * @param String $sql
	 */
	function fetchAll($sql = ''){
		
	}
	
	/**
	 *基于一个数组，插入一条数据到一个表 
	 *
	 * @Param	string	$table			表名
	 * @Param	object	$array			和数据表字段名称对应的数据
	 * @Param	string	$primary_key	主键名称
	 */
	function insert( $table, &$array, $primary_key=NULL ){
		
	}
	
	/**
	 * 删除记录
	 * @Param	string	$table		表名
	 * @Param	string	$where	           条件
	 * @return mixed
	 */
	function delete( $table, $where=null){
		
	}	
	
	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	function getInsertId(){
		
	}
	
	/**
	 * 更新记录
	 * @Param	string	$table		表名
	 * @Param	object	$array		和数据表字段名称对应的对象
	 * @Param	string	$where	条件
	 * @param   Boolean $updateNulls 是否更新空值
	 */
	public function update( $table, &$array, $where=null, $updateNulls=true ){
		
	}
	
	/**
	 * 清空数据库
	 * @param string $table
	 */
	public function truncate($table){

	}
	
	/**
	 * 启动事务
	 * @return mixed
	 */
	public function startTransaction(){

	}
	
	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit(){

	}
	
	/**
	 * 回滚事务
	 * @return mixed
	 */
	public function rollback(){
		
	}	
	
	/**
	 * 更新记录
	 * 
	 * @return VO_Database_Select
	 */
	function getSelect(){
		
	}
	
	/**
	 * 获取SQL语句
	 * 
	 * @return string
	 */
	function getSql(){
		
	}	
	
	/**
	 * 获取错误信息
	 */
	public function getErrorMessage(){

	}	
	
	/**
	 * 获取错误号码
	 */
	public function getErrorNumber(){

	}	
	
	/**
	 * 关闭数据库
	 * @return boolean
	 */
	public function close(){
	
	}
	
	/**
	 * 诊断函数
	 * @return	string
	 */
	public function explain()
	{
	
	}

	/**
	 * 取得数据库表信息
	 * @param string $dbname
	 */
	public function getTables($dbname = null){
		
	}	
	
	/**
	 * 取得指定表的所有字段
	 * @param string $table
	 */
	public function getFields($table){
		
	}

	/**
     * 创建数据库
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $name  待创建数据库名称
     * @return bool  是否创建成功
     */
    public function createDatabase($name){
	    
    }

	/**
     * 删除数据库
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $name  待删除数据库名称
     * @return bool  是否删除成功
     */
    public function dropDatabase($name){
	    
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
     * @return bool
     */
    public function createTable($table, array $columns, $options=null){
	    
    }

    /**
     * 删除表
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $table  表名
     * @return bool
     */
    public function dropTable($table){
	    
    }

	/**
	 * 重命名表名
	 * @param string $tablename  原表名
	 * @param string $tablename_new 新表名
	 * @return bool 是否重命名成功
	 */
	public function renameTable($tablename, $tablename_new){
		
	}

	/**
     * 清空表数据重建
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
     * @param  string  $table    表名
     * @return bool
     */
    public function truncateTable($table){
	    
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
	 */
	public function addColumn($table, $column, $type, $default=null, $is_null=true, $comment='', $after=null){
		
	}

	/**
	 * 删除表字段
	 * @param string $table  表名
	 * @param string $column 列名
	 */
	public function dropColumn($table, $column = null){
		
	}

	/**
	 * 重命名表字段
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
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
		
	}

	/**
     * 新增主键
     * @access public
     * @author Chen QiQIng <cqq254@163.com>
     * @param   string  $table  表名
     * @param   string  $name  主键名
     * @param   array|strings   $columns  列名,如果为字符串，刚以,分隔
     * @return  bool  是否添加成功
     */
    public function addPrimaryKey($table, $name, $columns){
	    
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
    public function createIndex($table, $index_name, $columns, $unique=false){
	    
    }

    /**
     * 删除索引
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  string  $index_name  索引名称
     * @param  string  $table  索引所在表名
     * @return bool  是否删除成功
     */
    public function dropIndex($table, $name){
	    
    }
}