<?php
/**
 * Shard扩展
 * 
 * @category    Leb
 * @package     Leb_Shard
 * @author      Chen QiQing <qiqing@leju.com>
 * @license     http://slae.leju.com Slae Team Lincense
 * @copyright   © 1996 - 2014 新浪乐居
 * @version     $Id: model.php 50110 2014-08-26 16:09:19Z Chen QiQing $
 */

class Leb_Shard
{
    /**
     * 属性值数组
     * @access private
     * @var array
     */
    private $_attr = array();

    /**
     * 错误信息
     * @access private
     * @var array
     */
    private $_errors = array();

    /**
     * 使用场景名称
     * @access private
     * @var string
     */
    private $_scenes = '';

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


	public function __construct($model){
		$this->_model = $model;
	}

    /**
     * 数据验证规则
     *
     * 格式
     *          array('title', 'ruleName', 'param', 'on' => 'attrName')
     *
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return array
     */
    public function rules()
    {
        return array();
    }

    /**
     * 设置使用场景
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $scenes
     * @return string
     */
    public function setScenes($scenes)
    {
        $this->_scenes = $scenes;
    }

    /**
     * 返回使用场景名称
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return string
     */
    public function getScenes()
    {
        return $this->_scenes;
    }

    /**
     * 设置属性值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $name
     * @param  mixed   $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->_attr[$name] = $value;
    }

    /**
     * 获取属性值或关系对象
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->_attr[$name]))
            return $this->_attr[$name];
        elseif(property_exists($this, $name))
            return $this->$name;
        else
            return null;
    }

    /**
     * 返回是否设置对象
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_attr[$name]);
    }

    /**
     * 置空对象或属性
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->_attr[$name]);
    }

    /**
     * 获取字符串属性值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * 设置可以序列化的属性
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return array
     */
    public function __sleep()
    {
        return array_keys((array)$this);
    }

    /**
     * 获取错误信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $attrName
     * @param  array   $def
     * @return mixed
     */
    public function getErrors($attrName = null, $def = array())
    {
        if(null === $attrName)
            return $this->_errors;
        else
            return isset($this->_errors[$attrName]) ? $this->_errors[$attrName] : $def;
    }

    /**
     * 获取指定错误
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $attrName
     * @param  array   $def
     * @return mixed
     */
    public function getError($attrName='', $def = null)
    {
        return isset($this->_errors[$attrName]) ? $this->_errors[$attrName] : $def;
    }

    /**
     * 增加错误信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $attrName
     * @param  mixed   $error
     * @return mixed
     */
    public function addError($attrName, $error)
    {
        return $this->_errors[$attrName][] = $error;
    }

    /**
     * 清除错误信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $attrName
     * @return void
     */
    public function clearErrors($attrName = null)
    {
        if(null === $this->_errors)
            $this->_errors = array();
        else
            unset($this->_errors[$attrName]);
    }

    /**
     * 返回错误信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $attrName
     * @return bool
     */
    public function hasErrors($attrName = null)
    {
        if(null === $attrName)
            return $this->_errors!==array();
        else
            return isset($this->_errors[$attrName]);
    }

    /**
     * 验证属性值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $attr
     * @param  bool    $clearErrors
     * @return bool
     */
    public function validate($attr = '', $clearErrors = true)
    {
        $clearErrors && $this->clearErrors();
        if(!$validators = $this->getValidators($attr))
            return true;

        foreach($validators as $validator)
        {
            if($attr && !in_array($attr, $validators->on))
                continue;
            foreach($validator->field as $field)
            {
                $validator->validate($this, $field);
                if($validator->skip && $this->hasErrors())
                    break;
            }
        }
        return !$this->hasErrors();
    }

