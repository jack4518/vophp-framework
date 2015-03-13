<?php
/**
 * 定义 VO_Database_Comdb COMDB数据类，用于操作Data表和Cache
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
 
class VO_Database_Comdb extends VO_Object{
	/**
	 * VO_Database_Comdb对象
	 * @var VO_Database_Comdb
	 */
    static private $_instance = null;

    /**
	 * VO_Gearman对象,通过Gearman异步写
	 * @var VO_Gearman
	 */
    public $gearman  = array();

    /**
	 * 异步操作APP
	 * @var string
	 */
    public $app      = 'openapp';

	/**
	 * 当前Model对象
	 * @var VO_Model
	 */
    private $_model = null;

	/**
	 * VO_Cache_Abstract对象
	 * @var VO_Cache_Abstract
	 */
	private $_cacher = null;  //缓存对象

	/**
	 * VO_Utils_Hash对象
	 * @var VO_Utils_Hash
	 */
    private $_hash   = array();

    /**
	 * ComDB数据库节点数据库配置信息
	 * @var array
	 */
    private $_comdb_config = array();         //所有ComDB节点配置信息

    /**
     * 数据记录的版本
     * @var int
     */
    private $_version = 0;

    /**
     * ComDB的配置最新版本号
     * @var int
     */
    private $_last_cid    = 0;

    /**
     * 当前数据库句柄
     * @var VO_Database_Abstrace
     */
    private $_db = null;

    /**
     * 数据层DAO类型
     * @var int
     */
    private $_dao_type = DAO_ALL;

    /**
     * 替换前缀
     * @var string
     */
    const PARAM_PREFIX= ':vo_';

	/**
	 * ComDB配置信息
	 * @var array
	 */
    private $_config = array(
		'enable' => true, //是否开启ComDB功能
		'cache' => 'memcache', //缓存所使用的引擎
		'id_mode' => 'TABLE_NAME_ID', //DATA表的ID模式,可选值[64_BIT_GLOBAL, 64_BIT_COMPAT, TABLE_NAME_ID]
		 //DATA表对应的数据库分片配置
	    'db_config'=>array(
	    	'adapter' 	=> 	'mysql',  //数据库引擎
			'host'    	=> 	'127.0.0.1',  //数据库主机名
			'port' 		=> 	'3306',  //数据库连接端口
			'user' 		=> 	'jackchen',  //数据库用户名
			'password'	=> 	'840704',  //数据库密码
			'database'	=> 	'vophp_com',  //数据库库名
			'prefix' 	=> 	'vo_',  //数据库表前缀
			'suffix' 	=> 	'',   //数据库表后缀
	        'charset'   => 'utf8',
	        'persist'   => false,
	    ),
	    'table_name' => 'data',  //Data表名
	    'key_name'   => 'key',   //数据主键名
	    'value_name' => 'value', //数据内容字段名
	    'version_name'   => 'version',   //数据版本号
	    'createdate_name' => 'create_date', //数据迁移或创建时间戳
	    'editdate_name' => 'edit_date', //数据最近更新时间戳
	    'status_name'=> 'status',//数据有效状态，置空则物理删除否则逻辑标记删除
	    'copies'    => 2,       //数据备份数，默认1没有备份
	    'config_table' => 'info',  //Comdb配置信息存放表名
	    'move_table'   => 'move',  //数据迁移日志表，置空则不记录
	    'cache_table'  => 'cache', //刷新缓存日志表，置空则不记录
	    'bucket'    => 'comdb', //Couchbase桶名
	    'auto_move'  => true,    //是否自动迁移数据
	    'gearman'   => array(), //异步写data表
	    'async'     => true,
	    'expire' => 0,  //数据过期时间，默认为永不过期
	);

    /**
     * 构造函数
     * @return VO_Database_Comdb
     */
    public function __construct($model=null){
	    $this->_model = $model;
	    $this->_dao_type = $this->_model->getDaoType();
	    $config = C('comdb');
        $db_config  = $config['db_config'];
        $this->_db = VO_Model::getDao()->getMasterConnect($db_config);
        unset($config['db_config']);
        $this->_config = array_merge($this->_config, $config);
        $this->_cacher = $this->_getCacher();
        assert(is_numeric($this->_config['copies']) && $this->_config['copies'] > 0);
    }

    /**
     * 实例化对象
     * @param  VO_Model $model  Model对象
     * @return VO_Database_Comdb
     */
    static public function getInstance($model=null){
        if(!self::$_instance){
            if(PHP_INT_MAX == 2147483647){
               // $this->triggerError(__CLASS__.' does not support 32-bit systems', E_USER_ERROR);
            }
            self::$_instance = new self($model);
        }
        return self::$_instance;
    }

    /**
	 * 获取COMDB的ID模式
	 * @return string  ID模式
	 */
    public function getIdMode(){
	    $id_modes = array('64_BIT_GLOBAL', '64_BIT_COMPAT', 'TABLE_NAME_ID');
	    $db_config = C('db');
	    if(isset($db_config['id_mode']) && !empty($db_config['id_mode']) && in_array($db_config['id_mode'], $id_modes)){
		    return $db_config['id_mode'];
	    }else{
		    return 'TABLE_NAME_ID';
	    }
    }
    
    /**
	 * 获取数据库的ID值
	 * @param  int  $shard_id  分片shardID
	 * @param  int  $old_id    兼容模式下的旧ID
	 * @param  int  $flag      兼容模式flag ID
	 * @return string  ID模式
	 */
    public function getPrimaryValue($key){
	    $id_mode = $this->getIdMode();
	    switch($id_mode){
		    case '64_BIT_GLOBAL' : 
		    case '64_BIT_COMPAT' :
		    	$id = $key;
		    	break;

		    case 'TABLE_NAME_ID' : 
		    default:
		    	$table_name = $this->_model->getTableName();
		    	$id = $table_name . '_' . $key;
		    	break;
	    }
	    return $id;
	    
    }

    /**
     * 更新k/v对
     * @param	numeric $key   		键名称
     * @param	array   $value 		值
     * @param	array 	$options 	设置条件，如什么时候过期，所在域等
     * @param	int 	$expire		数据有效期,0为永不过期
     * @return	boolean	true|false  是否保存成功
     */
    public function set($key, $value, $options=array(), $expire=0){
        if(!$key || !is_numeric($key)){ 
            return false;
		}elseif(empty($value)){
			return false;
		}

        if(!is_string($value)){
            if(is_array($value)){
	            $value = self::_toString($value);
            }
            $value = self::_encode($value);
        }

        $cache_ret = $db_ret = false;
        //优先存Couchbase
        $key = $this->getPrimaryValue($key);
        if(DAO_ALL == $this->_dao_type || DAO_FAST_CACHE == $this->_dao_type){
	        if(!$expire){
		        $expire = $this->_config['expire'];
	        }
	        $expire = (int)$expire;
	        $options['life_time'] = $expire;
            $cache_ret = $this->_getCacher()->set($key, $value, $options);
            if(DAO_FAST_CACHE == $this->_dao_type){
                return $cache_ret;
            }
        }

        if(DAO_ALL == $this->_dao_type || DAO_FAST_DATA == $this->_dao_type){ //需要存Data表
            if($this->_conifg['async']){
	            $db_ret = $this->_asyncSet($key, $value);
	        }else{
		        $db_ret = $this->_saveToData($key, $value);
	        }
            if(DAO_FAST_DATA == $this->_dao_type){
                return $db_ret;
            }
        }
        return $cache_ret && $db_ret;
    }

    /**
     * 获得值内容
     * @param string | array $key
     * @param mixed | false
     * @return array | false
     */
    public function get($key){
        if(!$key){
	        return false;
        }
        $tkeys = $key;
        if(!is_array($key)){
	        $tkeys = array($key);
        }
        $tkeys = array_unique($tkeys);

        $Sets = array();
        if(DAO_TYPE_BOTH == $this->_dao_type || DAO_TYPE_MEMCACHE == $this->_dao_type)
            $Sets = Slae::app()->cache->comdb->get($tkeys);
        $diff = array_diff($tkeys, array_keys($Sets));
        if($diff && (DAO_TYPE_BOTH == $this->_dao_type || DAO_TYPE_MYSQL == $this->_dao_type) && $this->_getEffectConfig())
        {
            $move = $cache = array();
            foreach($this->_comdb_config as $cid => $citem)
            {
                for($i=0; $i < $this->_config['copies'] && $diff; $i++)
                {
                    $mkey = $this->mergeId($diff, $i, $cid);
                    $this->getFromDb($mkey, $i, $cid, $Sets, $move, $cache);
                    $diff = array_diff($tkeys, array_keys($Sets));
                }
                if(!$diff)break;
            }

            if($move && $this->_config['auto_move'])
            {
                if($this->_config['async'])
                    $this->_asyncGet($move);
                else
                {
                    $cid = array_keys($this->_comdb_config);
                    $this->rebalance($move, $cid[0], $Sets);
                }
            }
        }

        $sets = array();
        foreach($Sets as $k => $item)
            if($tmp = self::_decode($item))
                $sets[$k] = $tmp;

        $sets && !is_array($key) && $sets = $sets[$key];
        return $sets ? $sets : false;
    }

    /**
     * 删除键
     *
     * @param integer or array $key
     * @param integer $timeout
     * @param boolean true | false
     */
    public function delete($key, $timeout=0)
    {
        if(!$key)
            return false;
        !is_array($key) && $key = array($key);
        $iOK = 0;
        $cOK = false;
        $mOK = true;
        if(DAO_TYPE_BOTH == $this->_dao_type || DAO_TYPE_MYSQL == $this->_dao_type)
        {
            if($this->_config['async'])
                $this->_asyncDelete($key);
            else
                $this->deleteDb($key);
        }

        if(DAO_TYPE_BOTH == $this->_dao_type || DAO_TYPE_MEMCACHE == $this->_dao_type)
        {
            foreach($key as $k)
                $cOK = Slae::app()->cache->comdb->delete($k);
        }

        return $mOK && $cOK;
    }

    /**
     * 设置数据版本
     * @param  int  版本号
     * @return void
     */
    public function setVersion($version)
    {
        assert(is_numeric($version) && is_int($version));
        assert($version > 0);
        if(4294967295 == $version){
            $version = 1;
        }
        $this->_version = $version;
    }

    /**
     * 是否为新记录
     */
    public function isNewRecord()
    {
        return !$this->_version;
    }

    /**
     * 设置存储模型
     */
    public function setDaoType($daoType)
    {
        assert($daoType >= DAO_TYPE_NONE && $daoType <= DAO_TYPE_MYSQL);
        $this->_dao_type = $daoType;
    }

    /**
     * 根据主键值获取所在节点信息
     */
    public function getNodeByKey($key)
    {
        if(!$key || !$this->_getEffectConfig())
            return false;

        $item = null;
        foreach($this->_comdb_config as $cid => $citem)
        {
            $item = $this->_getConfigKey($key, 0, $cid);
            break;
        }

        return $item ? self::_decode($item) : $item;
    }

    /**
     * 向数据库Data表插入数据
     * @param 	VO_Database_Abstrace	$db		数据库实例
     * @param 	string	$key	键值
     * @param 	string	$value	值内容
     * @param 	string	$table	表名
     */
    private function _insert(&$db, $key, $value, $table=''){
        assert(isset($key) && is_numeric($key));
        assert(is_string($value));
        $sql = sprintf('INSERT INTO `%s` SET `%s`=:%s,`%s`=:%s %s %s %s ON DUPLICATE KEY UPDATE `%s`=%s %s %s %s',
            $table ? $table : $this->_config['table_name'],
            $this->_config['key_name'],   $this->_config['key_name'],
            $this->_config['value_name'], $this->_config['value_name'],
            $this->_config['editdate_name'] ? sprintf(',`%s`=unix_timestamp(now())', $this->_config['editdate_name']) : '',
            $this->_config['createdate_name'] ? sprintf(',`%s`=unix_timestamp(now())', $this->_config['createdate_name']) : '',
            $this->_config['version_name'] ? sprintf(",`%s`='%d'", $this->_config['version_name'], $this->_version+1) : '',
            $this->_config['value_name'], self::PARAM_PREFIX . $this->_config['value_name'],
            $this->_config['status_name'] ? sprintf(',`%s`=0', $this->_config['status_name']) : '',
            $this->_config['version_name'] ? sprintf(",`%s`='%d'", $this->_config['version_name'], $this->_version+1) : '',
            $this->_config['editdate_name'] ? sprintf(',`%s`=unix_timestamp(now())', $this->_config['editdate_name']) : ''
        );

        $param = array(
            ':'.$this->_config['key_name']   => $key,
            ':'.$this->_config['value_name'] => $value,
            self::PARAM_PREFIX . $this->_config['value_name'] => $value,
        );

        try{
            return $db->query($sql, $param);
        }catch(PDOException $e){
	        
        }
        return false;
    }

    /**
     * 检索Data表
     */
    private function _select($pdo, $key=array(), $tbl='')
    {
        $str = implode("','", $key);
        $count = count($key);
        $sql = sprintf("SELECT `%s`,`%s`%s FROM `%s` WHERE `%s` %s ('%s') %s",
            $this->_config['key_name'],
            $this->_config['value_name'],
            $this->_config['version_name'] ? sprintf(',`%s`', $this->_config['version_name']) : '',
            $tbl ? $tbl : $this->_config['table_name'],
            $this->_config['key_name'],
            $count > 1 ? 'IN' : '=',
            $str,
            $this->_config['status_name'] ? sprintf('AND `%s`=0', $this->_config['status_name']) : ''
        );

        $data = array();
        try {
            $rows = $pdo->queryAll($sql);
            foreach($rows as $item)
            {
                $k = $item[$this->key_name];
                $v = $item[$this->_config['value_name']];
                $data[$k] = $v;
            }
        } catch(PDOException $e) {
        }

        return $data;
    }

    /**
     * 删除Data表中数据
     */
    private function _delete($pdo, $key, $table=''){
        $sql = '';
        if($this->_config['status_name']){
            $sql = sprintf('UPDATE `%s` SET `%s`=1 %s WHERE `%s`=:%s',
                $table ? $table : $this->_config['table_name'],
                $this->_config['status_name'],
                $this->_config['editdate_name'] ? sprintf(',`%s`=unix_timestamp(now())', $this->_config['editdate_name']) : '',
                $this->_config['key_name'],
                $this->_config['key_name']
            );
        }else{
            $sql = sprintf('DELETE FROM `%s` WHERE `%s`=:%s',
                $table ? $table : $this->_config['table_name'],
                $this->_config['key_name'],
                $this->_config['key_name']
            );
        }
        try{
            return $pdo->execute($sql, array(':'.$this->_config['key_name']=>$key));
        }catch(PDOException $e){
	        
        }
        return false;
    }

    /**
     * 根据ID及哈希次序生成配置key
     * @param	int 	$id		数据键值key
     * @param 	int		$offset	备份总数
     * @param	int		$c_id	配置版本
     * @return 	int		数据库配置信息
     */
    private function _getConfigKey($id, $offset=0, $c_id=0){
        //assert(is_numeric($offset) && $offset <= $this->_config['copies']);
        //assert($c_id && is_numeric($c_id) && $c_id > 0);
        $hash = $this->_getHash($c_id);
        if(!$hash){
            return '';
        }
        $key = $hash->lookup($id);
        $count = $hash->getListCount();
        $tmp = array();
        for($i=0; $i<$offset; $i++){
            if($count-$i > 1){
                $tmp[] = $key;
                $hash->removeTarget($key);
            }
            $key = $hash->lookup($id);
        }
        foreach($tmp as $target){
            $cfg = self::_decode($target);
            $hash->addTarget($target, $cfg['weight']);
        }

        return $key;
    }

    /**
     * 获取所有ComDB的有效配置信息(倒序)
     * @param	bool	$refresh	是否需要获取
     * @return 	array	ComDB节点数据库配置信息
     */
    private function _getEffectConfig($refresh=false){
        if(!$this->_comdb_config && !$refresh){
            $sql = 'SELECT * FROM `' . $this->_config['config_table'] . '` WHERE `status`=1 ORDER BY `id` DESC';
            $rows = $this->_db->fetchAll($sql);
            foreach($rows as $item){
                $key = $item['id'];
                $this->_comdb_config[$key] = $item;
            }
            $keys = array_keys($this->_comdb_config);
            $keys && $this->_last_cid = $keys[0];
        }
        return $this->_comdb_config;
    }

    /**
     * 返回指定版本一致性哈希对象
     * @param	int		$config_version_id	ComDB数据库配置版本ID
     * @return  VO_Utils_Hash	Hash对象
     */
    private function _getHash($config_version_id){
        if(isset($this->_hash[$config_version_id])){
            return $this->_hash[$config_version_id];
        }
        $hash = null;
        if(!isset($this->_hash[$config_version_id]) && isset($this->_comdb_config[$config_version_id])){
            $config = $this->_comdb_config[$config_version_id];
            $servers = self::_decode($config['config']);
            if(!$servers){
                $this->triggerError('无法获取ComDB数据节点配置信息，请确认配置表信息!');
            }
            assert($this->_config['copies'] && $this->_config['copies'] <= count($servers));
            assert($config['replicas'] > 0);
            $hash = new VO_Utils_Hash('crc32', $config['replicas']);
            foreach($servers as $item){
                $skey = self::_encode($item);
                $hash->addTarget($skey, $item['weight']);
            }
            $this->_hash[$config_version_id] = $hash;
        }

        return $hash;
    }

    /**
     * 做数据迁移
     */
    private function rebalance($ids, $cid, $data)
    {
        foreach($ids as $id => $item)
        {
            if(!isset($data[$id]))
                continue;
            for($i=0; $i < $this->_config['copies']; $i++)
            {
                $ckey = $this->_getConfigKey($id, $i, $cid);
                $cfg = self::_decode($ckey);
                if(!$cfg || !$pdo = VO_Comdb_Pdo::getInstance($cfg))
                    continue;

                $tbl = !empty($cfg['table']) ? $cfg['table'] : $this->_config['table_name'];
                if($sOK = $this->_insert($pdo, $id, $data[$id], $tbl))
                {
                    $ckey = $this->_getConfigKey($id, $item['offset'], $item['cid']);
                    $cfg = self::_decode($ckey);
                    if($cfg && $pdo = VO_Comdb_Pdo::getInstance($cfg))
                    {
                        $sql = '';
                        $tbl = !empty($cfg['table']) ? $cfg['table'] : $this->_config['table_name'];
                        if($this->_config['status_name'])
                            $sql = sprintf('UPDATE `%s` SET `%s`=1 WHERE `%s`=:%s', $tbl, $this->_config['status_name'], $this->_config['key_name'], $this->_config['key_name']);
                        else
                            $sql = sprintf('DELETE FROM `%s` WHERE `%s`=:%s', $tbl, $this->_config['key_name'], $this->_config['key_name']);
                        $pdo->execute($sql, array(':'.$this->_config['key_name']=>$id));
                    }
                }
                //$this->_config['move_table'] && $this->setMoveLog($id, $item['cid'], $cid, $i, $sOk);
            }
        }
    }

    private function info($id, $action, $cver, $dver, $message)
    {
        $sql = 'INSERT INTO `log` SET `doc_id`=:doc_id,`action`=:action,`message`=:message,`dver`=:dver,`cver`=:cver';
        $param = array(
            ':doc_id'=>$id,
            ':action'=>$action,
            ':dver'  =>$dver,
            ':cver'  =>$cver,
            ':message'=>$message
        );

        $this->_db->execute($sql, $param);
    }


    /**
     * 合并相同节点id
     */
    private function mergeId($ids, $offsetCopy, $configId)
    {
        $mkey = array();
        foreach($ids as $id)
        {
            $ckey = $this->_getConfigKey($id, $offsetCopy, $configId);
            $mkey[$ckey][] = $id;
        }

        return $mkey;
    }

    /**
     * 记录迁移日志
     */
    private function setMoveLog($id, $fromVer, $toVer, $ver, $status)
    {
        $addr = getClientIp();
        $sql = 'INSERT INTO `' . $this->_config['move_table'] . '` SET `from`=:from,`to`=:to,`doc_id`=:doc_id,`ver`=:ver,`status`=:status,`addr`=:addr';
        $this->_db->execute(
            $sql,
            array(
                ':from'  =>$fromVer,
                ':to'    =>$toVer, 
                ':doc_id'=>$id,
                ':ver'   =>$ver,
                ':status'=>$status ? 0 : 1,
                ':addr'  =>$addr,
            )
        );
    }

    /**
     * 从Data集群取数据
     */
    private function getFromDb($mkey, $offsetCopy, $configId, &$Sets, &$move, &$cache)
    {
        foreach($mkey as $ckey => $keys)
        {
            $cfg = self::_decode($ckey);
            if(!$cfg || !$pdo = VO_Comdb_Pdo::getInstance($cfg))
                continue;

            $tbl = !empty($cfg['table']) ? $cfg['table'] : $this->_config['table_name'];
            if(!$dbv = $this->_select($pdo, $keys, $tbl))
                continue;
            $Sets += $dbv;
            if($offsetCopy || $configId != $this->_last_cid)
            {
                $moveIds = array_keys($dbv);
                $move += array_fill_keys($moveIds, array('offset'=>$i, 'cid'=>$cid));
            }

            if(DAO_TYPE_BOTH != $this->_dao_type)
                continue;
            foreach($dbv as $k => $v)
                $cache[$k] = Slae::app()->cache->comdb->set($k, $v, 0, 0);
        }
    }

    /**
     * 从Data表中删除
     */
    private function deleteDb($keys)
    {
        if(!$this->_getEffectConfig())
            return false;

        $iOK = 0;
        foreach($keys as $key)
        {
            foreach($this->_comdb_config as $cid => $citem)
            {
                for($i=0; $i < $this->_config['copies']; $i++)
                {
                    $ckey = $this->_getConfigKey($key, $i, $cid);
                    $cfg = self::_decode($ckey);
                    if(!$cfg || !$pdo = VO_Comdb_Pdo::getInstance($cfg))
                        continue;

                    $tbl = !empty($cfg['table']) ? $cfg['table'] : $this->_config['table_name'];
                    $this->_delete($pdo, $key, $tbl) && $iOK++;
                }
            }
        }

        return $iOK;
    }

    /**
     * 写数据到数据库
     * @param 	string	$key	键值
     * @param	string	$value	内容
     * @return 	bool	是否保存成功
     */
    private function _saveToData($key, $value){
        $ret = false;
        if(!$this->_getEffectConfig()){
            return $OK;
        }
        for($i=0; $i<$this->config['copies']; $i++){
            $ckey = $this->_getConfigKey($key, $i, $this->_last_cid);
            $db_config  = self::_decode($ckey);
            $db = VO_Model::getDao()->getMasterConnect($db_config);
            if(!$db){
                continue;
            }
            $table = !empty($db_config['table']) ? $db_config['table'] : $this->_config['table_name'];
            $ret = $this->_insert($db, $key, $value, $table);
            if(!$i && false === $ret){
                return false;
            }
        }
        return $ret;
    }

    /**
     * 发送异步写库通知
     * @param	string	$key	数据键名称
     * @param	string	$value	数据内容
     * @return 	void
     */
    private function _asyncSet($key, $value){
        $param = array(
        	'key' => $key,
        	'value'=>$value,
        );
        $ret = $this->sendAsync('comdbset', $param);
        if($ret <> ''){
	        return true;
        }else{
	        //$this->_log('runAsync failed key:'.$key, 'error', 'sys');
	        return false;
        }
    }

    /**
     * 发送异步迁移数据通知
     * @param	string	$key	数据键名称
     * @return 	void
     */
    private function _asyncGet($key){
        $param = array('key'=>$key);
        if(!$this->sendAsync('comdbget', $param)){
            //$this->_log('runAsync failed key:'.self::_encode($key), 'error', 'sys');
        }
    }

    /**
     * 发异步删除通知
     * @param	string	$key	数据键名称
     * @return 	void
     */
    private function _asyncDelete($key){
        $param = array(
        	'key' => $key,
        );
        $ret = $this->sendAsync('comdbdelete', $param);
        if(!$ret){
            //$this->_log('runAsync failed key:'.self::_encode($key), 'error', 'sys');
        }
    }

    /**
     * 发送异步消息
     */
    private function sendAsync($action, $param)
    {
        //Worker与前端部署到同一机器检测worker安装目录是否存在
        /*
        if(!file_exists(_APP_._DS_.'openapp'._DS_.'replica.php')){
            $this->triggerError('Asynchronous worker is not install! checkout worker from framework/openapp to app/openapp', E_USER_ERROR);
            return false;
        }
         */

        $payload=array(
            'domain' => $_SERVER['HTTP_HOST'],
            'app'    => $this->app,
            'controller' => 'replica',
            'action' => $action,
            'params' => $param,
        );

        return Slae::app()->gearman->runAsync('run_fastcgi_action', $payload);
    }

    /**
     * 强制转换为字符串，兼容CouchBase大整数精度丢失及保留数组格式数据
     * @param	mixed	$data	数据内容
     * @return 	string	数据内容
     */
    private static function _toString($data){
        foreach($data as &$item){
            if(is_array($item)){
                $item = self::_toString($item);
            }else{
                $item = (string)$item;
            }
        }
        return $data;
    }

	/**
     * 对数据进行JSON编码
     * @param	mixed	$data	数据内容
     * @return 	string[JSON]	数据内容
     */
    private static function _encode($value){
        $json = false;
        if(version_compare(PHP_VERSION, '5.4.0') >= 0){
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        }elseif(version_compare(PHP_VERSION, '5.2.0') >= 0){
            $json = json_encode($value);
       	}
        return $json;
    }

	/**
     * 对数据进行JSON解码
     * @param	string[JSON]	$data	数据内容
     * @return 	string	数据内容
     */
    private static function _decode($json){
        $value = false;
        if(!is_string($json)){
            return false;
        }
        if(version_compare(PHP_VERSION, '5.4.0') >= 0){
            $value = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        }elseif(version_compare(PHP_VERSION, '5.2.0') >= 0){
            $value = json_decode($json, true);
        }
        return $value;
    }

	/**
	 * 获取缓存对象
	 * @param  string  $name  缓存类型
	 * @return VO_Cache_Abstract
	 */
    private function _getCacher($name=null){
	    $cacher = null;
	    if(!$name){
		    $name = !empty($this->_config['cache']) ? $this->_config['cache'] : 'memcache';
	    }
	    switch(strtoupper($name)){
		    case 'COUCHBASE':
		    	$cacher = VO_Nosql_Couchbase::getInstance($this->_config['bucket']);
		    	break;
		    	
		    case 'REDIS':
		    	$cacher = VO_Nosql_Redis::getInstance();
		    	break;
		    	
		    case 'MONGODB':
		    	$cacher = VO_Nosql_Mongodb::getInstance();
		    	break;

		    case 'MEMCACHE':
		    default:
		    	$cacher = VO_Cache_Memcache::getInstance();
		    	break;
	    }
	    return $cacher;
    }

	/**
	 * 记录日志
	 * @param	string	$message	log信息
	 * @param	string	$type		数据类型
	 * @return 	void
	 */
    private	function _log($message, $type='log'){
	    if(!$this->_loger){
	    	$this->_loger = new VO_Log();
    	}
		$this->_loger->log($message, $type);
    }
}