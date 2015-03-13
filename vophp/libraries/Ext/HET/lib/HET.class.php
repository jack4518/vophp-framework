<?php
/**
 * HET: the light&fast PHP compiling template engine
 * 
 * @author  : Hellex <Hellex@live.com>
 * @touch   : 22.08.2003
 * @link    : http://www.hellex.cn
 * @version : 2.2.0 (Require PHP version 4.3.0 or later)
 *
 * Usage Example I :
 *
 * $page = new HET ;
 * $page->out( 'templates/template.html' ) ;
 * 
 * Usage Example II :
 *
 * $page = new HET ;
 * $page->compile_dir = 'templates_c/usage' ;
 * $data = array( 'abc' => 'I am ABC' , 'hello' => 'I am Hello' ) ;
 * $page->out( 'templates/template.html' , $data ) ;
 * 
 * Usage Example III :
 *
 * $page = new HET ;
 * $data = array( 'abc' => 'I am ABC' , 'hello' => 'I am Hello' ) ;
 * $page->out( 'templates/template.html' , $data , 'templates_c/usage' ) ;
 *
 * Usage Example IV :
 *
 * $page = new HET ;
 * $page->cache_lifetime = 3600 ;
 * $page->use_cache() ;
 * $data = array( 'abc' => 'I am ABC' , 'hello' => 'I am Hello' ) ;
 * $page->out( 'templates/template.html' , $data ) ;
 *
 */
