<?php
/**
 * 定义VO_View_Adapter_Votpl_Compiler 模板编译类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-12-06
 **/

defined('VOPHP') or die('Restricted access');

class VO_View_Adapter_Votpl_Compiler{
	/**
	 * 模板变量
	 * @var array
	 */
	var $_vars = array();
	
	/**
	 * 注册后的模板函数存储器
	 * @var array
	 */
	var $_tpl_functions = array();
	
	/**
	 * 注册后的模板修饰器存储器
	 * @var array
	 */
	var $_tpl_modifiers = array();
	
	/**
	 * 插件存储器
	 * @var array
	 */
	var $_tpl_plugins = array();
	
	var $_slc = 'include|include_once|require|require_once|global|clone|function|die|exit' ; 
	
	var $_option = array();
	
	/**
	 * 模板的目录
	 * @var string
	 */
	var $templateDir = '';
	
	/**
	 * 是否允许使用php内置函数
	 * @var boolean
	 */
	var $isAllownPhpFunction = false;
	
	/**
	 * 模板文件内容
	 * @var string
	 */
	var $_content = '';
	
	/**
	 * 注册过模板引擎变量修饰器
	 * @var array
	 */
	var $_modifier = array();
	
	/**
	 * 模板引擎源文件地址存储器
	 * @var array
	 */
	var $_include_files = array();
	
	/**
	 * 包含其它模板文件正则表达式
	 * @var string
	 */
	var $_regx_include_tpl = null;
	
	/**
	 * foreach正则表达式
	 * @var string
	 */
	var $_regx_foreach = null;
	
	/**
	 * while正则表达式
	 * @var string
	 */
	var $_regx_while = null;
	
	/**
	 * 变量正则表达式
	 * @var string
	 */
	var $_regx_var = null;
	
	/**
	 * 模板引擎标识的左定界符
	 * @var string
	 */
	var $left_delimiter = '{';
	
	/**
	 * 模板引擎标识的右定界符
	 * @var string
	 */
	var $right_delimiter = '}';
	
	var $not_ld_rd = '';
	
	/**
	 * 当前解析的模板文件全路径
	 * @var string
	 */
	var $templeteFile = '';
	
	/**
	 * 构造函数
	 * @return VO_View_Adapter_Votpl_Compiler
	 */
	protected function __construct(array &$view_config = null){
		if($view_config){
			foreach($view_config as $k => $v){
				$this->$k = $v;
			}
		}
		
		$this->left_delimiter = preg_quote($this->left_delimiter, '~');
		$this->right_delimiter = preg_quote($this->right_delimiter, '~');
		
		$this->not_ld_rd = $this->left_delimiter . $this->right_delimiter;
		
		/**
		 * 包含其它模板文件正则表达式,匹配:
		 * 	{include_tpl "folder/file.html"}
		 */
		$this->_regx_include_tpl = '/' . $this->left_delimiter . '\s*include_tpl\s+(["\'])([\w\/\.\\\\`\$]*)(["\'])\s*' . $this->right_delimiter . '/';
		
		/**
		 * 包含PHP文件正则表达式,匹配:
		 * 	{include_php "/inc/func.php"}
		 */
		$this->_regx_include_php = '/' . $this->left_delimiter . '\s*include_php\s+["\']([\w\/\.\\\]*)["\']\s*' . $this->right_delimiter . '/';
		
		/**
		 * 匹配if或者elseif则表达式,匹配:
		 * 	{if $i>0} 或者 {elseif $j>0}
		 */
		$this->_regx_if = '/' . $this->left_delimiter . '\s*(if|elseif)\s+([^\}]+)\s*' . $this->right_delimiter . '/i';
		
		/**
		 * foreach正则表达式,匹配:
		 * {foreach $arrays $val} 或者 {foreach $arrays as $val} 或者  {foreach $arrays as $key => $val}
		 */
		$this->_regx_foreach = '/' . $this->left_delimiter . '\s*foreach[\s]+\$([a-zA-Z_]\w*)([^\s=\$' . $this->right_delimiter . ']*)[\s]+(as[\s]+)?\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$' . $this->right_delimiter . ']*)(\s*=>\s*)?(\$)?([a-zA-Z_][a-zA-Z0-9_]*)?([^ =\.\$' . $this->not_ld_rd . ']*)?\s*' . $this->right_delimiter . '/i';
		
		/**
		 * while正则表达式,匹配:
		 * {whle $x>10}
		 */
		$this->_regx_while = '/' . $this->left_delimiter . '\s*while\s+(.+?)\s*' . $this->right_delimiter . '/i';
		
		/**
		 *变量正则表达式,匹配:
		 * $var
		 * $arr.x
		 * $arr['x']
		 */
		$this->_regx_var = '/\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$' . $this->right_delimiter . ']*)?\s*/';
		
		/**
		 *变量赋值正则表达式,匹配:
		 * $var = 5
		 * $arr.x = 'abc'
		 * $arr['x'] = 'abc123'
		 * * $arr['x'].y = 'I am array'
		 */
		$this->_regx_set_var = '/' . $this->left_delimiter . '\s*\$([a-zA-Z_][\w]*)([\w\'\"\[\]]*)?(\s*\.\s*)?([\w\.]*)\s*=\s*([^=]*?)\s*' . $this->right_delimiter . '/';
		
		/**
		 *匹配系统变量正则表达式,匹配:
		 * $vo.cookie.username
		 * $vo.session.name
		 * $vo.server.http_host
		 */
		$this->_regx_systemVar = '/' . $this->left_delimiter . '\s*\$vo\.([\w.]+)\s*(.*?)\s*' . $this->right_delimiter . '/';
		
		/**
		 *匹配原始PHP代码块正则表达式,匹配:
		 * {php}echo 'hello world!';{/php}
		 */
		$this->_regx_php_tag = '/' . $this->left_delimiter . '\s*php\s*' . $this->right_delimiter . '(.*?)' . $this->left_delimiter . '\s*\/php\s*' . $this->right_delimiter . '/s';
		
		/**
		 *匹配原始数据输出块正则表达式,匹配:
		 * {literal}echo 'hello world!';{/literal}
		 */
		$this->_regx_literal = '/' . $this->left_delimiter . '\s*literal\s*' . $this->right_delimiter . '(.*?)' . $this->left_delimiter . '\s*\/literal\s*' . $this->right_delimiter . '/s';
	}
	
