<?php
/**
 * 管理员模型
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-05-19
 * */
class Index extends VO_Model{
	
	/**
	 * 表名
	 * @var string
	 */
	protected $_name = 'manager';
	
	/**
	 * 主键
	 * @var string
	 */
	protected $_key = 'id';
	
	/**
	 * 用户组模型
	 * @var Group
	 */
	private $_groupModel = '';
	
	/**
	 * 省份模型
	 * @var Province
	 */
	private $_provinceModel = '';

	/**
	 * 城市模型
	 * @var City
	 */
	private $_cityModel = '';

	/**
	 * 地区模型
	 * @var Area
	 */
	private $_areaModel = '';
	
	/**
	 * 项目配置信息模型
	 */
	private $_configModel = null;
	
	/**
	 * 操作痕迹模型
	 * @var Trace
	 */
	private $_traceModel = null;
	
	
	/**
	 * 构造函数
	 */
	function __construct(){
		parent::__construct();

		/*
		$this->_traceModel = $this->loadModel('trace', 'system');
		$this->_groupModel = $this->loadModel('group');
		$this->_provinceModel = $this->loadModel('province');
		$this->_cityModel = $this->loadModel('city');
		$this->_areaModel = $this->loadModel('area');
		$this->_configModel = $this->loadModel('config', 'system');
		*/
	}
	
	/**
	 * 获取所有用户
	 * 
	 * @return array  所有用户信息
	 */
	public function getAll(){
		//$ret = $this->alterColumn('abc', 'abc', 'char(50)', 'null', true, '测试一下');
		//$ret = $this->renameTable('manager_bak', 'manager');

		/*
		$memcache = $this->load('cache.memcache');
		$memcache->set('name', 'cqq');
		var_dump($memcache->get('name'));
		*/
		/*
		$couchbase = $this->load('nosql.couchbase');
		$couchbase->set('name', 'ddf');
		var_dump($couchbase->get('name'));
		*/
		
		//$redis = $this->load('redis');
		//$mongodb = $this->load('nosql.mongodb');
		//$coll = $mongodb->selectCollection('manager');
		//$arr = array('username' => '启清', 'age' => 20, 'hobby' => array('唱歌', '打球', '旅游', '跳舞','打牌',));
		//$ret = $mongodb->insert($arr);
		$where = array(
			'username' => array(array('启', 'i'), 'regex', 'OR', 
				array(
					'age' => array(20, '='),
					'hobby' => array(array('唱歌'), 'IN'),
				),
			),
			//'username' => array('李四', '='),
			//'age' => array(array(10, 20), 'IN'),
			//'age' => array(array(10, 0), 'MOD'),
			//'age' => array(array(10, 32), 'NOT IN'),
			//'age' => array(array( 20), 'ALL'),
			//'age' => array(true, 'EXISTS'),
			/*
			'age' => array(
				array(true, 'EXISTS', 'OR'),
				array(20, 'IN'),
			),
			*/
			/*
			'hobby' => array(
				array(
					'school' => array('跳舞', 'IN'),
					'abc' => array('10', '>'),
				),
				'ELEMMATCH',
				'OR'
			),
			*/
			//'age' => array(array(10, 30), 'between'),
			//'username' => array(array('四', 'i'), 'regex'),
			
			/*
			array(
				array('name' => 'jackchen'),
				array('title' => '新浪乐居'),
				'OR'
			),
			*/
			
		);
		$field = array(
			'username' => 1,
			//'_id' => 0,
			'age' => 1,
			'hobby' => array(array(3, 2), 'SLICE')
		);
		//var_dump($mongodb->field($field)->where( array('age' => array('DESC', 'ORDER' )) )->order(array('username' => 'asc'))->offset(0)->limit(20)->find());
		//$ret = $mongodb->update(array('_id' => '54f411fa155ea9f08900002b'), array('username' => '张三'));
		//var_dump($ret);
		//$mongodb->close();
		//$mongodb->createCollection('abcdef');
		//var_dump($mongodb->ensureIndex('www_vocms_com', array('username' => 1)));
		//var_dump($redis->set('a', 20));
		//var_dump($redis->get('a'));
		$user = array(
			'id' => 16,
			'username' => "4518",
			'password' => '3rfsdfdsf',
			'slat' => 'qqq',
			'email' => 'dsaf@fa.com',
			'abc' => 'dfdf',
			'group_id' => '6'
		);
		$ret = $this->insert($user);
		//$ret = $this->startTrans();
		//dump('abc');
		//$this->dropIndex('idx_a');
		$this->find();
		$cache = new VO_Cache_Memcache();
		var_dump($cache->get('vo_manager_10'));
		exit;
		$ret = $this->save($user);
		//$this->commit();
		//$ret = $this->delete(array('id' => 15));
		//$users = $this->find('id');
		//$ret = $this->addColumn('abddcqqs', 'int(8)', 0, false, '测试', 'edit_date');
		var_dump($ret, $this->getError());
		//trigger_error('fadsfsdafds', E_USER_ERROR);
		//throw new VO_Exception('fadsfsdafds');
		//var_dump($users);
		var_dump($ret);
		return $users;
	}
    
