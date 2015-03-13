<?php
/**
 * 计算字符串的单词数(以空格为分隔符分隔)
 * @param	string	$str	原始字符串
 * @return	int		单词数
 */
function Modifier_CountWord($str){
	$arr = preg_split('/\s+/', $str);
    $count = preg_grep('/[a-zA-Z0-9\\x80-\\xff]/', $arr);
    return count($count);
}
?>