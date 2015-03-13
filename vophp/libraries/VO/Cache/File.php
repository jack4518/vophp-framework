<?php
/**
 * 定义  VO_Cache_File  文件缓存类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-30
 **/

defined('VOPHP') or die('Restricted access');

require_once(VO_LIB_DIR .  DS . 'Cache' . DS . 'Abstract.php');
class VO_Cache_File extends VO_Cache_Abstract{
	/**
	 * 缓存文件存放目录
	 * @var string
	 */
	protected $cache_dir = 'cache';
	
	/**
	 * 缓存文件存放目录扩展名
	 * @var string
	 */
	protected $file_ext = 'php';
	
	/**
	 * 缓存过期时间(默认为5分钟)
	 * @var int
	 */
	public $life_time = 300;
	
	/**
	 * 是否压缩缓存数据
	 * @var boolean
	 */
	protected $compressed = false;
	
	/**
	 * 是否使用持久连接
	 * @var boolean
	 */
	protected $pconnect = false;
	
	/**
	 * 构造函数
	 * @param array $option 缓存参数
	 */
	public function __construct(array $options = null){
		if( C('cache.cache_dir') != '' ){
			$cache_dir = str_replace( array('/', '\\'), array(DS), C('cache.cache_dir'));
			if(substr($cache_dir, 0, 1) == DS){
				$cache_dir = substr($cache_dir, 1);
			}
			if(substr($cache_dir, -1, 1) == DS){
				$cache_dir = substr($cache_dir, -1, 1);
			}
			$this->cache_dir = SITE_DIR . DS . $cache_dir;
		}
		if(!empty($options)){
	        foreach($options as $k => $v){
				if(isset($this->$k)){
					$this->$k = $v;
				}
			}
		}
		if (!is_dir($this->cache_dir)) {
			@mkdir($this->cache_dir,0777);
		}
		//parent::__construct($options);
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Cache_File
	 */
	public static function getInstance(array $option = null){
		static $instance = null;
		if( !$instance instanceof VO_Cache_File ){
			$instance = new self($option);
		}
		return $instance;
	}
	
	/**
	 * 判断缓存文件是否存在
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isExist($filename){
		if (file_exists($this->_getFileName($filename))) {
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 判断缓存文件是否有效
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isValid($filename){
		if($this->isExist($filename) || ( (time()- $this->getModifyTime($filename)) < $this->lifttime)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 写入缓存
	 * @param string $name  缓存名称
	 * @param mixed  $data   缓存值时间
	 * @param array  $lifttime  缓存参数
	 * @param boolean $isover  是否覆盖缓存
	 * @return boolean  成功返回True 失败返回False
	 */
	public function set($name, $data, $lifttime=0, $isover=true){
		$filename = $this->_getFileName($name);
		if(!$isover && $this->isValid($filename)){
			return false;
		}
		$lifttime = $lifttime ? $lifttime : $this->life_time;
		$data = serialize($data);
		if(function_exists("file_put_contents")){
			@file_put_contents($filename,$data);
		}else{
			VO_Filesystem_File::write($filename,$data);
		}
		chmod($filename,0777);
		touch($filename, time() + $lifttime);
	}
	
	/**
	 * 读取缓存,读取失败或缓存失效返回false
	 * @param string $filename  缓存名称
	 */
	public function get($name){
		$content = '';
		$filename = $this->_getFileName($name);
		if( !file_exists($filename) ){
			return false;
		}
		if(@filemtime($filename) >= time()) {
			if(function_exists("file_get_contents")){
				$content = file_get_contents($filename);
			}else{
				$content = implode('',$filename);
			}
			return unserialize($content);
		}else{
			@unlink($filename);
			return false;
		}
	}
	
	/**
	 * 清除指定缓存
	 * @param string $filename 缓存名称
     * @return boolean
	 */
	public function remove($name){
		if (@unlink($filename = $this->_getFileName($name))){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 清空过期缓存文件
	 */
	public function clean($mode=null){
		$files = VO_Filesystem_Folder::files($this->cache_dir);
		foreach ($files as $name) {
			if (!$this->isValid($name)) {
				@unlink($filename = $this->_getFileName($name));
			}
		}
	}	
	
	/**
	 * 清空所有缓存
     * @return boolean
	 */
	public function cleanAll(){
		$files = VO_Filesystem_Folder::files($this->cache_dir);
		foreach ($files as $name){
			@unlink($this->cache_dir . DS . $name . '.' . $this->file_ext);
		}
	}
	
	/**
	 * 获取缓存的时效
	 * @return int $this->lifetime
	 */
 	public function getLifetime(){
        return $this->lifetime;
    }
    
	/**
	 * 设置缓存的时效
	 * @param int $lifetime
	 */
 	public function setLifetime($lifetime=300){
 		$lifetime = (int)$lifetime;
        if ($lifetime != false){
            $this->life_time = $lifetime;
            return true;
        }else{
        	return false;
        }
    }
    
	/**
	 * 设置缓存的目录
	 * @param int $lifetime
	 */
 	public function setDir($folder){
 		if(empty($folder)){
 			return false;
 		}
 		if(!is_dir($folder)){
 			mkdir($folder);
 			chmod($folder,777);
 		}
        $this->cache_dir = $folder;
    }
    
	/**
	 * 设置缓存文件的扩展名
	 * @param int $lifetime
	 */
 	public function setExt($ext){
 		if(empty($ext)){
 			return false;
 		}
        $this->file_ext = $ext;
    }    

	/**
	 * 获取缓存文件上次修改时间
	 *
	 * @param string $filename
	 * @return datetime
	 */
	private function getModifyTime($filename){
    if($this->isExist($filename)){
      return filemtime($filename);
		}
	}
	
	/**
	 * 获取完整文件名
	 */
	private function _getFileName($filename=''){
		if($this->file_ext){
			$filename = $this->cache_dir . DS . $filename . '.' . $this->file_ext;
		}else{
			$filename = $this->cache_dir . DS . $filename;
		}
		return $filename;
	}
}