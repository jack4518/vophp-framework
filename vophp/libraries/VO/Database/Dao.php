<?php
/**
 * 定义 VO_DAO 数据DAO层数据接口
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-23
 **/
 defined('VOPHP') or die('Restricted access');

class VO_Database_Dao extends VO_Object{
   /**
     * 是否打开就近读写功能
     * @access private
     * @var bool
     */
	private $_local_type = null;

    /**
     * 当前model实例
     * @access private
     * @var Leb_Model
     */
	private $_model= null;

    /**
     * ShardId最大值
     * @var int
     */
    const VSID_MAX = 4095;

    /**
     * ShardId最小值
     * @var int
     */
    const VSID_MIN = 1;

    /**
     * flag最小值
     * @var int
     */
    const FLAG_MIN = 0;

    /**
     * flag最大值
     * @var int
     */
    const FLAG_MAX = 4095;

	/**
	 * 是否启用异步机制
	 */
	protected $_is_rsync = true;

    /**
     * 未知
     * @access private
     * @var array
     */
    private static $_collection;

    /**
     * 未知
     * @access private
     * @var array
     */
    private $shard;

    /**
     * 未知
     * @var string
     */
    const SHARD_TABLE_NAME = 'virt_shard_info';

	/**
	 * 数据库连接存储器
	 * @var array
	 */
    private static $_connect = array(); 


    /**
     * 构造函数
     * @access public
     * @author Chen QiQing <qiqing@leju.com>
     * @return void 
     */
	public function __construct($model){
		$this->_model = $model;
	}
	
    /**
     * 获取同步/异步机制
     * @access protected
     * @author Chen QiQing <qiqing@leju.com>
     * @return void 
     */
	public function getRsync(){
		return $this->_is_rsync;
	}

	/*********************************************** 数据源处理  ****************************************************/

    /**
     * 获取数据库配置信息
     *
     * 获取数据源顺序：
     * 1 IDC就近读写
     * 2 shard切库数据源
     * 3 自定义数据源
     * 4 默认配置数据源
     *
     * @access public
     * @author qiqing<qiqing@leju.com>
     * @param  bool   $is_write   是否写库
     * @return array
     */
    public function getDbConfig($is_write=false){
	    $db_config = C('db');
        if($is_write == true){
			if(isset($db_config['read'])){
				return $db_config['read'];
			}
		}else{
			if(isset($db_config['write'])){
				return $db_config['write'];
			}
		}
    }

	/**
	 * 获取数据库的ID模式
	 * @return string  ID模式
	 */
    public function getIdMode(){
	    $id_modes = array('AUTO_INCREASE', '64_BIT_GLOBAL', '64_BIT_COMPAT');
	    $db_config = C('db');
	    if(isset($db_config['id_mode']) && !empty($db_config['id_mode']) && in_array($db_config['id_mode'], $id_modes)){
		    return $db_config['id_mode'];
	    }else{
		    return 'AUTO_INCREASE';
	    }
    }

	/**
	 * 获取数据库的ID值
	 * @param  int  $shard_id  分片shardID
	 * @param  int  $old_id    兼容模式下的旧ID
	 * @param  int  $flag      兼容模式flag ID
	 * @return string  ID模式
	 */
    public function getPrimaryValue($shard_id=0, $old_id=0, $flag=0){
	    $id_mode = $this->getIdMode();
	    switch($id_mode){
		    case '64_BIT_GLOBAL' : 
		    	if(!$this->is64bit()){
		        	$this->triggerError('64位ID模式不支持32位操作系统，请确认系统是否为32位操作系统', E_USER_ERROR);
		        }
		    	$id = $this->makeSerialId($shard_id);
		    	break;

		    case '64_BIT_COMPAT' :
		    	if(!$this->is64bit()){
		        	$this->triggerError('64位ID模式不支持32位操作系统，请确认系统是否为32位操作系统', E_USER_ERROR);
		        }
		    	$id = $this->makeCompatSerialId($old_id, $flag, $shard_id);
		    	break;

		    case 'AUTO_INCREASE' : 
		    default:
		    	$id = 0;
		    	break;
	    }
	    return $id;
	    
    }



	/*********************************************** Shard处理  ****************************************************/

