<?php
/**
 * 定义  VO_Acl_Resource 资源控制器
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

class VO_Acl_Resource extends VO_Object{
	
	
	protected $resourceId = null;
	/**
	 * 构造器
	 */
	public function __construct($resource){
		$this->setResourceId($resource);
	}
	
	/**
	 * 设置资源ID
	 */
	public function setResourceId($resource){
		$this->resourceId = (string)$resource;
	}
	
	/**
	 * 返回资源ID
	 */
	public function getResourceId(){
		return $this->resourceId;
	}
}