<?php
/**
 * 定义  VO_String_Abstract 字符串接口
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

class VO_String_Abstract{
	/**
	 * UTF-8类型
	 * @var string UTF8
	 */
	const UTF8 = 'utf-8';
	
	/**
	 * UTF-16类型
	 * @var string UTF16
	 */
	const UTF16 = 'utf-16';
	
	/**
	 * GBK类型
	 * @var string GBK
	 */
	const GBK = 'GBK';
	
	/**
	 * GB2312类型
	 * @var string GB2312
	 */
	const GB2312 = 'GB2312';
	
	/**
	 * BIG5类型
	 * @var string BIG5
	 */
	const BIG5 = 'BIG5';
	
	/**
	 * 数字
	 * @var string NUMBER
	 */
	const NUMBER = 'number';
	
	/**
	 * 小写字母
	 * @var string ALPHA_LOWER
	 */
	const ALPHA_LOWER = 'alpha_lower';
	
	/**
	 * 大写字母
	 * @var string ALPHA_UPPER
	 */
	const ALPHA_UPPER = 'alpha_upper';
	
	/**
	 * 小写字母加数字
	 * @var string ALNUM_LOWER
	 */
	const ALNUM_LOWER = 'alnum_lower';
	
	/**
	 * 大写字母加数字
	 * @var string ALNUM_UPPER
	 */
	const ALNUM_UPPER = 'alnum_upper';
	
	/**
	 * 不区分大小写的字母和数字
	 * @var string ALNUM_NO_MATCH_CASE
	 */
	const ALNUM_NO_MATCH_CASE = 'alnum_no_match_case';
	
	/**
	 * 不区分大小写的字母、数字和以下字符(!@_#$%^*&+[])
	 * @var string COMPOSITE
	 */
	const COMPOSITE = 'composite';
	
	/**
	 * 转换为半角
	 * @var string TODBC
	 */
	const TODBC = 'todbc';
	
	/**
	 * 转换为全角
	 * @var string TOSBC
	 */
	const TOSBC = 'tosbc';
}