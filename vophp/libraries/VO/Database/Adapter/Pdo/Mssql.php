<?php
/**
 * 定义Pdo_MsSQL数据库驱动类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Pdo_Mssql extends VO_Database_Adapter_Pdo_Abstract{
	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_dbType = 'MsSQL';
		$this->_options = $config;
		self::connect();
		$this->setUTF();
	}
	
	/**
	 * 连接数据库
	 */
	public function connect(){
		$host		= array_key_exists('host', $this->_options)	? $this->_options['host']		: 'localhost';
		$port		= array_key_exists('port', $this->_options)	? $this->_options['port']		: '';
		$user		= array_key_exists('user', $this->_options)	? $this->_options['user']		: '';
		$password	= array_key_exists('password',$this->_options)	? $this->_options['password']	: '';
		$database	= array_key_exists('database',$this->_options)	? $this->_options['database']	: '';
		$prefix		= array_key_exists('prefix', $this->_options)	? $this->_options['prefix']	: 'vo_';
		
		$this->_table_prefix = $prefix;
		
		if(empty($database)){
			$this->triggerError('请在配置文件中指定要连接的' . $this->_dbType . '数据库.');
			exit;
		}
		
		// 检查数据库连接是否可用
		if(!extension_loaded( 'pdo_mssql' )){
			trigger_error('"PDO_' . $this->_dbType . '" 数据库扩展不可用,请检查php.ini配置文件是否开启该扩展.', E_USER_ERROR);
			exit;
		}
		
		//连接数据库       
	    if (isset($port)) {
            $seperator = ':';
        	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $seperator = ',';
            }
            $host .= $seperator . $port;
            unset($port);
        }
        
        $dsn = 'mssql:dbname=' . $database . ';host:' . $host;

		$this->_resource = new PDO($dsn, $user, $password);			

		// 连接数据库
		if(!$this->_resource instanceof PDO){
			trigger_error('无法能通过PDO连接到' . $this->_dbType . '数据库！请检查配置或者MsSQL数据库服务器是否启动.', E_USER_ERROR);
			exit;
		}
	}	
	/**
	 * 测试MsSQL数据库是否可用
	 * @return  boolean  成功返回 true, 否则返回 false
	 */
	public function test()
	{
		return (method_exists($this->_resource, 'query' ));
	}
	
	/**
	 * 描述
	 * @return 返回MsSQL信息
	 */
	public function getVersion()
	{
		$sql = "SELECT @@VERSION AS ver";
		return $this->fetchOne($sql);
	}
	
	private function _parseVersion(){
		$version = $this->getVersion();
		preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $info);
		return $info[1];
	}	
	
	/**
	 *基于一个数组，插入一条数据到一个表 
	 *
	 * @Param	string	$table		表名
	 * @Param	object	$array		和数据表字段名称对应的对象y
	 * @Param	string	$keyName	主键名称
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
			$values[] = $this->isQuoted( $k ) ? $this->quote( $v, false ) : (int) $v;
		}

		$sql = sprintf( $fmtsql, implode( ',', $fields ) ,  implode( ',', $values ) );
		if (!$this->execute($sql)) {
			return false;
		}
		$id = $this->getInsertId();
		if ($keyName && $id) {
			$object->$keyName = $id;
		}else{
			$id = null;
		}
		return true;
	}
	
	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	function getInsertId()
	{
		$version = $this->_parseVersion();
		$sql = ($version >= 8) ? "SELECT SCOPE_IDENTITY() AS id" : "select @@IDENTITY AS 'id'";
		return (int) $this->fetchOne($sql);
	}
	
	/**
	 * 诊断函数
	 * 
	 * @return	string
	 */
	function explain()
	{
		return false;
	}

}