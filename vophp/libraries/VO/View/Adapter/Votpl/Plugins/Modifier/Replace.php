<?php
/**
 * 去除左空格
 * @param	string	$str		原始字符串
 * @param	mixed	$search		搜索的内容
 * @param	string	$replace	替换的内容
 * 
 * @return	string	处理后的字符串
 */
function Modifier_Replace($str, $search, $replace){
	return str_replace($search, $replace, $str);
}
?>