	/**
	 * 根据用户ID获取用户信息
	 * @param  mixed $ids  用户的ID，可以是数组
	 * @return array   所有用户信息
	 */
	public function getByIds($ids){
	   $users = array();
	   if(!is_array($ids)){
	       $ids = array($ids);
	   }
       foreach($ids as $k => $id){
            $id =intval($id); 
            $where = array(
    			'id' => $id,
    			'is_deleted' => '0',
    			'username' => array('%asdf%', 'LIKE')
    		);
            $row = $this->foundRows()->field('id')->where($where)->order('create_date DESC')->page(10, 5)->select();
            $row = $this->where($where)->find();
            if($row){
                $users[] = $row;
            }
       }
       return $users;
	}    
	
	/**
	 * 判断用户是否存在
	 * @param string $name	用户名
	 */
	public function isManagerExist($name){
		$where = array(
			'username' => $name
		);
		$ret = $this->where($where)->count();
		return $ret;
	}
	
	/**
	 * 根据用户名获取用户信息
	 * @param string $name	用户名
	 */
	public function getByUsername($name){
		$where = array(
			'username' => $name
		);
		$row = $this->where($where)->find();
		return $row;
	}
	
	/**
	 * 增加管理员
	 * @param array $post 	管理员信息
	 */
	public function addManager($post = array()){
		$errors = '';
		if(empty($post)){
			$this->_error = '提交的用户信息不能为空';
			return false;
		}else{
			if($post['group_id'] < _USER_GROUP_ID){
				$this->_error = '您没有权限添加此用户组用户,请更换用户组或者联系更高级别的管理员';
				return false;
			}
			
			$rules = array(
				'username' => array(
					array('isAlnumu', '用户名只能由字母、数字、下划线组成!'),
					array('notEmpty', '用户名不能为空!'),
				),
				'password' => array(
					array('notEmpty', '密码不能为空!'),
					array('strlenBetween', 6, 16, '密码长度必须大于6位字符小于16位字符!' ),
					array('equal', $post['verify_password'], '两次输入的密码不一致!' ),
				),
				'email' => array(
					array('notEmpty', '电子邮箱不能为空!'),
					array('isEmail', '邮箱格式不正确!' ),
				),
			);
			$validator = VO_Validator::getInstance();
			$ret = $validator->validateRowsWithRules($post, $rules, $errors);
			if($ret){
				$row = $this->getByUsername($post['username']);
				if($row){
					$this->_error = '管理员昵称为' . $post['username'] . '的用户已经存在,请更换用户名';
					return false;
				}else{
					if(isset($post['group_id']) && $post['group_id'] <=0){
						$post['group_id'] = 5;
					}
					
					$password_key = $this->_configModel->getByKey('manager_password_key');
					
					$post['password'] = md5(sha1($post['password']) . $manager_password_key);
					$post['creator'] = _USER_ID;
					$post['create_date'] = TIMESTAMP;
					
					$ret = $this->add($post);
					if($ret){
						//增加足迹
						$this->_traceModel->addTrace('添加了一个名为' . $post['username'] . '的管理员帐户');
						
						
						$is_sendmail = $this->_configModel->getByKey('is_sendmail_for_addmanager');
						if($is_sendmail == 1){
							$group = $this->_groupModel->getById($post['group_id']);
							//发送问题到邮箱
							$mail = C('mail');
							$to_mail = $post['email'];
							$subject = _USER_NAME . '在' . $_SERVER['SERVER_NAME'] .'网站后台为您添加的帐户信息';
							$content = '<span style="font-weight:bold;">帐号信息:</span><br/>用户登录名：' . $post['username'] . '<br />用户密码:' . $post['verify_password'] . '<br />所属管理组:' . $group['cn_name'] . '<br />电子邮箱:' . $post['email'];
							$mailer = VO_Mail::getInstance();
							$mailer->send($mail, $to_mail, $subject, $content);
						}
						return true;
					}else{
						$this->_error = '添加管理员失败!错误信息：' . $this->_db->getErrorMessage();
						return false;						
					}
				}
			}else{
				$this->_error = array_shift($errors);
				return false;
			}
		}
	}
	
