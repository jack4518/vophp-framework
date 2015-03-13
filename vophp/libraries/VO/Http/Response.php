<?php
/**
 * ���� VO_Http Http_Responce��Ӧ��
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
	 * header��Ϣ������
	 * @var array
	 */
	private static $_headers = array();
	
	/**
	 * MIME����
	 * @var string
	 */
	private static $_mime_type = 'text/html';

	/**
	 * ���췽��
	 */
	public function __construct(){}
	
	/**
	 * ��ȡ��һʵ��
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
	 * �Ƿ���$_GET����
	 * @return bool
	 */
	public static function get(){
		return $this->_headers;
	}
	
	/**
	 * �Ƿ���$_POST����
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
	 * ���ͷ��Ϣ
	 * @param	mixed	$key	�������ַ������������飬��Ϊ�ַ���ʱ�����$valueΪ�գ�����Ϊ$keyΪ����ͷ��Ϣֵ������$keyΪHTTP����$valueΪHTTPֵ
	 							��$keyΪ����ʱ�����ʾ��$keyΪһ��ͷ��Ϣ��ֵ��
	 * @param	string	$value	HTTPֵ
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