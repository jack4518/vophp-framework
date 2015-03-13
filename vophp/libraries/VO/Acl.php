<?php
/**
 * 定义VO_Acl(Access Contrll List 访问权限控制器)
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-09-09
 * */

defined('VOPHP') or die('Restricted access');

class VO_Acl{
	
	/**
	 * 增加操作常量
	 * @var string
	 */
	const OP_ADD = 'ADD';
	
	/**
	 * 删除操作常量
	 * @var string
	 */
	const OP_REMOVE = 'REMOVE';
	
	/**
	 * 允许操作常量
	 * @var string
	 */
	const TP_ALLOW = 'ALLOW';
	
	/**
	 * 拒绝操作常量
	 * @var string
	 */
	const TP_DENY = 'DENY';			
	
	/**
	 * 角色ID存储器
	 * @var array
	 */
	protected $role_ids = array();
	
	/**
	 * 资源ID存储器
	 * @var array
	 */
	protected $resource_ids = array();
	
	/**
	 * 角色存储对象
	 * @var VO_Acl_Role_Storage
	 */
	protected $roleStorage = null;
	
	/**
	 * 资源存储对象
	 * @var VO_Acl_Resource_Storage
	 */
	protected $resourceStorage = null;
	
	/**
	 * 资源和角色对应的权限规则
	 * @var array
	 */
	private $_rules = array();
	