	/**
	 * 获取单一实例
	 * @return VO_View_Adapter_Votpl_Compiler
	 */
	public static function getInstance(array &$options=null){
		static $instance = null;
		if( !$instance instanceof VO_View_Adapter_Votpl_Compiler ){
			$instance = new self($options);
		}
		
		return $instance;
	}
	
	/**
	 * 编译模板
	 * @param string $templateFile	模板文件名称
	 * @param string $compileFile	编译后的文件名
	 * @param array	$vars	注册的变量
	 * @param array $functions	注册的函数
	 * @return void
	 */
	public function compile($templateFile, $compileFile, &$vars, &$functions, &$modifiers, &$plugins){
		$this->templeteFile = $templateFile;
		$this->_tpl_vars = &$vars;
		$this->_tpl_functions = &$functions;
		$this->_tpl_modifiers = &$modifiers;
		$this->_tpl_plugins = &$plugins;
		
		$templateContent = file_get_contents( $templateFile );
		$content = $templateContent;
		if( empty($templateContent) ){
			return false;
		}
		$templateContent = $this->_parse($templateContent);
		VO_Filesystem_File::write($compileFile, $templateContent);
	}
	
	/**
	 * 解析模板中包含其它模板文件语法 include_tpl
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseIncludeTpl($content = ''){
		if( preg_match_all( $this->_regx_include_tpl, $content, $matchs ) ){
			foreach( $matchs[2] as $k => $v){
				preg_match_all('/\`\$([\w]*)\`/',  $v, $vars);
				if($vars){
					foreach($vars[1] as $y => $var){
						$replace = $this->_tpl_vars[$var];
						$v = str_replace( $vars[$y][0], $replace, $v );
					}
				}
				$tplFilePath = str_replace( array("\\", '/'), array(DS, DS), $v );
				if( substr($tplFilePath, 0, 1) == DS ){
					//采用绝对路径引入模板文件
					$tplFilePath = SITE_DIR . DS . $this->templateDir . $tplFilePath;
				}else{
					//采用相对路径引入模板文件
					$tplFilePath = dirname($this->templeteFile). DS . $tplFilePath;
				}
				if( !file_exists($tplFilePath)){
					$this->_error('The template file ' . $tplFilePath . ' is not exists!');
				}
				$includeFileContent = file_get_contents($tplFilePath);
				$includeFileContent = $this->_parseIncludeTpl($includeFileContent); //递归include_tpl
				$content = str_replace( $matchs[0][$k], $includeFileContent, $content );
			}
			return $content;
		}else{
			return $content;
		}
	}
	
	/**
	 * 解析模板中包含PHP文件语法 include_php
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseIncludePhp($content){
		if( preg_match_all( $this->_regx_include_php, $content, $matchs ) ){
			foreach( $matchs[0] as $k => $v){
				$matchs[1][$k] = str_replace( array("\\", '/'), array(DS, DS), $matchs[1][$k] );
				if( substr($matchs[1][$k], 0, 1) == DS){
					$matchs[1][$k] = SITE_DIR . $matchs[1][$k];
				}else{
					$matchs[1][$k] = SITE_DIR . DS . $matchs[1][$k];
				}
				if( file_exists($matchs[1][$k])){
					$replace = '<?php include "' . $matchs[1][$k] . '"; ?>';
					$content = str_replace( $matchs[0][$k], $replace, $content);
				}else{
					$this->_error('Include PHP file "' . $matchs[1][$k] . '" is not exists.');
				}
			}
		}
		return $content;
	}
	
	/**
	 * 解析系统变量语法,支持'SESSION', 'COOKIE', 'SERVER', 'ENV', 'GET', 'POST', 'REQUEST'等变量,
	 * 		例如：$vo.cookie.name解析成$_COOKIE['name']
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseSystemVar($content){
		$systemVars = array('SESSION', 'COOKIE', 'SERVER', 'ENV', 'GET', 'POST', 'REQUEST');
		if( preg_match_all( $this->_regx_systemVar, $content, $matchs ) ){
			//var_dump($matchs);
			//var_dump($this->_tpl_vars);
			foreach( $matchs[0] as $i => $val ){
				$var = trim( $matchs[1][$i] );
				if( $var != '' ){
					//如果是以'.'分隔的参数，则拆分为数组,例如:$vo.version.date则拆分成$vo['version']['date']
					$arrays = explode('.', $matchs[1][$i]);
					$systemVar = array_shift($arrays);
					if( !in_array(strtoupper($systemVar), $systemVars)){
						continue;
					}
					$var = strtoupper($systemVar);
					foreach($arrays as $k => $v){
						$v = trim($v);
						if( $var == 'SERVER' || $var == 'ENV'){
							$v = strtoupper($v);
						}
						$var .= '[\'' . $v . '\']';
						
					}
					$var = '$_' . $var;
					$modifier = trim($matchs[2][$i]);
					if($modifier){
						$var = $this->_parseModifier( $var, '', $modifier, true );
					}
					$replace = '<?php echo ' . $var . '; ?>';
					$content = str_replace( $matchs[0][$i], $replace, $content );
				}
			}
		}
		return $content;
	}
	
	/**
	 * 解析嵌入的PHP标签,
	 * 		例如：{php} echo 'I am php code!'{/php}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parsePhpTag($content){
		if( preg_match_all( $this->_regx_php_tag, $content , $matchs ) ){
			foreach( $matchs[0] as $i => $val ){
				$replace = '<?php ';
				$lft = stripslashes($this->left_delimiter);
				$rgt = stripslashes($this->right_delimiter);
				$matchs[1][$i] = str_replace( array( $lft , $rgt) , array( '_&ls&_' , '_&rs&_' ) , $matchs[1][$i] );
				$replace .= $matchs[1][$i];
				$replace .= '?>';
				$content = str_replace( $matchs[0][$i], $replace, $content ) ;
			}
		}
		return $content;
	}
	
	/**
	 * 解析literal标签,被此标签包含的内容将会原样输出
	 * 		例如：{literal} echo 'I am php code!'{/literal}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseLiteral($content){
		if( preg_match_all( $this->_regx_literal, $content , $matchs ) ){
			foreach( $matchs[0] as $i => $val ){
				$replace = '<?php echo \'';
				$lft = stripslashes($this->left_delimiter);
				$rgt = stripslashes($this->right_delimiter);
				$matchs[1][$i] = str_replace( array( $lft , $rgt) , array( '_&ls&_', '_&rs&_' ) , $matchs[1][$i] );
				$replace .= addcslashes($matchs[1][$i], '\'');
				$replace .= '\'; ?>';
				$content = str_replace( $matchs[0][$i], $replace, $content ) ;
			}
		}
		return $content;
	}
	
	/**
	 * 解析模板中的变量赋值语法 ,例如：{$votpl_version = "1.0"}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseSetVar($content){
		//变量赋值
		//$pattern = '/{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$\}]*)?(\s*\.\s*)?([^=\$\}]*)\s*=\s*([^};]*)\s*}/';
		if( preg_match_all( $this->_regx_set_var, $content , $matchs ) ){
			//var_dump($matchs);
			foreach( $matchs[0] as $i => $val ){
				$var = trim( $matchs[1][$i] );
				$replace = '<?php $this->_vars[\'' . $var  . '\']' . trim($matchs[2][$i]);
				if( $var != '' ){
					//如果是以'.'分隔的参数，则拆分为数组,例如:$vo.version.date则拆分成$vo['version']['date']
					if( trim($matchs[3][$i]) == '.'){
						$arrays = explode('.', trim($matchs[4][$i]));
						foreach($arrays as $k => $v){
							$v = trim($v);
							if(!empty($v)){
								if(is_numeric($v)){
									$replace .= '[' . $v . ']';
								}else{
									$replace .= '[\'' . $v . '\']';
								}
							}
						}
						$value = $this->_parseString($matchs[5][$i]);
						$value = $this->_replaceVar($value);
						$replace .= ' = ' . $value . '; ?>';
					}else{
						$value = $this->_parseString($matchs[5][$i]);
						$value = $this->_replaceVar($value);
						$replace .=trim($matchs[4][$i]) . ' = ' . $value . '; ?>';
					}
					$content = str_replace( $matchs[0][$i], $replace, $content ) ;
				}
			}
		}
		return $content;
	}
	
	/**
	 * 解析模板中的foreach语法 ,例如：
	 * {foreach $arrays $val} 或者 {foreach $arrays as $val} 或者  {foreach $arrays as $key => $val}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseForeach($content){
		//数组foreach循环
		if( preg_match_all($this->_regx_foreach, $content, $matchs ) ){
			//var_dump($matchs);
			foreach( $matchs[0] as $k => $val){
				$var = trim($matchs[1][$k]);
				if( $var != '' ){
					if( !array_key_exists( $matchs[4][$k] , $this->_tpl_vars ) ){
						$this->_tpl_vars[trim($matchs[4][$k])] = ''; 
					}
					
					if( !empty($matchs[8][$k]) && !array_key_exists( $matchs[8][$k] , $this->_tpl_vars ) ){
						$this->_tpl_vars[trim($matchs[8][$k])] = ''; 
					}
					$var = $this->_parseString('$'.$var.$matchs[2][$k]);
					$var = $this->_replaceVar($var);
					if( (strtolower(trim($matchs[3][$k])) == 'as') && (trim($matchs[6][$k]) == '=>') ){
						$replace = '<?php if( @is_array( ' . $var . ' ) ){' . ' foreach( ' . $var . ' as $this->_vars["' . trim($matchs[4][$k]) . '"]' . trim($matchs[5][$k]) . ' => $this->_vars["' . trim($matchs[8][$k]) . '"]' . trim($matchs[9][$k]) . ' ){ ?>';
					}else{
						$replace = '<?php if( @is_array( ' . $var . ' ) ){' . ' foreach( ' . $var . ' as $this->_vars["' . trim($matchs[4][$k]) . '"]' . trim($matchs[5][$k]) . ' ){  ?>';
					}
					$content = str_replace( $val, $replace, $content ) ;
				}
			}
		}
		
		if( preg_match_all( '/' . $this->left_delimiter . '\s*(foreach\s*else)\s*' . $this->right_delimiter . '(.*)(' . $this->left_delimiter . '\s*\/foreach\s*' . $this->right_delimiter . ')/Us', $content, $matchs) ){
			foreach( $matchs[0] as $i => $val){
				if( strtolower($matchs[1][$i]) == 'foreach else' || strtolower($matchs[1][$i]) == 'foreachelse'){
					$replace = '<?php $this->_vars["vo"]["foreach"]["index"]++; } $this->_vars["vo"]["foreach"]["index"] = 0; }else{ ?> ' . $matchs[2][$i] . '<?php } ?>';
					$content = str_replace( $val, $replace, $content ) ;
				}
			}
		}
		return $content;
	}
	
	/**
	 * 解析模板中的if和elseif逻辑语法 ,例如：{if $i>0}、{elseif $j>0}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseIf($content){
		if( preg_match_all($this->_regx_if, $content, $matchs ) ){
			foreach( $matchs[0] as $i => $val){
				if(strtolower( $matchs[1][$i] ) == 'elseif'){
					$matchs[1][$i] = '}elseif' ;
				}
				
				if( preg_match_all( $this->_regx_var, $matchs[2][$i], $vars ) ){
					foreach($vars[0] as $k => $var){
						$replace = '$this->_vars["' . $vars[1][$k] . '"]' . $vars[2][$k];
						$matchs[2][$i] = str_replace( $vars[0][$k], $replace, $matchs[2][$i]);
					}
				}
				
				$replace = '<?php '. $matchs[1][$i] . '( ' . $this->_parseString($matchs[2][$i]) . ' ){ ?>';
				$content = str_replace( $val, $replace, $content ) ;
			}
		}
		return $content;
	}
	
	/**
	 * 解析while语法 ,例如：{while $a>10}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseWhile($content){
		//编译while语法
		if( preg_match_all( $this->_regx_while, $content, $matchs ) ){
			foreach( $matchs[0] as $i => $val){
				if( preg_match_all( $this->_regx_var, $matchs[1][$i], $vars ) ){
					foreach($vars[0] as $k => $var){
						$replace = '$this->_vars["' . $vars[1][$k] . '"]' . $vars[2][$k];
						$matchs[1][$i] = str_replace( $vars[0][$k], $replace, $matchs[1][$i]);
					}
				}
				$replace = '<?php while(' . $this->_parseString($matchs[1][$i]).'){ ?>';
				$content = str_replace( $val, $replace, $content ) ;
			}
		}
		return $content;
	}
	
	/**
	 * 解析自增或者自減，例如：
	 * {$i++} {$i--} {++$i} {--$i} {$array.index++}等
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseAutoIncrease($content){
		//编译自增或者自减语法: $i++或者$i--
		if( preg_match_all('/' . $this->left_delimiter . '\s*\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\-\*\/\+\.' . $this->right_delimiter . ']*)?\s*(\.)?\s*([^=\$' . $this->not_ld_rd . ']*)?(\+\+|--)+\s*' . $this->right_delimiter . '/', $content, $matchs ) ){
			//var_dump($matchs);
			foreach( $matchs[0] as $i => $val){
				if($matchs[3][$i] == '.'){
					$var = trim( $matchs[1][$i] );	
					$arrays = explode('.', trim($matchs[4][$i]));
					$replace = '<?php $this->_vars[\'' . $var  . '\']' . trim($matchs[2][$i]);
					foreach($arrays as $k => $v){
						$v = trim($v);
						if(!empty($v)){
							$firstChar = substr($v, 0, 1);
							if( ($firstChar == '$') ){
								//连接变量
								$tmpVar = substr($v, 1);
								$replace .= ' . $this->_vars["' . trim($tmpVar) . '"]';
							}else if( ($firstChar == "'") || ($firstChar == '"') ){
								//连接字符串
								$replace .= ' . ' . $v;
							}else{
								//数组
								$replace .= '[\'' . $v . '\']';
							}
						}
					}
					$replace .= trim($matchs[5][$i]) . '; ?>';
				}else{
					$replace = '<?php $this->_vars["' . $matchs[1][$i] . '"]' . trim($matchs[5][$i]) .' ?>';
				}
				$content = str_replace( $val, $replace, $content ) ;
			}
			return $content;
		}
		
		//编译自增或者自减语法:++$i或者--$i
		if( preg_match_all('/' . $this->left_delimiter . '\s*(\+\+|--)+\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.' . $this->left_delimiter . ']*)?\s*(\.)?\s*([^=\$' . $this->not_ld_rd . ']*)?\s*' . $this->right_delimiter . '/', $content, $matchs ) ){
			foreach( $matchs[0] as $i => $val){
				if($matchs[4][$i] == '.'){
					$var = trim( $matchs[2][$i] );	
					$arrays = explode('.', trim($matchs[5][$i]));
					$replace = '<?php ' . trim($matchs[1][$i]) . '$this->_vars[\'' . $var  . '\']' . trim($matchs[3][$i]);
					foreach($arrays as $k => $v){
						$v = trim($v);
						if(!empty($v)){
							$firstChar = substr($v, 0, 1);
							if( ($firstChar == '$') ){
								//连接变量
								$tmpVar = substr($v, 1);
								$replace .= ' . $this->_vars["' . trim($tmpVar) . '"]';
							}else if( ($firstChar == "'") || ($firstChar == '"') ){
								//连接字符串
								$replace .= ' . ' . $v;
							}else{
								//数组
								$replace .= '[\'' . $v . '\']';
							}
						}
					}
					$replace .= '; ?>';
				}else{
					$replace = '<?php $this->_vars["' . $matchs[2][$i] . '"] ' . trim($matchs[1][$i]) .' ?>';
				}
				$content = str_replace( $val, $replace, $content ) ;
			}
		}
		return $content;
	}
	
	/**
	 * 解析模板中的函数调用语法 ,例如：{show("username")}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	private function _parseFunction($content){
		//编译函数语法
		if( preg_match_all('/' . $this->left_delimiter . '\s*([a-zA-Z_][a-zA-Z0-9_]*)\(([^}\)]*)\)' . $this->right_delimiter . '/', $content , $matchs ) ){
			//var_dump($matchs);
			foreach( $matchs[1] as $i => $val ){
				if( strtolower($val) == 'array' ){
					continue;
				}
				$functionName = trim( $val ) ;
				if( $this->_isFunctionExist( $functionName ) ){
					$matchs[2][$i] = $this->_replaceVar($matchs[2][$i]);
					$replace = '<?php echo ' . $this->_getFunction($functionName) . '(' . $this->_parseString($matchs[2][$i]) .  '); ?>';
					$content = str_replace( $matchs[0][$i], $replace, $content ) ;
				}else{
					//$this->_error( 'VOTPL Error:Can not call to unregistered function "' . $functionName . '(' . $matchs[2][$i] . ')" , please use Votpl::registerFunction() to register a function.' ) ;
				}
			}
		}
		return $content;
	}
	
	/**
	 * 解析模板中的变量输出语法 ,例如：
	 * {$name}或者{$people["address"}或者{$people.address.name}或者{$people['address']}或者{$people['address'].name}
	 * @param string $content 模板文件内容
	 * @return void
	 */
	
