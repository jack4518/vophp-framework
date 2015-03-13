<?php
/**
 * 定义VO_View_Adapter_Votpl VOTPL模板引荐核心文件
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-12-25
 **/

defined('VOPHP') or die('Restricted access');

define('VOTPL_PATH', dirname(__FILE__));
require_once(VO_LIB_DIR . DS . 'View' . DS . 'Adapter' . DS . 'Abstract.php');
class VO_View_Adapter_Votpl extends VO_View_Adapter_Abstract{
	
	/**
	 * VOPHP的配置文件
	 * @var array
	 */
	var $_config = array();
	
	/**
	 * 函数存储器
	 * @var array
	 */
	var $_functions = array();
	
	/**
	 * 修饰器存储器
	 * @var array
	 */
	var $_modifiers = array();	
	
	/**
	 * 对象存储器
	 * @var object
	 */
	var $_objects = array();
	
	/**
	 * 变量存储器
	 * @var array
	 */
	var $_vars = array();
	
	/**
	 * 缓存对象
	 * @var VO_Cache_File
	 */
	var $_cache = null;
	
	/**
	 * 模板的配置信息
	 * @var array
	 */
	protected $view_config = array(
				'adapter'	=> 'votpl',
				'rendererMode'	=>	'static',
				'templateDir'	=>	'views',		//模板目录
				'compileDir'	=>	'views\templates_c',		//模板编译目录
				'cacheDir'	=>	'views\cache',		//模板缓存目录
				'isCache'	=>	false,		//模板是否缓存
				'cacheLifetime'	=>	300,		//缓存时间,单位为秒,默认为300秒，即5分钟
				'left_delimiter'	=> '{',		//模板语法的左定界符
				'right_delimiter'	=> '}', 		//模板的右定界符
				'isAllownPhpFunction'	=>	true,	//是否允许模板使用PHP内置函数，如果开启此选项，当模板使用PHP内置函数时，视图不需要手动注入函数，建议手动注入模板需要的方法
				'isForceCompile'	=>	true,		//是否强制编译
			);
	
	/**
	 * 构造方法
	 * @return VO_View_Adapter_Votpl
	 */
	public function __construct(array $view_config = null){
		if($view_config){
			$this->view_config = array_merge($this->view_config, $view_config);
		}
		
		if( $this->view_config['isCache'] == true ){
			$this->_cache = new VO_Cache_File();
		}
		$this->_config = VO_Registry::get('config');

		parent::__construct();
		
		$this->_init();
	}
	
	/**
	 * 初始化视图
	 */
	private function _init(){
		$this->_vars['vo']['foreach']['index'] = 0;
	}
	
	/**
     * 设置模板变量
     * @param mixed $name 模板变量名称
     * @param mixed $value 变量内容
     */
    public function assign($name, $value = null) {
        if (is_array($name) && is_null($value)) {
            $this->_vars = array_merge($this->_vars, $name);
        } else {
            $this->_vars[$name] = $value;
        }
    }
    
