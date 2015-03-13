<?php
/**
 * 定义  VO_Http_Ini XML文件解析类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-25
 **/

defined('VOPHP') or die('Restricted access');

error_reporting(E_ALL   &   ~E_NOTICE); 

class VO_Parse_Xml{
	
	//是否解析Section
	protected static $is_section = true;
	
	//选项的分隔字符
	protected static $item_separator = '.';
	
	//变量数组
	protected static $tmp_arr = '';
	
	protected static $_objectName = '';
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Parse_Xml
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Parse_Xml ){
			$instance = new self();
		}
		return $instance;
	}
	/**
	 * 解析XML文件
	 * @param Array  $config 
	 */
	public static function Parse($file, $session=null, $type='object', $is_section = true){
		if(is_bool($is_section)){
			self::$is_section = $is_section;
		}
		$xmlArray = self::_parseXml($file);
		
		$session_data = self::__getBySection($xmlArray,$session);
		if($type=='object'){
			$data= new stdClass();
	        foreach ($session_data as $key => $value) {
	            if (is_array($value)) {
	                $data->$key = self::_toObject($value);
	            } else {
	                $data->$key = $value;
	            }
	        }        
	        return $data;
		}else{
       		return $session_data;
		}
	}
	
	/**
	 * 将对象转化为数组
	 * @param array $arr
	 */
	public static function _toObject($arr){
		$obj = new stdClass();
		foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $obj->$key = self::_toObject($value);
            } else {
                $obj->$key = $value;
            }
        }
        return $obj;
	}
	/**
	 * 载入XML文件
	 * @param string $file
	 */
	private static function _loadXmlFile($file=''){
		if(!file_exists($file)){
			$this->triggerError('XML文件不存在.');
			exit;
		}
		if (strstr($file, '<?xml')) {
            $arr = simplexml_load_string($file);
        } else {
            $arr = simplexml_load_file($file);
        }
		return $arr;
	}
	
	/**
	 * 解析XML文件
	 * @param array $array
	 */
	protected static function _parseXml($file){
		$xml_array = self::_loadXmlFile($file);
		if(!is_object($xml_array)){
			return false;
		}
		$data_array = array();
		foreach($xml_array as $k => $v){
			if(is_object($v)){
				$data_array[$k] = self::_toArray($v);
			}else{
				$tmpArr = (array)$v;
        		$data_array[$k] = $tmpArr[0];
			}
			
			if( count($v->attributes()) > 0 ){
				foreach($v->attributes() as $attr_k => $attr_v){
					$tmpArr = (array)$attr_v;
					$data_array[$k][$attr_k] = $tmpArr[0];
				}
			}
		}        
		return $data_array;
	}
	
	/**
	 * 解析数组键值.将[db.host]解析成array('db'=>host)
	 * @param 预设数组 $arr
	 * @param 键值 $key
	 * @param 值 $v
	 */
	protected static function _processKey($arr,$key='',$v=''){
		$pieces = explode(self::$item_separator,$key,2);
		if($pieces[0] && $pieces[1]){
			$flag = strpos($pieces[1],self::$item_separator);
			if($flag !== false){
				echo $arr[$pieces[0]];
				$arr[$pieces[0]] = self::_processKey($arr[$pieces[0]],$pieces[1],$v);
			}else{
				$arr[$pieces[0]][$pieces[1]] = $v;
			}
		}else{
			$arr[$pieces[0]] = $v;
		}
		return $arr;
	}
	
	/**
	 * 将对象转化为数组
	 * @param Object $element
	 */
	protected static function _toArray($element){
		$data_array = array();
		foreach($element as $k => $v){
			if($v->children()){
				$data_array[$k] = self::_toArray($v);
			}else{
				$tmpArr = (array)$v;
        		$data_array[$k] = $tmpArr[0];
			}
			
			if( count($v->attributes()) > 0 ){
				foreach($v->attributes() as $attr_k => $attr_v){
					$tmpArr = (array)$attr_v;
					$data_array[$k][$attr_k] = $tmpArr[0];
				}
			}
        }
        return $data_array;
	}
	
	/**
	 * 根据指定的session返回其值
	 * @param unknown_type $arr
	 * @param unknown_type $session
	 */
	public static function __getBySection($arr,$session=null){
		if( empty($session) || !is_array($arr)){
			return $arr;
		}
		foreach ($arr as $key => $value) {
			if($key == $session){
				return $value;
			}
	    }
	    return $arr;
	}
	
}