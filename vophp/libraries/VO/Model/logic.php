	<?php

	/**
	 * 查询核心处理逻辑
	 * 
	 * @category    Leb
	 * @package     Leb_Dao_Logic
	 * @author      Liu Guangzhao <guangzhao@leju.com>
	 * @license     http://slae.leju.com Slae Team Lincense
	 * @copyright   © 1996 - 2014 新浪乐居
	 * @version     $Id: idc.php 50110 2013-07-10 08:09:19Z Liu Guangzhao $
	 */

	defined('_LEB_FRAMEWORK_') or exit("You don't have the access to execution Leb Framework");
	class Leb_Dao_Logic extends Leb_Model
	{
		/**
		 * model对象
		 * @access private
		 * @var object
		 */
		private $_model;

		/**
		 * DAO对象
		 * @access private
		 * @var object
		 */
		private $_dao;

		/**
		 * couchBase的Key
		 * @var string
		 */
		const PLAIN_KEY = 'plain_cacher_key';

		/**
		 * 实例化逻辑层对象
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object  $model
		 * @return object
		 */
		public static function getInstance($model)
		{
			return new Leb_Dao_Logic($model);
		}

		/**
		 * 初始化
		 * @access protected
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object  $model
		 * @return void
		 */
		public function __construct($model)
		{
			$this->_model = $model;
			$this->_dao = new Leb_DAO($model);
		}

		/**
		 * 根据schema过滤数据
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $data
		 * @return array  $data
		 */
		private function verify(array $data)
		{
			$schema = $this->_model->getMeta()->getIndexSchema();
			foreach($schema as $col => $item)
			{
				isset($data[$col]) && $data[$col] = $item->typecast($data[$col]);
			}

			return $data;
		}

		/**
		 * 查询处理
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object        $criteria 查询条件criteria对象
		 * @param  bool			 $all      是否查询多条数据 
		 * @param  bool          $default  当查询错误或者无数据时返回此默认数据
		 * @return array | bool
		 */
		public function doQuery($criteria, $all=false, $default=false)
		{
			if(!$criteria || !$criteria instanceof Leb_Criteria){
				$this->_model->addError('', 'criteria is null or are not Leb_Criteria instance');
				return $default;
			}

			$vsid = $this->_model->getVsId();
			$ids = array();
			$Sets = array();
			call_user_func(array($this->_model, 'beforeFind'));
			$dao_type = $this->_model->getDaoType();
			if(!$dao_type){
				//如果只从索引表取数据
				$db= $this->_dao->getSlaveConnect($vsid);
				if(!$db){
					$this->_model->addError('', 'can\'t connect the database,please check config.');
					return $default;
				}
				$build = new Leb_Builder();
				$sql = $build->buildFind($this->_model->getTrueTableName(), $criteria);
				$Sets = $db->queryAll($sql, $build->getParam());
			}else{
				//从ComDB取数据
				$set = $this->_getSets($criteria, array(), false);
				if($set){
					$ids = array();
					$Sets = array();
					$pk  = $this->_model->getMeta()->primaryKey;

					if($this->_model->getIdMode() == Leb_Model::ID_GLOBAL){
						foreach($set as $k => $item){
							$ids[] = $item[$pk];
						}
						$Sets =  Slae::app()->comdb->get($ids);
					}else{
						$Sets = $this->doCompatQuery($ids);
					}
					if($Sets && $criteria->getOrder() && count($Sets) > 1){
						$tmp = $Sets;
						$Sets = array();
						foreach($ids as $id){
							if(isset($tmp[$id])){
								$Sets[] = $tmp[$id];
							}
						}
					}elseif($Sets){
						$Sets = array_values($Sets);
					}
				}
			}
			if(count($ids) > count($Sets)){
				$this->_model->addError('', 'some data primary key find in index table, but there full data not in comDB.');
			}
			$Sets = $this->fiterField($Sets, $criteria);
			if(!$Sets){
				if(!empty($ids)){
					$this->_model->addError('', 'data primary key find in index table, but full data not in comDB.');
				}
				return $default;
			}else{
				call_user_func(array($this->_model, 'afterFind'));
				if($all){
					return $Sets;
				}else{
					return $Sets[0];
				}
			}
		}

		/**
		 * 兼容老版本逻辑查询
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $ids
		 * @return array  $Sets
		 */
		private function doCompatQuery(&$ids)
		{
			$cache = Slae::app()->cache->default;
			$daoType = $this->_model->getDaoType();
			$ids = $this->_getCacheKey($ids);
			$ids = $this->_getHashKey($ids);
			if($cache && (self::DAO_TYPE_MEMCACHE == $daoType || self::DAO_TYPE_BOTH == $daoType))
				$Sets = $cache->get($ids);

			if((self::DAO_TYPE_BOTH == $daoType || self::DAO_TYPE_MYSQL == $daoType)
				&& ($diff = array_diff($ids, array_keys($Sets)))
				&& $pdo = $this->_model->getSlaveConnect())
			{
				$diff = array_values($diff);
				$build = new Leb_Builder();
				$c = new Leb_Criteria();
				$c->add(array(self::DB_CFG_DATA_KEY=>$diff));
				$sql = $build->buildFind(self::DB_CFG_DATA, $c);
				if($set = $pdo->queryAll($sql, $build->param))
				{
					$Sets += $set;
					if(self::DAO_TYPE_BOTH == $daoType && $cache)
					{
						foreach($set as $item)
							$cache->set($item[self::DB_CFG_DATA_KEY], $item[self::DB_CFG_DATA_VALUE]);
					}
				}
			}

			if($Sets)
			{
				if(DATA_VALFMT_JSON)
					foreach($Sets as &$item)$item = json_decode($item, true);
				else
					foreach($Sets as &$item)$item = unserialize($item);
			}

			return $Sets;
		}

		/**
		 * 执行MySQL统计函数
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object  $criteria
		 * @param  string  $method
		 * @return array
		 */
		public function doQueryFunc($criteria, $method)
		{
			$vsid = $this->_model->getVsId();
			$db = $this->_model->getSlaveConnect($vsid);
			if(!$db){
				return false;
			}

			$criteria->Limit(1);
			$build = new Leb_Builder();
			$m = 'build'.$method;
			$table = $this->_model->getTrueTableName();
			$sql = $build->$m($table, $criteria);
			$row = $db->query($sql, $build->getParam());
			if(!$row){
				return false;
			}else{
				$row = array_values($row);
				return $row[0];
			}
		}

		/**
		 * 插入数据
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array       $attr
		 * @param  string      $method
		 * @return bool | int
		 */
		public function doInsert(array $attr)
		{
			$indexColumn = $this->_model->getMeta()->getIndexColumn();
			$attr = $this->verify($attr);
			$index = array_intersect_key($attr, array_flip($indexColumn));
			if(!$index){
				$this->_model->addError('', 'attributes can not be empty');
				return false;
			}

			if(!call_user_func(array($this->_model, 'beforeInsert'))){
				$this->_model->addError('', 'Insert canceled by user');
				return false;
			}

			/*
			if($denyNull = $this->_model->getMeta()->getDenyNull())
			{
				$denyNull = array_flip($denyNull);
				if($null = array_diff_key($denyNull, $index))
				{
					$error = sprintf('columns(%s) does not have default value', implode(',', array_flip($null)));
					return !$this->_model->addError('', $error);
				}
			}
			 */

			//优先插入ComDB
			//1.有主键 全局或兼容格局
			//2.没有on update
			//3.不含有非空字段
			//$pk = $this->_model->getMeta()->primaryKey;
			//$insertFirst = $this->_model->_globalIdMode && !$this->model->getMeta()->getUpdateColumn();

			//先写分库后写总库/核心库
			$table = $this->_model->getTrueTableName();
			$build = new Leb_Builder();
			$sql = $build->buildInsert($table, $index);
			$vsid = $this->_model->getVsId();
			$id = $this->_model->getDao()->getMasterConnect($vsid)->execute($sql, $build->getParam());
			//var_dump($sql, $build->getParam());

			do{
				if(false !== $id){
					$this->_fetch($attr, $table);
				}
				$dao_type = $this->_model->getDaoType();
				if(false === $id || !$dao_type){
					break;
				}
				$attr = array_merge($this->_model->getMeta()->getColumnDefault(), $attr);
				if($this->_model->getIdMode() == Leb_Model::ID_GLOBAL){ //全局ID模式
					if($this->_model->getDao()->isMirror()){//如果有写总库
						$this->_doCoreInsert($index);
					}
					$pk = $this->_model->getMeta()->primaryKey;
					$key = $attr[$pk];
					$attr[self::PLAIN_KEY] = $key;
					Slae::app()->comdb->setDaoType($dao_type);
					$data = Slae::app()->comdb->get($key); //写comDB
					if($data){
						$attr = array_merge($data, $attr);
					}
					if(!Slae::app()->comdb->set($key, $attr)){
						//delete index data
					}
				}elseif(!$this->_doCompatInsert($id, $attr)){
					//delete index data
				}
			}while(false);

			if(false !== $id){
				unset($attr[self::PLAIN_KEY]);
			}
			call_user_func(array($this->_model, 'afterInsert'));
			return $id;
		}

		/**
		 * 插入后执行的函数
		 * @access protected
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  bool		$ret    是否插入成功
		 * @return mixed	
		 */
		protected function afterInsert($ret)
		{
			return false;
		}


		/**
		 * 批量插入数据
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $attrs          需要插入的字段信息
		 * @param  bool   $vsid           ShardID 
		 * @return bool   $ret            是否插入成功
		 */
		public function doInsertAll(array $attrs, $vsid=false)
		{
			if($this->_model->getDao()->getRsync){
				return $this->_sendAsync(
					'insertAll',
					array(
						'attr'=>$attrs,
						'vsid'=>$vsid,
					)
				);
			}else{
				$table = $this->_model->getTrueTableName();
				$vsid = $this->_model->getVsId();
				$build = new Leb_Builder();
				$sql = $build->buildInsertAll($table, $attrs, $this->_model);
				$ret = $this->_model->getDao()->getMasterConnect($vsid)->execute($sql, $build->getParam());
				if($ret){
					$pk = $this->_model->getMeta()->primaryKey;
					foreach($attrs as $attr){
						Slae::app()->comdb->set($attr[$pk], $attr);
					}
				}

				unset($build);
				return $ret;
			}
		}

		/**
		 * 同步/异步写总库
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $attr  待写入的数据
		 * @return bool   $ret   是否插入成功
		 */
		private function _doCoreInsert(array $attr)
		{
			$ret = false;
			$table = $this->_model->getTrueTableName();
			if($this->_model->getDao()->isMirrorAsync()){
				$ret = $this->_sendAsync('insert', array('attr'=>$attr));
				if(!$ret){
					Slae::log("send async into {$table} {$attr[$this->_model->getMeta()->primaryKey]} failed");
				}
			}else{
				$vsid  = $this->_model->getDao()->getMirrorVsid();
				$build = new Leb_Builder();
				$sql   = $build->buildInsert($table, $attr);
				$ret = $this->_model->getMasterConnect($vsid)->execute($sql, $build->param);
				if(!$ret){
					Slae::log("into {$table} {$attr[$this->_model->getMeta()->primaryKey]} failed");
				}
			}
			return $ret;
		}

		/**
		 * 根据表结构定义检测是否含有自增/更新字段，获取自增/当前时间戳
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array       $attr	待处理的数据
		 * @param  string      $table   数据表名
		 * @param  bool|int	   $id		主键ID
		 * @return bool
		 */
		private function _fetch(&$attr, $table, $id=false)
		{
			$autoInc= $this->_model->getMeta()->getAutoIncColumn();
			$update = $this->_model->getMeta()->getUpdateColumn();
			if(!$autoInc && !$update){
				return false;
			}

			$pk = $this->_model->getMeta()->primaryKey;
			if(!$pk){
			trigger_error('table(' . $this->_model->getTableName() . ') must define primaryKey', E_USER_ERROR);
			}

			if($autoInc && isset($attr[$autoInc[0]])){
				$autoInc = array();
			}
			if(!$autoInc && !$update){
				return false;
			}

			$select = self::criteria();
			$select->field($autoInc[0]);
			$field = $select->getField();
			if($update){
				$field = implode(',', $update);
			}
			$field = trim($select->getField(), ' ,'); 
			$select->file($field); 
			if(false !== $id){
				$select->add(array($pk => $id));
			}elseif(is_scalar($pk) && isset($attr[$pk])){
				$select->add(array($pk => $attr[$pk]));
			}elseif(is_array($pk) && $tmp = array_intersect_key($attr, array_flip($pk))){
				$select->add($tmp);
			}else{
				trigger_error('logic error', E_USER_ERROR);
			}

			$vsid = $this->getVsId();
			$build = new Leb_Builder();
			$sql = $build->buildFind($table, $c);
			$pdo = $this->_model->getSlaveConnect($vsid);
			$tmp = $pdo->query($sql, $build->getParam());
			if($pdo && $tmp){
				$attr = array_merge($attr, $tmp);
				return true;
			}else{
				return false;
			}
		}

		/**
		 * 兼容老版本插入逻辑
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  int    $id
		 * @param  array  $attr
		 * @return bool
		 */
		private function _doCompatInsert($id, array $attr)
		{
			$oid = $id;
			$id = $this->_getCacheKey($id);
			$attr[self::PLAIN_KEY] = $id;
			$id = $this->_getHashKey($id);

			if($daoType == self::DAO_TYPE_BOTH || $daoType == self::DAO_TYPE_MYSQL){
				$vsid = $this->_model->getVsId();
				$sql = "INSERT INTO `{$data}` SET `{$key}`=:key, `value`=:value";
				$this->_model->getMasterDbConnect($vsid)->execute($sql, array(':key'=>$id, ':value'=>json_encode($attr)));
			}

			if($daoType == self::DAO_TYPE_BOTH || $daoType == self::DAO_TYPE_MEMCACHE){
				//
			}

			return true;
		}

		/**
		 * 更新属性值
		 *
		 * 结果以更新分库为准，总库/核心库异步处理
		 *
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array       $attr    待更新的数据
		 * @param  object      $criteia  更新条件
		 * @param  array       $ids
		 * @param  array       $default   默认值
		 * @return bool|int    $ret   成功更新影响条数,失败返回false
		 */
		public function doUpdate(array $attr, $criteria, &$ids=array(), &$def=array())
		{
			$pk = $this->_model->getMeta()->primaryKey;
			if(!$pk){
				$this->_model->addError('', 'primaryKey can not be empty!');
				return false;
			}

			if(!call_user_func(array($this->_model, 'beforeUpdate'))){
				$this->_model->addError('', 'Update canceled by user');
				return false;
			}

			$ids = array();
			$ret = 0;
			//$criteria->offset = 0;
			//$criteria->limit  = 100;
			$indexColumn = $this->_model->getMeta()->getIndexColumn();
			$indexColumn = array_flip($indexColumn);
			$attr = $this->verify($attr);
			$build = new Leb_Builder();
			$table = $this->_model->getTrueTableName();
			$index = array_intersect_key($attr, $indexColumn);
			$data  = array_diff_key($indexColumn, $attr);

			$ids = $this->_getSets($criteria);
			//分批更新避免结果集太大内存不足
			if($ids){
				//更新分库索引
				if($index){
					$vsid = $this->_model->getVsId();
					$sql = $build->buildUpdate($table, $criteria, $index);
					$ret = $this->_model->getDao()->getMasterConnect($vsid)->execute($sql, $build->getParam());

					if(false === $ret){
						return $ret;
					}
					$is_fetch = $this->_fetch($attr, $table, isset($ids[0]) ? $ids[0] : false);
					if($is_fetch){
						$index = array_intersect_key($index, $attr);
						var_dump($index);
					}

					//更新总库/核心库索引
					$this->_model->getDao()->isMirror() && $this->doCoreUpdate($ids, $index);
				}

				//更新Data表/ComDB
				if($this->_model->getDaoType() && $this->_model->getIdMode() == Leb_Model::ID_GLOBAL){
					$this->doGlobalUpdate($attr, $ids);
				}elseif($this->_model->getDaoType()){
					$this->doCompatUpdate($attr, $ids);
				}
			}

			$ret && call_user_func(array($this->_model, 'afterSave'));
			return $ret;
		}

		/**
		 * 更新符合条件的结果集数据
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array   $attr
		 * @param  object  $criteia
		 * @return bool
		 */
		public function doUpdateAsync(array $attr, $criteria)
		{
			return '' != $this->_sendAsync(
				'updateAll',
				array(
					'attr'=>$attr,
					'vsid'=>$this->_model->getVsId(),
					'criteria'=>$criteria,
				)
			);
		}

		/**
		 * 异步/同步更新总库
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $ids
		 * @param  array  $attr
		 * @return void
		 */
		private function doCoreUpdate(array $ids, array $attr)
		{
			$table = $this->_model->getTrueTableName();
			if($this->_model->getDao()->isMirrorAsync()){
				if(!$this->_sendAsync('update', array('ids'=>$ids, 'attr'=>$attr))){
					Slae::log("send async update {$table} failed");
				}
			}else{
				$pk    = $this->_model->getMeta()->primaryKey;
				$vsid  = $this->_model->getDao()->getMirrorVsid();
				$build = new Leb_Builder();
				$c     = self::criteria(array($pk=>$ids));
				$sql   = $build->buildUpdate($table, $c, $attr);
				if(false === $this->_model->getMasterConnect($vsid)->execute($sql, $build->param)){
					Slae::log("update {$table} failed");
				}
			}
		}

		/**
		 * 兼容老版本更新逻辑
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $attr
		 * @param  array  $ids
		 * @return bool
		 */
		private function doCompatUpdate(array $attr, $ids)
		{
			foreach($ids as $id)
			{
				if(!$data = Slae::app()->cache->default->get($id))
					continue;
				if(!$data = leb_json_decode($data))
					continue;
				$data += $attr;
				$data = leb_json_encode($data);
				Slae::app()->cache->default->set($id, $data);
			}

			return false;
		}

		/**
		 * 更新ComDB数据
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $attr		待更新的内容
		 * @param  array  $ids      待更新的ID记录
		 * @return int    $ret		是否更新成功
		 */
		private function doGlobalUpdate(array $attr, $ids)
		{
			$ret = 0;
			$temp = array();
			$def = $this->_model->getMeta()->getColumnDefault();
			foreach($ids as $id){
				$set = Slae::app()->comdb->get($id);
				if(!$set){
					$set = array_merge($def, $attr);
					Slae::log('can not found key:'.$id);
				}

				$data = array_merge($set, $attr);
				$data[self::PLAIN_KEY] = $id;
				$is_set = Slae::app()->comdb->set($id, $data);
				if(!$is_set){
					Slae::log('can not set key:'.$id);
				}else{
					$ret++;
				}
			}
			return $ret;
		}

		/**
		 * 删除指定数据
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object      $criteia
		 * @return int | bool  $ret
		 */
		public function doDelete($criteria)
		{
			if(!call_user_func(array($this->_model, 'beforeDelete')))
				return !$this->_model->addError('', 'Delete canceled by user');

			$ids = $this->_getSets($criteria);
			if($this->_model->getDaoType() && empty($ids)){
				return 0;
			}
			$build = new Leb_Builder();
			$table = $this->_model->getTrueTableName();
			$vsid = $this->_model->getVsId();
			$sql = $build->buildDelete($table, $criteria);
			$ret = $this->_model->getDao()->getMasterConnect($vsid)->execute($sql, $build->getParam());
			if(false === $ret || !$this->_model->getDaoType()){
				return $ret;
			}

			$failed = array();
			if($this->_model->getIdMode() == Leb_Model::ID_GLOBAL){
				$is_mirror = $this->_model->getDao()->isMirror();
				if($is_mirror){
					$this->doCoreDelete($ids);
				}
				foreach($ids as $id){
					$is_ok = Slae::app()->comdb->delete($id);
					if(!$is_ok){
						$failed[] = $id;
					}
				}
				return $ret;
			}else{
				$this->doCompatDelete($ids, $failed);
			}

			if($failed){
				//set log
			}

			if($ret){
				call_user_func(array($this->_model, 'afterDelete'));
			}
			return $ret;
		}

		/**
		 * 异步删除数据
		 * @access public
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object  $criteia
		 * @return bool
		 */
		public function doDeleteAsync($criteria)
		{
			return '' != $this->_sendAsync(
				'deleteAll',
				array(
					'vsid'=>$this->_model->getVsId(),
					'criteria'=>$criteria,
				)
			);
		}

		/**
		 * 兼容老版本删除逻辑
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $ids
		 * @param         $failed
		 * @return bool
		 */
		private function doCompatDelete($ids, &$failed)
		{
			$daoType = $this->_model->getDaoType();
			$cache = Slae::app()->cache->default;
			foreach($ids as $id)
			{
				$key = $this->_getCacheKey($id);
				$hkey= $this->_getHashKey($key);

				if(self::DAO_TYPE_BOTH == $daoType || self::DAO_TYPE_MYSQL == $daoType)
				{
					$critera = $this->_model->criteria(array(self::DB_CFG_KEY=>$hkey));
					$sql = $build->buildDelete(self::DB_CFG_DATA, $criteria);
					if(false === $this->_model->getDao()->getMasterConnect()->execute($sql, $build->param))
						continue;
				}

				if($cache && (self::DAO_TYPE_BOTH == $daoType || self::DAO_TYPE_MEMCACHE == $daoType))
					$cache->del($hkey);
			}

			return false;
		}

		/**
		 * 删除总库数据
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array  $ids
		 * @return void
		 */
		private function doCoreDelete(array $ids)
		{
			$table = $this->_model->getTrueTableName();
			if($this->_model->getDao()->isMirrorAsync()){
				$params = array('ids'=>$ids);
				$ret = $this->_sendAsync('delete', $params);
				if(!$ret){
					Slae::log("send async delete {$table} failed", Leb_Log::LEVEL_ERROR);
				}
			}else{
				$vsid  = $this->_model->getDao()->getMirrorVsid();
				$build = new Leb_Builder();
				$pk    = $this->_model->getMeta()->primaryKey;
				$cond  = $this->_model->criteria();
				$where = array(
					$pk => array($ids, 'IN'),
				);
				$cond->where($where);
				$sql   = $build->buildDelete($table, $cond);
				$ret = $this->_model->getDao()->getMasterConnect($vsid)->execute($sql, $build->getParam());
				if(!$ret){
					Slae::log("delete {$table} - {$pk} - {$id} failed", Leb_Log::LEVEL_ERROR);
				}
			}
		}

		/**
		 * 根据查询条件获取主键集合,然后根据这些主键集合可以去ComDB中取数据
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object  $criteia		 sql查询标准对象
		 * @param  array   $default		 查询失败默认返回值
		 * @param  bool    $only_id		 查询结果集是否仅有主键值
		 * @return array
		 */
		private function _getSets($criteria, $default=array(), $only_id=true)
		{
			if(!$criteria->getOrder() && !$criteria->getGroup() && 1 == count($criteria->getWhere())){
				$ids = $this->_detectSet($criteria, $only_id);
				if($ids){
					return $ids;
				}
			}
			$vsid = $this->_model->getVsId();
			$pdo = $this->_model->getDao()->getSlaveConnect($vsid);
			if(!$pdo){
				$this->_model->addError('', 'Can not get database connect');
				return $default;
			}

			$pk = $this->_model->getMeta()->getPrimaryKey();
			$build = new Leb_Builder();
			$field = $criteria->getField();
			$temp_field = $field;
			$criteria->field($pk);

			$order = $criteria->getOrder();
			if(!$order){
				$criteria->order($pk);
			}
			$sql = $build->buildFind($this->_model->getTrueTableName(), $criteria);
			$ids = $pdo->queryAll($sql, $build->getParam());
				var_dump($ids);
			$criteria->order($order);
			$criteria->field($temp_field);

			if($ids){
				$id_array = array();
				foreach($ids as $k => $item){
					$id_array[] = $item['id'];
				}
				return $id_array;
			}else{
				return $default;
			}
		}

		/**
		 * 检测是否有指定主键结果集
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  object  $criteia  sql查询标准对象
		 * @param  bool    $only_id   查询结果集是否仅有主键值
		 * @return array   $ids
		 */
		private function _detectSet($criteria, $only_id=false)
		{
			$ids = array();
			$pk = $this->_model->getMeta()->primaryKey;
			$where = $criteria->getWhere();
			if(isset($where[$pk])){
				$ids[$pk] = $where[$pk];
			}
			return $ids;
		}

		/**
		 * 返回逻辑字段key
		 *
		 * 结构为：数据库名_表名_主键
		 *
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  mixed         $val
		 * @return array | bool
		 */
		private function _getCacheKey($val)
		{
			if($this->_model->_globalIdMode)
				return $val;

			$key = $this->_model->getMeta()->primaryKey;
			!$key && trigger_error('primary key can not be empty');
			$dbName = $this->_model->getDbName();
			$table  = $this->_model->getTableTableName();
			if(is_array($val) && !is_array($key) && !$tmp=array())
			{
				foreach($val as $v)
					$tmp[$v] = $dbName.'_'.$table.'_'.$key.'_'.$v;
				return $tmp;
			}
			elseif(!is_array($val) && !is_array($key))
				return $dbName.'_'.$table.'_'.$key.'_'.$val;
			elseif(is_array($key) && is_array($val))
			{
				$equal = true;
				foreach($key as $k)
				{
					if(!isset($val[$k]) && !$equal=false)break;
				}

				if($equal)
				{
					$val = ksort(array_intersect_key($val, array_flip($key)));
					return $dbName.'_'.$table.'_'.implode('_', array_keys($val)).'_'.implode('_', $val);
				}
				elseif(sort($key) && ($skey = implode('_', $key)) && !$tmp=array())
				{
					$key = array_flip($key);
					foreach($val as $v)
					{
						$v = ksort(array_intersect_key($v, $key));
						$kv= implode('_', $v);
						$tmp[$kv] = $dbName.'_'.$table.'_'.$skey.'_'.$kv;
					}
					return $tmp;
				}
			}
			return false;
		}

		/**
		 * 根据配置返回逻辑字段key
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  string  $key
		 * @return string  $key
		 */
		private function _getHashKey($key)
		{
			if(DATA_KEY_MD5 && !$this->_model->_globalIdMode)
			{
				if(is_array($key))
				{
					$tmp = array();
					foreach($key as $k => $v)
						$tmp[$k] = md5($v);
					return $tmp;
				}
				else
					return md5($key);
			}
			else
				return $key;
		}

		/**
		 * 发送异步双写任务
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  string         $action
		 * @param  array          $param
		 * @return bool | string  $task
		 */
		private function _sendAsync($action, $param)
		{
			//检测worker是否部署
			//if(!$this->workerExists())
			//    return false;

			//检测域名是否存在
			if(empty($_SERVER['HTTP_HOST']) || false !== ip2long($_SERVER['HTTP_HOST']))
			{
				trigger_error('can not found domain', E_USER_ERROR);
				return false;
			}

			$vsid = $this->_model->getDao()->getMirrorVsid();
			$payload=array(
				'domain' => $_SERVER['HTTP_HOST'],
				'app'    => 'openapp',
				'controller' => 'model',
				'action' => $action,
				'params' => array(
					'vsid'=> $vsid,
					'tbl' => $this->_model->getTableName(),
					'prefix'=>$this->_model->getPrefix(),
					'suffix'=>$this->_model->getSuffix(),
					'cfg' => $this->_model->getDao()->getDbInfo(true, $vsid),
					'globalIdMode'=>$this->_model->getIdMode(),
					'daoType'=>$this->_model->getDaoType(),
					'domain' => $_SERVER['HTTP_HOST'],
				),
			);

			$payload['params'] = array_merge($payload['params'], $param);
			var_dump($payload);
			foreach($payload['params'] as &$item){
				if(is_array($item))
					$item = json_encode($item);
				elseif(is_object($item))
					$item = serialize($item);
			}

			$task = Slae::app()->gearman->runAsync('run_fastcgi_action', $payload);
			return $task;
		}

		/**
		 * 检测异步Worker是否安装
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  void
		 * @return bool
		 */
		private function _isWorkerExists()
		{
			//Worker与前端部署到同一机器检测worker安装目录是否存在
			if(file_exists(_APP_ . _DS_ . 'openapp' . _DS_ . 'model.php')){
				return true;
			}
			trigger_error('Asynchronous worker is not install! checkout worker from framework/openapp to app/openapp', E_USER_ERROR);
			return false;
		}


		/**
		 * 过滤查询结果集的字段数据,从全数据中返回查询字段 
		 * @access private
		 * @author Liu Guangzhao <guangzhao@leju.com>
		 * @param  array	$result   从ComDB返回的结果集全数据
		 * @param  Leb_Criteria  $criteria   Leb_Criteria  SQL信息存储对象
		 * @return array  过滤后的结果集数据 
		 */
		private function fiterField($result=array(), &$criteria=null){
			$return = array();
			if(!empty($result)){
				$fields = $criteria->getField();
				if(empty($fields) || $fields == '*'){
					$return = $result;
				}else{
					$fields = explode(',', $fields);
					$fields = array_flip($fields);
					foreach($result as $k => $item){
						$return[] = array_intersect_key($item, $fields);	
					}
				}
			}
			return $return;
	}
}
