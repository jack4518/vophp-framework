<?php
/**
 * VO_String 字符串类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-28
 **/

defined('VOPHP') or die('Restricted access');

VO_Loader::import('String.Abstract');

class VO_String extends VO_String_Abstract{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return VO_String
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_String ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 去除空格
	 * @param	string	$str	原始字符串
	 * @return	string	去除空格后的字符串
	 */
	public static function stripSpace($str){
		$str = trim($str);// 首先去掉头尾空格
		$str = preg_replace('/\s(?=\s)/', '', $str);	// 接着去掉两个空格以上的
		$str = preg_replace('/[\n\r\t]/', ' ', $str);	// 最后将非空格替换为一个空格
		return $str;	
	}
	
	/**
	 * 计算字符数(不是字节数)
	 * @param	  string   $str 字符串
	 * @return	  int　　	字符数
	 */
	public static function countStr($str){
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
	
	/**
	* 产生一个指定长度的随机字符串,并返回给用户
	* @param int $len 产生字符串的位数
	* @param string $mod  随机字符串的模式
	* @param string $randstr  随机字符串种子
	* 
	* @return string  返回生成的字符串
	*/
	public static function getRandStr($len=6, $mod=self::ALPHA_LOWER, $randstr='') {
		if($randstr != ""){
			$chars = $randstr;
		}
		switch($mod){
			case self::NUMBER :
				$chars = '1234567890';
				break;
			case self::ALPHA_LOWER :
				$chars = 'abdefghijkmnopqrstuvwxyz';
				break;
			case self::ALPHA_UPPER :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case self::ALNUM_LOWER :
				$chars = 'abdefghijkmnopqrstuvwxyz1234567890';
				break;
			case self::ALNUM_UPPER :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
				break;
			case self::ALNUM_NO_MATCH_CASE :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabdefghijkmnopqrstuvwxyz1234567890';
				break;
			case self::COMPOSITE :
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabdefghijkmnopqrstuvwxyz1234567890!@_#$%^*&+[]';
				break;
		}
		mt_srand((double)microtime()*1000000*getmypid()); // 构建随机数种子
		$password = "";
		while( strlen($password)<$len ){
			$password .= substr($chars,(mt_rand()%strlen($chars)),1);
		}
		return $password;
	}
	
	/**
	 * 以字节来截取字符串函数
	 * @param	string	$string	待截取的字符串
	 * @param	string  $length	要截取的长度
	 * @param   boolean $append 是否需要增加结尾字符
	 * @param   string  $appendstr 结尾字符
	 * @return  string          截取后的字符串
	 */
	public static function cutStrByBit($str, $length=20, $append=false, $appendstr='...'){
		//我是rqwer中国人
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
		   if($append){
		   	    $StringLast .= $appendstr; 
		   }
		   return $StringLast; 
		}
	}
	