	/**
	 * 编辑管理员
	 * @param array $post 	管理员信息
	 */
	public function editManager($post = array()){
		$configModel = $this->loadModel('config', 'system');
		$errors = '';
		if(empty($post)){
			$this->_error = '提交的用户信息不能为空';
			return false;
		}else{
			$row = $this->getById($post['id']);
			if( empty($row) ){
				$this->_error = '所要修改的用户不存在,请确认';
				return false;
			}elseif( $row['id'] == _USER_ID ){
				//如果是修改自己的用户信息
				$post = array_merge($row, $post);
			}elseif($row['group_id'] < _USER_GROUP_ID){
				$this->_error = '您没有权限修改此用户';
				return false;
			}
				
			$rules = array(
				'username' => array(
					array('isAlnumu', '用户名只能由字母、数字、下划线组成!'),
					array('notEmpty', '用户名不能为空!'),
				),
				'email' => array(
					array('notEmpty', '电子邮箱不能为空!'),
					array('isEmail', '邮箱格式不正确!' ),
				),
			);
			if( !empty($post['password']) ){
				$rules['password'] = array(
					array('strlenBetween', 6, 16, '密码长度必须大于6位字符小于16位字符!' ),
					array('equal', $post['verify_password'], '两次输入的密码不一致!' ),
				);
			}
			$validator = VO_Validator::getInstance();
			$ret = $validator->validateRowsWithRules($post, $rules, $errors);
			if($ret){
				if(isset($post['group_id']) && $post['group_id'] <=0){
					$post['group_id'] = 5;
				}
				if( !empty($post['password']) ){
					$mail_password = '新密码为:' . $post['password'];
					$manager_password_key = $this->_configModel->getByKey('manager_password_key');
					$post['password'] = md5(sha1($post['password']) . $manager_password_key);
				}else{
					unset($post['password']);
					$mail_password = '密码为您的旧密码';
				}
				$post['edit_date'] = TIMESTAMP;
				$post['editor'] = _USER_ID;
				
				$ret = $this->save($post);
				if($ret){
					//增加足迹
					$this->_traceModel->addTrace('修改了管理员' . $post['username'] . '的帐户信息');
					
					$is_sendmail = $this->_configModel->getByKey('is_sendmail_for_editmanager');
					if($is_sendmail == 1){
						$group = $this->_groupModel->getById($post['group_id']);
						//发送问题到邮箱
						$mail = C('mail');
						$to_mail = $post['email'];
						$subject = _USER_NAME . '修改了您在' . $_SERVER['SERVER_NAME'] .'网站后台的帐户信息';
						$content = '<span style="font-weight:bold;">帐号信息:</span><br/>用户登录名：' . $post['username'] . '<br />' . $mail_password . '<br />所属管理组:' . $group['cn_name'] . '<br />电子邮箱:' . $post['email'];
						$mailer = VO_Mail::getInstance();
						$mailer->send($mail, $to_mail, $subject, $content);
					}
					return true;
				}else{
					$this->_error = '修改管理员失败!错误信息：' . $this->_db->getErrorMessage();
					return false;						
				}
			}else{
				$this->_error = array_shift($errors);
				return false;
			}
		}
	}

	/**
	 * 删除管理员
	 * @param int $id	管理员ID
	 * @return mixed
	 */
	public function deleteManager($ids){
		if(empty($ids)){
			$this->_error = '非法的用户ID';
			return false;
		}
		
		if(!is_array($ids)){
			$id = $ids;
			$ids = array($id);
		}
		foreach($ids as $k => $id){
			$row = $this->getById($id);
			if($row){
				if($row['group_id'] < _USER_GROUP_ID){
					$this->_error = '您没有权限删除此用户';
					return false;
				}else{
					$data = array(
						'id'	=>	$id,
						'is_deleted' => 1,
						'deleter'	=>	_USER_ID,
						'delete_date'	=>TIMESTAMP
					);
					$ret = $this->save($data);
					if($ret){
						//增加足迹
						$this->_traceModel->addTrace('删除了管理员' . $row['username'] . '帐户');
					}else{
						$this->_error = '删除失败';
						return false;
					}
				}
			}else{
				$this->_error = '所要删除的用户不存在,请确认';
					return false;
			}
		}
		return true;
	}
	
