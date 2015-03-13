<?php

/**
 * IDC多机房扩展
 * 
 * @category    Leb
 * @package     Leb_Idc_Info
 * @author      Liu Guangzhao <guangzhao@leju.com>
 * @license     http://slae.leju.com Slae Team Lincense
 * @copyright   © 1996 - 2014 新浪乐居
 * @version     $Id: idc.php 50110 2013-07-10 08:09:19Z Liu Guangzhao $
 */

class VO_Database_Idc extends VO_Object{
    /**
     * IDC信息
     * @access private
     * @var array
     */
    private static $idc = null;

    /**
     * 初始化
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return void
     */
    public function init()
    {
        if(!self::$idc)
        {
            $idc = require(_CONFIG_.'idc.php');
            foreach($idc as $id => $host)
            {
                foreach($host as $item)
                {
                    $baddr = ip2long($item[0]);
                    $eaddr = ip2long($item[1]);
                    $baddr > $eaddr && list($baddr, $eaddr) = array($eaddr, $baddr);
                    self::$idc[$id][] = array($baddr, $eaddr);
                }
            }
        }
    }

    /**
     * 根据IP地址获取IDC机房编号
     * @access public
     * @author Liu Guangzhao <guangzhao@leju.com>
     * @param  void
     * @return int   $idc
     */
    public function whereIs()
    {
        $addr = '';
        !empty($_SERVER['SERVER_ADDR']) && $addr = $_SERVER['SERVER_ADDR'];
        if(!$addr)
            $this->triggerError('Cannot get the server address', E_USER_ERROR);
        $addr = ip2long($addr);
        $idc = false;
        foreach(self::$idc as $id => $host)
        {
            foreach($host as $item)
            {
                if($addr >= $item[0] && $addr <= $item[1])
                {
                    $idc = $id;
                    break;
                }
            }
        }

        if(false === $idc)
            $this->triggerError('Cannot locate the IDC information', E_USER_ERROR);

        return $idc;
    }
}
