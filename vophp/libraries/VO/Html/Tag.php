<?php
/**
 * 定义 VO_Html_Tag HTML页面元素类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-11-12
 **/
defined('VOPHP') or die('Restricted access');

class VO_Html_Tag{
	
	/**
	 * 生成HTML标签:<br />
	 * 
	 * @param	string	$num	生成<br />的个数
	 * @return	string	生成后的HTML标签
	 */
	public static function br($num =1){
		return str_repeat('<br />', $num);	
	}
	
	/**
	 * 生成HTML标签:&nbsp;
	 * 
	 * @param	string	$num	生成&nbsp;的个数
	 * @return	string	生成后的HTML标签
	 */
	public static function nbsp($num =1){
		return str_repeat('&nbsp;', $num);	
	}	
	
	/**
	 * 生成HTML标签:<hr />
	 * 
	 * @param	string	$width  <hr />标签的宽度
	 * * @param	string	$height <hr />标签的高度
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function hr($width, $height, $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'width'		=>	$width,
			'height'	=>	$height,
			'align'		=> 'left',
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('hr', '', $attributes, true);	
	}
	
	/**
	 * 生成图像标签<img src='图像地址' />
	 * 
	 * @param	string	$src 图像标签的图像路径
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function img($src, $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'border'	=>	0,
			'src'		=>	$src,
			'alt'		=>	'',
			'title'		=>	'',
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('img', '', $attributes, true);		
	}
	
	/**
	 * 生成超级链接标签<a href='链接地址' ></a>
	 * 
	 * @param	string	$href <a>标签的链接路径
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function a($href, $text, $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'href'		=>	$href,
			'title'		=>	$text,
		);
		$attributes = array_merge($attr, $attributes);
		$a = self::createTag('a', $text, $attributes, false);
		$a .= self::endTag('a');
		return $a;		
	}
	
	/**
	 * 生成<p>标签<p>文本内容</p>
	 * 
	 * @param	string	$text	<p>标签的内容
	 * @param	string	$class	<p>标签的样式
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function p($text, $class='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array();
		!empty($class) ? $attr['class'] = $class : '';
		$attributes = array_merge($attr, $attributes);
		$p = self::createTag('p', $text, $attributes, false);
		$p .= self::endTag('p');
		return $p;		
	}	
	
	/**
	 * 生成<h{n}>标签<h{n}></h{n}>
	 * 
	 * @param	string	$num 	<h{n}>标签的标号:如1为<h1>,2为<h2>
	 * @param	string	$text	<h{n}>标签的内容
	 * @param	string	$class	<h{n}>标签的样式
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function h($num, $text, $class='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		switch($num){
			case '6' : $tag = 'h6'; break;
			case '5' : $tag = 'h5'; break;
			case '4' : $tag = 'h4'; break;
			case '3' : $tag = 'h3'; break;
			case '2' : $tag = 'h2'; break;
			case '1' : $tag = 'h1'; break; 
			default: $tag = 'h1';
		}
		$attr = array();
		!empty($class) ? $attr['class'] = $class : '';
		$attributes = array_merge($attr, $attributes);
		$h = self::createTag($tag, $text, $attributes, false);
		$h .= self::endTag($tag);
		return $h;		
	}	
	
	/**
	 * 生成<div>标签<div></div>
	 * 
	 * @param	string	$text	<div>标签的内容
	 * @param	string	$id 	<div>标签的id属性
	 * @param	string	$class	<div>标签的class属性
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function div($text, $id='', $class='', $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array();
		!empty($id) ? $attr['id'] = $id : '';
		!empty($class) ? $attr['class'] = $class : '';
		$attributes = array_merge($attr, $attributes);
		$div = self::createTag('div', $text, $attributes, false);
		$div .= self::endTag('div');
		return $div;		
	}	
	
	/**
	 * 生成<link>标签<link href='链接地址' />
	 * 
	 * @param	string	$href link标签的链接地址
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function link($href, $attributes = array()){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'href'		=>	$href,
			'rel'		=>	'stylesheet',
			'type'		=>	'text/css',
			'title'		=>	'',
		);
		$attributes = array_merge($attr, $attributes);
		return self::createTag('link', '', $attributes, true);		
	}	
	
	/**
	 * 生成<script>标签<script src='' language='javascript' ></script>
	 * 
	 * @param	string	$src 脚本文件的路径
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function script( $src, $attributes = array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'language'	=>	'javascript',
			'type'		=>	'text/javascript',
			'src'		=>	$src,
		);
		$attributes = array_merge($attr, $attributes);
		$script = self::createTag('script', '', $attributes, false);
		$script .= self::endTag('script');
		return $script;		
	}
	
	/**
	 * 生成<iframe>标签<iframe src='' ></iframe>
	 * 
	 * @param	string	$src 	iframe标签的链接文件路径
	 * @param	int		$width	iframe的宽度
	 * @param	int		$height	iframe的高度
	 * @param	string	$name	iframe的名称
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function iframe( $src, $width=300, $height=300, $name='vo_iframe', $attributes = array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'src'		=>	$src,
			'width'		=>	$width,
			'height'	=>	$height,
			'title'		=> '',
			'align'		=>	'top',
			'scrolling'	=> 'auto',
			'frameborder'	=>	0,
			'marginwidth'	=> 0,
			'marginheight'	=>	0,
		);
		$attributes = array_merge($attr, $attributes);
		$iframe = self::createTag('iframe', '', $attributes, false);
		$iframe .= self::endTag('iframe');
		return $iframe;		
	}
	
	/**
	 * 生成<marquee>标签<marquee >滚动内容</marquee>
	 * 
	 * @param	string	$src 	marquee标签的链接文件路径
	 * @param	int		$width	marquee的宽度
	 * @param	int		$height	marquee的高度
	 * @param	string	$direction	marquee的滚动方向
	 * @param	string	$name	marquee的名称
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function marquee( $text, $width=300, $height=300, $direction='up', $name='vo_marquee', $attributes = array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'loop'		=>	-1,
			'width'		=>	$width,
			'height'	=>	$height,
			'direction'	=>	$direction,
			'title'		=> '',
			'align'		=>	'center',
			'behavior'	=> 'scroll',
			'onmouseover'	=>	'this.stop()', 
			'onmouseout'	=>	'this.start()',
			'scrollamount'	=>	2,
			'scrolldelay'	=>	2,
		);
		$attributes = array_merge($attr, $attributes);
		$marquee = self::createTag('marquee', $text, $attributes, false);
		$marquee .= self::endTag('marquee');
		return $marquee;		
	}

	/**
	 * 生成<object>标签:<object >flash内容/object>
	 * 
	 * @param	string	$src 	swf文件路径
	 * @param	int		$width	swf的宽度
	 * @param	int		$height	swf的高度
	 * @param	string	$name	swf的名称
	 * @param	array	$attributes  其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function swf( $src, $width=300, $height=300, $name='vo_swf', $attributes = array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'name'		=>	$name,
			'id'		=>	$name . '_id',
			'width'		=>	$width,
			'height'	=>	$height,
			'title'		=> '',
			'align'		=>	'center',
			'classid'	=>	'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000',
			'codebase'	=>	'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0',
		);
		$attributes = array_merge($attr, $attributes);
		$swf = self::createTag('object', '', $attributes, false);
		$swf .= '<param name="movie" value="' . $src . '" />';
  		$swf .= '<param name="quality" value="high" />';
  		$swf .=	'<param name="wmode" value="transparent">';
  		$swf .= '<embed src="' . $src . '" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="' . $width . '" height="' . $height . '"></embed>';
		$swf .= self::endTag('object');
		return $swf;		
	}	
	
	/**
	 * 生成邮件链接标签<a href="mailto:admin@domain.com">admin@domain.com</a>
	 * 
	 * @param	string	$email 邮件链接的email地址
	 * @return	string	生成后的HTML标签
	 */
	public static function mailto( $email ){
		$href = 'mailto:' . $email;
		$mailto = self::a($href, $email);
		return $mailto;		
	}		
	
