<?php
/**
 * 定义VO_Date 日期类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-8-23
 **/

defined('VOPHP') or die('Restricted access');

class VO_Date {
	/**
	 * 将日期转换成时间戳
	 * @param string $date 格式为 "YYYY-mm-dd"
	 */
	public static function dateToTimestamp($date, $date_separator=''){
		if(!$date){
			return 0;
		}
		$timestamp = strtotime($date);
		if( $timestamp=== false || $timestamp===-1){
			$keys = array('year', 'month', 'day', 'hour', 'minute', 'second');
			$date_separator = preg_quote($date_separator);
			$separator = '/[' . '\s-:\/\\' . $date_separator . ']/';
			$match = preg_split($separator, $date);
			
			$fill = array_fill(count($match), 6-count($match), 0);
			if(is_array($fill)){
				$match = array_merge($match, $fill);
			}
			
			foreach ($match as $k => $v) {
				if( empty($v) || (int)$v <0 ){
					$match[$k] = 0;	
				}else{
					$match[$k] = (int)$match[$k];
				}
			}
			$date_arr = array_combine($keys, $match);
			$date_arr = self::_checkDate($date_arr);
			$timestamp = mktime($date_arr['hour'], $date_arr['minute'], $date_arr['second'], $date_arr['month'], $date_arr['day'], $date_arr['year']);
		}
		return $timestamp;
	}
	
	/**
	 * 在某个日期上加N年
	 * @param string $date   日期字符串
	 * @param int $year_num　　加上或者减去的年数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addYear($date, $year_num=0, $return_timestamp=false){
		return self::_addDate($date, $year_num, 'YEAR', $return_timestamp);
	}

	/**
	 * 在某个日期上加N月
	 * @param string $date   日期字符串
	 * @param int $month_num　　加上或者减去的月数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addMonth($date, $month_num=0, $return_timestamp=false){
		return self::_addDate($date, $month_num, 'MONTH', $return_timestamp);
	}

	/**
	 * 在某个日期上加N天
	 * @param string $date   日期字符串
	 * @param int $day_num　　加上或者减去的天数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addDay($date, $day_num=0, $return_timestamp=false){
		return self::_addDate($date, $day_num, 'DAY', $return_timestamp);
	}
	
	/**
	 * 在某个日期上加N小时
	 * @param string $date   日期字符串
	 * @param int $hour_num　　加上或者减去的小时数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addHour($date, $hour_num=0, $return_timestamp=false){
		return self::_addDate($date, $hour_num, 'HOUR', $return_timestamp);
	}

	/**
	 * 在某个日期上加N分钟
	 * @param string $date   日期字符串
	 * @param int $minute_num　　加上或者减去的分钟数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addMinute($date, $minute_num=0, $return_timestamp=false){
		return self::_addDate($date, $minute_num, 'MINUTE', $return_timestamp);
	}

	/**
	 * 在某个日期上加N秒
	 * @param string $date   日期字符串
	 * @param int $second_num　　加上或者减去的秒数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addSeconds($date, $second_num=0, $return_timestamp=false){
		return self::_addDate($date, $second_num, 'SECONDS', $return_timestamp);
	}

	/**
	 * 在某个日期上加N周
	 * @param string $date   日期字符串
	 * @param int $day_num　　加上或者减去的周数
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	public static function addWeek($date, $week_num=0, $return_timestamp=false){
		return self::_addDate($date, $week_num, 'WEEK', $return_timestamp);
	}	
	
	
	/**
	 * 比较两个日期的大小
	 * @param string $date  第一个日期
	 * @param string $date_secound   第二个日期
	 * @return number 如果第一个日期大于第二个日期返回1,相等返回0,小于返回-1
	 */
	public static function diffDate($date, $date_secound){
		$time = self::dateToTimestamp($date);
		$time_secound = self::dateToTimestamp($date_secound);
		if($time > $time_secound){
			return 1;
		}else if($time == $time_secound){
			return 0;
		}else{
			return -1;
		}
	}
	/**
	 * 获取当前的微秒数
	 * @param	无
	 * @return int  当前微秒数
	 */
	public static function getCurrentMicroSecond(){
		list($usec, $sec) = explode(" ", microtime());
    	return ((float)$usec + (float)$sec);
	}	
	
	
	/**
	 * 增加或者减少时间的通用方法
	 * @param string $date   日期字符串
	 * @param int $day_num　　加上或者减去的秒数
	 * @param string $type    操作的类型
	 * @param bool $return_timestamp　　返回的是时间戳还是日期字符串
	 * @return number|string　　返回操作后的日期或者时间戳
	 */
	private static function _addDate($date, $day_num=0, $type='DAY', $return_timestamp=false){
		$seconds = 0;
		$timestamp = VO_Date::dateToTimestamp($date);
		$type = strtoupper($type);
		if($type == 'YEAR'){
			$date = date('Y-m-d-H-i-s', $timestamp);
			$arr = explode('-', $date);
			$arr[0] += (int)$day_num;
			$arr = implode('-', $arr);
			
			$time = VO_Date::dateToTimestamp($arr);
		}else if($type == 'MONTH'){
			$date = date('Y-m-d-H-i-s', $timestamp);
			$arr = explode('-', $date);
			$arr[1] += (int)$day_num;
			if($arr[1]>12){
				$arr[0] += 1;
				$arr[1] = $arr[1] - 12;
			}
			$arr = implode('-', $arr);
			$time = VO_Date::dateToTimestamp($arr);
		}else{
			switch($type){
				case 'WEEK' : $seconds = (int)$day_num * 7 * (3600 * 24);
							  break;
				case 'DAY' : $seconds = (int)$day_num * (3600 * 24);
							  break;
				case 'HOUR' : $seconds = (int)$day_num * 3600;
							  break;
				case 'MINUTE' : $seconds = (int)$day_num * 60;
							  break;
				case 'SECONDS' : $seconds = (int)$day_num;
							  break;
			}
			$time = $timestamp + $seconds;
		}
		if($return_timestamp === true){
			return $time;
		}else{
			return date('Y-m-d H:i:s', $time);
		}
	}
	
	private static function _checkDate(&$date_arr){
		if( strlen($date_arr['year']) < 3 && strlen($date_arr['day']) >2  ){
			$temp_year = $date_arr['year'];
			$date_arr['year'] = $date_arr['day'];
			$date_arr['day'] = $date_arr['month'];
			$date_arr['month'] = $temp_year;
		}
		if( !checkdate($date_arr['month'], $date_arr['day'], $date_arr['year']) ){
			$this->triggerError('日期格式不合法');
			exit;
		}
		
		if($date_arr['hour'] > 23){
			$this->triggerError('日期中的小时数不合法');
			exit;
		}
		
		if($date_arr['minute'] > 59){
			$this->triggerError('日期中的分钟数不合法');
			exit;
		}
		
		if($date_arr['second'] > 59){
			$this->triggerError('日期中的秒数不合法');
			exit;
		}
		return $date_arr;
	}
}