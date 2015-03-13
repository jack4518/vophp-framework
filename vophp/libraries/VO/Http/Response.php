<?php
/**
 * 定义 VO_Http Http_Responce响应类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-07
 **/

defined('VOPHP') or die('Restricted access');

class VO_Http_Response{
	
	/**
	 * header信息保存器
	 * @var array
	 */
	private static $_headers = array();
	
	/**
	 * MIME类型
	 * @var string
	 */
	private static $_mime_type = 'text/html';

	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
	 * 获取单一实例
	 * @return VO_Http
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Http ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 是否有$_GET参数
	 * @return bool
	 */
	public static function get(){
		return $this->_headers;
	}
	
	/**
	 * 是否有$_POST参数
	 * @return bool
	 */
	public static function setContentType($type='text/html'){
		foreach( $this->_headers as $k => $value ){
			if(strcasecmp($value, 'Content-Type: ', 14) == true){
				$this->_headers[$k] = 'Content-Type:' . $type;
				return $this;
			}
		}
		return $this;
	}
	
	/**
	 * 添加头信息
	 * @param	mixed	$key	可以是字符串或者是数组，当为字符串时，如果$value为空，则认为$key为整条头信息值，否则$key为HTTP键，$value为HTTP值
	 							当$key为数组时，则表示，$key为一个头信息键值对
	 * @param	string	$value	HTTP值
	 * @return bool
	 */
	public static function addHeader($key, $value=''){
		if(empty($key)){
			return false;
		}else{
			if(is_array($key)){
				foreach($key as $k => $v){
					$this->_headers[] = $k . ': ' . $v;
				}
			}else{
				if(!empty($value)){
					$this->_headers[] = $key . ': ' . $value; 
				}else{
					$this->_headers[] = $key; 
				}
			}
			return $this->_headers;
		}
	}
}