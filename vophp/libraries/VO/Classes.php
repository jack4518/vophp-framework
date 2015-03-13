<?php
/**
 * 定义 VO_Class类 自动加载类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-05
 **/
defined('VOPHP') or exit("You don't have the access to execution VOPHP Framework");
final class VO_Classes{
	
	/**
	 * 类实例存储器
	 * @var VO_Loader
	 */
	static $instance = null;
		
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Loader
	 */
	public static function getInstance(){
		if( !self::$instance instanceof VO_Loader ){
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 获取类和文件的对应关系
	 * @return  array  类名和文件的对应关系
	 */
	public static function getRelationConfig(){
		return array(
		    'VO_String'    => 'VO/String.php',
		    'VO_Pinyin'    => 'VO/String/Pinyin.php',
		    'VO_Utils_Hash'=> 'VO/Utils/Hash/Hash.php',
		);
	}

	/**
	 * 根据类名获取类对应的文件名和路径
	 * @param  string  $class_name  类名
	 * @return string | bool  类对应的文件名，没有找到对应关系，则返回false
	 */
	public static function getClassFile($class_name=''){
		$class_name = trim($class_name);
		$relations = self::getRelationConfig();
		if(!empty($class_name) && !empty($relations) && is_array($relations)){
			if(array_key_exists($class_name, $relations)){
				$file = str_replace(array('/', '\\', '_'), DS, $relations[$class_name]);
				return $file;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}