    /**
     * 生成一个新格式全局序列ID
     * 格式：(42B microtime) + (12B vsid) + (10B autoinc)
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
	 * @param  int    $vsid   Shard ID
     * @return mixed  $serial_id 全局64位ID,生成不成功则返回false
     */
    public static function makeSerialId($vsid)
    {
        if(!is_numeric($vsid) || $vsid < self::VSID_MIN || $vsid > self::VSID_MAX){
            return false;
		}else{
			$vsid = (int)$vsid;
		}

        if(function_exists('make_serial_id')){
            $id = make_serial_id($vsid);
            return $id ? (string)$id : false;
        }

        $auto_inc_sig = self::_getNextValueByShareMemory();
        if(empty($auto_inc_sig)){
			return false;
		}

        $ntime = microtime(true);
        $time_sig = intval($ntime * 1000);
        $serial_id = $time_sig << 12 | $vsid;
        $serial_id = $serial_id << 10 | ($auto_inc_sig % 1024);
        return (string)$serial_id;
    }

    /**
     * 从新格式全局序列ID反解析出虚拟shard编号
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int  $serial_id  新格式全局序列ID
     * @return int  $vsid      虚拟shard编号或者false
     */
    public static function extractVirtShardId($serial_id)
    {
        if(!$serial_id || !is_numeric($serial_id)){
            return false;
		}else{
			$serial_id = (int)$serial_id;
		}

        if(function_exists('extract_virt_shard_id')){
			return extract_virt_shard_id($serial_id);
		}

        if(self::isCompatSerialId($serial_id)){
            $old_id = $flag = $vsid = 0;
            if(!self::extractCompatSerialInfo($serial_id, $old_id, $flag, $vsid)){
                return false;
			}else{
				return $vsid;
			}
        }elseif(self::isGlobalSerialId($serial_id)){
            return $serial_id >> 10 & (0xFFF);
		}else{
			return false;
		}
    }

    /**
     * 根据全局ID获取其时间戳
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int  $serial_id  新格式全局序列ID
     * @return int  $time      创建ID时间戳或者false
     */
    public static function extractTimestamp($serial_id)
    {
        if(!self::isGlobalSerialId($serial_id)){
			return false;
		}

        $time = $serial_id >> 22;
        $time = intval($time / 1000);
        return $time;
    }

    /**
     * 判断是否是局序列id
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int  $serial_id  新格式全局序列ID
     * @return bool
     */
    public static function isGlobalSerialId($serial_id)
    {
        $high28b = $serial_id >> 36;
        if(!$high28b){
			return false;
		}
        $high4b = ($serial_id >> 60) & 0xF; // 最高4位的值
        return 0 != $high4b;
    }

    /**
     * 生成一个兼容老序列的新格式全局序列ID
     *
     * 序列类似MySQL的auto_increment
     * 格式：(4B 0) + (12B flag) + (12B vsid) + (36B old id)
     * 
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int    $flag  原ID所属表编号，防止新兼容ID冲突
     * @return mixed
     */
    public static function makeCompatSerialId($old_id, $flag, $vsid)
    {
        if(!is_numeric($old_id) || !is_numeric($vsid) || !is_numeric($flag)){
			return false;
		}
        if($vsid < self::VSID_MIN || $vsid > self::VSID_MAX){
			return false;
		}
        if($flag < self::FLAG_MIN || $flag > self::FLAG_MAX){
			return false;
		}

        $old_id= (int)$oldId;
        $flag = (int)$flag;
        $vsid = (int)$vsid;

        if(function_exists('make_compat_serial_id')){
            $id = make_compat_serial_id($old_id, $flag, $vsid);
            return $id ? (string)$id : false;
        }

        $serial_id = $flag << 12 | $vsid;
        $serial_id = $serial_id << 36 | $old_id;
        return (string)$serial_id;
    }

    /**
     * 替换64位ID中的ShardId值
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int  $serial_id  兼容或全新格局ID
     * @param  int  $shard_id   ShardId
     * @return mixed
     */
    public static function replaceShardId($serial_id, $shard_id)
    {
        $shard_id = (int)$shard_id;
        $serial_id= (int)$serial_id;
        if($shard_id < self::VSID_MIN || $shard_id > self::VSID_MAX){
			return false;
		}

        $old_id = $flag = $vsid = false;
        if(self::isGlobalSerialId($serial_id)){
            $time = $serial_id >> 22;
            $id   = $serial_id & 0x3ff;
            return ($time << 22 | $shard_id << 10) | $id;
        }elseif(self::isCompatSerialId($serial_id) && self::extractCompatSerialInfo($serial_id, $old_id, $flag, $vsid)){
            return self::makeCompatSerialId($old_id, $flag, $shard_id);
		}else{
			return false;
		}
    }

