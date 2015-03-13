<?php
/**
 * 定义  VO_Http_Ini Ini文件解析类
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

class VO_Parse_Ini{
	//是否解析Section
	protected static $is_section = true;
	
	//选项的分隔字符
	protected static $item_separator = '.';
	
	//变量数组
	protected static $tmp_arr = '';
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Parse_Ini
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Parse_Ini ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 解析Ini文件
	 * @param Array  $config 
	 */
	public static function Parse($file, $session=null, $type='object', $is_section = true){
		if(is_bool($is_section)){
			self::$is_section = $is_section;
		}
		$iniArray = self::_parseIni($file);
		
		$session_data = self::__getBySection($iniArray, $session);
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
	 * 载入ini文件
	 * @param string $file
	 */
	protected static function _loadIniFile($file=''){
		if(!file_exists($file)){
			$this->triggerError('Ini文件不存在.');
			exit;
		}
		$arr = parse_ini_file($file,self::$is_section);
		return $arr;
	}
	
	/**
	 * 解析ini文件
	 * @param array $array
	 */
	protected static function _parseIni($file){
		$ini_array = self::_loadIniFile($file);
		if(!is_array($ini_array)){
			return false;
		}
		$data_array = array();
		foreach($ini_array as $section_key => $section){
			if(is_array($section)){
				foreach($section as $k => $v){
					$pieces = explode(self::$item_separator,$k,2);
					if(count($pieces)>1){
						$item = $ini_array[$section_key][$k];
						$data_array[$section_key][$pieces[0]] = self::_processKey(array(),$pieces[1],$v);
					}else{
						$data_array[$section_key][$pieces[0]] = $v;
					}
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
	protected static function _processKey($arr, $key='', $v=''){
		$pieces = explode(self::$item_separator, $key, 2);
		if(isset($pieces[0]) && isset($pieces[1])){
			$flag = strpos($pieces[1],self::$item_separator);
			if($flag !== false){
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