	/**
     * 以引用方式设置模板变量
     * @param mixed $name 模板变量名称
     * @param mixed $value 变量内容
     */
    public function assignByRef($name, &$value = null)  {
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
     * @return string
     */
    public function fetch($file, $cache_id = null) {
    	//是否开启缓存
        if( $this->view_config['isCache'] ) {
        	$this->_cache->setLifetime($this->view_config['cacheLifetime']);
	        $this->view_config['cacheDir'] = str_replace(array('\\', '/'), array(DS,DS), $this->view_config['cacheDir']);

	    	if( (substr($this->view_config['cacheDir'],-1)==DS) && (strlen($this->view_config['cacheDir'])>0) ){
	    		$this->view_config['cacheDir'] = substr($this->view_config['cacheDir'], 0,-1);
	    	}
	    	if( empty($this->view_config['cacheDir']) ){
	    		$this->view_config['cacheDir'] = $this->view_config['compileDir'];
	    	}
        	$cacheDir = SITE_DIR . DS . $this->view_config['cacheDir'] . DS;
        	$this->_cache->setDir($cacheDir);
        	if($cache_id){
        		$cache_id = substr($file, 0, strpos($file, '.')) . '_' . $cache_id;
        	}else{
        		$cache_id = substr($file, 0, strpos($file, '.')) . '_' . substr(md5($file),0,25);
        	}
			$cachefile  = $cacheDir . $cache_id;
        	$content = $this->_cache->get($cache_id);
        	if( $content ){
        		return $content;
	        }
        }
        /*
        // 生成输出内容并缓存
        extract($this->vars);
        ob_start();
        $isSucc = @include($this->templete_dir . DS . $file);
        */
    	$compileFile = $this->_getCompileFile($file);
    	
        ob_start();
    	$isSucc = include($compileFile);
        if(!$isSucc){
        	$this->triggerError('无法加载模板"' . $file . '"的编译文件,请检测模板是否编译成功');
        	exit;
        }
        $contents = ob_get_contents();
        ob_end_clean();
        

        //设置缓存
        if ($this->view_config['isCache']) {
            $this->_cache->set($cache_id, $contents, $this->view_config['cacheLifetime']);
        }
        return $contents;
    }

    /**
     * 显示指定模版的内容
     *
     * @param string $file 模板文件名
     * @param string $cache_id 缓存 ID，如果指定该值则会使用该内容的缓存输出
     */
    public function display($file, $vars=array(), $cache_id = null) {
    	try{
    		if(!empty($vars)){
    			$this->_vars = array_merge($this->_vars, $vars);
    		}
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
     * 获取编译文件
     */
    private function _getCompileFile($template){
    	$templateFile = $this->_getViewFile($template);    	
    	if( !file_exists($templateFile) ){
    		$this->triggerError('无法加载模板文件"' . $templateFile . '",请检测模板文件是否存在');
        	exit;
    	}
    	
    	//注入模板目录的URL和网站的基本URL
    	$this->_setViewUrl($templateFile);
    	
   		//检测模板编译
    	$this->view_config['compileDir'] = str_replace(array('\\', '/'), array(DS,DS), $this->view_config['compileDir']);
    	if(substr($this->view_config['compileDir'], -1) == DS){
    		$this->view_config['compileDir'] = substr($this->view_config['compileDir'], 0, -1);
    	}
    	$compileDir = SITE_DIR . DS . $this->view_config['compileDir'] . DS;
    	if( !is_dir($compileDir) ){
    		mkdir($compileDir);
    	}
    	$filename = VO_Filesystem_File::stripExt($template);
    	$compileFile = $compileDir . $filename . '_' . md5($template) . '.php';

    	if( !$this->isForceCompile && file_exists($compileFile) && filemtime($compileFile) > filemtime($templateFile)){
    		return $compileFile;
    	}else{
    		//编译模板
    		$compile = VO_View_Adapter_Votpl_Compiler::getInstance($this->view_config);
    		$compile->compile($templateFile, $compileFile, $this->_vars, $this->_functions, $this->_modifiers, $this->_plugins);
    		return $compileFile;
    	}
    	    	
    }
    
    /**
     * 注册函数
     * @param $tplFunctionName	模板函数名称
     * @param $functionName		实际函数名称
     * @reutrn void
     */
    public function registerFunction($tplFunctionName, $functionName=null){
    	if( $functionName === null ){
    		$this->_functions[strtolower($tplFunctionName)] = $tplFunctionName ;
    	}else{
    		$this->_functions[strtolower($tplFunctionName)] = $functionName ;
    	}
    }
    
    /**
     * 注册修饰器
     * @param $tplFunctionName	模板修饰器名称
     * @param $functionName		实际修饰器名称
     * @reutrn void
     */
    public function registerModifier($tplModifierName, $modifierName=null){
    	if( $modifierName === null ){
    		$this->_modifiers[strtolower($tplModifierName)] = $tplModifierName ;
    	}else{
    		$this->_modifiers[strtolower($tplModifierName)] = $modifierName ;
    	}
    }    
    
    /**
     * 注册对象
     * @param $tplObjectName	模板对象名称
     * @param $objectName		实际对象名称
     * @reutrn void
     */
    public function registerObject($tplObjectName, $objectName=null){
    	if( $objectName === null ){
    		$this->_vars[strtolower($tplObjectName)] = $tplObjectName ;
    	}else{
    		$this->_vars[strtolower($tplObjectName)] = $objectName ;
    	}
    }
    
	/**
     * 删除注册过的函数
     * @param $tplFunctionName	模板函数名称
     * @reutrn void
     */
    public function unRegisterFunction($tplFunctionName){
    	if(is_array($tplFunctionName)){
    		foreach( $tplFunctionName as $fun ){
                unset($this->_functions[$fun]);
    		}
    	}else{
    		unset($this->_functions[$tplFunctionName]);
    	}
    }
    
	/**
     * 删除注册过的修饰器
     * @param $tplModifierName	模板修饰器名称
     * @reutrn void
     */
    public function unRegisterModifier($tplModifierName){
    	if(is_array($tplModifierName)){
    		foreach( $tplModifierName as $modifier ){
                unset($this->_modifiers[$modifier]);
    		}
    	}else{
    		unset($this->_functions[$tplModifierName]);
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
     * @return void
     */
    public function setTemplateDir($dir=null){
    	$this->view_config['templeteDir'] = $dir;
    }  
    
	/**
     * 设置模板编译目录
     * @param string $dir
     * @return void
     */
    public function setCompileDir($dir=null){
    	$this->view_config['compileDir'] = $dir;
    }
    
    /**
     * 关闭缓存
     */
    public function setCache($iscache = true){
    	$this->view_config['isCache'] = $iscache;
    }
    
    /**
     * 设置模板缓存目录
     * @param $dir
     * @reutrn void
     */
    public function setCacheDir($dir){
    	$this->_cache->setDir($dir);
    }
    
	/**
	 * 设置模板缓存的时效
	 * @param int $lifetime
	 * @reutrn bool
	 */
 	public function setLifetime($lifetime=300){
 		$lifetime = (int)$lifetime;
        if($lifetime > 0){
            $this->_cache->setLifetime($lifetime);
            return true;
        }else{
        	return false;
        }
    }
    
}