    /**
     * 是否是兼容格式全局序列ID
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int  $serial_id
     * @return bool
     */
    public static function isCompatSerialId($serial_id)
    {
        $high28b = $serial_id >> 36;
        if(0 == $high28b){
			return false;
		}
        $high4b = $serial_id >> 60 & 0xF; // 最高4位的值
        return 0 == $high4b;
    }

    /**
     * 解析是兼容格式全局序列ID获取对应的信息
     *
     * 格式：(4B 0) + (12B flag) + (12B vsid) + (36B old id)
     *
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  int  $serial_id
     * @param  int  $old_id  老式36-integer
     * @param  int  $flag   老式12-integer ID的类型标识
     * @param  int  $vsid   该ID记录的虚拟shard编号（12-integer）
     * @return mixed
     */
    public static function extractCompatSerialInfo($serial_id, &$old_id, &$flag, &$vsid)
    {
        if(!$serial_id || !is_numeric($serial_id)){
            return false;
		}else{
			$serial_id = (int)$serial_id;
		}

        if(function_exists('extract_compat_serial_info')){
			return extract_compat_serial_info($serial_id, $old_id, $flag, $vsid);
		}

        if(!self::isCompatSerialId($serial_id)){
			return false;
		}

        $old_id = $serial_id & 0xFFFFFFFFF;
        $vsid = $serial_id >> 36 & 0xFFF;
        $flag = $serial_id >> 48 & 0xFFF;
        return true;
    }

    /**
     * 返回PHP是否支持64位ID
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return bool
     */
    public static function is64bit(){
        return PHP_INT_MAX > 2147483647;
    }

    /**
     * 通过本机共享内存件来生成一个auto_increment序列
     *
     * 序列类似MySQL的auto_increment
     *
     * @access private
     * @author Chen QiQing <cqq254@163.com>
     * @param  void
     * @return mixed
     */
    private static function _getNextValueByShareMemory(){
        $addr = '127.0.0.1';
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}elseif(!empty($_SERVER['SERVER_ADDR'])){
			$addr = $_SERVER['SERVER_ADDR'];
		}

        $skey = 'global_serial_generator_seed_'.$addr;
        $ikey = crc32($skey);

        $sem = $shm = null;
        $retry_times = 1;
        do{
            $sem = sem_get($ikey, 1, 0777);
            $shm = shm_attach($ikey, 128, 0777);
            if(is_resource($sem) && is_resource($shm))
                break;

            $cmd = "ipcrm -M 0x00000000; ipcrm -S 0x00000000; ipcrm -M {$ikey} ; ipcrm -S {$ikey}";
            $last_line = exec($cmd, $output, $retval);
        }while($retry_times-- > 0);

        if(!sem_acquire($sem)){
			return false;
		}

        $next_value = false;
        if(shm_has_var($shm, $ikey)){
            shm_put_var($shm, $ikey, $next_value=shm_get_var($shm, $ikey)+1);
		}else{
			shm_put_var($shm, $ikey, $next_value=1);
		}

        $shm && shm_detach($shm);
        $sem && sem_release($sem);
        return $next_value;
    }

	/****************************************************数据库操作*************************************/
    /**
     * 获取写库操作对象
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @return mixed 
     */
    public function getMasterConnect($db_config=array()){
        return $this->_getDbConnect($db_config, true);
    }

    /**
     * 获取从库操作对象（vsid不传则返回最近使用的分库）
     * @access public
     * @author Chen QiQing <cqq254@163.com>
     * @param  mixed  $vsid  Shard ID
     * @return mixed
     */
    public function getSlaveConnect($db_config=array()){
        return $this->_getDbConnect($db_config, false);
    }

    /**
     * 获取数据库操作对象
     * @access private 
     * @author Chen QiQing <cqq254@163.com>
     * @param  bool   $is_write  是否连接写库
     * @param  mixed  $vsid  Shard ID
     * @return mixed
     */
    private function _getDbConnect($db_config=array(), $is_write=false){
	    if(!$db_config){
	        $db_config = $this->getDbConfig($is_write);
	    }
		if(!$db_config){
			$this->triggerError('无法获取数据库配置信息', E_USER_ERROR);
			return false;
		}
        return VO_Database::getDb($db_config);
    }
}
