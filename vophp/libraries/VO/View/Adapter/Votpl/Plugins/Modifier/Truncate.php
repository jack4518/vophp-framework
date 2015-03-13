<?php
/**
 * 截取字符串
 * @param 	string	$str	需要处理的字符串
 * @param	int	    $len	截取长度
 * @param 	string	$etc	字符串截取后的后缀
 * @return string  截取后的字符串
 */
function Modifier_Truncate($str, $length=10, $etc='...'){
	$ret = '';
	$bit_len = 0;
	if(strlen($str) <= $length ){ 
   		return $str; 
    }else{
    	$i = 0;
    	while($i < $length){ 
   			$ret = substr($str,$i,1); 
   			if( ord($ret) >= 224 ){ 
				$ret = substr($str,$i,3); 
			    $i = $i + 3; 
   			}elseif( ord($ret) >=192 ){ 
			    $ret = substr($str,$i,2); 
			    $i = $i + 2; 
		    }else{ 
				$i = $i + 1; 
		    } 
	   	    $StringLast[] = $ret; 
	   }
   	   $StringLast = implode("",$StringLast); 
	   $StringLast .= $etc; 
	   return $StringLast; 
	}
}
?>