	/**
	 * 构造函数
	 * @return VO_Acl
	 */
	public function __construct(){ }
	
	
	public function show(){
		//var_dump($this->roleStorage);
		//var_dump($this->resourceStorage);
		var_dump($this->_rules);
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Acl
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Acl ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 增加一个角色
	 * @param mixd $role  VO_Acl_Role | string  角色
	 * @param mixd $parents   父角色，可从此角色中继承权限
	 * @return VO_Acl
	 */
 	public function addRole($role, $parents = null){
        if(is_string($role)){
            $role = new VO_Acl_Role($role);
        }
        $this->_getRoleStorage()->add($role, $parents);

        return $this;
    }
    
	/**
	 * 增加一个资源
	 * @param mixd $resource  VO_Acl_Role | string  资源
	 * @param mixd $parents   父资源，可从此资源中继承权限
	 * @return VO_Acl
	 */
 	public function addResource($resource, $parents = null){
        if(is_string($resource)){
            $resource = new VO_Acl_Resource($resource);
        }
        $this->_getResourceStorage()->add($resource, $parents);

        return $this;
    }
    
	/**
	 * 删除一个角色
	 * @param mixd $role  VO_Acl_Role | string  角色
	 */
 	public function removeRole($role){
        $this->_getRoleStorage()->remove($role);
    }
    
	/**
	 * 删除所有角色
	 * @param mixd $role  VO_Acl_Role | string  角色
	 */
 	public function removeRoleAll($role){
        $this->_getRoleStorage()->removeAll();
    }    

	/**
	 * 删除一个资源
	 * @param mixd $role  VO_Acl_Role | string  角色
	 */
 	public function removeResource($resource){
        $this->_getResourceStorage()->remove($resource);
    }

	/**
	 * 删除所有一个资源
	 * @param mixd $role  VO_Acl_Role | string  角色
	 */
 	public function removeResourceAll($resource){
        $this->_getResourceStorage()->removeAll();
    } 
       
    
    /**
     * 允许访问规则
     * @param $roles
     * @param $resources
     * @param $privileges
     */
    public function allow($roles, $resource = null, $privilege = null){
    	return $this->setRule($operation=self::OP_ADD, $type=self::TP_ALLOW, $roles, $resource, $privilege);
    }
    
	/**
     * 拒绝访问规则
     * @param $roles
     * @param $resources
     * @param $privileges
     */
    public function deny($roles = null, $resource = null, $privilege = null){
    	return $this->setRule($operation=self::OP_ADD, $type=self::TP_DENY, $roles, $resource, $privilege);
    }
    
    /**
     * 移除允许的规则
     * @param $roles
     * @param $resources
     * @param $privileges
     */
	public function removeAllow($role = null, $resource = null, $privilege = null){
        return $this->setRule($operation=self::OP_REMOVE, $type=self::TP_DENY, $role, $resource, $privilege);
    }
    
    /**
     * 移除所有允许的规则
     * @param $roles
     * @param $resources
     * @param $privileges
     */
	public function removeAllowAll(){
        return $this->setRule($operation=self::OP_REMOVE, $type=self::TP_DENY, null, null, null);
    }
        
    /**
     * 移除拒绝规则
     * @param $roles
     * @param $resources
     * @param $privileges
     */
	public function removeDeny($role = null, $resource = null, $privilege = null){
		return $this->setRule($operation=self::OP_REMOVE, $type=self::TP_ALLOW, $role, $resource, $privilege);
    }
    
    /**
     * 移除所有拒绝规则
     */
	public function removeDenyAll(){
		return $this->setRule($operation=self::OP_REMOVE, $type=self::TP_ALLOW, null, null, null);
    }       
    
    /**
     * 用户是否有对某个资源的权限
     * @param $roles   //角色
     * @param $resources //资源
     * @param $privileges //权限
     * @param $loop 迭代父级目录的几层权限
     */
    public function isAllowed($role, $resource=null, $privilege = null, $loop=2){
    	$i = 2;
    	if($role==null){
    		$this->triggerError('Role is not null');
    		exit;
    	}
    	$currentResource = $resource;
    	while($i>0){
	    	$role = $this->_getRoleStorage()->get($role);
	        if(empty($resource)){
	    		$resource = null;
	    	}else{
	    		$resource = $this->_getResourceStorage()->get($resource);
	    	}
	    	foreach($this->_rules as $j => $rule){
	    		//查询Role具有所有资源的权限
	    		if( $rule['roleId']->getRoleId() == $role->getRoleId()
	    		     && $rule['resourceId'] == ''
	    		     && $rule['privilege'] == $privilege 
	    		     && $rule['type'] == self::TP_ALLOW ){
	    			return true;
	    		}
	    		
	    		//查询Role具有所有指定资源的权限
	    		if( $resource && $rule['roleId']->getRoleId() == $role->getRoleId()
	    		     && $rule['resourceId']->getResourceId() == $resource->getResourceId()
	    		     && $rule['privilege'] == $privilege 
	    		     && $rule['type'] == self::TP_ALLOW ){
	    			return true;
	    		}
	    	}
	    	$resource = $this->_getResourceStorage()->getParents($resource);
	    	$i--;
	    	if(empty($resource)){
	    		break;
	    	}
    	}
    	$loop--;
    	if($loop!=0){
    		//查找父角色规则
    		$roles = $this->_getRoleStorage()->getParents($role);
    		if(is_array($roles)){
    			foreach($roles as $k => $role){
    				return $this->isAllowed($role, $currentResource, $privilege, $loop);
    			}
    		}else{
    			return $this->isAllowed($roles, $currentResource, $privilege, $loop);
    		}
    		
    	}
    	
    	return false;
    }
   
    /**
     * 设置规则通过方法
     * @param $roles
     * @param $type
     * @param $resources
     * @param $privilege
     */
    private function setRule($operation=self::OP_ADD, $type=self::TP_ALLOW, $roles, $resources = null, $privilege = null){
	    if(!is_array($roles)){
	    		$roles = (array) $roles;
    	}
    	if(!is_array($resources)){
    		$resources = (array) $resources;
    	}
    	
    	if(empty($roles)){
    		$roles = array(null);
    	}
    	
   		if(empty($resources)){
    		$resources = array(null);
    	}
    	
    	//角色迭代
    	$temp = array();
    	foreach($roles as $k => $v){
    		if($v !== null){
    			$temp[] = $this->_getRoleStorage()->get($v);
    		}
    	}
    	$roles = $temp;
    	
    	//资源迭代
    	$temp = array();
    	foreach($resources as $k => $v){
    		if($v !== null){
    			$temp[] = $this->_getResourceStorage()->get($v);
    		}else{
    			$temp[] = null;
    		}
    	}
    	$resources = $temp;
    	unset($temp);
    	//设置权限规则
    	switch($operation){
    		case self::OP_ADD:
	    		foreach($resources as $k => $resource){
		    		foreach($roles as $i => $role){
		    			$this->_rules[] = array(
		    				'resourceId' => $resource,
		    				'roleId' => $role,
		    				'privilege' => $privilege,
		    				'type' => $type,
		    			);
		    		}
	    		}
	    		break;
	    		
    		case self::OP_REMOVE:
    			if(empty($roles) || empty($resources)){
	    			foreach($this->_rules as $k => $rule){
		    			$this->_rules[$k]['type'] = $type;
		    		}
    			}else{
	    			foreach($resources as $k => $resource){
			    		foreach($roles as $i => $role){
				    		foreach($this->_rules as $j => $rule){
					    		//查询Role具有所有指定资源的权限
					    		if( $resource && $rule['roleId']->getRoleId() == $role->getRoleId()
					    		     && $rule['resourceId']->getResourceId() == $resource->getResourceId()
					    		     && $rule['privilege'] == $privilege){
					    			$this->_rules[$j]['type'] = $type;
					    		}
					    	}
			    		}
	    			}
    			}
	    		break;
    	}
    	//var_dump($this->_rules);
    }
    
    /**
     * 获取角色存储对象VO_Acl_Rolestorage
     * @return VO_Acl_Role_Storage
     */
    private function _getRoleStorage(){
    	if(!$this->roleStorage instanceof VO_Acl_Role_Storage){
    		$this->roleStorage = new VO_Acl_Role_Storage();
    	}
    	return $this->roleStorage;
    }
    
	/**
     * 获取资源存储对象VO_Acl_Resourcestorage
     * @return VO_Acl_Resource_Storage
     */
    private function _getResourceStorage(){
    	if(!$this->resourceStorage instanceof VO_Acl_Resource_Storage){
    		$this->resourceStorage = new VO_Acl_Resource_Storage();
    	}
    	return $this->resourceStorage;
    }
}