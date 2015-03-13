<?php
/**
 * 去除转义的字符
 * @param	string	$str	原始字符串
 * @return	string	转换后的字符串
 */
function Modifier_StripSlashes($str){
	return stripslashes($str);
}
?>