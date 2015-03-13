<?php
/**
 * ���� VO_Search ��������ӿڲ�
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Search{
	
	/**
	 * �����������洢��
	 * @var array
	 */
	protected static $search_instances = array();
	
	/**
	 * ���캯��
	 */
	public function __construct(){}
	
	/**
	 * ��ȡ��������ʵ��
	 * @param Array  $config 
	 * @return VO_Search_Adapter_Abstract
	 */
	public static function getInstance(array $search_config=null){
		if(empty($search_config)){
			$search_config = C('search');
		}
		
		$key = md5(serialize($search_config));
		if(isset(self::$search_instances[$key]) && self::$search_instances[$key] instanceof VO_Search_Adapter_Abstract){
			return self::$search_instances[$key];
		}
		
		VO_Loader::import('Search.Adapter.Abstract');
		if(empty($search_config) ){
			$this->triggerError('�����������ò�������ȷ���������ò���.');
			exit;
		}
		if(!is_array($search_config) && !is_object($search_config)){
			$this->triggerError('�����������ò�������Ϊ������Ƕ���.');
			exit;
		}
		
		$adapter = is_object($search_config) ? $search_config->adapter : $search_config['adapter'];
		if(!$adapter){
			$this->triggerError('����������������������ȷ����ȷ��VOPHP�Ƿ�֧�����е�����������.');
			exit;
		}
		
		$db_adapter_namespace = 'VO_Search_Adapter';

		
		$adapter_config = $search_config[$adapter];
		
		//����ĸ��д
		$adapters = explode('_', $adapter);
		foreach($adapters as $k => $v){
			$adapters[$k] == ucfirst($v);
		}
		$adapter = implode('_', $adapters);
		$adapter = $db_adapter_namespace . '_' . $adapter;
		self::$search_instances[$key] = new $adapter( (array)$adapter_config );
		return self::$search_instances[$key];
	}
}