	/**
	 * 设置管理员状态
	 * @param array $ids	管理员ID
	 * @param int	$status	管理员状态
	 */
	public function setStatus($ids, $status=null){
		if(!is_array($ids) || empty($ids)){
			$id = $ids;
			$ids = array($id);
		}
		
		$data = array();
		foreach($ids as $k => $id){
			$data = array('id'	=> $id);
			if($status === null){
			    $data['status'] = 0;
			}else{
				$data['status'] = $status;
			}
			$ret = $this->save($data);
		}
		if($ret){
			return true;
		}else{
			$this->_error = '设置失败';
			return false;
		}
	}
	
	/**
	 * 高级搜索信息
	 * @param array $post	查询及过滤等相关条件
	 * @return	array	查询到的数据信息
	 */
	public function seniorSearch($post){
		if(is_array($post)){
			//传过来的搜索条件中不在此定义的默认为等于搜索
			$cond = array(
					'username' => array('%' . $post['username'] . '%', 'LIKE'),
					'email' => array('%' . $post['email'] . '%', 'LIKE'),
			);
			
			//排序
			$order = $post['order'];
			$order_type = $post['order_type'];
			
			$limit = isset($post['limit']) && !empty($post['limit']) ? $post['limit'] : 20;
			$page = isset($post['page']) && !empty($post['page']) ? $post['page'] : 1;
			$offset = ($page-1) * $limit;
			
			unset($post['order']);
			unset($post['order_type']);
			unset($post['limit']);
			unset($post['page']);
			
			foreach($post as $k => $v){
				if(empty($v)){
					unset($post[$k]);
					continue;
				}
				if(array_key_exists($k, $cond)){
					$post[$k] = $cond[$k];
				}
			}
			
			//过滤已删除的管理员用户
			$post['is_deleted'] = 0;
			$post['group_id'] = array(_USER_GROUP_ID, '>=');
			
			$rows = $this->foundRows()->where($post)->order($order . ' ' . $order_type)->limit($limit)->offset($offset)->select();
			$total = $this->getFoundRows();
			if($rows){
				foreach($rows as $k => $row){
					$group = $this->_groupModel->getById($row['group_id']);
					if($group){
						$rows[$k]['group_name'] = $group['cn_name'];
					}
					//取出所在省市区
					if( !empty($row['area_id']) ){
						$where = array(
							'city_id' => $row['city_id'],
							'area_id' => $row['area_id'],
						);
						$area = $this->_areaModel->where($where)->find();
						if($area){
							$rows[$k]['areapath'] = $area['areapath'];
						}else{
							$row[$k]['areapath'] = '';
						}
					}elseif( !empty($row['city_id']) ){
						$where = array(
							'province_id' => $row['province_id'],
			                'city_id' => $row['city_id'],
						);
              			$city = $this->_cityModel->where($where)->find();
		              	if($city){
		                	$rows[$k]['areapath'] = $city['citypath'];
		              	}else{
		                	$row[$k]['areapath'] = '';
		              	}
					}elseif( !empty($row['province_id']) ){
			             $where = array(
			                'province_id' => $row['province_id']
			             );
			             $province = $this->_provinceModel->where($where)->find();
			             if($province){
			             	$rows[$k]['areapath'] = $province['provincepath'];
			             }else{
			                $row[$k]['areapath'] = '';
			             }
					}
				}
			}
			return array(
				'total'	=>	$total,
				'data'	=>	$rows,
			);
		}else{
			$this->_error = '非法的查询条件';
			return false;
		}
	}
	
	/**
	 * 将数据中的创建者、编辑者、删除者转换成用户名
	 * @param unknown_type $row	相应数据
	 * @return refence	返回数据的引用
	 */
	public function getUserNameForData($row){
		//创建者、编辑者、删除者
		if( isset($row['creator']) ){
			$user = $this->getById($row['creator']);
			if($user){
				$row['creator_user'] = $user['username'];
			}else{
				$row['creator_user'] = '';
			}
		}
		if( isset($row['editor']) ){
			$user = $this->getById($row['editor']);
			if($user){
				$row['editor_user'] = $user['username'];
			}else{
				$row['editor_user'] = '';
			}
		}
		if( isset($row['deleter']) ){
			$user = $this->getById($row['deleter']);
			if($user){
				$row['deleter_user'] = $user['username'];
			}else{
				$row['deleter_user'] = '';
			}
		}
		return $row;
	}
}