	private function _parseEchoVar($content){
		//编译输出变量语法
		//if( preg_match_all('/\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$\}\?\|]*)?\s*(\.)?\s*([^=\?\{\}]*)\s*\}/' , $content , $matchs ) ){
		//if( preg_match_all('/' . $this->left_delimiter . '\s*\$([a-zA-Z_][\w_]*)([\w\[\]\'\"]*)?\s*([^\|]*?)\s*([^=]*?)\s*' . $this->right_delimiter . '/' , $content , $matchs ) ){
		if( preg_match_all('/' . $this->left_delimiter . '[\r]*\$([^\|]*?)(\|([^' . $this->right_delimiter . ']*))*\s*' . $this->right_delimiter . '/m' , $content , $matchs ) ){
			//echo '<pre>';
			//var_dump($matchs);
			foreach($matchs[0] as $i => $var){
				$var = $matchs[1][$i];
				$var = $this->_parseString('$'.$var);
				$var = $this->_parseModifier( $var, $matchs[2][$i] );
				$replace = '<?php echo ' . $var . '; ?>';
				$content = str_replace( $matchs[0][$i], $replace, $content ); 
			}
		}
		return $content;
	}
	
	/**
	 * 解析变量修饰器
	 * 如：{$students['score'].name|strips|truncate:10:20:'......'}
	 * @param string $var	变量名称字符,此时$var为students
	 * @param string $modifiers	修饰器和参数,此时$modifiers为strips|truncate:10:20:'......'
	 * @param bool	 $isSystemVar 是否是系统变量，如果是系统变量，则不加上$this->_vars前缀
	 * $return string
	 */
	private function _parseModifier($var, $modifiers='', $isSystemVar=false){
		preg_match('/\$([\w]+)(.*)/', $var, $match);
		$vname = $match[1];
		if( false == $isSystemVar){
			$var = '$this->_vars["'. $vname . '"]' . $match[2];
		}
		
		if( empty($modifiers) ){
			return $var;
		}
		$modifiers = str_replace( array('\\\'', '\"'), array('_&onequotes&_', '_&twoquotes&_'), $modifiers);
		preg_match_all( '/:(\"|\')(.*?)(\"|\')/',  $modifiers, $m);
		foreach( $m[0] as $key => $v){
			$m[2][$key] = str_replace( array('|', ':'), array('_&vertical&_', '_&colon&_'), $m[2][$key]);
			$replace = ':' . $m[1][$key] . $m[2][$key] . $m[3][$key];
			$modifiers = str_replace( $m[0][$key], $replace, $modifiers);
		}
		$modifiers = explode('|', $modifiers);
		if($modifiers){
			foreach($modifiers as $key => $modifier){
				if(empty($modifier)){
					continue;
				}
				
				$params = explode(':', $modifier);
				$fun = array_shift($params);
				//是否为注册的修饰器
				if(array_key_exists( $fun, $this->_tpl_modifiers) ){
					$functionName = $this->_tpl_modifiers[$fun];
				}else{
					//引擎自带的修饰器
					$functionName = 'Modifier_' . ucfirst($fun);
					if( !array_key_exists( $functionName, $this->_tpl_plugins) ){
						$dir = dirname(__FILE__);
						$file = $dir . DS . 'Plugins' . DS . 'Modifier' . DS . ucfirst($fun) . '.php';
						if( !file_exists($file) ){
							$this->_error('Modifier "' . $fun . '" is not registered, Please use registerModifier function to register it.');
						}
						$this->_tpl_plugins[$functionName] = array($functionName, $params );
					}
				}
				
				if( empty($params)){
					$var = $functionName . '(' . $var . ')';
				}else{
					foreach( $params as $k => $item){
						$params[$k] = $this->_parseString($item);
					}
					$params = implode(', ', $params);
					$params = str_replace( array('_&onequotes&_', '_&twoquotes&_'), array('\\\'', '\"'), $params);
					$params = str_replace( array('_&vertical&_', '_&colon&_' ), array('|', ':'), $params);
					$params = $this->_replaceVar($params);
					$var = $functionName . '(' . $var . ',' . $params . ')';
				}				
			}
		}
		return $var;
	}
	
