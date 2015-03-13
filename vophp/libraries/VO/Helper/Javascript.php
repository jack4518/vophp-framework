<?php
/**
 * 定义  VO_Helper_Javascript JavaScript封装类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-25
 **/

defined('VOPHP') or die('Restricted access');

class VO_Helper_Javascript{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Helper_Javascript
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Helper_Javascript ){
			$instance = new self();
		}
		return $instance;
	}
    
    /**
     *　返回上一页
     * @param $step 返回的层数 默认为1
     */
    public static function back($step = -1){
        $msg = "history.go(".$step.");";
        self::_write($msg);
    }

    /**
     * 弹出警告的窗口
     * @param $msg 		警告信息
     * @param $url 		跳转到指定链接
     */
    public static function alert($msg, $url=''){
        $msg = "alert('".$msg."');";
        if($url){
        	$msg .= "window.location.href = '$url';";
        }
        self::_write($msg);
    }
    /**
     * 输出JavaScript代码
     * @param $msg
     */
    public static function _write($msg){
        echo "<script language='javascript'>";
        echo $msg;
        echo "</script>";
    }

    /**
     * 刷新当前页
     */
    public static function reload(){
        $msg = "location.reload();";
        self::_write($msg);
    }
    /**
     * 刷新弹出父页
     */
    public static function reloadOpener(){
        $msg = "if (opener)    opener.location.reload();";
        self::_write($msg);
    }

    /**
     * 跳转到url
     * @param $url 目标页
     */
    public static function location($url){
        $msg = "window.location.href = '$url';";
        self::_write($msg);
    }
    /**
     * 关闭窗口
     */
     public static function close(){
        $msg = "window.close()";
        self::_write($msg);
     }
    /**
     * 提交表单
     * @param $form 表单名
     */
    public static function submit($form){
        $msg = $form.".submit();";
        self::_write($msg);
    }
}
?>