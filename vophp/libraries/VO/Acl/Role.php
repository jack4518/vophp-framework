<?php
/**
 * 定义  VO_Acl_Role   Access Contrll List 角色控制器
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

class VO_Acl_Role extends VO_Object{
	
	
	protected $role_id = null;
	/**
	 * 构造器
	 */
	public function __construct($role){
		$this->setRoleId($role);
	}
	
	/**
	 * 设置角色ID
	 */
	public function setRoleId($role){
		$this->role_id = (string)$role;
	}
	
	/**
	 * 返回角色ID
	 */
	public function getRoleId(){
		return $this->role_id;
	}
}