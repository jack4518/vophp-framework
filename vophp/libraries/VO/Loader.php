<?php
/**
 * 定义 VO_Loader 自动加载类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-07-05
 **/
require_once(VO_LIB_DIR . DS . 'Classes.php');
class VO_Loader{	
	/**
	 * 类实例存储器
	 * @var VO_Loader
	 */
	static $instance = null;
	
	/**
	 * 自动加载类规则存储器
	 * @var array
	 */
	protected static $_autoload_rules = array();
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		spl_autoload_register( array(__CLASS__, 'autoload') );
	}

	/**
	 * 获取框架版本号
	 * @return int  框架版本号
	 */
	public function getVersion(){
		return VOPHP_VERSION;
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Loader
	 */
	public static function getInstance(){
		if( !self::$instance instanceof VO_Loader ){
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * 初始化加载器
	 * @return void
	 */
	public static function init(){
		spl_autoload_register( array(__CLASS__, 'autoload') );
	}

	/**
	 * 初始化加载器
	 * param  string  $class  类名(去掉VO_后的名称，如果是多级目录则可以使用.号进行分隔)
	 *                        　　例如:VO_Loader::loader('string.pinyin'); 加载VO_String_Pinyin类
	 *                        　　例如:VO_Loader::loader('model'); 加载VO_Model类
	 * @return bool | Object  返回对应的类实例对象，或者返回false
	 */
	public static function load($class='', $xargs=array()){
		$relations = array(
			//'string' => 'VO_String',
			'cache' => 'VO_Cache_File',
			'redis' => 'VO_Nosql_Redis',
		);
		$xargs = func_get_args();
		$class = $xargs[0];
		unset($xargs[0]);
		$xargs = array_values($xargs);
		
		if(!empty($class) && array_key_exists($class, $relations)){
			$class = $relations[$class];
		}else{
			preg_match('/[^0-9a-z.]/i', $class, $match);
			if($match){
				trigger_error('类名:' . $class . ' 包含非法字符.', E_USER_ERROR);
				return false;
			}else{
				$classes = explode('.', $class);
				$classes = array_map('ucwords', $classes);
				$class = 'VO_' . implode('_', $classes);
			}
		}
		if(class_exists($class)){
			if(method_exists($class, 'getInstance')){
				return call_user_func_array(array($class, 'getInstance'), $xargs);
			}else{
				$ReflectionClass = new ReflectionClass($class);
				return $ReflectionClass->newInstanceArgs($xargs);
			}
		}else{
			trigger_error('类:' . $class . ' 不存在，请确认类文件是否已经包含与include_path中，或者类名的规则是否正确,\n类名规则：VO_类名称,以下划线分隔[首字母大写]，例如：VO_String_Pinyin类');
			return false;
		}
	}
	
	/**
	 * 载入文件
	 * @param string $filename 文件名
	 * @param string $path 文件的路径
	 * @param string $type 文件的后缀名
	 * @return Execption | void  文件导入成功，则include加载,否则抛出异常
	 */
	public static function import( $filename, $basepath=VO_LIB_DIR, $type='php'){
		$filename = str_replace( array('.', '_'), DS, $filename);
		$file = $basepath . DS . $filename . '.' . $type;
		if( file_exists($file) ){
			include_once $file;
		}else{
			$this->triggerError($file . ' 文件不存在.');
			exit;
		}
	}
	
	/**
	 * 解析类名，加载相应的文件
	 * @param  string  $class  类名
	 * @return bool
	 */
	protected static function autoload($class){
		if (class_exists($class, false) || interface_exists($class, false)) {
            return true;
        }
        $file = null;
		$class = trim($class, '\\');
		//加载规则
		if(self::$_autoload_rules){
			foreach(self::$_autoload_rules as $k => $v){
				$file = call_user_func($v, $class);
        		if ($file){
        			break;
        		}
			}
		}

		//由于和smarty的自动加载冲突，所以需要过滤掉以smarty开头的自动加载
		$filter = array('smarty');
		foreach($filter as $key => $val){
			if(strpos(strtolower($class), $val) === 0) {
				return true;
			}
		}
		if(!$file){
			//加载类和文件对应关系
			$ClassRelation = VO_Classes::getInstance();
			$file = $ClassRelation::getClassFile($class);
			if(!$file){
				//使用默认规则加载类文件
				$items = explode('_', $class);
				if(!empty($items)){
					$items = array_map('ucfirst', $items);
					$class = implode('_', $items);
				}
				$file = str_replace('_', DS, $class) . '.php';
			}
		}
		self::_securityCheck($file);
		$include_path = get_include_path();
		$file_is_exists = false;
		if(!empty($include_path)){
			$include_paths = explode(PATH_SEPARATOR, $include_path);
			foreach( $include_paths as $k => $path ){
				$file_path = $path . DS . $file;
				$file_path = str_replace(array('\\\\', '//'), DS, $file_path);
				if(file_exists($file_path)){
					require($file_path);
					return true;
				}
			}
		}
		if($file_is_exists == false){
			trigger_error('无法找到类名:' . $class . ' 对应的类文件，请确认类文件是否已经包含到项目中，或者是否包含到include_path环境变量中.', E_USER_ERROR);
				return false;
		}
	}
	
	/**
	 * 注册自动加载类规则
	 * @param mixed $func
	 * @return void
	 */
	public static function registerAutoload($func){
		if( is_callable($func) ){
			$key = self::callbackToString($func);
			if( !array_key_exists($key, self::$_autoload_rules) ){
				self::$_autoload_rules[$key] = $func;
			}
		}
	}
	
	/**
	 * 文件名安全检查
	 * @param String $filename
	 * @return bool
	 */
    protected static function _securityCheck($filename){
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            $this->triggerError( '类文件名包含非法字符！');
            exit;
        }else{
        	return true;
        }
    }
    
	/**
	 * 将回调函数名称转成字符串以方便存储 
	 * @param $callback
	 * @return string
	 */
	protected static function callbackToString($func){
		if(is_string($func)){
			return $func;
		}elseif( is_array($func) ){
			return get_class(array_shift($func)) . '::' . array_shift($func) ;
		}else{
			$this->triggerError('注册的自动加载类规则必须是一个方法名或者是一个数组(对象::方法)');
		}
	}
}