	/*
	private function _parseEchoVar(){
		//编译输出变量语法
		if( preg_match_all('/\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$\}]*)?\s*(\.)?\s*([^=\{\}]*)\s*\}/' , $content , $matchs ) ){
			$size = count( $matchs[0] );
			foreach($matchs[0] as $i => $var){
				if( isset($ref) ){
					unset($ref);
				}
				$var = $matchs[1][$i];
				
				if( !array_key_exists( $var , $this->_tpl_vars ) ) {
					$this->_tpl_vars[$var] = '';
				}
				//解析数组['a']['b'],如果数组键名不存在就以此键名生成一个数组元素
				preg_match_all( '/\[["\']?([0-9a-zA-Z]*)["\']?\]/', $matchs[2][$i], $keys );
				$ref = &$this->_tpl_vars[$var];
				if( !empty($keys[0]) ){
					foreach( $keys[1] as $k => $val){
						if( !isset($ref[$val]) ){
							$ref[$val] = '';
						}
						$ref = &$ref[$val];
					}
				}
				
				if($matchs[3][$i] == '.'){
					$arrays = explode('.', trim($matchs[4][$i]));
					$replace = '<?php echo $this->_vars["' . $var  . '"]' . trim($matchs[2][$i]);
					foreach($arrays as $k => $v){
						$v = trim($v);
						if(!empty($v)){
							$firstChar = substr($v, 0, 1);
							if( ($firstChar == '$') ){
								//连接变量
								$tmpVar = substr($v, 1);
								if( !array_key_exists( $tmpVar , $this->_tpl_vars ) ) {
									$this->_tpl_vars[$tmpVar] = '';
								}
								unset($ref);
								$ref = &$this->_tpl_vars[$tmpVar];
								$replace .= ' . $this->_vars["' . trim($tmpVar) . '"]';
							}else if( ($firstChar == "'") || ($firstChar == '"') ){
								//连接字符串
								$replace .= ' . ' . $v;
								unset($ref);
							}else{
								//数组
								if( !isset( $ref[$v] ) ) {
									$ref[$v] = '';
								}
								$ref = &$ref[$v];
								$replace .= '["' . $v . '"]';
							}
						}
					}
					$replace .= '; ?>';
				}else{
					$replace = '<?php echo $this->_vars["' . $var  . '"]' . $matchs[2][$i] . trim($matchs[4][$i]) . '; ?>';
				}
				$content = str_replace( $matchs[0][$i], $replace, $content ); 
			}
		}
		var_dump($this->_tpl_vars);
	}
	*/

