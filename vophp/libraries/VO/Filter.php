<?php
/**
 * VOPHP框架过滤器
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-10-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Filter extends VO_Object{
	
	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
	 * 获取单一实例
	 * 
	 * @return VO_Filter
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Filter ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 递归对数据进行转义
	 * @param $value
	 */
	public static function stripSlashesRecursive( $value ){
		if(is_array($value)){
			foreach($value as $k => $v){
				$value[$k] = sef::stripSlashesRecursive($v);
			}
		}else{
			$value = stripslashes( $value );
		}
		//$value = is_array( $value ) ? array_map( array( $this, 'stripSlashesRecursive' ), $value ) : stripslashes( $value );
		return $value;
	}
	
	/**
	 * 过滤相应的字符
	 * @param string $source 待过滤的源字符串
	 * @param string $type　过滤类型
	 * @return mixed
	 */
	public function clean($source, $type='string'){
		switch( strtoupper($type) ){
			case 'INT' :
			case 'INTEGER' :
				// 只取整数
				preg_match('/(-?[0-9]+)/', (string) $source, $matches);
				$result = @(int) $matches[0];
				break;

			case 'FLOAT' :
			case 'DOUBLE' :
				// 取小数
				preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $source, $matches);
				$result = @ (float) $matches[0];
				break;

			case 'BOOL' :
			case 'BOOLEAN' :
				$result = (bool) $source;
				break;

			case 'WORD' :
				$result = (string) preg_replace( '/[^A-Z_]/i', '', $source );
				break;

			case 'ALNUM' :
				$result = (string) preg_replace( '/[^A-Z0-9]/i', '', $source );
				break;

			case 'CMD' :
				$result = (string) preg_replace( '/[^A-Z0-9_\.-]/i', '', $source );
				$result = ltrim($result, '.');
				break;

			case 'BASE64' :
				$result = (string) preg_replace( '/[^A-Z0-9\/+=]/i', '', $source );
				break;

			case 'STRING' :
				$result = (string) trim(strip_tags($source));
				break;

			case 'ARRAY' :
				$result = (array) $source;
				break;

			case 'PATH' :
				$pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
				preg_match($pattern, (string) $source, $matches);
				$result = @ (string) $matches[0];
				break;

			case 'USERNAME' :
				$result = (string) preg_replace( '/[\x00-\x1F\x7F<>"\'%&]/', '', $source );
				break;

			default :
				if (is_array($source)) {
					foreach ($source as $key => $value){
						if (is_string($value)) {
							$source[$key] = trim(strip_tags($value));
						}
					}
					$result = $source;
				} else {
					if (is_string($source) && !empty ($source)) {
						$result = trim(strip_tags($source));
					} else {
						$result = $source;
					}
				}
				break;
		}
		return $result;
	}

}