<?php
/**
 * 定义  VO_String_Ubbcode ubbc编码操作类
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

class VO_String_Ubbcode{  
    var $nest;  // 递归深度，for debug
    //可处理标签及处理函数表  
    var $tags = array(
        'url' => '$this->url',  
        'email' => '$this->email',  
        'mail' => '$this->email',  // 为了容错，[mail]和[email]等效
        'img' => '$this->img',  
        'b' => '$this->simple',  
        'i' => '$this->simple',  
        'u' => '$this->simple',  
        'tt' => '$this->simple',  
        's' => '$this->simple',  
        'strike' => '$this->simple',  
        'h1' => '$this->simple',  
        'h2' => '$this->simple',  
        'h3' => '$this->simple',  
        'h4' => '$this->simple',  
        'h5' => '$this->simple',  
        'h6' => '$this->simple',  
        'sup' => '$this->simple',  
        'sub' => '$this->simple',  
        'em' => '$this->simple',  
        'strong' => '$this->simple',  
        'code' => '$this->simple',    
        'small' => '$this->simple',  
        'big' => '$this->simple',  
        'blink' => '$this->simple',
        'fly' => '$this->fly',
        'move' => '$this->move',
        'glow' => '$this->CSSStyle',
        'shadow' => '$this->CSSStyle',
        'blur' => '$this->CSSStyle',
        'wave' => '$this->CSSStyle',
        'sub' => '$this->simple',
        'sup' => '$this->simple',
        'size' => '$this->size',
        'face' => '$this->face',
        'font' => '$this->face',  // 为了容错，[font]和[face]等效
        'color' => '$this->color',
        'html' => '$this->html',
        'quote' => '$this->quote',
        'swf' => '$this->swf',
        'upload' => '$this->upload'
        );  
    function ubbcode(){  
      $this->$nest= 0;
      $this->$sLastModified= sprintf("%s", date("Y-m-j H:i", getlastmod()));
    }  
    /***********************************************************************
    *  对使用者输入的 E-Mail 作简单的检查，
    *  检查使用者的 E-Mail 字串是否有 @ 字元，
    *  在 @ 字元前有英文字母或数字，在之后有数节字串，
    *  最后的小数点后只能有二个或三个英文字母。
    *  super@mail.wilson.gs 就可以通过检查，super@mail.wilson 就不能通过检查
    ************************************************************************/
    function emailcheck($str) {
      if (eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $str)) 
        return true;
      else
        return false;  
    }
    /***********************************************************************
    *  对使用者输入的 URL 作简单的检查，
    *  目前只能简单判断，不能自动检查fpt，finger等
    ************************************************************************/
    function checkURL($str) {
      $bValidURL= true;
      if (eregi("([a-z0-9-]+([\.][a-z0-9\-]+)+)", $str, $er_arr)) {    
		  /*
		  printf ("0. %s 
		\n", $er_arr[0]);
		  printf ("1. %s 
		\n", $er_arr[1]);
		  printf ("2. %s 
		\n", $er_arr[2]);
		  printf ("3. %s 
		\n", $er_arr[3]);
		  printf ("4. %s 
		\n", $er_arr[4]);
		  */
      }else{
         $bValidURL= false;
      }
      return $bValidURL;
    }
    /***********************************************************************
    *  对使用者输入的 图片URL 作简单的检查，
    *  目前只能简单判断结尾是否为图片文件
    *  不支持由CGI动态生成的图片，比如计数器这类的
    ************************************************************************/
    function checkImgURL($str) {
      if ($this->checkURL($str)) {
        if(eregi("\.(jpeg|jpg|gif|bmp|png|pcx|tiff|tga|lwf)$", $str)) 
          return true;
        else
          return false;
      }
      else
        return false;
    }
    /***********************************************************************
    *  自动补全URL部分，主要是协议前缀，
    *  默认是htpp://，支持https://；ftp://；finger://；gopher://等
    *  函数并不对URL的合法性作检查
    ************************************************************************/
    function formatURL($str) {
      if (!eregi("^(ftp|http|https|mms|gopher|finger|bbs|telnet)\/\/|\\\\)", $str))
        $str= 'http://'.$str;
      return $str;
    }
    //对$str进行UBB编码解析  
    function parse($str){  
        $nest ++;
        $parse = ''.($str);  
        $ret = '';  
        while(true){  
            //查找[xx] 或者[xx=xx] , 但不包括[xx=]
            $eregi_ret=eregi("\[([a-z][a-z0-9]{0,7})(=[a-zA-Z0-9#.:/&@|\?,%=_\+\"\']+)?\]", $parse, $eregi_arr); 
            if(!$eregi_ret){  
                $ret .= $parse;  
                break; //如果没有，返回  
            }
			/*  for Debug
			            else 
			            {
			              printf ("$. %s
			", $eregi_ret);
			              printf ("0. %s
			", $eregi_arr[0]);
			              printf ("1. %s
			", $eregi_arr[1]);
			              printf ("2. %s
			", $eregi_arr[2]);
			              printf ("3. %s
			", $eregi_arr[3]);
			            }
			*/
            $pos = @strpos($parse, $eregi_arr[0]);  // 起始位置
            $tag_start= $eregi_arr[1];
            $tag= strtolower($eregi_arr[1]);
            $tag_param= $eregi_arr[2];
            $parse2 = substr($parse, 0, $pos);//标记之前
            $parse = substr($parse, $pos + $eregi_ret);//标记之后
            if(!isset($this->tags[$tag])){  
                $ret .= $parse2.'['.$tag_start.']';  
                continue;    //如果是不支持的标记  
            }  
            //查找对应的结束标记  
            $eregi_ret=eregi("\[(/".$tag.")\]", $parse, $eregi_arr);  
            if(!$eregi_ret){  
                $ret .= $parse2.'['.$tag_start.$tag_param.']';  
                continue;//没有对应该的结束标记  
            }  
            $pos= strpos($parse, $eregi_arr[0]);  
            $value= substr($parse, 0, $pos);   //起止标记之间的内容
            $tag_end= $eregi_arr[1];
            $parse= substr($parse, $pos + $eregi_ret);//结束标记之后的内容  
            // 允许嵌套标记，递归分析
            if (!(($tag == 'code') or ($tag=="url") or ($tag=="email") or ($tag=="img"))){
                $value= $this->parse($value);  
            }
            $ret.= $parse2;
            $parseFun= sprintf('$ret .= %s($tag_start, $tag_param, $tag_end, $value);', $this->tags[$tag]); 
            eval($parseFun);  
        }  
        $nest --;
        return $ret;  
    }
  /*****************************************************
    * 简单替换，类似变为
    * 标签内容不便，只是替代括号为<>
    *****************************************************/
    function simple($start, $para, $end, $value){
        if (strlen($para) > 0) 
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
        else
          return sprintf("<%s>%s<%s>", $start, $value, $end);
    }
    /*****************************************************
    * 如下认为合法可以没有“http://”；ftp一定要自己加“ftp://”
    * 93611
    * [/URL]
    * http://www.fogsun.com
    *****************************************************/
    function url($start, $para, $end, $value){  
        $sA= $value;
        $sURL= substr(trim($para), 1);
        if (strlen($sURL) > 0) 
        {
          if (strlen($value) == 0) 
            $sA= $sURL;
        }
        else 
        {
          $sURL= trim($value);
        }
        $sURL= $this->formatURL($sURL);
        if($this->checkURL($sURL)) 
          return "[url=%5C%22$sURL%5C%22]$sA";  
        else {
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
        }
    }  
    /*****************************************************
    * 如下认为合法可以没有“mailto:”头；
    * pazee
    * 
    * pazee@21cn.com
    *****************************************************/
    function email($start, $para, $end, $value){  
        $sA= $value;
        $sURL= substr(trim($para), 1);
        if (strlen($sURL) > 0) 
        {
          if (strlen($value) == 0) 
            $sA= $sURL;
        }
        else 
        {
          $sURL= trim($value);
        }
        //if (strtolower(substr($sURL, 0, 7)) != "mailto:")  
          $sURL= "mail.php?email=". $sURL;  
        if($this->emailcheck(substr($sURL, 15))) 
          return "[url=%5C%22" . $sURL . "%5C%22]" . $sA . "[/url]";  
        else
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
    }  
    /*****************************************************
    * 显示图片；如下用法认为合法
    * [img=www.21cn.com/title.jpg][/img]
    * 
    *****************************************************/
    function img($start, $para, $end, $value){  
        $sURL= substr(trim($para), 1);
        if (strlen($sURL) <= 0) 
          $sURL= trim($value);
        //$sURL= $this->formatURL($sURL);
        if ($this->checkImgURL($sURL))  
          return sprintf("[url=%5C%22%s%5C%22][/url]", $sURL,$sURL);  
        else
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
    }  
    /*****************************************************
    * 字符串从右向左循环移动 
    * 无参数
    * 等效与html的
    *****************************************************/
    function fly($start, $para, $end, $value){  
      if (strlen($para)>0) // 有参数
        return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
      else
        return ''.$value.'';  
    }  
    /*****************************************************
    * 字符串来回移动 
    * 无参数
    * 等效与html的
    *****************************************************/
    function move($start, $para, $end, $value) {
      if (strlen($para)>0) // 有参数
        return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
      else
        return ''.$value.'';  
    }  
    /*****************************************************
    * 字符晕光效果包括 glow、shadow和blur
    * 字符晕光效果[glow=a,b,c]或者[shadow=a,b,c]
    * 3个参数允许缺省
    * 实现文字阴影特效，
    * glow, shadow,blur 属性依次为颜色、宽度和边界大小
    * wave 属性依次为变形频率、宽度和边界大小
    *****************************************************/
    function CSSStyle(&$start, &$para, &$end, &$value){
        $rets= sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
        if (strlen($para)==0) 
        {
          $para="=,,";
        }
        if (eregi("^=([#]?[[:xdigit:]]{6}|[a-z0-9]*),([0-9]*),([0-9]*)", $para, $er_arr))
        {
          $color=  ($er_arr[1] != "") ? $er_arr[1] : red;   // Default Color
          $width=  ($er_arr[2] != "") ? $er_arr[2] : 400;   // Default Width
          $border= ($er_arr[3] != "") ? $er_arr[3] : 5;     // Default Border
          switch ($start) 
          {
            case "glow":
            case "shadow":
              $rets= sprintf("%s", $start, $color, $border, $width, $value);
              break;
            case "blur";
              $rets= sprintf("%s", $start, $border, $color, $width, $value);
              break;
            case "wave":
              $color=  ($er_arr[1] != "") ? $er_arr[1] : 4;   // Default Color
              $border= ($er_arr[3] != "") ? $er_arr[3] : 2;     // Default Border
              $rets= sprintf("%s", $start, $color, $border, $width, $value);
              break;
          }
        }
        return  $rets;
    }  
    /*****************************************************
    * 字体颜色 xxx 
    * n 可以是 #xxxxxx 或者 xxxxxx (6位16进制数)
    * red,greed,blue,black等颜色保留字也有效
    * 等效与html的xxx
    * [color]xxxx等效于 
    *****************************************************/
    function color($start, $para, $end, $value){
        $cl= strtolower(substr($para, 1));
        if ($cl == "")
          $cl= "red";
        if (eregi("(^[#]?[[:xdigit:]]{6})|red|green|blue|yellow|blue|white|gray|brown|silver|purple|orange" ,$cl)) 
          return sprintf("%s",$cl, $value);
        else
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
    }
    /*****************************************************
    * 字体大小 [size=n]xxx 1<= n <= 7；
    * 等效与html的[size=n]xxx
    *****************************************************/
    function size($start, $para, $end, $value){
        $size= substr($para, 1);
        if ($size >=1 && $size <=7 && (strlen($para) > 1))
          return sprintf("[size=%s]%s",$size, $value);
        else
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
    }  
    /*****************************************************
    * 字体名字 [face=n] n字体名称，不需要引号
    * 等效与html的xxx
    *****************************************************/
    function face($start, $para, $end, $value){
        $fn= substr($para, 1);
        if (!eregi("[[:punct:]]", $fn) && strlen($para) > 1) {
          switch (strtoupper($fn))
          {
            case "ST":
              $fn= "宋体";
              break;
            case "HT":
              $fn= "黑体";
              break;
            case "KT":
              $fn= "楷体_GB2312";
              break;
            case "FT":
              $fn= "仿宋_GB2312";
              break;
            case "YY":
              $fn= "幼圆";
              break;
            case "LS":
              $fn= "隶书";
              break;
            case "XST":
              $fn= "新宋体";
              break;
            default:
              $fn= substr($para, 1);
          }
          return sprintf("%s",$fn, $value);
        }
        else
          return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);
    }  
     /*****************************************************
    * 文件上传[upload]
    *****************************************************/
    function upload($start, $para, $end, $value){
        $fn= trim(substr($para, 1));
        if (!eregi("[[:punct:]]", $fn) && strlen($para) > 1) {
            if (eregi("jpg|jpeg|bmp|gif|png", $fn)) {
                if ($this->checkImgURL($value))  
                    return sprintf(" 此主题相关图片如下:
[url=%5C%22%s%5C%22][/url]
",$fn,$value,$value);
                else 
                    return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);    
            } elseif ($fn == "swf") {
                return sprintf(" 此主题相关Flash:
[url=%5C%22%s%5C%22]全屏欣赏[/url] (点右键->另存为可将动画下载)",$fn,$value,$value);
            } elseif (eregi("rar|zip|doc", $fn)) {
                return sprintf(" [url=%5C%22%s%5C%22]点击下载此主题相关附件[/url]
",$fn,$value);
            }
        } else 
            return sprintf("[%s%s]%s[%s]", $start, $para, $value, $end);            
    }     
    /*****************************************************
    * 调试代码标签[html]
    *****************************************************/
    function html($start, $para, $end, $value)
    {
      if (strlen($value) > 0) {
          $value = eregi_replace('
', "", $value);
          return sprintf("
%s
[Ctrl+A 全部选择提示：你可先修改部分代码，再按运行]
",$value);
      } else {
          return sprintf("[%s]%s[%s]", $start, $value, $end);
      }
    }
    /*****************************************************
    * 引用标签[quote]
    *****************************************************/
    function quote($start, $para, $end, $value)
    {
      if (strlen($value) > 0) {
          return sprintf(" 
以下为引用内容:
%s
",$value);
      } else {
          return sprintf("[%s]%s[%s]", $start, $value, $end);
      }
    }
    /*****************************************************
    * FLASH[swf]
    *****************************************************/
    function swf($start, $para, $end, $value)
    {
      if (strlen($value) > 0) {
          return sprintf ("
[url=%5C%22%s%5C%22]全屏欣赏[/url] (点右键->另存为可将动画下载)",$value,$value);
      } else {
          return sprintf("[%s]%s[%s]", $start, $value, $end);
      }
    }
}  
?>