<?php
/**
 * 首字母大写
 * @param	string	$str	原始字符串
 * @return	string	处理后的字符串
 */
function Modifier_Ucfirst($str){
	$str = ucwords($str);
	return $str;
}
?>