	/**
	 * 解析模板中的基本标记语法 ,例如：if else while等
	 * @return void
	 */
	private function _parseTags($content){
		// 解析结束字符和其它字符
		$patterns	= array( 
			'/' . $this->left_delimiter . '\s*\/if\s*' . $this->right_delimiter . '/i' , 
			'/' . $this->left_delimiter . '\s*\/while\s*' . $this->right_delimiter . '/i',  
			'/' . $this->left_delimiter . '\s*else\s*' . $this->right_delimiter . '/i' , 
			'/' . $this->left_delimiter . '\s*\/loop\s*' . $this->right_delimiter . '/i' ,
			'/' . $this->left_delimiter . '\s*\/foreach\s*' . $this->right_delimiter . '/i' ,  
			'/' . $this->left_delimiter . '\s*continue\s*' . $this->right_delimiter . '/i' , 
			'/' . $this->left_delimiter . '\s*break\s*' . $this->right_delimiter . '/i'  ,
			'/' . $this->left_delimiter . '\s*\/switch\s*' . $this->right_delimiter . '/i' ,
		) ;
		$replaces	= array( 
			'<?php } ?>' , 
			'<?php } ?>' , 
			'<?php }else{ ?>' , 
			'<?php }} ?>' ,
			'<?php $this->_vars["vo"]["foreach"]["index"]++; } $this->_vars["vo"]["foreach"]["index"] = 0; } ?>' ,   
			'<?php continue; ?>' , 
			'<?php break; ?>',
			'<?php } ?>'  ,
		) ;
		$content = preg_replace( $patterns , $replaces , $content ) ;
		
		
		$patterns	= array( 
			'_&lttg&_' , 
			'_&lttgr&_' ,  
			'_&ls&_' , 
			'_&rs&_' , 
			'_&sr&_' , 
			'_&sctpp&_' , 
			'_&sctppr&_' , 
			'_&srr&_' 
		) ;
		$replaces	= array( 
			'<?php echo \'<?\'; ?>' , 
			'<?' ,
			stripslashes($this->left_delimiter) , 
			stripslashes($this->right_delimiter) ,
			'\\\'' , 
			'<?php echo "<script language=\"php\">"; ?>' , 
			'<script language="php">' , 
			'\\\''
		) ;
		$content  = str_replace( $patterns , $replaces , $content ) ; 
		return $content;
	}
	