    /**
     * 生成验证器
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return array  $validators
     */
    public function getValidators()
    {
        require_once(_VALIDATOR_.'abstract.php');
        $validators = array();
        foreach($this->rules() as $rule)
        {
            if(isset($rule[0]) && isset($rule[1]) && $obj = Leb_Validator_Abstract::getInstance($rule[1], $rule[0], array_slice($rule, 1)))
                $validators[] = $obj;
            else
                throw new Leb_Exception("Can't find class {$rule[1]}");
        }

        return $validators;
    }
	/***************************************************************************************************/

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
     * @param  int    $vsid
     * @param  bool   $master
     * @return array
     */
    public function getDbInfo($vsid=0, $master=false){
		$vsid = (int)$vsid;
		if(empty($vsid)){
			$this->addError('', 'Vsid is require not empty');
			return false;
		}
        $cfg = null;

        // 1.IDC就近数据源读写
        //$this->_localType && $cfg = $this->getIdcDbInfo($master);
        $param = array($master, $this->_vsid);
        if($this->_local_type){
            $idc = new Leb_Idc_Info();
            $param[] = $idc->whereIs();
        }

        // 2.Shard数据源
		$id_mode = $this->_model->getIdMode();
        if($id_mode == Leb_Model::ID_GLOBAL && $vsid){
            $cfg = $this->getShardConfig($vsid);
        }

        // 3.自定义数据源(从子类中的dbInfo方法中设置的数据库信息取)
		if(!$cfg){
			$cfg = call_user_func_array(array($this->_model, 'dbInfo'), $param);
			if($cfg){
				isset($cfg[self::DB_CFG_PREFIX]) && self::$_prefix = $cfg[self::DB_CFG_PREFIX];
				isset($cfg[self::DB_CFG_SUFFIX]) && self::$_suffix = $cfg[self::DB_CFG_SUFFIX];
			}
		}

		if(!$cfg){
			// 4.默认数据源
			$cfg = $this->_dbInfo($master);
		}

        if($cfg){
            $this->_dbOption();
            $master && !isset($cfg[self::DB_CFG_MASTER]) &&  $cfg[self::DB_CFG_MASTER] = $cfg[self::DB_CFG_SLAVE];
            return $cfg;
        }
        else
            return array();
    }

    /**
     * 返回默认数据库配置信息
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  bool   $master
     * @param  int    $vsid
     * @return array
     */
    private function _dbInfo($master=false, $vsid=false)
    {
        $cfg = include(_CONFIG_.'db.php');
        !isset($cfg[self::DB_CFG_MASTER]) && $cfg[self::DB_CFG_MASTER] = $cfg[self::DB_CFG_SLAVE];
        return $cfg;
    }

    /**
     * 获取数据配置属性
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @param  void
     * @return void
     */
    private function _dbOption()
    {
        if(is_null(self::$_prefix) || is_null(self::$_suffix))
        {
            $cfg = include(_CONFIG_.'db.php');
            self::$_prefix = !empty($cfg[self::DB_CFG_PREFIX]) ? $cfg[self::DB_CFG_PREFIX] : '';
            self::$_suffix = !empty($cfg[self::DB_CFG_SUFFIX]) ? $cfg[self::DB_CFG_SUFFIX] : '';
        }
    }

    /**
     * 生成一个新格式全局序列ID
     *
     * 格式：(42B microtime) + (12B vsid) + (10B autoinc)
     *
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return mixed  $serial_id
     */
    public static function makeSerialId($vsid)
    {
        if(!is_numeric($vsid) || $vsid < self::VSID_MIN || $vsid > self::VSID_MAX)
            return false;
        else
            $vsid = (int)$vsid;

        if(function_exists('make_serial_id'))
        {
            $id = make_serial_id($vsid);
            return $id ? (string)$id : false;
        }

        $auto_inc_sig = self::getNextValueByShareMemory();
        if(empty($auto_inc_sig))
            return false;

        $ntime = microtime(true);
        $time_sig = intval($ntime * 1000);
        $serial_id = $time_sig << 12 | $vsid;
        $serial_id = $serial_id << 10 | ($auto_inc_sig % 1024);
        return (string)$serial_id;
    }

    /**
     * 从新格式全局序列ID反解析出虚拟shard编号
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId  新格式全局序列ID
     * @return int  $vsid      虚拟shard编号或者false
     */
    public static function extractVirtShardId($serialId)
    {
        if(!$serialId || !is_numeric($serialId))
            return false;
        else
            $serialId = (int)$serialId;

        if(function_exists('extract_virt_shard_id'))
            return extract_virt_shard_id($serialId);

        if(self::isCompatSerialId($serialId))
        {
            $oldId = $flag = $vsid = 0;
            if(!self::extractCompatSerialInfo($serialId, $oldId, $flag, $vsid))
                return false;
            else
                return $vsid;
        }
        elseif(self::isGlobalSerialId($serialId))
            return $serialId >> 10 & (0xFFF);
        else
            return false;
    }

