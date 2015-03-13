<?php
/**
 * 计算字符串的字符个数
 * @param 	string	字符串参数
 * @return	string	字符串个数
 */
function Modifier_CountChar($str, $include_spaces=false){
	$i = 0;
	$count = 0;
	$len = strlen ($str);
	while ($i < $len) {
		$chr = ord ($str[$i]);
		$count++;
		$i++;
		if($i >= $len) break;
		if($chr & 0x80) {
			$chr <<= 1;
			while ($chr & 0x80) {
				$i++;
				$chr <<= 1;
			}
		}
	}
	return $count;
}
?>