	/**
	 * 生成<ul><li>标签
	 * @param	array 	$lis		UL内li标签的数据
	 * @param	array	$attributes	其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function ul( array $lis, $attributes=array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$ul = self::createTag('ul', '', $attributes, false);
		foreach($lis as $li){
			$ul .= '<li>' . $li . '</li>'; 
		}
		$ul .= self::endTag('ul');
		return $ul;	
	}
	
	/**
	 * 生成<ol><li>标签
	 * @param	array 	$lis		OL内li标签的数据
	 * @param	array	$attributes	其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function ol( array $lis, $attributes=array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$attr = array(
			'type'		=>	'1',
		);
		$attributes = array_merge($attr, $attributes);
		$ol = self::createTag('ol', '', $attributes, false);
		foreach($lis as $li){
			$ol .= '<li>' . $li . '</li>'; 
		}
		$ol .= self::endTag('ol');
		return $ol;	
	}	
	
	/**
	 * 生成<dl><dt><dd>标签
	 * @param	array 	$dts		dl内dt和dd标签的数据
	 * @param	array	$attributes	其它的属性(以键值形式实现，键为属性名称，值为属性值)
	 * @return	string	生成后的HTML标签
	 */
	public static function dl( array $dts, $attributes=array() ){
		if(! is_array($attributes)){
			$attributes = (array) $attributes;
		}
		$dl = self::createTag('ol', '', $attributes, false);
		foreach($dts as $key => $dt){
			if( is_array($dt) ){
				$dl .= '<dt>' . $key . '</dt>';
				foreach($dt as $k => $dd){
					$dl .= '<dd>' . $dd . '</dd>';
				}
			}else{
				$dl .= '<dt>' . $dt . '</dt>';
			}
		}
		$dl .= self::endTag('dl');
		return $dl;	
	}
	
	/**
	 * 生成HTML标签
	 * 
	 * @param string	$tag			HTML标签
	 * @param string	$text			内容
	 * @param array		$attributes		属性
	 * @param bool		$isAutoClose	是否为自闭标签
	 * @return string	生成后的HTML标签
	 */
	protected static function createTag($tag='', $text='', $attributes=array(), $isAutoClose=false){
		if(empty($tag)){
			return '';
		}
		$str = '<' . strtolower($tag);
		if(! empty($attributes)){
			foreach($attributes as $attr => $value){
				if( !is_string($attr) && !ctype_alpha($attr) ){
					continue;
				}
				$str .= ' ' . strtolower($attr) . '="' . $value . '"';
			}
		}
		$str .= $isAutoClose ? ' />' : '>';
		$str .= $text;
		return $str;
	}
	
	/**
	 * 生成HTML结束标签
	 * 
	 * @param	string	$tag	HTML标签名称
	 * @return	string			生成后的HTML关闭标签 如：</button>
	 */
	protected static function endTag($tag=''){
		if(empty($tag)){
			return '';
		}
		return '</' . strtolower($tag) . '>';
	} 
}