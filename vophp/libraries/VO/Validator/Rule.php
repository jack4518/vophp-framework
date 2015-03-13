<?php
/**
 * 定义  VO_Validator_Rule 验证规则类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Validator_Rule{	
	/**
     * 本地化变量
     * @var array
     */
    protected $_locale;
    
    /**
     * 构造函数
     */
	function __construct(){
		$this->_locale = localeconv();
	}
    
	/**
	 * 获取单一的VO_Validator_Rule实例
	 * @return VO_Validator_Rule
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Validator_Rule ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 是否为字符串并且不为空
	 * @param mixed $value
	 * @return Boolean
	 */
	function stringNotEmpty($value){
		return !empty($value) && is_string($value);
	}
	
	/**
	 * 是否为数组且不为空
	 * @param mixed $value
	 * @return Boolean
	 */
	function arrayNotEmpty($value){
		return !empty($value) && is_array($value);
	}
		
	/**
     * 使用正则表达式进行验证
     * @param mixed $value
     * @param string $regxp
     * @return Boolean
     */
	function regex($value, $regxp)
    {
        return preg_match($regxp, $value) > 0;
    }
    
    /**
     * 等于指定值
     */
    function equal($value, $test)
    {
        return $value == $test && strlen($value) == strlen($test);
    }

    /**
     * 不等于指定值
     */
    function notEqual($value, $test)
    {
        return $value != $test || strlen($value) != strlen($test);
    }

    /**
     * 是否与指定值完全一致
     */
    function same($value, $test)
    {
        return $value === $test;
    }

    /**
     * 是否与指定值不完全一致
     */
    function notSame($value, $test)
    {
        return $value !== $test;
    }

    /**
     * 验证字符串长度
     */
    function strlen($value, $len)
    {
        return strlen($value) == (int)$len;
    }

    /**
     * 最小长度
     */
    function minLength($value, $len)
    {
        return strlen($value) >= $len;
    }

    /**
     * 最大长度
     */
    function maxLength($value, $len)
    {
        return strlen($value) <= $len;
    }
    
    /**
     * 长度是否在指定长度之间
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return boolean
     */
    function strlenBetween($value, $min, $max){
    	return $this->minLength($value, $min) && $this->maxLength($value, $max);
    }    

    /**
     * 最小值
     */
    function min($value, $min)
    {
        return $value >= $min;
    }

    /**
     * 最大值
     */
    function max($value, $max)
    {
        return $value <= $max;
    }

    /**
     * 在两个值之间
     *
     * @param mixed $value
     * @param int|float $min
     * @param int|float $max
     * @param boolean $inclusive 是否包含 min/max 在内
     *
     * @return boolean
     */
    function between($value, $min, $max, $inclusive = true)
    {
        if ($inclusive)
        {
            return $value >= $min && $value <= $max;
        }
        else
        {
            return $value > $min && $value < $max;
        }
    }

    /**
     * >指定值
     */
    function greaterThan($value, $test)
    {
        return $value > $test;
    }

    /**
     * >=指定值
     */
    function greaterOrEqual($value, $test)
    {
        return $value >= $test;
    }

    /**
     * <指定值
     */
    function lessThan($value, $test)
    {
        return $value < $test;
    }

    /**
     * <=指定值
     */
    function lessOrEqual($value, $test)
    {
        return $value <= $test;
    }

    /**
     * 不为 null
     */
    function notNull($value)
    {
        return !is_null($value);
    }

    /**
     * 不为空
     */
    function notEmpty($value,$skipZeroString=false)
    {
    	if ($skipZeroString && $value === '0') return true ;
    	return !empty($value);
    }

    /**
     * 是否是特定类型
     */
    function isType($value, $type)
    {
        return gettype($value) == $type;
    }

    /**
     * 是否是字母加数字
     */
    function isAlnum($value){return ctype_alnum($value);}

    /**
     * 是否是字母
     */
    function isAlpha($value) {   return ctype_alpha($value); }

    /**
     * 是否是字母、数字加下划线
     */
    function isAlnumu($value){return preg_match('/[^a-zA-Z0-9_]/', $value) == 0;}
    
    /**
     * 是否是中文 字母
     */
    function isChinese($value){return preg_match( "/^[\x80-\xff]+/",$value,$match) && ($match[0] == $value);}

    /**
     * 是否为字母、数字加下划线和中文
     */
    function isChineseAlnumu($value){return preg_match( "/^[a-zA-Z0-9_\x80-\xff]+/",$value,$match) && ($match[0] == $value);}
    
    /**
     * 是否是控制字符
     */
    function isCntrl($value){return ctype_cntrl($value);}

    /**
     * 是否是数字
     */
    function isDigits($value){return ctype_digit($value);}

    /**
     * 是否是可见的字符
     */
    function isGraph($value){return ctype_graph($value);}

    /**
     * 是否是全小写
     */
    function isLower($value){return ctype_lower($value);}

    /**
     * 是否是可打印的字符
     */
    function isPrint($value){return ctype_print($value);}

    /**
     * 是否是标点符号
     */
    function isPunct($value){return ctype_punct($value);}

    /**
     * 是否是空白字符
     */
    function isWhitespace($value){return ctype_space($value);}

    /**
     * 是否是全大写
     */
    function isUpper($value){return ctype_upper($value);}

    /**
     * 是否是十六进制数
     */
    function isXdigits($value){return ctype_xdigit($value);}

    /**
     * 是否是 ASCII 字符
     */
    function isAscii($value){return preg_match('/[^\x20-\x7f]/', $value) == 0;}

    /**
     * 是否是是否是电子邮件地址
     */
    function isEmail($value){return preg_match('/^[a-z0-9]+[._\-\+]*@([a-z0-9]+[-a-z0-9]*\.)+[a-z0-9]+$/i', $value);}

    /**
     * 是否是日期（yyyy/mm/dd、yyyy-mm-dd）
     */
    function isDate($value)
    {
        if (strpos($value, '-') !== false) 
        	$p = '-';
        elseif (strpos($value, '/') !== false) 
        	$p = '\/';
        else 
        	return false;

        if (preg_match('/^\d{4}' . $p . '\d{1,2}' . $p . '\d{1,2}$/', $value))
        {
            $arr = explode($p, $value);
            if (count($arr) < 3) return false;

            list($year, $month, $day) = $arr;
            return checkdate($month, $day, $year);
        }
        else
            return false ;
    }

    /**
     * 是否是时间（hh:mm:ss）
     */
    function isTime($value)
    {
        $parts = explode(':', $value);$count = count($parts);
        if ($count != 2 || $count != 3) return false;
        if ($count == 2) $parts[2] = '00';
        $test = @strtotime($parts[0] . ':' . $parts[1] . ':' . $parts[2]);
        if ($test === - 1 || $test === false || date('H:i:s') != $value)
            return false;
        return true;
    }

    /**
     * 是否是 日期 + 时间
     */
    function isDatetime($value)
    {
        $test = @strtotime($value);
        if ($test === false || $test === - 1)
            return false;
        return true;
    }

    /**
     * 是否是 整数
     */
    function isInt($value)
    {
        $value = str_replace($this->_locale['decimal_point'], '.', $value);
        $value = str_replace($this->_locale['thousands_sep'], '', $value);
        return strval(intval($value)) == $value;
    }
	
    /**
     * 是否是 浮点数
     */
    function isFloat($value)
    {
        $value = str_replace($this->_locale['decimal_point'], '.', $value);
        $value = str_replace($this->_locale['thousands_sep'], '', $value);
		
        return strval(floatval($value)) == $value ;
    }

    /**
     * 是否是 IPv4 地址（格式为 a.b.c.h）
     */
    function isIpv4($value){$test = @ip2long($value);return $test !== - 1 && $test !== false;}

    // 是否是八进制数值
    function isOctal($value){return preg_match('/0[0-7]+/', $value);}

    /**
     * 是否是二进制数值
     */
    function isBinary($value){return preg_match('/[01]+/', $value);}

    /**
     * 是否是 Internet 域名
     */
    function isDomain($value){return preg_match('/[a-z0-9\.]+/i', $value);}
    
    /**
     * 验证是否 不是被注入攻击的值
     * the hacker defense for php 
     */
	function notHackerDefense($value){
		$notAllowedExp = array(	
			'/<[^>]*script.*\"?[^>]*>/','/<[^>]*style.*\"?[^>]*>/',
			'/<[^>]*object.*\"?[^>]*>/','/<[^>]*iframe.*\"?[^>]*>/',
			'/<[^>]*applet.*\"?[^>]*>/','/<[^>]*window.*\"?[^>]*>/',
			'/<[^>]*docuemnt.*\"?[^>]*>/','/<[^>]*cookie.*\"?[^>]*>/',
			'/<[^>]*meta.*\"?[^>]*>/','/<[^>]*alert.*\"?[^>]*>/',
			'/<[^>]*form.*\"?[^>]*>/','/<[^>]*php.*\"?[^>]*>/','/<[^>]*img.*\"?[^>]*>/'
		);//not allowed in the system
		foreach ($notAllowedExp as $exp){ //checking there's no matches
			if ( preg_match($exp, $value) ) return false;
		}
		return true ;
	}
	
	/**
	 * 验证中国大陆的手机号
	 * @param string $mobile 手机号码
	 * @return bool
	 */
	public function isChineseMobile($mobile){
		$mobile = trim($mobile);
		if(strlen($mobile)  != 11){
			return false;
		}
		$first_three = substr($mobile, 0, 3);
		if(preg_match("/^13[0-9]{9}$|15[0-35-9]{1}[0-9]{8}$|18[05-9]{1}[0-9]{8}$/", $mobile)){
			return true;
		}else{
			return false;		 	
		}
	}
	
	/**
	 * 是否为合法的身份证号码
	 * @param string $idcard  15位或者18位的身份证号码
	 */
	public function isIdCard($idcard) {
		if(strlen($idcard) == 15 || strlen($idcard) == 18){
		   if(strlen($idcard) == 15){
		    $idcard = $this->idCard15To18($idcard);
		   }
		   if($this->check18IdCard($idcard)){
		    return true;
		   }else{
		    return false;
		   }
		}else{
		   return false;
		}
	}
	
	/**
	 * 计算身份证号码中的检校码
	 * @param string $idcard_base  身份证号码的前十七位
	 * @return string 检校码
	 */
	private function idCardVerifyNumber($idcard_base){
		if (strlen($idcard_base) != 17){
			return false;
		}
		$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); //debug 加权因子
		$verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); //debug 校验码对应值
		$checksum = 0;
		for ($i = 0; $i < strlen($idcard_base); $i++){
			$checksum += substr($idcard_base, $i, 1) * $factor[$i];
		}
		$mod = $checksum % 11;
		$verify_number = $verify_number_list[$mod];
		return $verify_number;
	}
	
	/**
	 * 将15位身份证升级到18位
	 * @param string $idcard 15位身份证号码
	 * @return string 18位身份证号码
	 */
	private function idCard15To18($idcard){
		if (strlen($idcard) != 15){
			return false;
		}else{// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
			if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){
				$idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
			}else{
				$idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
			}
		}
		$idcard = $idcard . $this->idCardVerifyNumber($idcard);
		return $idcard;
				
	}
	
	/**
	 * 18位身份证校验码有效性检查
	 * @param string $idcard
	 * @return bool
	 */
	private function check18IdCard($idcard){
		if (strlen($idcard) != 18){ 
				return false; 
		}
		$idcard_base = substr($idcard, 0, 17);
		if ($this->idCardVerifyNumber($idcard_base) != strtoupper(substr($idcard, 17, 1))){
			return false;
		}else{
			return true;
		}
	}
	
}