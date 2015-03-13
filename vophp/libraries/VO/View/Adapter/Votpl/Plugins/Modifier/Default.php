<?php
/**
 * 默认字符(当一个变量值为空是，则采用这个默认值)
 * @param 	string	待处理的字符串
 * @return	string	处理后的字符串
 */
function Modifier_Default($str, $default=''){
	if( !isset($str) || $str === '' ){
		return $default;
	}else{
		return $str;
	}
}
?>