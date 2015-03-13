<?php
/**
 * 定义  VO_View_Adapter_Php 视图渲染类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-08-10
 **/

defined('VOPHP') or die('Restricted access');
require_once(VO_LIB_DIR . DS . 'View' . DS . 'Adapter' . DS . 'Abstract.php');
class VO_View_Adapter_Php extends VO_View_Adapter_Abstract{
	
	/**
	 * 模板变量保存器
	 * @var array $vars
	 */
	public $vars = array();
	
	/**
	 * 配置文件
	 * @var array
	 */
	public $_config = null;
	
	/**
	 * 模板的配置信息
	 * @var array
	 */
	protected $view_config = array(
				'adapter'	=> 'php',
				'rendererMode'	=>	'auto',
				'templateDir'	=>	'', //模板目录
				'cacheDir'	=>	'', //模板缓存目录
				'isCache'	=>	false, //模板是否缓存
				'cacheLifetime'	=>	0, //缓存时间,单位为秒,默认为0秒
			);

	/**
	 * 构造方法
	 */
	public function __construct(array $view_config = null){
		if($view_config){
			$this->view_config = array_merge($this->view_config, $view_config);
		}
		$this->_config = VO_Registry::get('config');
		parent::__construct();
	}	
	
    /**
     * 设置模板变量
     * @param mixed $name 模板变量名称
     * @param mixed $value 变量内容
     */
    public function assign($name, $value = null) {
        if (is_array($name) && is_null($value)) {
            $this->vars = array_merge($this->vars, $name);
        } else {
            $this->vars[$name] = $value;
        }
    }
    
	/**
     * 以引用方式设置模板变量
     * @param mixed $name 模板变量名称
     * @param mixed $value 变量内容
     */
    public function assignByRef($name, &$value = null) {
        if (is_array($name) && is_null($value)) {
            $this->_vars = array_merge($this->_vars, $name);
        } else {
            $this->_vars[$name] = &$value;
        }
    }

	/**
     * 删除已经注入的变量
     * @param mixed $vars
     */
    public function removeAssign($vars){
    	if(is_array($vars)){
    		foreach( $vars as $var ){
                unset($this->_vars[$var]);
    		}
    	}else{
    		unset($this->_vars[$vars]);
    	}
    }
    
    /**
     * 删除所有已经注入的变量
     * @param	null
     * @return	void
     */
    public function clearAssign(){
    	unset($this->_vars);
    }

    /**
     * 构造模板输出内容
     *
     * @param string $file 模板文件名
     * @param string $cacheId 缓存 ID，如果指定该值则会使用该内容的缓存输出
     *
     * @return string
     */
    public function fetch($file, $cache_id = null) {
    //是否开启缓存
        if( $this->view_config['isCache'] ) {
        	$cache = new VO_Cache_File();
        	$cache->setLifetime($this->view_config['cacheLifetime']);
	        $this->view_config['cacheDir'] = str_replace(array('\\', '/'), array(DS,DS), $this->view_config['cacheDir']);

	    	if( (substr($this->view_config['cacheDir'],-1)==DS) && (strlen($this->view_config['cacheDir'])>0) ){
	    		$this->view_config['cacheDir'] = substr($this->view_config['cacheDir'], 0,-1);
	    	}
        	$cacheDir = SITE_DIR . DS . $this->view_config['cacheDir'] . DS;
        	$cache->setDir($cacheDir);
        	if($cache_id){
        		$cache_id = substr($file, 0, strpos($file, '.')) . '_' . $cache_id;
        	}else{
        		$cache_id = substr($file, 0, strpos($file, '.')) . '_' . substr(md5($file),0,25);
        	}
			$cachefile  = $cacheDir . $cache_id;
        	$content = $cache->get($cache_id);
        	if( $content ){
        		return $content;
	        }
        }

        $file = $this->_getViewFile($file);
        if(!file_exists($file)){
        	$this->triggerError('无法加载模板文件"' . $file . '",请检测模板文件是否存在');
        	exit;
        }
        
        //注入模板目录的URL和网站的基本URL
        $this->_setViewUrl($file);
        
        // 生成输出内容并缓存
        extract($this->vars);
        ob_start();
                
        $isSucc = require($file);
        if(!$isSucc){
        	$this->triggerError('无法加载模板文件"' . $file . '",请检测模板文件是否存在');
        	exit;
        }
        $contents = ob_get_contents();
        ob_end_clean();
        if ($this->view_config['isCache']) {
            //缓存输出内容
            $cache->set($cachefile, $contents, $this->view_config['cacheLifetime']);
        }
        
        return $contents;
    }

    /**
     * 显示指定模版的内容
     *
     * @param string $file 模板文件名
     * @param string $cache_id 缓存 ID，如果指定该值则会使用该内容的缓存输出
     */
    public function display($file, $cache_id = null) {
    	try{
        	echo $this->fetch($file, $cache_id);
    	}catch(Exception $e){
    		$message = $e->getMessage();
    		$file = $e->getFile();
    		$code = $e->getCode();
    		$line = $e->getLine();
    		$error = new VO_Error();
    		$error->error($message, $code, $file, $line);
    	}
    }
   
    /**
     * 设置当前视图的URL目录地址，如：http://localhost/views,用于视图中的图片、样式表等文件加载的前缀
     *
     * @param string $file 模板文件名
     * 
     * @return void
     */    
    private function _setViewUrl($file){
    	$document_root = str_replace(array('/', '\\'), DS, $_SERVER['DOCUMENT_ROOT']);

        $view_url = str_replace(array($document_root, DS),  array($this->_config['base_url'], '/'), dirname($file) );
        $this->assign('view_url', $view_url);
        $this->assign('base_url', $this->_config['base_url']);
    }

    /**
     * 设置模板目录
     * @param string $dir
     * @return null
     */
    public function setTemplateDir($dir=null){
    	$this->view_config['templete_dir'] = $dir;
    }  
    
	/**
     * 设置模板编译目录
     * @param string $dir
     * @return null
     */
    public function setCompileDir($dir=null){
    	$this->view_config['compile_dir'] = $dir;
    }
    
    /**
     * 关闭缓存
     */
    public function setCache($iscache = true){
    	$this->view_config['is_cache'] = $iscache;
    }
    
    /**
     * 设置模板缓存目录
     * @param $dir
     */
    public function setCacheDir($dir){
    	$this->view_config['cache_dir'] = $dir;
    	$cache = VO_Cache_File::getInstance();
    	$cache->setDir($dir);
    }
    
	/**
	 * 设置模板缓存的时效
	 * @param int $lifetime
	 */
 	public function setLifetime($lifetime=300){
 		$lifetime = (int)$lifetime;
        if($lifetime != false){
            $this->view_config['life_time'] = $lifetime;
            return true;
        }else{
        	return false;
        }
    }      
}	