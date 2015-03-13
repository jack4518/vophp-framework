<?php
/**
 * 定义Pdo_MySQL数据库驱动类
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Database_Adapter_Pdo_Mysql extends VO_Database_Adapter_Pdo_Abstract{

	/**
	 * 绑定对象
	 * @var PDOStatement
	 */
	private $_stmt = null;

	/**
	 * 构造函数
	 * @param Array $options  数据库连接参数
	 */
	public function __construct($config){
		$this->_dbType = 'MySQL';
		$this->_options = $config;
		self::connect();
		$this->setUTF();
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Database_Adapter_Pdo_Mysql
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
		$port		= array_key_exists('port', $this->_options)	? $this->_options['port']		: '3306';
		$user		= array_key_exists('user', $this->_options)	? $this->_options['user']		: '';
		$password	= array_key_exists('password',$this->_options)	? $this->_options['password']	: '';
		$database	= array_key_exists('database',$this->_options)	? $this->_options['database']	: '';
		$prefix		= array_key_exists('prefix', $this->_options)	? $this->_options['prefix']	: 'vo_';
		
		$this->_table_prefix = $prefix;
		if(empty($database)){
			$this->triggerError('请指定要连接的MySQL数据库.');
			exit;
		}
		
		// 检查数据库连接是否可用
		if(!extension_loaded( 'pdo_mysql' )){
			$this->triggerError('"MySQL" 数据库扩展不可用.');
			exit;
		}
			
		//连接数据库        
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database;
        $option = array(PDO::ATTR_PERSISTENT=>true, PDO::ATTR_ERRMODE=>2, PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8');
		$this->_resource = new PDO($dsn, $user, $password, $option);	
		//$this->_resource->query('set names utf8;');			

		// 连接数据库
		if(!$this->_resource instanceof PDO){
			$this->triggerError('无法能通过PDO连接到MySQL数据库！请检查配置或者MySQL数据库服务器是否启动.');
			exit;
		}
	}

	/**
     * 取得数据表的字段信息
     * @params string	$table_name	表名
     * @return string
     */
    public function getFields($table_name){
        $sql   = 'DESCRIBE ' . $table_name;
        $result = $this->fetchAll($sql);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $val['Name'] = isset($val['name'])?$val['name']:$val['Name'];
                $val['Type'] = isset($val['type'])?$val['type']: $val['Type'];
                $name= strtolower(isset($val['Field'])?$val['Field']:$val['Name']);
                $info[$name] = array(
                    'name'    => $name ,
                    'type'    => $val['Type'],
                    'notnull' => (bool)(((isset($val['Null'])) && ($val['Null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                    'default' => isset($val['Default'])? $val['Default'] :(isset($val['dflt_value'])?$val['dflt_value']:""),
                    'primary' => isset($val['Key'])?strtolower($val['Key']) == 'pri':(isset($val['pk'])?$val['pk']:false),
                    'autoinc' => isset($val['Extra'])?strtolower($val['Extra']) == 'auto_increment':(isset($val['Key'])?$val['Key']:false),
                );
            }
        }
        
        return $info;
    }

	/**
	 * 获取最后一次Insert的ID号
	 * @return int 返回最后一次插入数据的id
	 */
	public function getInsertId()
	{
		return $this->_resource->lastInsertId();
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
	 * 取得指定表的所有字段
	 * @param string $table
	 */
	 /*
	public function getFields($table){
		$sql = 'DESC ' . $table;
		$ret= $this->query($sql);
		if($ret){
			$rows = $this->_stmt->fetchAll();
		}else{
			$rows = array();
		}

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
		$fields = array();
	}
	*/
}
