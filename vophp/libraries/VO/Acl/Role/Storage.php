<?php
/**
 * 定义  VO_Acl_Rolestorage   Access Contrll List 角色存储器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-09-09
 **/

defined('VOPHP') or die('Restricted access');

class VO_Acl_Role_Storage extends VO_Object{
	
	public $roles = array();
	
	
	/**
	 * 构造器
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Acl_Role_Storage
	 */
	public static function getInstance(){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	/**
	 * 增加一个角色
	 * @param VO_Acl_Role $role
	 * @param $parents
	 * @return VO_Acl_Rolestorage
	 */
	public function add($role=null, $parents=null){
		if($this->has($role)){
			$this->triggerError('角色' . $role . '已经存在');
			exit;
		}
		$role_id = $role->getRoleId();
		if(!is_array($parents)){
			$parents = (array) $parents;
		}
		$parentsRoles = array();
		if(!empty($parents)){
			foreach ($parents as $parent){
				if($parent instanceof VO_Acl_Role){
					$parent_id = $parent->getRoleId();
				}else{
					$parent_id = (string) $parent;
				}
				$parentRole = $this->get($parent_id);
				$parentsRoles[$parent_id] = $parentRole;
				$this->roles[$parent_id]['children'][$role_id] = $role;
			}
		}
		
		$this->roles[$role_id] = array(
            'instance' => $role,
            'parents'  => $parentsRoles,
            'children' => array()
        );
        return $this;
	}
	
	/**
	 * 删除角色
	 * @param mixed $role VO_Acl_Role | string
	 * @return VO_Acl_Rolestorage
	 */
	public function remove($role){
        $role_id = $this->get($role)->getRoleId();
        if($this->roles[$role_id]['children']){
	        foreach ($this->roles[$role_id]['children'] as $childId => $child) {
	            unset($this->roles[$role_id]['parents'][$role_id]);
	        }
        }
        
        if($this->roles[$role_id]['parents']){
	        foreach ($this->roles[$role_id]['parents'] as $parentId => $parent) {
	            unset($this->roles[$parentId]['children'][$role_id]);
	        }
        }
        unset($this->roles[$role_id]);

        return $this;
	}
	
	/**
	 * 角色是否已经存在
	 * @param $role 
	 */
	public function has($role){
		if($role instanceof VO_Acl_Role){
			$role_id = $role->getRoleId();
		}else{
			$role_id = (string) $role;
		}
		return isset($this->roles[$role_id]);
	}
	
	/**
	 * 取得角色对象
	 * @param $mixed $role  VO_Acl_Role | string
	 * @return VO_Acl_Role
	 */
	public function get($role){
        if ($role instanceof VO_Acl_Role) {
            $role_id = $role->getRoleId();
        } else {
            $role_id = (string) $role;
        }

        if (!$this->has($role_id)) {
            $this->triggerError('角色' . $role . '不存在');
			exit;
        }
        return $this->roles[$role_id]['instance'];
    }
    
    /**
     * 获取父角色
     * @param $role
     * @return array
     */
	public function getParents($role){
        $role_id = $this->get($role)->getRoleId();
        return $this->roles[$role_id]['parents'];
    }
    
    /**
     * 删除所有角色
     * @return VO_Acl_Rolestorage
     */
	public function removeAll(){
        $this->_roles = array();

        return $this;
    }

    /**
     * 获取所有角色
     */
    public function getRoles(){
        return $this->_roles;
    }
}