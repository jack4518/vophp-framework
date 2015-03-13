<?php
/**
 * 定义Pdo_Sqlite数据库驱动类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Pdo_Sqlite extends VO_Database_Adapter_Pdo_Abstract{
	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_dbType = 'SQLite';
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
			$this->triggerError('请指定要连接的' . $this->_dbType . '数据库.');
			exit;
		}
		
		// 检查数据库连接是否可用
		if(!extension_loaded( 'pdo' )){
			$this->triggerError('"' . $this->_dbType . '" 数据库扩展不可用.');
			exit;
		}

		//连接数据库
		if(file_exists($database)){
			try{
				$this->_resource = new PDO( 'sqlite2:' . $database ); //尝试连接SQLite2
			}catch(PDOException $e) {
				
			}
			
			if (!$this->_resource){
				$this->_resource = new PDO( 'sqlite:' . $database ); //失败后尝试SQLite3
			}
		}else{
			trigger_error('SQLite数据库文件:' . $database . '不存在，请确认文件路径是否正确.', E_USER_ERROR);
			exit;
		}
		// 连接数据库
		if(!$this->_resource instanceof PDO){
			$this->triggerError('无法能通过PDO连接到' . $this->_dbType . '数据库！请检查配置或者' . $this->_dbType . '数据库服务器是否启动.');
			exit;
		}
	}
}