	/**
	 * 解析模板文件
	 * @param string $content
	 */
	private function _parse($content){
		$content = $this->_parseIncludeTpl($content);

		$lft = stripslashes($this->left_delimiter);
		$lft = addcslashes($lft, $lft);
		$rgt = stripslashes($this->right_delimiter);
		$rgt = addcslashes($rgt, $rgt);
		$content = str_replace( array( '<?' , $lft , $rgt , "\\\'" ) , array( '_&lttg&_' , '_&ls&_' , '_&rs&_' , '_&sr&_'  ) , $content );
		$content = preg_replace( '/<script\s+language\s*=\s*(\'|")php(\'|")\s*>/i' , '_&sctpp&_' , $content ) ;
		
		$content = $this->_parseIncludePhp($content);
		
		$content = $this->_parseLiteral($content);
		
		$content = $this->_parsePhpTag($content);
		
		$content = $this->_parseSystemVar($content);
		
		//解析模板注释语法
		$content = preg_replace( array('/' . $this->left_delimiter . '\s*#/', '/#\s*' . $this->right_delimiter . '/'), array('<?php /**', '*/ ?>'), $content );
		
		$content = $this->_parseAutoIncrease($content);
		
		$content = $this->_parseFunction($content);
		
		$content = $this->_parseForeach($content);
	
		$content = $this->_parseSetVar($content);

		$content = $this->_parseWhile($content);
		
		$content = $this->_parseIf($content);
	
		$content = $this->_parseEchoVar($content);

		$content = $this->_parseTags($content);
		
		$pluginFile = '<?php include \'' .  dirname(__FILE__) . DS . 'Plugins' .  DS . 'Plugins.php\'; ?>' . PHP_EOL;
		$plugins = var_export($this->_tpl_plugins, true);
		$content = $pluginFile . '<?php loadPlugins('. $plugins . ') ?>' . PHP_EOL . $content;

		return $content;
	}
	