	/**
	 * 以字符的个数截取字符串(两个半角英文字母等于一个汉字)
	 * @param	string	$string	待截取的字符串
	 * @param	string  $length	要截取的长度
	 * @param   boolean $append 是否需要增加结尾字符
	 * @param   string  $appendstr 结尾字符
	 * @return  string          截取后的字符串
	 */
	public static function cutStr($str, $start=0, $length=null, $append=false, $appendstr='...'){
		$returnstr = '';
		$i = 0;
		$n = 0;
		$str_length = strlen($str);  //字符串的字节数
		$total_length = self::countStr($str); //字符串的总字符数
		$start = ($start>=0) ? $start :  (int)($total_length + $start);
		if(!$length){
			$length = $total_length;
		}else if( $length<0 ){
			$length = (int)($total_length + $length - $start);
		}
		//echo $length;
		while( ($n<$total_length) && ($i<=$str_length) ){
			$ret = substr($str,$i,1);
			$ascnum=ord($ret);  //得到字符串中第$i位字符的ascii码
			if ($ascnum >= 224){ //如果ASCII位高与224，
				$returnstr .= substr($str,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
				$i=$i+3; //实际Byte计为3
				$n++; //字串长度计1
			} elseif ($ascnum >= 192){ //如果ASCII位高与192，
				$returnstr .= substr($str,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
				$i=$i+2; //实际Byte计为2
				$n++; //字串长度计1
			} elseif ( ($ascnum >= 65) && ($ascnum <= 90) ){ //如果是大写字母，
				$returnstr .= substr($str,$i,1);
				$i=$i+1; //实际的Byte数仍计1个
				$n++; //但考虑整体美观，大写字母计成一个高位字符
			} else { //其他情况下，包括小写字母和半角标点符号，
				$returnstr .= substr($str,$i,1);
				$i=$i+1; //实际的Byte数计1个
				$n=$n+0.5; //小写字母和半角标点等与半个高位字符宽...
			}
			
			if($start >= $n ){
				$returnstr = '';
			}
			
			//将两个英文计算为一个字节,一个汉字计算为一个字节
			if( $n >= $length ){
				break;
			}
			
			/*
			//将一个汉字计算为一个字节
			$result_length = self::countStr($returnstr);
			if( $result_length >= $length ){
				break;
			}
			*/
		}
		if( ($str_length>$length) && $append && $appendstr ){
			$returnstr .= $appendstr;//超过长度时在尾处加上省略号
		}
		return $returnstr;
	}
	
	/**
	 * 以字符的个数截取字符串(两个半角英文字母等于一个汉字)
	 * @param	string	$string	待截取的字符串
	 * @param	string  $length	要截取的长度
	 * @param   boolean $append 是否需要增加结尾字符
	 * @param   string  $appendstr 结尾字符
	 * @return  string          截取后的字符串
	 */
	public static function cutStrForHtml($str, $length=null, $append=false, $appendstr='...'){ 
		$return = '';
		$num = 0;
		$start_tags = array();
		$end_tags = array();
		
	    if(substr($str) < $length || empty($str)){
	    	return $str;
	    }
	    for($i=0; $i<substr($str); $i++){
	    	if($str{i} <> '<'){
	    		$num++;
	    	}else{
	    		
	    	}
	    }
	}

	/**
	 * 驼峰格式字符串转成下划线格式字符串
	 * @param	string	$data	待转换的字符串
	 * @return	string	返回转换后的中文字符串
	 */
	public static function humpToUnderLineMode($str){
		return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $str));
	}

	/**
	 * 下划线格式字符串转成驼峰格式字符串
	 * @param	string	$data	待转换的字符串
	 * @return	string	返回转换后的字符串
	 */
	public static function underLineToHumpMode($str){
		if(empty($str)){
			return $str;
		}else{
			$temp = explode('_', $str);
			$temp = array_map('ucfirst', $temp);
			$temp[0] = strtolower($temp[0]);
			return implode('', $temp);
		}
	}	
	
	/**
	 *	人民币数字转中文币制
	 * @param	string	$data	待转换的数字
	 * @return	string	返回转换后的中文数字
	 */
	public static function numberTocncap($data){
	   $capnum = array( "零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖" );
	   $capdigit = array( "", "拾", "佰", "仟" );
	   $subdata = explode( ".", $data );
	   $yuan = $subdata[0];
	   $j = 0;
	   $nonzero = 0;
	   for( $i=0; $i<strlen($subdata[0]); $i++ ){
		  if( $i==0 ){ //确定个位 
			 if($subdata[1]){ 
				$cncap = (substr($subdata[0],-1,1)!=0) ? "元" : "元零";
			 }else{
				$cncap = "元";
			 }
		  }
		  if( $i==4 ){ //确定万位
		  		$j = 0;
		  		$nonzero = 0;
		  		$cncap = "万" . $cncap; 
		  } 
		  if($i==8){ //确定亿位
		  		$j = 0;
		  		$nonzero = 0;
		  		$cncap = "亿" . $cncap; 
		  }
		  $numb = substr($yuan,-1,1); //截取尾数
		  $cncap = ($numb) ? $capnum[$numb].$capdigit[$j].$cncap : (($nonzero)?"零".$cncap:$cncap);
		  $nonzero = ($numb) ? 1 : $nonzero;
		  $yuan = substr($yuan,0,strlen($yuan)-1); //截去尾数      
		  $j++;
	   }	
	   if($subdata[1]){
		 $chiao=(substr($subdata[1],0,1))?$capnum[substr($subdata[1],0,1)]."角":"零";
		 $cent=(substr($subdata[1],1,1))?$capnum[substr($subdata[1],1,1)]."分":"零分";
	   }
	   $cncap .= $chiao.$cent."整";
	   $cncap = preg_replace("/(零)+/","\\1",$cncap); //合并连续“零”
	   return $cncap;
	}
	