if ( ! class_exists ( 'HET' ) )
{
    class HET
    {
        /**
         * @public string  
         * Compiled templates directory(contains path)
         */
        var $compile_dir = 'templates_c' ; 
        var $single_dir  = false ;
        
        /**
         * @public string  
         * Compiled templates postfix
         */
        var $postfix = '.php' ; 
        
        /**
         * @public int
         * cache life time
         */
        var $cache_lifetime = 1800 ;
        
        /**
         * @public string
         * cache dir(contains path)
         */
        var $cache_dir = 'templates_c' ;
        
        /**
         * @private string
         * cache filename 
         */ 
        var $_cache_file  ;
        
        /**
         * @public string  
         * Compiled template postfix
         */
        var $cache_postfix = '.cache' ; 
        
        /**
         * @public Uncache buffering
         *
         * @var boolean
         */
        var $caching    = true ;       
        var $cache_safe = true ;
        
        /**
         * @public boolean
         * Alert on error exists 
         */
        var $error_report = true ; 
        var $error        = '' ;
        
        /**
         * @public boolean
         * Forces templates to compile 
         */  
        var $fc = false ;
        
        /**
         * @private class
         * HET Compiler 
         */ 
        var $_htc ;
        
        /**
         * @protected array
         * Functions
         */ 
        var $_functions = array() ; 
        
        /**
         * @protected array
         * data
         */ 
        var $_data = array() ; 
               
        /**
         * Constructor , do nothing
         *
         * @return void
         */
        function HET(){}
        
        /**
         * Output data
         *
         * @param string    $template
         * @param array     $data
         * @param string    $compile_dir
         * @return void
         */
        function out ( $template , $data = array() , $compile_dir = '' )
        {
            $this->_data  =& $data ;
            $template_c   =  $this->_get_template_c_filename ( $template , $compile_dir ) ;

            if ( ! file_exists ( $template_c ) || $this->fc || filemtime( $template ) > filemtime( $template_c ) )
                $this->_compile( $template , $template_c ) ;
                
            // output
            include $template_c ; 
        }
        
        function _get_template_c_filename ( $template , $compile_dir ) 
        {
            ! file_exists( $template ) 
              && $this->_error ( 'HET->out():' . $template . ' does not exist.' ) ;  
            
            ( $compile_dir ) && $this->compile_dir = $compile_dir ;
            $basename = basename( $template ) ; 
            
            $compile_dir = ! $this->single_dir ? $this->compile_dir . '/' . strlen( $_SERVER['SCRIPT_FILENAME'] ) . '/' . strlen( $basename ) . '/' : $this->compile_dir . '/' ;
            $template_c  = $compile_dir . $basename . md5( $_SERVER['SCRIPT_FILENAME'] ) . $this->postfix  ;
            return $template_c ;  
        }
        
        /**
         * @public - Output Content Buffering
         *
         * Usage Example:
         * $page = new HET ;
         * $page->use_cache() ; // follow above
         * ...
         *
         * Usage Example II:
         * $page = new HET ;
         * $page->use_cache( array( 'id' , 'page' ) ) ; // accept $_REQUEST['id'] , $_REQUEST['page']
         *  ...
         *
         * @param array    $accept_key  - accept $_REQUEST variables
         */
        function use_cache ( $accept_key = array() )
        {
            $this->_cache_file = $this->_get_cache_filename( $accept_key ) ;

            if ( file_exists( $this->_cache_file ) && time() - filemtime( $this->_cache_file ) < $this->cache_lifetime ) 
                exit( readfile( $this->_cache_file ) ) ;
                
            ob_start( array( &$this , 'create_cache' ) ) ;
        }
        
        function _get_cache_filename ( $accept_key )
        {
            ! file_exists( $this->cache_dir ) && $this->_mkdir ( $this->cache_dir ) ;
            ! ( $realpath = realpath ( $this->cache_dir ) )
              && $this->_error( 'HET->use_cache():' . $this->cache_dir . '/' . ' does not exist.' ) ;
            
            if ( $this->cache_safe )
            {
                $script_filename = $_SERVER['SCRIPT_FILENAME'] ;
                if ( ! empty ( $accept_key ) ) 
                {
                    foreach( $accept_key as $key )
                        if ( $key && isset( $_REQUEST[$key] ) ) $script_filename .= '|' . $key . '=' . $_REQUEST[$key] ;
                }
            }
            else
            {
                $script_filename = $_SERVER['SCRIPT_FILENAME'] . $_SERVER['QUERY_STRING'] ;
            }
            
            $cache_dir = ! $this->single_dir ? $realpath . '/' . strlen( $script_filename ) . '/' . strlen( basename( $script_filename ) ) . '/' : $realpath . '/' ;
            ! file_exists( $cache_dir ) && $this->_mkdir ( $cache_dir ) ;
            
            return $cache_dir . md5 ( $script_filename ) . $this->cache_postfix ; 
        }
        
        
        /**
         * @public - create cache
         *
         * @param  string    $buffer
         * @return string
         */
        function create_cache ( $buffer )
        {
            if ( ! $this->caching ) return $buffer ;             
            if ( $handle = fopen( $this->_cache_file , 'w' ) )
            {
                fwrite( $handle ,  $buffer ) ;
                fclose( $handle ) ;
            }
            return $buffer ;
        }
        
        /**
         * @public - register functions
         *
         * @param string/array    $fn
         * @return void
         */
        function fn ( $fn )
        {
            if ( $fn )
                is_array( $fn )
                ? $this->_functions = array_merge( $this->_functions , array_change_key_case( array_flip( $fn ) ) )
                : $this->_functions[strtolower($fn)] = '' ;
        }
        
        /**
         * @public - Returns Template Output
         *
         * @param string    $template
         * @param array     $data
         * @param string    $compile_dir
         * @return string
         */
        function result ( $template , $data = array() , $compile_dir = '' )
        {
            ob_start() ;
            $this->out( $template , $data , $compile_dir ) ;
            $content  =  ob_get_contents() ;
            ob_end_clean() ;
            return $content ;
        }
        
        /**
         * @private - Compiles template
         *
         * @param string    $template
         * @param string    $template_c
         * @return boolean
         */
        function _compile ( $template , $template_c  )
        {
            if ( ! is_object( $this->_htc ) )
            {
                ! class_exists( 'HETCompiler' ) &&  include 'HETCompiler.class.php' ;
                $this->_htc = new HETCompiler ;
            }
            return $this->_htc->compile( $template , $template_c , $this->_data , $this->_functions ) ;
        }
		
        /**
         * @protected - make dir
         * 
         * @param string    $dir
         * @return boolean
         */
        function _mkdir ( $dir )
        {
            if ( is_dir( $dir ) ) return true ;
            if ( ! is_object( $this->_htc ) )
            {
                ! class_exists( 'HETCompiler' )  && include 'HETCompiler.class.php' ;
                $this->_htc = new HETCompiler ;
            }
            return $this->_htc->_make_dir( $dir ) ;
        }
        
        /**
         * @protected - error reporting
         * 
         * @param string    $error_msg
         * @return boolean
         */
        function _error ( $error_msg )
        {
            $this->error_report ? exit( $error_msg ) : $this->error = $error_msg ;
        }
    }
}