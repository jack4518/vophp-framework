<?php
/**
 * 去除空格
 * @param	string	$str	原始字符串
 * @return	string	去除空格后的字符串
 */
function Modifier_StripSpace($str){
	$str = trim($str);
	$str = preg_replace('/\s/', '', $str);	// 接着去掉两个空格以上的
	$str = preg_replace('/\s(?=\s)/', '', $str);	// 接着去掉两个空格以上的
	$str = preg_replace('/[\n\r\t]/', '', $str);	// 最后将非空格替换为一个空格
	return $str;
}
?>