	/**
	 * 解析字符串
	 * @param string $string
	 */
	private function _parseString( $string ){
		$value = trim($string) ;
		//如果是数字
		if ( is_numeric( $value ) ){
			return $value;
		}

		//标记解析
		$value = str_replace( array( '_&lttg&_', '_&sctpp&_', '_&sr&_' ), array('_&lttgr&_', '_&sctppr&_', '_&srr&_' ), $value );
		//如果是字符串
		if ( preg_match('/^\'([^\']*)\'$/' , $value ) || preg_match('/^\"([^\']*)\"$/' , $value ) ){
			return $value;
		}
		$temp = array();
		$cut_value = $value;
		if( preg_match_all('/\'([^\']*)\'/' , $cut_value , $matchs ) ){
			$size = count( $matchs[0] );               
			for( $i = 0; $i < $size; $i++ ){
				$temp[$i]['rpl']  = '\''. md5( $matchs[0][$i] ) . '\'';
				$temp[$i]['org']  = $matchs[0][$i] ;
				$cut_value        = str_replace( $temp[$i]['org'] , $temp[$i]['rpl'] , $cut_value ) ;
			}
		}
		
		// 检查文本中的函数是否存在
		if( preg_match_all('/([a-zA-Z_][\w]*)\s*\(/' , $cut_value , $matchs ) ){
			//var_dump($matchs);
			foreach( $matchs[1] as $i => $val ){
				if( strtolower($val) == 'array' ){
					continue;
				}
				$functionNmae = trim( $matchs[1][$i] ) ; //function name
				if ( !$this->_isFunctionExist( $functionNmae ) ){
					$this->_error( 'VOTPL Error:Can not call to unregistered function ' . $functionNmae . '() , please use Votpl::registerFunction() to register a function.' ) ;
				}
				$fun = $this->_getFunction(strtolower($functionNmae));
				$replace = $fun . '(';
				$cut_value = str_replace( $matchs[0][$i], $replace, $cut_value ) ; 
			}
		}
		//echo '<br />'.$cut_value .'<br />';
		//变量文本解析
		if ( preg_match_all( '/\$([a-zA-Z_][\w]*)([^ =\.\$]*)?(\s*\.\s*)?([^=><\|&\+\-\$]*)/' , $cut_value , $matchs ) ){
			$size = count( $matchs[0] );
			//echo '<pre>';
			//var_dump($matchs);
			for( $i = 0 ; $i < $size ; $i++ ){
				if( trim($matchs[3][$i]) == '.'){
					$arrays = explode('.', trim($matchs[4][$i]));
					$replace = '$'.trim($matchs[1][$i]) . trim($matchs[2][$i]);
					foreach($arrays as $k => $v){
						if( empty($v) ){
							continue;
						}
						if( !preg_match('/[\w_]/', $v) ){ //解析方法
							continue;
						}else{
							if(is_numeric($v)){
								$replace .= '[' . trim($v) . ']';
							}else{
								$replace .= '[\'' . trim($v) . '\']';
							}
						}
					}
				}else{
					$replace = '$'.trim($matchs[1][$i]) . trim($matchs[2][$i]). trim($matchs[4][$i]);
				}
				$cut_value = str_replace( $matchs[0][$i] , $replace, $cut_value );
			}
		}

		if( ! empty( $temp ) ){
			$size  = count($temp) ;
			$value = $cut_value ;
			foreach ( $temp as $tmp ){
				$value = str_replace( $tmp['rpl'] , $tmp['org'] , $value ) ;
			}
			return $value ;
		}
		return $cut_value ;
	}

