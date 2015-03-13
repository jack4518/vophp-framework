<?php
/**
 * VOPHP 数据过滤类
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

class VO_Http_Filter extends VO_Object{
	
	/**
	 * 构造方法
	 */
	public function __construct(){}
	
	/**
	 * 递归对数据进行转义
	 * @param $value
	 */
	public static function addSlashesRecursive( $value ){
		if(is_array($value)){
			foreach($value as $k => $v){
				$value[$k] = sef::addSlashesRecursive($v);
			}
		}else{
			$value = addslashes( $value );
		}
		return $value;
	}
	
	/**
	 * 递归对数据进行反转义
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

		//$value = is_array( $value ) ? array_map( array( self, 'stripSlashesRecursive' ), $value ) : stripslashes( $value );
		return $value;
	}
	
	/**
	 * 返回整型数据
	 * @param string $string
	 * @return float
	 */
	public static function getInt($string=''){
		return self::clean($string, 'INT');
	}
	
	/**
	 * 返回实型或者是双精度型数据
	 * @param string $string
	 * @return int
	 */
	public static function getFloat($string=''){
		return self::clean($string, 'FLOAT');
	}
	
	/**
	 * 返回布尔型数据
	 * @param string $string
	 * @return bool
	 */
	public static function getBool($string=''){
		return self::clean($string, 'BOOL');
	}
	
	/**
	 * 返回英文字母和下划线组成的数据
	 * @param string $string
	 * @return string
	 */
	public static function getWord($string=''){
		return self::clean($string, 'WORD');
	}
	
	/**
	 * 返回英文字母、数字和下划线组成的数据
	 * @param string $string
	 * @return string
	 */
	public static function getAlnum($string=''){
		return self::clean($string, 'ALNUM');
	}
	
	/**
	 * 返回英文字母、数字、下划线、点号和横杠组成的数据，即A-Za-z0-9_.-
	 * @param string $string
	 * @return string
	 */
	public static function getCmd($string=''){
		return self::clean($string, 'CMD');
	}

	/**
	 * 返回去除左右空间和HTML标签后的数据
	 * @param string $string
	 * @return string
	 */
	public static function getString($string=''){
		return self::clean($string, 'STRING');
	}

	/**
	 * 返回一个标准的文件路径格式数据
	 * @param string $string
	 * @return string
	 */
	public static function getPath($string=''){
		return self::clean($string, 'PATH');
	}
	
	/**
	 * 返回英文字母、数字、下划线、斜杠、加号和等号组成的BASE64数据，即 A-Za-z0-9/+=
	 * @param string $string
	 * @return string
	 */
	public static function getBase64($string=''){
		return self::clean($string, 'BASE64');
	}
	
	/**
	 * 返回安全的HTML数据，即去除了HTML中的注释、JavaScript代码块、<script>标签和<iframe>标签、HTML标签中的style样式expression属性
	 * @param string $string
	 * @return string
	 */
	public static function getSafeHtml($string=''){
		return self::clean($string, 'SAFEHTML');
	}	
	
	/**
	 * 过滤方法
	 * @param $source
	 * @param $type
	 */
	public static function clean($source, $type='string'){
		if( $source === '' ){
			return '';
		}
		switch( strtoupper($type) ){
			case 'INT' :
			case 'INTEGER' :
				// 只取整数
				preg_match('/-?[0-9]+/', (string) $source, $matches);
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
				$result = (string) preg_replace( '/[^A-Za-z_]/', '', $source );
				break;

			case 'ALNUM' :
				$result = (string) preg_replace( '/[^A-Za-z0-9_]/', '', $source );
				break;

			case 'CMD' :
				$result = (string) preg_replace( '/[^A-Za-z0-9_\.-]/', '', $source );
				$result = ltrim($result, '.');
				break;

			case 'BASE64' :
				$result = (string) preg_replace( '/[^A-Za-z0-9\/+=]/', '', $source );
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
				
			case 'SAFEHTML' : 
				$result = preg_replace( "/(<script[^>]*>.*?<\\/script>)/si", '', $source );
				$result = preg_replace( "/(<iframe[^>]*>.*?<\\/iframe>)/si", '', $result );
				//$result = preg_replace( "/(<style[^>]*>.*?<\/style>)?/si", '', $result );
				$result = preg_replace( '/(<!--.+?-->)/', '', $result );
				$result = preg_replace( '/:(expression.*?\);)/i', '', $result );
				break;
				
			case 'RAW' : 
				return $source;
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

	/**
	 *清除文本中的所有HTML字符和Script代码
	 */
	public static function cleanText ( $text ){
		$text = preg_replace( "'<script[^>]*>.*?</script>'si", '', $text );
		$text = preg_replace( '/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text );
		$text = preg_replace( '/<!--.+?-->/', '', $text );
		$text = preg_replace( '/{.+?}/', '', $text );
		$text = preg_replace( '/&nbsp;/', ' ', $text );
		$text = preg_replace( '/&amp;/', ' ', $text );
		$text = preg_replace( '/&quot;/', ' ', $text );
		$text = strip_tags( $text );
		$text = htmlspecialchars( $text );
		return $text;
	}
	
	
}