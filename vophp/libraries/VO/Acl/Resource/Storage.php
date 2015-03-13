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

class VO_Acl_Resource_Storage extends VO_Object{
	
	public $resources = array();
	
	/**
	 * 构造器
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Acl_Resource_Storage
	 */
	public static function getInstance(){
		if( !isset(self::$_instance) || self::$_instance === null ){
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	/**
	 * 增加一个资源
	 * @param VO_Acl_Resource $resource
	 * @param $parents
	 * @return VO_Acl_Resourcestorage
	 */
	public function add($resource=null, $parent=null){
		if($this->has($resource)){
			$this->triggerError('资源' . $resource . '已经存在');
			exit;
		}
		$resource_id = $resource->getResourceId();
		if(!empty($parent)){
				if($parent instanceof VO_Acl_Resource){
					$parent_id = $parent->getResourceId();
				}else{
					$parent_id = (string) $parent;
				}
				$parent = $this->get($parent_id);
				$this->resources[$parent_id]['children'][$resource_id] = $resource;
		}
		
		$this->resources[$resource_id] = array(
            'instance' => $resource,
            'parents'  => $parent,
            'children' => array()
        );
        return $this;
	}
	
	/**
	 * 删除资源
	 * @param mixed $resource VO_Acl_Resource | string
	 * @return VO_Acl_Resourcestorage
	 */
	public function remove($resource){
        $resource_id = $this->get($resource)->getResourceId();
        if($this->resources[$resource_id]['children']){
	        foreach ($this->resources[$resource_id]['children'] as $childId => $child) {
	            unset($this->resources[$resource_id]['parents'][$resource_id]);
	        }
        }
        
        if($this->resources[$resource_id]['parents']){
	        foreach ($this->resources[$resource_id]['parents'] as $parentId => $parent) {
	            unset($this->resources[$parentId]['children'][$resource_id]);
	        }
        }
        unset($this->resources[$resource_id]);

        return $this;
	}
	
	/**
	 * 资源是否已经存在
	 * @param $resource 
	 */
	public function has($resource){
		if($resource instanceof VO_Acl_Resource){
			$resource_id = $resource->getResourceId();
		}else{
			$resource_id = (string) $resource;
		}
		return isset($this->resources[$resource_id]);
	}
	
	/**
	 * 取得资源对象
	 * @param $mixed $resource  VO_Acl_Resource | string
	 * @return VO_Acl_Resource
	 */
	public function get($resource){
        if ($resource instanceof VO_Acl_Resource) {
            $resource_id = $resource->getResourceId();
        } else {
            $resource_id = (string) $resource;
        }
        if (!$this->has($resource_id)) {
            $this->triggerError('资源' . $resource . '不存在');
			exit;
        }
        return $this->resources[$resource_id]['instance'];
    }
    
    /**
     * 获取父资源
     * @param $resource
     * @return array
     */
	public function getParents($resource){
        $resource_id = $this->get($resource)->getResourceId();
        return $this->resources[$resource_id]['parents'];
    }
    
    /**
     * 删除所有资源
     * @return VO_Acl_Resourcestorage
     */
	public function removeAll(){
        $this->_resources = array();

        return $this;
    }

    /**
     * 获取所有资源
     */
    public function getResources(){
        return $this->_resources;
    }	
}