    /**
     * 根据全局ID获取其时间戳
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId  新格式全局序列ID
     * @return int  $time      创建ID时间戳或者false
     */
    public static function extractTimestamp($serialId)
    {
        if(!self::isGlobalSerialId($serialId))
            return false;

        $time = $serialId >> 22;
        $time = intval($time / 1000);
        return $time;
    }

    /**
     * 判断是否是新格式的新格式的全局序列id
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId  新格式全局序列ID
     * @return bool
     */
    public static function isGlobalSerialId($serialId)
    {
        $high28b = $serialId >> 36;
        if(!$high28b)
            return false;
        $high4b = ($serialId >> 60) & 0xF; // 最高4位的值
        return 0 != $high4b;
    }

    /**
     * 生成一个兼容老序列的新格式全局序列ID
     *
     * 序列类似MySQL的auto_increment
     * 格式：(4B 0) + (12B flag) + (12B vsid) + (36B old id)
     * 
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int    $flag  原ID所属表编号，防止新兼容ID冲突
     * @return mixed
     */
    public static function makeCompatSerialId($oldId, $flag, $vsid)
    {
        if(!is_numeric($oldId) || !is_numeric($vsid) || !is_numeric($flag))
            return false;
        if($vsid < self::VSID_MIN || $vsid > self::VSID_MAX)
            return false;
        if($flag < self::FLAG_MIN || $flag > self::FLAG_MAX)
            return false;

        $oldId= (int)$oldId;
        $flag = (int)$flag;
        $vsid = (int)$vsid;

        if(function_exists('make_compat_serial_id'))
        {
            $id = make_compat_serial_id($oldId, $flag, $vsid);
            return $id ? (string)$id : false;
        }

        $serial_id = $flag << 12 | $vsid;
        $serial_id = $serial_id << 36 | $oldId;
        return (string)$serial_id;
    }

    /**
     * 替换64位ID的ShardId值
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId  兼容或全新格局ID
     * @param  int  $shardId   ShardId
     * @return mixed
     */
    public static function tranShardId($serialId, $shardId)
    {
        $shardId = (int)$shardId;
        $serialId= (int)$serialId;
        if($shardId < self::VSID_MIN || $shardId > self::VSID_MAX)
            return false;

        $oldId = $flag = $vsid = false;
        if(self::isGlobalSerialId($serialId))
        {
            $time = $serialId >> 22;
            $id   = $serialId & 0x3ff;
            return ($time << 22 | $shardId << 10) | $id;
        }
        elseif(self::isCompatSerialId($serialId) && self::extractCompatSerialInfo($serialId, $oldId, $flag, $vsid))
            return self::makeCompatSerialId($oldId, $flag, $shardId);
        else
            return false;
    }

    /**
     * 是否是兼容格式全局序列ID
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId
     * @return bool
     */
    public static function isCompatSerialId($serialId)
    {
        $high28b = $serialId >> 36;
        if(0 == $high28b)
            return false;
        $high4b = $serialId >> 60 & 0xF; // 最高4位的值
        return 0 == $high4b;
    }

    /**
     * 解析是兼容格式全局序列ID获取对应的信息
     *
     * 格式：(4B 0) + (12B flag) + (12B vsid) + (36B old id)
     *
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId
     * @param  int  $oldId  老式36-integer
     * @param  int  $flag   老式12-integer ID的类型标识
     * @param  int  $vsid   该ID记录的虚拟shard编号（12-integer）
     * @return mixed
     */
    public static function extractCompatSerialInfo($serialId, &$oldId, &$flag, &$vsid)
    {
        if(!$serialId || !is_numeric($serialId))
            return false;
        else
            $serialId = (int)$serialId;

        if(function_exists('extract_compat_serial_info'))
            return extract_compat_serial_info($serialId, $oldId, $flag, $vsid);

        if(!self::isCompatSerialId($serialId))
            return false;

        $oldId = $serialId & 0xFFFFFFFFF;
        $vsid = $serialId >> 36 & 0xFFF;
        $flag = $serialId >> 48 & 0xFFF;
        return true;
    }

    /**
     * 返回PHP是否支持64位ID
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return bool
     */
    public static function is64bit()
    {
        return PHP_INT_MAX > 2147483647;
    }

