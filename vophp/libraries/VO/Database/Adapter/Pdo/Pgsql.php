<?php
/**
 * 定义Pdo_Pgsql数据库驱动类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Pdo_Pgsql extends VO_Database_Adapter_Pdo_Abstract{
	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_dbType = 'PostgreSQL';
		$this->_options = $config;
		self::connect();
		$this->setUTF();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Database_Adapter_Pdo_Pgsql
	 */
	public function getInstance(){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	/**
	 * 连接数据库
	 */
	public function connect(){
		$host		= array_key_exists('host', $this->_options)	? $this->_options['host']		: 'localhost';
		$port		= array_key_exists('port', $this->_options)	? $this->_options['port']		: '5432';
		$user		= array_key_exists('user', $this->_options)	? $this->_options['user']		: '';
		$password	= array_key_exists('password',$this->_options)	? $this->_options['password']	: '';
		$database	= array_key_exists('database',$this->_options)	? $this->_options['database']	: '';
		$prefix		= array_key_exists('prefix', $this->_options)	? $this->_options['prefix']	: 'vo_';
		
		$this->_table_prefix = $prefix;
		if(empty($database)){
			$this->triggerError('请指定要连接的' . $this->_dbType . '数据库.', E_USER_ERROR);
		}
		
		// 检查数据库连接是否可用
		if(!extension_loaded( 'pdo_pgsql' )){
			$this->triggerError('"PDO_' . $this->_dbType . '" 数据库扩展不可用.', E_USER_ERROR);
		}
		
		//连接数据库        
        $dsn = 'pgsql:host=' . $host . ' port=' . $port . ' dbname=' . $database;
        try{
			$this->_resource = new PDO($dsn, $user, $password);
		}catch(VO_Exception $e){
			$this->triggerError('连接' . $this->_dbType . '数据库了错！请检查配置或者' . $this->_dbType . '数据库服务器是否启动.', E_USER_ERROR);
		}

		// 连接数据库
		if(!$this->_resource instanceof PDO){
			$this->triggerError('无法能通过PDO连接到' . $this->_dbType . '数据库！请检查配置或者' . $this->_dbType . '数据库服务器是否启动.', E_USER_ERROR);
		}
	}
	
	/**
	 * 返回MySQL信息
	 * @return string
	 */
	public function getVersion()
	{
		$sql = "SELECT VERSION() AS ver";
		return $this->fetchOne($sql);
	}
	
	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	function getInsertId()
	{
		$sql = 'select lastval()';
		return $this->fetchOne($sql);
	}

	/**
	 * 启动事务
	 * @return mixed
	 */
	public function startTransaction(){
		$sql = 'START TRANSACTION';
		return $this->query($sql);
	}
	
	/**
	 * 保存回滚节点
	 * @param string $pointName
	 * @return boolean
	 */
	public function savePoint($pointName){
		return $this->query('SAVEPOINT ' . $pointName);
	}
	
	
	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit(){
		$sql = 'COMMIT';
		$this->query($sql);
	}
	
	/**
	 * 回滚事务
	 * @return mixed
	 */
	public function rollback(){
		$sql = 'ROLLBACK';
		$this->query($sql);
	}

	/**
	 * 回滚到保存点
	 * @param string $pointName
	 * return mixed
	 */
	public function rollbackToPoint($pointName){
		$sql = 'ROLLBACK TO SAVEPOINT ' . $pointName;
		return $this->query($sql);
	}	
}