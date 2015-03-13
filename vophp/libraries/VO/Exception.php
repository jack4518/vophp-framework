<?php
/**
 * 定义 VO_Exception 异常处理类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-05
 **/

defined('VOPHP') or die('Restricted access');

class VO_Exception extends Exception{
	
	private $_previous = null;
	
	/**
	 * 
	 * @param string $msg 错误消息
	 * @param int $code 错误代码
	 * @param Exception $previous
	 * @return VO_Exception
	 */
	public function __construct($msg='', $code=0, Exception $previous=null){
		if(version_compare(PHP_VERSION, '5.3.0', '<')) {
            parent::__construct($msg, (int) $code);
            $this->_previous = $previous;
        }else{
            parent::__construct($msg, (int) $code, $previous);
        }
        //设置程序为正常终止，非系统错误导致的程序终止
		VO_Registry::set('is_crumble', false);
	}
	/**
	 * 调用未定义的方法里调用 
	 * @param string $method
	 * @param array $args
	 */
	public function __call($method, array $args){
        if('getprevious' == strtolower($method)){
            return $this->_getPrevious();
        }
        return null;
    }
    /**
     * 魔术方法
     */
	public function __toString(){
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            if (null !== ($e = $this->getPrevious())) {
                return $e->__toString() 
                       . "\n\nNext " 
                       . parent::__toString();
            }
        }
        return parent::__toString();
    }

    /**
     * 获取Exception
     * @return Exception|null
     */
    protected function _getPrevious(){
        return $this->_previous;
    }
}