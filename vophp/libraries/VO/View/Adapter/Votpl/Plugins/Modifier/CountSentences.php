<?php
/**
 * 计算句子数
 * @param 	string	多个字符串参数
 * @return	string	 句子数
 */
function Modifier_CountSentences($str){
	 return preg_match_all('/[^\s]+[\.。](?!\w)/', $str, $match);
}
?>