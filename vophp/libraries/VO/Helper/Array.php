<?php
/**
 * 定义 VO_Helper_Array 数组助手类
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

class VO_Helper_Array extends ArrayObject{

	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 清除数组中的空元素
	 * @param array $array
	 * @param boolean $trim
	 */
	public static function removeEmpty(&$array, $trim = true){
		if(!is_array($array)){
			return false;
		}
	    foreach($array as $key => $value){
	        if (is_array($value)){
	            self::removeEmpty($array[$key]);
	        }else{
	            $value = trim($value);
	            if($value == ''){
	                unset($array[$key]);
	            }elseif($trim){
	                $array[$key] = $value;
	            }
	        }
	    }
	}
	
	/**
	 * 从一个二维数组中返回指定键的所有值
	 * @param mixed $array
	 * @param string $col
	 * @return array
	 */
	public static function getValuesByCol(&$array, $col){
		if(!is_array($array)){
			return $array;
		}
	    $ret = array();
	    foreach($array as $row){
	        if(isset($row[$col])){ 
	        	$ret[] = $row[$col];
	        }
	    }
	    return $ret;
	}
	
	/**
	 * 将一个二维数组转换为 hashmap
	 * 如果省略 $valueField 参数，则转换结果每一项为包含该项所有数据的数组。
	 * @param array $array
	 * @param string $keyField
	 * @param string $valueField
	 * @return array
	 */
	public static function toHashMap(&$array, $keyField, $valueField=null){
		if(!is_array($array)){
			return $array;
		}
	    $ret = array();
	    if($valueField){
	        foreach($array as $row){
	            $ret[$row[$keyField]] = $row[$valueField];
	        }
	    }else{
	        foreach($array as $row){
	            $ret[$row[$keyField]] = $row;
	        }
	    }
	    return $ret;
	}
	
	/**
	 * 将一个二维数组按照指定键值分组
	 * @param array $array
	 * @param string $col
	 * @return array
	 */
	public static function groupByKey(&$array, $col){
		if(!is_array($array)){
			return $array;
		}
	    $ret = array();
	    foreach($array as $row){
	        $key = $row[$col];
	        $ret[$key][] = $row;
	    }
	    return $ret;
	}
	
	/**
	 * 将一个平面的二维数组按照指定的字段转换为树状结构
	 * 当 $returnReferences 参数为 true 时，返回结果的 tree 字段为树，refs 字段则为节点引用。
	 * 利用返回的节点引用，可以很方便的获取包含以任意节点为根的子树。
	 * @param array $array 原始数据
	 * @param string $fid 节点ID字段名
	 * @param string $fparent 节点父ID字段名
	 * @param string $fchildrens 保存子节点的字段名
	 * @param boolean $returnReferences 是否在返回结果中包含节点引用
	 * @return array
	 */
	public static function arrayToTree($array, $fid, $fparent='parent_id', $fchildrens='childrens', $returnReferences=false){
	    $pkvRefs = array();
	    foreach($array as $offset => $row) {
	        $pkvRefs[$row[$fid]] =&$array[$offset];
	    }
	
	    $tree = array();
	    foreach($array as $offset => $row){
	        $parentId = $row[$fparent];
	        if((int)$parentId >0) {
	            if (!isset($pkvRefs[$parentId])){
	            	continue;
	           	}
	            $parent =& $pkvRefs[$parentId];
	            $parent[$fchildrens][] =& $array[$offset];
	        }else{
	            $tree[] =& $array[$offset];
	        }
	    }
	    if($returnReferences){
	        return array('tree' => $tree, 'refs' => $pkvRefs);
	    }else{
	        return $tree;
	    }
	}
	
	/**
	 * 将树转换为平面的数组
	 * @param array $node
	 * @param string $fchildrens
	 * @return array
	 */
	public static function treeToArray(& $node, $fchildrens = 'childrens'){
	    $ret = array();
	    if (isset($node[$fchildrens]) && is_array($node[$fchildrens])) {
	        foreach ($node[$fchildrens] as $child) {
	            $ret = array_merge($ret, self::treeToArray($child, $fchildrens));
	        }
	        unset($node[$fchildrens]);
	        $ret[] = $node;
	    } else {
	        $ret[] = $node;
	    }
	    return $ret;
	}

	/**
	 * 按数组值的中文拼音排序
	 * @param array $array	待排序的数组
	 * @return bool
	 */
	function asortByPinYing(&$array) {
		if(!isset($array) || !is_array($array)) {
			return false;
		}
		foreach($array as $k=>$v) {
			$array[$k] = iconv('UTF-8', 'GBK//IGNORE',$v);
		}
		asort($array);
		foreach($array as $k=>$v) {
			$array[$k] = iconv('GBK', 'UTF-8//IGNORE', $v);
		}
		return true;
	}	
	
	/**
	 * 根据指定的键值对数组排序
	 * @param array $array 要排序的数组
	 * @param string $keyname 键值名称
	 * @param int $sortDirection 排序方向
	 * @return array
	 */
	public static function sortByKey($array, $keyname, $sortDirection=SORT_ASC){
	    return self::sortByMultiKey($array, array($keyname => $sortDirection));
	}
	
	/**
	 * 将一个二维数组按照指定列进行排序，类似 SQL 语句中的 ORDER BY
	 * @param array $rowset
	 * @param array $args
	 */
	public static function sortByMultiKey($rowset, $args){
	    $sortArray = array();
	    $sortRule = '';
	    foreach($args as $sortField => $sortDir){
	        foreach($rowset as $offset => $row){
	            $sortArray[$sortField][$offset] = $row[$sortField];
	        }
	        $sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
	    }
	    if(empty($sortArray) || empty($sortRule)){ 
	    	return $rowset;
	    }
	    eval('array_multisort(' . $sortRule . '$rowset);');
	    return $rowset;
	}

	/**
	 * 从数组中随机取一个元素
	 * @param array $array
	 * @return mixed
	 */
	public static function getRandom($array)
	{
		if ( ! is_array($array)){
			return $array;
		}
		return $array[array_rand($array)];
	}

	/**
	 * 将一个多维数组转换成一维数组
	 * @param array $arr	多维数组
	 */
	public static function oneToTwo($arr){
		static $tmp=array();
		foreach($arr as $k => $v){
			if(is_array($v)){
				self::oneToTwo($v);
			}else{
				$tmp[]=$v;
			}
		}
		return $tmp;
	}
	
	/**
	 * 二维数组根据指定的键排序
	 * @param array $arr  待排序的数组
	 * @param string $orderKey    排序的键名
	 * @param string $type  排序类型(升序：ASC或者是降序:DESC)
	 * @return array   排序扣的二维数组
	 */
	public static function MultiArraySortByKey($arr,$orderKey,$orderType='ASC'){ 
		$keysvalue=array(); 
		foreach($arr as $k=>$val) { 
			$keysvalue[$k] =$val[$orderKey]; 
		
		} 
		asort($keysvalue);
		reset($keysvalue);
		 
		$newAarray = array();
		$keyss = array_keys($keysvalue); 
		if( strtoupper($orderType) == "DESC" ){ 	
			for($i=count($arr)-1; $i>=0; $i--) { 
				$newAarray[$keyss[$i]] = $arr[$keyss[$i]]; 
			}
		}else{
			for($i=0; $i<count($arr); $i++){ 
				$newAarray[$keyss[$i]] = $arr[$keyss[$i]]; 
			} 
		} 
		return $newAarray; 
	}

	/**
	 * 获取一个数组中所有元素的全部组合
	 * @param	array	数组
	 * $line	string	可以不用传
	 */
	public static function getArrayCombin($array='', $line=''){
		$return = array();
		if (count($array)==1) 
			return array($line.$array[0]);
		else{
			$func = __FUNCTION__;
			$cur = array_pop($array);
			$return = array_merge($return, $func($array, $line.$cur));
			$return = array_merge($return, $func($array, $line));
			$return = array_merge($return, $func(array($cur), $line));
		}
		return $return;
	}	
}	