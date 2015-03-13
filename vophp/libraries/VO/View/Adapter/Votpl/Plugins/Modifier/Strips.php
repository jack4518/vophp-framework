<?php
/**
 * 去除字符串的标签或者自义字符
 * @param 	string	$str	待处理的字符串
 * @param 	string	$tags	需要去除的文字或者标签
 * @return	string	处理后的字符串
 */
function Modifier_Strips($str, $tags=''){
	if($tags){
		return strip_tags($str, $tags);
	}else{
  		return strip_tags($str);
	}
}
?>