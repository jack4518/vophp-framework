<?php
/**
 * ���� VO_Class�� �Զ�������
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
	 * ��ʵ���洢��
	 * @var VO_Loader
	 */
	static $instance = null;
		
	/**
	 * ���캯��
	 */
	public function __construct(){
		
	}
	
	/**
	 * ��ȡ��һʵ��
	 * @return VO_Loader
	 */
	public static function getInstance(){
		if( !self::$instance instanceof VO_Loader ){
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * ��ȡ����ļ��Ķ�Ӧ��ϵ
	 * @return  array  �������ļ��Ķ�Ӧ��ϵ
	 */
	public static function getRelationConfig(){
		return array(
		    'VO_String'    => 'VO/String.php',
		    'VO_Pinyin'    => 'VO/String/Pinyin.php',
		    'VO_Utils_Hash'=> 'VO/Utils/Hash/Hash.php',
		);
	}

	/**
	 * ����������ȡ���Ӧ���ļ�����·��
	 * @param  string  $class_name  ����
	 * @return string | bool  ���Ӧ���ļ�����û���ҵ���Ӧ��ϵ���򷵻�false
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