<?php
/**
 * 定义  VO_Acl_Resourcestorage   Access Contrll List 资源存储器
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

class VO_Acl_Resourcestorage extends VO_Object{
	
	
	/**
	 * 构造器
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Acl_Resourcestorage
	 */
	public static function getInstance(){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
}