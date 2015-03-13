<?php
/**
 * 正则替换
 * @param	string	$str		原始字符串
 * @param	mixed	$search		搜索的正则表达式
 * @param	string	$replace	替换的内容
 * 
 * @return	string	处理后的字符串
 */
function Modifier_RegxReplace($str, $search, $replace){
	if(is_array($search)) {
		foreach($search as $key => $regx){
			$search[$key] = _regex_replace_check($regx);
		}
	}else{
		$search = _regex_replace_check($search);
	}       
	
	return preg_replace($search, $replace, $str);
}

function _regex_replace_check($search){
    if (($pos = strpos($search,"\0")) !== false){
		$search = substr($search,0,$pos);
    }
    if (preg_match('!([a-zA-Z\s]+)$!s', $search, $match) && (strpos($match[1], 'e') !== false)) {
        $search = substr($search, 0, -strlen($match[1])) . preg_replace('![e\s]+!', '', $match[1]);
    }
    return $search;
}
?>