	/**
	 * 字符串半角和全角间相互转换
	 * @param string $str  待转换的字符串
	 * @param int    $type  TODBC:转换为半角；TOSBC，转换为全角
	 * @return string  返回转换后的字符串
	 */
	public static function convertStrType($str, $type) {
		$dbc = array( 
			'０' , '１' , '２' , '３' , '４' ,  
			'５' , '６' , '７' , '８' , '９' , 
			'Ａ' , 'Ｂ' , 'Ｃ' , 'Ｄ' , 'Ｅ' ,  
			'Ｆ' , 'Ｇ' , 'Ｈ' , 'Ｉ' , 'Ｊ' , 
			'Ｋ' , 'Ｌ' , 'Ｍ' , 'Ｎ' , 'Ｏ' ,  
			'Ｐ' , 'Ｑ' , 'Ｒ' , 'Ｓ' , 'Ｔ' , 
			'Ｕ' , 'Ｖ' , 'Ｗ' , 'Ｘ' , 'Ｙ' ,  
			'Ｚ' , 'ａ' , 'ｂ' , 'ｃ' , 'ｄ' , 
			'ｅ' , 'ｆ' , 'ｇ' , 'ｈ' , 'ｉ' ,  
			'ｊ' , 'ｋ' , 'ｌ' , 'ｍ' , 'ｎ' , 
			'ｏ' , 'ｐ' , 'ｑ' , 'ｒ' , 'ｓ' ,  
			'ｔ' , 'ｕ' , 'ｖ' , 'ｗ' , 'ｘ' , 
			'ｙ' , 'ｚ' , '－' , '　'  , '：' ,
			'．' , '，' , '／' , '％' , '＃' ,
			'！' , '＠' , '＆' , '（' , '）' ,
			'＜' , '＞' , '＂' , '＇' , '？' ,
			'［' , '］' , '｛' , '｝' , '＼' ,
			'｜' , '＋' , '＝' , '＿' , '＾' ,
			'￥' , '￣' , '｀'
		);
  		$sbc = array( //半角
			'0', '1', '2', '3', '4',  
			'5', '6', '7', '8', '9', 
			'A', 'B', 'C', 'D', 'E',  
			'F', 'G', 'H', 'I', 'J', 
			'K', 'L', 'M', 'N', 'O',  
			'P', 'Q', 'R', 'S', 'T', 
			'U', 'V', 'W', 'X', 'Y',  
			'Z', 'a', 'b', 'c', 'd', 
			'e', 'f', 'g', 'h', 'i',  
			'j', 'k', 'l', 'm', 'n', 
			'o', 'p', 'q', 'r', 's',  
			't', 'u', 'v', 'w', 'x', 
			'y', 'z', '-', ' ', ':',
			'.', ',', '/', '%', '#',
			'!', '@', '&', '(', ')',
			'<', '>', '"', '\'','?',
			'[', ']', '{', '}', '\\',
			'|', '+', '=', '_', '^',
			'¥','~', '`'
		);
		if($type == self::TODBC){
			return str_replace( $sbc, $dbc, $str );  //半角到全角
		}elseif($type == self::TOSBC){
			return str_replace( $dbc, $sbc, $str );  //全角到半角
		}else{
			return false;
		}
	}
	
	/**
	 * 将字节转换成Kb或者Mb
	 * @param   int 	$num    字节大小
	 * @param	int  	$round  小数点的位数,默认值为-1,表示没有小数位
	 * @return  string          转换后的字符串
	 */
	public static function toBitSize($num, $round=-1){
		if(!preg_match("/^[0-9]+$/", $num)){
			return 0;
		}
		$type = array( "B", "KB", "MB", "GB", "TB", "B" );
		$j = 0;
		while( $num >= 1024 ) {
			if( $j >= 5 ){
				return $num.$type[$j];
			}
			if($round != -1){
				$num = round($num / 1024,$round);
			}else{
				$num = $num / 1024;
			}
			$j++;
		}
		return $num.$type[$j];
	}
	
