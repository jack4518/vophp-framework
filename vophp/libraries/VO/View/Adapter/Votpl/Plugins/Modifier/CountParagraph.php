<?php
/**
 * 计算段落
 * @param 	string	多个字符串参数
 * @return	string	 段落数
 */
function Modifier_Paragraph($str){
	 return count(preg_split('/[\r\n]+/', $str));
}
?>