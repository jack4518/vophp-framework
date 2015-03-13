<?php
/**
 * 格式化日期输出(和PHP的date函数是一样的效果)
 * @param 	string	$string	日期时间戳
 * @param	strimg 	$format 日期的格式值
 * @return	string	格式化后的日期值
 */
function Modifier_DateFormat($string, $format = 'Y-m-d')
{
    $date = date($format, $string);
    return $date;
}

?>