	/**
	 * 将B、Kb、MB、GB或者TB　转换成字节数字
	 * @param   int 	$str    字节字符(如：500KB)
	 * @return  string          转换后的字节数
	 */
	public static function toIntSize($str){
		$str = trim($str);
		if(!preg_match("/^([0-9]+)([KkMmGgTt]?)(B|b)?$/i", $str, $match)){
			return 0;
		}
		$size = intval( $match[1] );
		$unit = strtoupper($match[2]);
		switch($unit){
			case 'T' : $size *= 1024;
			case 'G' : $size *= 1024;
			case 'M' : $size *= 1024;
			case 'K' : $size *= 1024;	
		}
		return $size;
	}	
	  
    /**
	 *	可逆的字符串加密函数
	 * @param	int 	$txtStream 	待加密的字符串内容
	 * @param	int  	$password 	加密密码
	 * @return  string 			加密后的字符串
	 */
    public static function enCrypt($txtStream,$password){
		//密锁串，不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,
		$lockstream = 'st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdef+IJK6/7nopqr89LMmGH012345uv';
        //随机找一个数字，并从密锁串中找到一个密锁值
        $lockLen = strlen($lockstream);
        $lockCount = rand(0,$lockLen-1);
        $randomLock = $lockstream[$lockCount];
        //结合随机密锁值生成MD5后的密码
        $password = md5($password.$randomLock);
        //开始对字符串加密
        $txtStream = base64_encode($txtStream);
        $tmpStream = '';
        $i=0;$j=0;$k = 0;
        for ($i=0; $i<strlen($txtStream); $i++) {
            $k = ($k == strlen($password)) ? 0 : $k;
            $j = (strpos($lockstream,$txtStream[$i])+$lockCount+ord($password[$k]))%($lockLen);
            $tmpStream .= $lockstream[$j];
            $k++;
        }
        return $tmpStream.$randomLock;
    }
	
	/**
	 *	可逆的字符串解密函数
	 * @param	int 	$txtStream 	待加密的字符串内容
	 * @param	int  	$password 	解密密码
	 * @return  string 		解密后的字符串
	 */
	public static function deCrypt($txtStream,$password){
		//密锁串，不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,
		$lockstream = 'st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdef+IJK6/7nopqr89LMmGH012345uv';
        $lockLen = strlen($lockstream);
        //获得字符串长度
        $txtLen = strlen($txtStream);
        //截取随机密锁值
        $randomLock = $txtStream[$txtLen - 1];
        //获得随机密码值的位置
        $lockCount = strpos($lockstream,$randomLock);
        //结合随机密锁值生成MD5后的密码
        $password = md5($password.$randomLock);
        //开始对字符串解密
        $txtStream = substr($txtStream,0,$txtLen-1);
        $tmpStream = '';
        $i=0;$j=0;$k = 0;
        for($i=0; $i<strlen($txtStream); $i++){
            $k = ($k == strlen($password)) ? 0 : $k;
            $j = strpos($lockstream,$txtStream[$i]) - $lockCount - ord($password[$k]);
            while($j < 0){
                $j = $j + ($lockLen);
            }
            $tmpStream .= $lockstream[$j];
            $k++;
        }
        return base64_decode($tmpStream);
    }
	