    /**
     * 通过本机共享内存件来生成一个auto_increment序列
     *
     * 序列类似MySQL的auto_increment
     *
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return mixed
     */
    private static function getNextValueByShareMemory()
    {
        $addr = '127.0.0.1';
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
        elseif(!empty($_SERVER['SERVER_ADDR']))
            $addr = $_SERVER['SERVER_ADDR'];

        $skey = 'global_serial_generator_seed_'.$addr;
        $ikey = crc32($skey);

        $sem = $shm = null;
        $retry_times = 1;
        do
        {
            $sem = sem_get($ikey, 1, 0777);
            $shm = shm_attach($ikey, 128, 0777);
            if(is_resource($sem) && is_resource($shm))
                break;

            $cmd = "ipcrm -M 0x00000000; ipcrm -S 0x00000000; ipcrm -M {$ikey} ; ipcrm -S {$ikey}";
            $last_line = exec($cmd, $output, $retval);
        }while($retry_times-- > 0);

        if(!sem_acquire($sem))
            return false;

        $next_value = false;
        if(shm_has_var($shm, $ikey))
            shm_put_var($shm, $ikey, $next_value=shm_get_var($shm, $ikey)+1);
        else
            shm_put_var($shm, $ikey, $next_value=1);

        $shm && shm_detach($shm);
        $sem && sem_release($sem);
        return $next_value;
    }

    /**
     * 大数进制转换
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $numberInput
     * @param  string  $fromBaseInput
     * @param  string  $toBaseInput
     * @return string
     */
    public static function convBase($numberInput, $fromBaseInput, $toBaseInput)
    {
        if($fromBaseInput == $toBaseInput)
            return $numberInput;

        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);

        $retval='';
        if($toBaseInput == '0123456789')
        {
            $retval = 0;
            for($i = 1;$i <= $numberLen; $i++)
            {
                $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
            }
            return $retval;
        }

        if($fromBaseInput != '0123456789')
            $base10= self::convBase($numberInput, $fromBaseInput, '0123456789');
        else
            $base10 = $numberInput;

        if($base10<strlen($toBaseInput))
            return $toBase[$base10];

        while($base10 != '0')
        {
            $retval = $toBase[bcmod($base10,$toLen)].$retval;
            $base10 = bcdiv($base10,$toLen,0);
        }

