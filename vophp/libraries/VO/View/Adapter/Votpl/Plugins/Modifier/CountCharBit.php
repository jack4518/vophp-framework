<?php
/**
 * 计算字符串的字节个数
 * @param 	string	字符串参数
 * @return	string	字符串个数
 */
function Modifier_CountCharBit($str, $include_spaces=false){
	if ($include_spaces){
       return strlen($str);
	}else{
    	return preg_match_all("/[^\s]/", $str, $match);
	}
}
?>