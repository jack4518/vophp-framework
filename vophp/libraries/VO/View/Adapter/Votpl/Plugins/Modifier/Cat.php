<?php
/**
 * 连接字符串
 * @param string	多个字符串参数
 * @return	string	连接后的字符串
 */
function Modifier_Cat(){
	$params = func_get_args();
	$params = implode('', $params);
	return $params;
}
?>