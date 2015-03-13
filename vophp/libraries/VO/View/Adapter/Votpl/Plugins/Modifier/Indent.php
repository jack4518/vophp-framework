<?php
/**
 * 去除左空格
 * @param	string	$str	原始字符串
 * @return	string	去除左空格后的字符串
 */
function Modifier_Indent($str, $len=4, $pefix=" "){
	return preg_replace('!^!m', str_repeat($pefix, $len), $str);
}
?>