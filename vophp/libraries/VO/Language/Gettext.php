<?php
/**
 * 定义VO_Language_Gettext 多语言类
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-05-25
 **/

defined('VOPHP') or die('Restricted access');

VO_Loader::import('Language.Abstract');

class VO_Language_Gettext extends VO_Language_Abstract{
	
	/**
	 * 文件句柄
	 * @var resource
	 */
	private $fp = null;
	
    private $_bigEndian   = false;
    private $_adapterInfo = array();
    private $_data        = array();	
	
	/**
	 * 构造函数
	 * @return	VO_Language_Gettext
	 */
	public function __construct($engin = 'array'){
		parent::init($engin);
	}
	
	/**
	 * 获取单一实例
	 * @return	VO_Language_Gettext
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Language_Gettext ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 载入语言文件
	 * @param string $languageFile	语言文件名称,不包含扩展名
	 * @param string $language	命名空间
	 * @return array
	 */
	public function load( $languageFile, $namespace='default' ){
		if( $namespace === null  ){
			$namespace='default';
		}else{
			$namespace = (string) $namespace;
		}
		
		if( empty($languageFile) ){
			$languageFile = array();
		}
		
		if( is_array($languageFile) ){
			if(empty($this->_language[$namespace])){
				$this->_language[$namespace] = $languageFile;
			}else{
				$this->_language[$namespace] = array_merge($this->_language[$namespace], $languageFile);
			}
			return $this->_language;
		}

		$languageFile = $this->getFile($languageFile, 'gettext');
		$file = $languageFile;
		if( file_exists($file)){
			$this->_data      = array();
	        $this->_bigEndian = false;
	        $this->fp      = @fopen($file, 'rb');
	        if(!$this->fp) {
	        	$error = new VO_Error();
	            $error->error( sprintf( T('Cant\'t open language file：%s', 'VOPHP'), $file) );
	        }
	        if(@filesize($file) < 10) {
	            $error = new VO_Error();
	            $error->error( sprintf( T('"%s" is not a gettext file', 'VOPHP'), $file) );
	        }
	
	        // get Endian
	        $input = $this->_readMOData(1);
	        if (strtolower(substr(dechex($input[1]), -8)) == "950412de") {
	            $this->_bigEndian = false;
	        } else if (strtolower(substr(dechex($input[1]), -8)) == "de120495") {
	            $this->_bigEndian = true;
	        } else {
	            $error = new VO_Error();
	            $error->error( sprintf( T('"%s" is not a gettext file', 'VOPHP'), $file) );
	        }
	        // read revision - not supported for now
	        $input = $this->_readMOData(1);
	
	        // number of bytes
	        $input = $this->_readMOData(1);
	        $total = $input[1];
	
	        // number of original strings
	        $input = $this->_readMOData(1);
	        $OOffset = $input[1];
	
	        // number of translation strings
	        $input = $this->_readMOData(1);
	        $TOffset = $input[1];
	
	        // fill the original table
	        fseek($this->fp, $OOffset);
	        $origtemp = $this->_readMOData(2 * $total);
	        fseek($this->fp, $TOffset);
	        $transtemp = $this->_readMOData(2 * $total);
	
	        for($count = 0; $count < $total; ++$count) {
	            if ($origtemp[$count * 2 + 1] != 0) {
	                fseek($this->fp, $origtemp[$count * 2 + 2]);
	                $original = @fread($this->fp, $origtemp[$count * 2 + 1]);
	                $original = explode(chr(00), $original);
	            } else {
	                $original[0] = '';
	            }
	
	            if ($transtemp[$count * 2 + 1] != 0) {
	                fseek($this->fp, $transtemp[$count * 2 + 2]);
	                $translate = fread($this->fp, $transtemp[$count * 2 + 1]);
	                $translate = explode(chr(00), $translate);
	                if ((count($original) > 1) && (count($translate) > 1)) {
	                    $this->_data[$namespace][$original[0]] = $translate;
	                    array_shift($original);
	                    foreach ($original as $orig) {
	                        $this->_data[$namespace][$orig] = '';
	                    }
	                } else {
	                    $this->_data[$namespace][$original[0]] = $translate[0];
	                }
	            }
	        }
	
	        $this->_data[$namespace][''] = trim($this->_data[$namespace]['']);
	        if (empty($this->_data[$namespace][''])) {
	            $this->_adapterInfo[$file] = 'No adapter information available';
	        } else {
	            $this->_adapterInfo[$file] = $this->_data[$namespace][''];
	        }
	
	        unset($this->_data[$namespace]['']);
	        $this->_language = array_merge($this->_language, $this->_data);
		}else{
			$error = new VO_Error();
			$error->error( sprintf( T('Language file "%s" is not exist', 'VOPHP'), $languageFile) );
		}
		return $this->_language;
	}
	
	/**
	 * 解析MO文件
	 * @param biniary $bytes
	 */
	private function _readMOData($bytes){
        if ($this->_bigEndian === false) {
            return unpack('V' . $bytes, fread($this->fp, 4 * $bytes));
        } else {
            return unpack('N' . $bytes, fread($this->fp, 4 * $bytes));
        }
    }
}