	/*
	 * 将指定的Ascii码转换成对应的字符
	 * @param 	 int 	$num  由getAsciiCode转换得到的相应数字字符串
	 * @return	 string 	     对应的Ascii码字符
	 */
	public static function getAsciiStr($num){
		$utf = '';
		preg_match_all( "/([0-9]{2,5})/", $num,$a);
	    $a = $a[0];
	    foreach ($a as $dec){
	        if ($dec < 128){
	            $utf .= chr($dec);
	        }else if ($dec < 2048){
	            $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
	            $utf .= chr(128 + ($dec % 64));
	        }else{
	            $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096)); 
	            $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64)); 
	            $utf .= chr(128 + ($dec % 64)); 
	        } 
	    } 
	    return $utf; 
	}

	/**
	 *	将字符串转换成对应的ASCII码数字字符串
	 * @param	  string   $str 字符串
	 * @return    string　   对应的ASCII码数字字符串
	 */
	public static function getAsciiCode($str){
		$scill = '';
		$len = strlen($str); 
	    $a = 0; 
	    while ($a < $len){
	        $ud = 0; 
	        if (ord($str{$a}) >=0 && ord($str{$a})<=127){
	            $ud = ord($str{$a}); 
	            $a += 1; 
	        }else if (ord($str{$a}) >=192 && ord($str{$a})<=223){
	            $ud = (ord($str{$a})-192)*64 + (ord($str{$a+1})-128); 
	            $a += 2; 
	        }else if (ord($str{$a}) >=224 && ord($str{$a})<=239){
	            $ud = (ord($str{$a})-224)*4096 + (ord($str{$a+1})-128)*64 + (ord($str{$a+2})-128); 
	            $a += 3; 
	        }else if (ord($str{$a}) >=240 && ord($str{$a})<=247){
	            $ud = (ord($str{$a})-240)*262144 + (ord($str{$a+1})-128)*4096 + (ord($str{$a+2})-128)*64 + (ord($str{$a+3})-128); 
	            $a += 4; 
	        }else if (ord($str{$a}) >=248 && ord($str{$a})<=251){
	            $ud = (ord($str{$a})-248)*16777216 + (ord($str{$a+1})-128)*262144 + (ord($str{$a+2})-128)*4096 + (ord($str{$a+3})-128)*64 + (ord($str{$a+4})-128); 
	            $a += 5; 
	        }else if (ord($str{$a}) >=252 && ord($str{$a})<=253){
	            $ud = (ord($str{$a})-252)*1073741824 + (ord($str{$a+1})-128)*16777216 + (ord($str{$a+2})-128)*262144 + (ord($str{$a+3})-128)*4096 + (ord($str{$a+4})-128)*64 + (ord($str{$a+5})-128); 
	            $a += 6; 
	        }else if (ord($str{$a}) >=254 && ord($str{$a})<=255){//error
	            $ud = false; 
	        } 
	        $scill .= "&#" . $ud . ";";
	    } 
	    return $scill; 
		
	}
	
	/**  
	* 模拟JavaScript中的escape函数功能  
	*   
	* @param   string   $str       源字符串  
	* @param   string   $charset   源字符串编码 'utf-8' or 'gb2312' or 'big5'  
	* @return string    返回编码后的字符串
	*/  
	public static function escape($str='', $charset=self::UTF8){
		if(!is_string($str)){
			return $str;
		}
		if($charset==strtolower("utf-8") || $charset==strtolower("utf8")){
			$str = self::convertEncoding($str,$charset,self::GB2312);
		}
		$sublen = strlen($str);    
		$reString = "";    
		for($i=0;$i<$sublen;$i++){    
			if(ord($str[$i])>=127){   
				$tmpString=bin2hex(iconv(self::GBK, "ucs-2", substr($str,$i,2)));    //此处GBK为目标代码的编码格式，请实际情况修改    
				if (!eregi("WIN",PHP_OS)){    
					$tmpString=substr($tmpString,2,2).substr($tmpString,0,2);    
				}    
				$reString.="%u".$tmpString;    
				$i++;    
			} else {    
				$reString.="%".dechex(ord($str[$i]));    
			}    
		}    
		return $reString;   
	}
	
	/**  
	* js 中的unescape功能  
	*   
	* @param string $str       源字符串  
	* @return string    返回解码后的字符串
	*/  
	public static function unescape($str='', $enCoding=self::UTF8){
		if(!is_string($str)){
			return $str;
		}
		$str = rawurldecode($str);
		preg_match_all("/%u.{4}|&#x.{4};|&#[0-9]+;|&#[0-9]+?|.+/U",$str,$r); 
		$ar = $r[0]; 
		foreach($ar as $k => $v) { 
			if(substr($v,0,2) == "%u") 
				$ar[$k] = iconv("UCS-2", $enCoding, pack("H4",substr($v,-4))); 
			elseif(substr($v,0,3) == "&#x") 
				$ar[$k] = iconv("UCS-2", $enCoding, pack("H4",substr($v,3,-1))); 
			elseif(substr($v,0,2) == "&#") { 
				$ar[$k] = iconv("UCS-2", $enCoding, pack("n",preg_replace("/[^0-9]/","",$v))); 
			} 
		}
		$str = self::safeEncoding( join("", $ar), $enCoding );
		return $str; 
	}
	
	/**
	 * 自动识别字符编码并转换
	 * @param string $string  字符串
	 * @param string $outEncoding  需要输出的编码
	 */
	public static function safeEncoding($string, $outEncoding = self::UTF8)
	{     
	    $encoding = "UTF-8";     
	    for($i=0;$i<strlen($string);$i++){     
	        if(ord($string{$i})<128){    
	            continue;     
	        }
	        if((ord($string{$i})&224)==224){     
	            //第一个字节判断通过     
	            $char = $string{++$i};     
	            if((ord($char)&128)==128){     
	                //第二个字节判断通过     
	                $char = $string{++$i};     
	                if((ord($char)&128)==128){     
	                    $encoding = "UTF-8";     
	                    break;     
	                }     
	            }     
	        }     
	        if((ord($string{$i})&192)==192){     
	            //第一个字节判断通过     
	            $char = $string{++$i};     
	            if((ord($char)&128)==128){     
	                // 第二个字节判断通过     
	                $encoding = "GB2312";     
	                break;     
	            }     
	        }     
		}
	    if(strtoupper($encoding) == strtoupper($outEncoding)) {    
	        return $string;     
	    }else{
	        return iconv($encoding,$outEncoding,$string);
		}   
	}  

	/**  
	* 转换字符串编码
	*   
	* @param string $source   源字符串  
	* @param string $source_lang   源字符串编码 　'utf-8' or 'gb2312' or 'big5'  
	* @param string $target_lang   目标字符串编码 'utf-8' or 'gb2312' or 'big5'  	
	* @return string  转换后的字符串
	*/
	public static function convertEncoding($source, $source_lang="", $target_lang='utf-8'){   
		if(function_exists("mb_detect_encoding") && $source_lang==""){
			$source_lang = mb_detect_encoding($source);
		}
		if($source_lang != ''){   
			$source_lang = str_replace(   
				array('gbk','utf8','big-5'),   
				array('gb2312','utf-8','big5'),   
				strtolower($source_lang)  
			);   
		}   
		if($target_lang != ''){
			$target_lang = str_replace(   
				array('gbk','utf8','big-5'),   
				array('gb2312','utf-8','big5'),   
				strtolower($target_lang) 
			);   
		}   
		if( ($source_lang == $target_lang) || ($source == '') ){   
			return $source;   
		}       
		if(function_exists('iconv')){   
			return iconv($source_lang,$target_lang,$source);   
		}   
		if(function_exists('mb_convert_encoding')){   
			return mb_convert_encoding($source,$target_lang,$source_lang);   
		}
	}
	
	/**
	 * 对字符串进行加密处理
	 * @param string $password  加密字符串
	 * @param string $salt 加密因子
	 * @return string 加密后的字符串
	 */
	public static function encodePassword($password, $salt=''){
		if(empty($password)){
			return '';
		}
		$salt = empty($salt) ? '' : $salt;
		$return = crc32($password);
		$return = md5($return . $salt);
		$return = substr($return, 16) . $salt . substr($return, 0, 17);
		$return = md5($return);
		return $return;
	}

	/**
	 * 将Md5字符串转换成两个64位整数
	 * @params	string	$md5	md5后的字符串
	 * @return	array	包含两个整数的数组
	 */
	public static function md5To64Int($md5){
		$intStrLen = strlen($md5);
	    $arrMd5Val = array();
	    for ($i = 0; $i < $intStrLen; ++$i){
	        $arrMd5Val[$i] = substr($md5, $i, 1);
	    }
	    $intStrHalfLen = $intStrLen / 2;
	    $arrRes = array();
	    $arrRes[0] = intval(0);
	    $arrRes[1] = intval(0);
	    for ($i = 0; $i < $intStrHalfLen; ++$i){
		    if (is_numeric($arrMd5Val[$i])){
		        $first = intval(ord($arrMd5Val[$i]) - ord('0'));
		    }else{
		        $first = intval(ord($arrMd5Val[$i]) - ord('a') + 10);
		    }

		    if (is_numeric($arrMd5Val[$intStrHalfLen + $i])){
		        $second = intval(ord($arrMd5Val[$intStrHalfLen + $i]) - ord('0'));
		    }else{
		        $second = intval(ord($arrMd5Val[$intStrHalfLen + $i]) - ord('a') + 10);
		    }
    
	        $arrRes[0] = intval((($arrRes[0]<<4)|$first));
	        $arrRes[1] = intval((($arrRes[1]<<4)|$second));
	    }
	    return $arrRes;
	}
}
?>