	/**
	 * 检查函数是否存在
	 * @param string $functionName
	 * @return boolean
 	 */
	public function _isFunctionExist ( $functionName ){
		if($this->isAllownPhpFunction && function_exists($functionName)){
			return true;
		}else if( array_key_exists( strtolower( $functionName ) , $this->_tpl_functions ) ){
			return true;
		}else{
			$dir = dirname(__FILE__);
			$file = $dir . DS . 'Plugins' . DS . 'Function' . DS . ucfirst($functionName) . '.php';
			if( file_exists($file) ){			
				return true;
			}else{
				return false;
			}
		}
	}

	/**
	 * 获取已经注册的函数
	 * @param string $functionName
	 */
	private function _getFunction( $functionName ){
		$functionName = strtolower($functionName);
		if($this->_isFunctionExist($functionName)){
			if($this->isAllownPhpFunction && function_exists($functionName)){ //PHP系统函数
				return $functionName;
			}else if( array_key_exists(strtolower( $functionName ), $this->_tpl_functions) && is_array( @$this->_tpl_functions[$functionName] ) ){ //类方法
				$classname = 'class_' . substr(md5($this->_tpl_functions[$functionName][0]), 0, 8);
				if(!array_key_exists($classname, $this->_tpl_vars)){
					$this->_tpl_vars[$classname] = new $this->_tpl_functions[$functionName][0];
				}
				return '$this->_vars[\'' . $classname . '\']->' . $this->_tpl_functions[$functionName][1];
			}else if( array_key_exists( strtolower( $functionName ), $this->_tpl_functions )) {
				return $this->_tpl_functions[$functionName];
			}else{
				$dir = dirname(__FILE__);
				$file = $dir . DS . 'Plugins' . DS . 'Function' . DS . ucfirst($functionName) . '.php';
				if( !file_exists($file) ){
					$this->_error('Function "' . $functionName . '" is not registered, Please use "registerFunction" function to register it.');
				}
				$fun = 'Function_' . $functionName;
				$this->_tpl_plugins[$fun] = array($fun);
				return 'Function_' . ucfirst($functionName);
			}
		}else{
			
		}
	}
	
	/**
	 * 得到替换后的函数,将"$a"替换成$this->_vars['a']
	 * @param string $var
	 */
	private function _replaceVar($var){
		if( preg_match_all( $this->_regx_var, $var, $matchs )){
			foreach( $matchs[0] as $k => $val){
				$replace = '$this->_vars["' . $matchs[1][$k] . '"]' . $matchs[2][$k];
				$var = str_replace( $matchs[0][$k], $replace, $var );
			}
		}
		return $var;
	}
	/**
	 * 输出错误提示
	 * @param string $error
	 * @return	void
	 */
	private function _error($error){
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		$html  = '<div style="width:500px;height:200px; margin:50px auto; border:1px solid #dddddd;">';
		$html .= '   <div style="width:480px;height:15px; font-size:14px; font-weight:bold;color:red; padding:10px; border-bottom:1px solid #dddddd; background:#F3F3F3;">VOTPL: ERROR MESSAGE</div>';
		$html .= '   <div style="width:480px;height:auto; font-size:12px; color:#333333; padding:10px;">' . $error . '</div>';
		$html .= '</div>';
		echo $html;
		exit;        	
	}
}