        return $retval;
    }

    /**
     * 返回是否为有效64位ID
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int  $serialId
     * @return bool
     */
    public static function isValidSerialId($serialId)
    {
        return self::isGlobalSerialId($serialId) || self::isCompatSerialId($serialId);
    }
    
    
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
     * 获取shard实例信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $config
     * @return mixed
     */
    public static function getInstance($config='')
    {
        $key = '';
        $key = !is_string($config) ? md5(json_encode($config)) : md5($config);
        if($key && isset(self::$_collection[$key]))
            return self::$_collection[$key];

        !$config && $config = require(_CONFIG_._DS_.'shard.php');
        isset($config['database']) && $config = $config['database'];
        $shard = new self();
        if($shard->init($config))
            return self::$_collection[$key] = $shard;
        else
            $shard = null;

        return $shard;
    }

    /**
     * 初始化
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $config
     * @return bool
     */
    private function init($config)
    {
        if(!_DEBUG_)
        {
            $key  = $this->getCacheKey($config['read']['dbname']);
            $redis = Leb_Dao_Redis::getInstance();
            if($redis && $data = $redis->get($key))
            {
                $tmp = gzuncompress($data);
                if($val = json_decode($tmp ? $tmp : $data, true))
                {
                    $this->shard = $val;
                    return true;
                }
            }
        }

        return $this->flush($config);
    }

    /**
     * shard信息缓存key
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $name
     * @return string
     */
    private function getCacheKey($name)
    {
        return $name.'_virt_shard_key_'.Slae::app()->getVer();
    }

    /**
     * shard信息更新
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  array  $cfg
     * @return bool
     */
    public function flush($cfg=null)
    {
        $conn = Leb_Db_Abstract::getConnection($cfg['read']);
        if(!$this->hasShardInfo($conn))
            return false;

        if(!$rows= $conn->queryAll('SELECT * FROM `'.self::SHARD_TABLE_NAME.'`'))
            return false;

        foreach($rows as $row)
        {
            $this->shard['byid'][$row['vsid']] = $row;
            $sv = explode(',', $row['shard_value']);
            foreach($sv as $v)
            {
                $bynameKey = $row['shard_name'] . '_' . $v;
                $this->shard['byname'][$bynameKey][$row['vsid']] = $row;
            }

            $this->shard['uptime'] = time();
        }

        $redis = Leb_Dao_Redis::getInstance();
        $data = json_encode($this->shard);
        function_exists('gzcompress') && $data = gzcompress($data, 9);
        $key  = $this->getCacheKey($cfg['read']['dbname']);
        $redis && $data && $redis->set($key, $data);

        return true;
    }

    /**
     * 根据名称获取shard信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  string  $shardName
     * @param  mixed   $shardValue
     * @return mixed
     */
    public function getShardByName($shardName, $shardValue)
    {
        if(is_array($shardValue))
        {
            $sa = array();
            foreach($shardValue as $v)
            {
                $key = $shardName . '_' . $v;
                isset($this->shard['byname'][$key]) && $sa[$v] = $this->shard['byname'][$key];
            }

            return $sa;
        }
        else
        {
            $key = $shardName . '_' . $shardValue;
            return isset($this->shard['byname'][$key]) ? $this->shard['byname'][$key] : false;
        }
    }

    /**
     * 根据Id获取shard信息
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int    $shardId
     * @param  array  $cfg
     * @return mixed
     */
    public function getShardById($shardId, $cfg=null)
    {
        return isset($this->shard['byid'][$shardId]) ? $this->shard['byid'][$shardId] : false;
    }

    /**
     * 加载物理Shard配置
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return array
     */
    private function getRealShard()
    {
        if(null === self::$_pshard)
        {
            self::$_pshard = array();
            $tmp = require(_CONFIG_._DS_.'physhard.php');
            $tmp && self::$_pshard = $tmp;
        }

        return self::$_pshard;
    }

    /**
     * 根据Id获取物理shard信息
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int    $shardId
     * @return mixed
     */
    private function getRealShardById($shardId)
    {
        $shard = $this->getRealShard();
        return isset($shard[$shardId]) ? $shard[$shardId] : false;
    }

    /**
     * 根据Vsid获取物理shard信息
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int    $vsid
     * @param  array  $cfg
     * @return mixed
     */
    private function getShardConfig($vsid, $cfg=null)
    {
        if(!$psids = $this->getShardById($vsid, $cfg))
            return false;
        $rpsid = $psids['read_phy_shard_id'];
        $wpsid = $psids['write_phy_shard_id'];

        $psconfig = array(
            self::DB_CFG_SLAVE  => $this->getRealShardById($rpsid),
            self::DB_CFG_MASTER => $this->getRealShardById($wpsid),
        );

        if(!$psconfig[self::DB_CFG_SLAVE] || !$psconfig[self::DB_CFG_MASTER])
            return false;

        $config = array();
        foreach($psconfig as $op => $tconfig)
        {
            $dsnps = explode(';', $tconfig);
            foreach($dsnps as $idx => $pair)
            {
                $epos = strpos($pair, '=');
                $tkey = substr($pair, 0, $epos);
                $tval = substr($pair, $epos + 1);
                if($tkey == 'dbuser')
                    $config[$op]['username'] = $tval;
                elseif($tkey == 'dbpass')
                    $config[$op]['password'] = $tval;
                else
                    $config[$op][$tkey] = $tval;
            }
        }

        return $config;
    }

    /**
     * 返回是否需要双写
     * @access protected
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int   $vsid
     * @return bool
     */
    protected function isMirror($vsid=false)
    {
        $vsid = $this->_getVsid($vsid);
        $shard = $this->getShardById($vsid);
        if(!$shard)
            return false;
        return 1 == $shard['is_mirror'] && $shard['mirror_vsid'] > 0;
    }

    /**
     * 返回是否为异步双写
     * @access protected
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int   $vsid
     * @return bool
     */
    protected function isMirrorAsync($vsid=false)
    {
        $vsid = $this->_getVsid($vsid);
        $shard = $this->getShardById($vsid);
        return isset($shard['mirror_type']) && 2 == $shard['mirror_type'];
    }

    /**
     * 获取双写ShardId
     * @access protected
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  int   $vsid
     * @return bool
     */
    protected function getMirrorVsid($vsid=false)
    {
        $vsid = $this->_getVsid($vsid);
        $shard = $this->getShardById($vsid);
        return isset($shard['mirror_vsid']) ? $shard['mirror_vsid'] : false;
    }

    /**
     * 检测shard信息表是否存在
     * @access private
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  object  $pdo
     * @return bool
     */
    private function hasShardInfo($pdo)
    {
        $set = $pdo->getTables();
        return $set && in_array(self::SHARD_TABLE_NAME, $set);
    }
}
