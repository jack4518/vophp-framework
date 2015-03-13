<?php
/**
 * 定义Odbc_Access数据库驱动类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Odbc_Access extends VO_Database_Adapter_Odbc_Abstract{
	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_dbType = 'Access';
		$this->_options = $config;
		self::connect();
		$this->setUTF();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Database_Adapter_Odbc_Access
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
		$user		= array_key_exists('user', $this->_options)	? $this->_options['user']		: '';
		$password	= array_key_exists('password',$this->_options)	? $this->_options['password']	: '';
		$database	= array_key_exists('database',$this->_options)	? $this->_options['database']	: '';
		$prefix		= array_key_exists('prefix', $this->_options)	? $this->_options['prefix']	: 'vo_';
		
		$this->_table_prefix = $prefix;
		if(empty($database)){
			$this->triggerError('请指定要连接的' . $this->_dbType . '数据库.');
			exit;
		}
		
		// 检查数据库连接是否可用
		if(!extension_loaded( 'odbc' )){
			$this->triggerError('"ODBC_' . $this->_dbType . '" 数据库扩展不可用.');
			exit;
		}
				
		//连接数据库        
        $dsn = 'Driver={Microsoft Access Driver (*.mdb)};Dbq=' . realpath($database);
		$this->_resource = odbc_connect($dsn, $user, $password);

		// 连接数据库
		if(!is_resource($this->_resource)){
			$this->triggerError('无法能通过ODBC连接到' . $this->_dbType . '数据库文件！请检查配置或者确认' . $this->_dbType . '数据库文件' . $database . '所在路径是否正确,并且读写权限是否正确.');
			exit;
		}
	}
	
	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	function getInsertId()
	{
		$sql = 'SELECT @@identity';
		return (int) $this->fetchOne($sql);
	}

		
	/**
	 * 诊断函数
	 * @return	string
	 */
	function explain()
	{
		
	}	
}