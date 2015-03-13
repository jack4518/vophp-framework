<?php
/**
 * HETCompiler : The Compiler of HET
 *
 * @author  : Hellex <Hellex@live.com>
 * @touch   : 22.08.2003
 * @link    : http://www.hellex.cn
 * @version : 2.2.0 (Require PHP version 4.3.0 or later)
 */

if ( ! class_exists( 'HETCompiler' ) )
{ 
    class HETCompiler extends HET 
    {   
        var $_c_data       ;   // refer to $this->_data
        var $_c_functions  ;   // refer to $this->_functions
        
        // special language construct
        var $_slc = 'include|include_once|require|require_once|global|clone|function|die|exit' ; 
        
        // Constructor , do nothing
        // @return void
        function HETCompiler(){}
        
        function compile( $template , $template_c , &$data , &$functions )
        {
            $this->_check_env() ; 
            
            $tpl_content = file_get_contents ( $template ) ;
            if ( empty( $tpl_content ) ) return;
            
            $this->_make_dir ( dirname( $template_c ) ) ;
            $this->_c_data       =& $data ;
            $this->_c_functions  =& $functions ;
            
            $tpl_content = $this->_parse( $tpl_content ) ;             
            return $this->create_file( $template_c ,  $tpl_content ) ;
        }
        
        function _parse ( $tpl_content ) 
        {
            // tags
            $tpl_content = preg_replace( '/<script +language *= *(\'|")php(\'|") *>/i' , '_&sctpp&_' , 
                           str_replace( array( '<?' , "\\{" , "\\}" , "\\\'" ) , 
                                        array( '_&lttg&_' , '_&ls&_' , '_&rs&_' , '_&sr&_'  ) , $tpl_content ) ) ;            
            // set
            if ( preg_match_all('/{ *\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$\}]*) *= *([^};]*) *}/', $tpl_content , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    if ( ( $key = trim($matchs[1][$i]) ) != '' )
                    {
                        ! array_key_exists( $key , $this->_c_data ) && $this->_c_data[$key] = '' ; 
                        $tpl_content = str_replace( $matchs[0][$i] , 
                                       '<?php $this->_data[\''.$key.'\']'.trim($matchs[2][$i]).'=' 
                                       . $this->_parse_string($matchs[3][$i]).'; ?>' ,
                                       $tpl_content ) ; 
                    }
                }
            }            
            // loop
            if ( preg_match_all('/{ *loop +\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$\}]*) +\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$]*) *}/i', $tpl_content , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    if ( ( $key = trim($matchs[1][$i]) ) != '' )
                    {
                        ! array_key_exists( $matchs[3][$i] , $this->_c_data ) && $this->_c_data[trim($matchs[3][$i])] = '' ; 
                        $tpl_content = str_replace( $matchs[0][$i] , 
                                       '<?php if(is_array($this->_data[\''.$key.'\']'.trim($matchs[2][$i]).')){'
                                       . ' foreach ($this->_data[\''.$key.'\']'.trim($matchs[2][$i])
                                       . ' as $this->_data[\''.trim($matchs[3][$i]).'\']'.trim($matchs[4][$i]) . ' ) { ?>'
                                       , $tpl_content ) ;
                    }
                }
            }            
            // logic
            if ( preg_match_all('/{ *(if|elseif|while)+([^}]+)}/i', $tpl_content , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    strtolower( $matchs[1][$i] ) == 'elseif' && $matchs[1][$i] = '}elseif' ;
                    $tpl_content = str_replace( $matchs[0][$i] , 
                                   '<?php '. $matchs[1][$i] . '(' . $this->_parse_string($matchs[2][$i]).'){ ?>' , $tpl_content ) ;
                }
            }            
            // function
            if ( preg_match_all('/{ *([a-zA-Z_][a-zA-Z0-9_]*)\(([^}]+)}/', $tpl_content , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    $fn_name = trim( $matchs[1][$i] ) ; 
                    if ( $this->_check_fn( $fn_name ) )
                        $tpl_content = str_replace( $matchs[0][$i] , 
                                       '<?php ' . $matchs[1][$i] . '('
                                       . $this->_parse_string($matchs[2][$i]).'; ?>' ,
                                       $tpl_content ) ; 
                }
            }            
            // var
            if ( preg_match_all('/\{ *\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$\{\}]*) *\}/' , $tpl_content , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    if ( array_key_exists( $matchs[1][$i] , $this->_c_data ) ) 
                        $tpl_content = str_replace( $matchs[0][$i] , 
                                      '<?php echo $this->_data[\''.$matchs[1][$i].'\']'.trim($matchs[2][$i]).'; ?>' ,
                                      $tpl_content ) ;   
                }
            }
                
            // others
            $patterns     = array( '/{ *\/if *}/i' , '/{ *\/while *}/i',  '/{ *else *}/i' , '/{ *\/loop *}/i' , '/{ *continue *}/i' , '/{ *break *}/i'  ) ;
            $replaces     = array( '<?php } ?>' , '<?php } ?>' , '<?php }else{ ?>' , '<?php }} ?>' ,  '<?php continue; ?>' , '<?php break; ?>' ) ;
            $tpl_content  = preg_replace( $patterns , $replaces , $tpl_content ) ;
            $patterns     = array( '_&lttg&_' , '_&lttgr&_' ,  '_&ls&_' , '_&rs&_' , '_&sr&_' , '_&sctpp&_' , '_&sctppr&_' , '_&srr&_' ) ;
            $replaces     = array( '<?php echo \'<?\'; ?>' , '<?' ,'{' , '}' ,'\\\'' , '<?php echo "<script language=\"php\">"; ?>' , '<script language="php">' , '\\\'') ;
            $tpl_content  = str_replace( $patterns , $replaces , $tpl_content ) ;
            
            return $tpl_content ;
        }
        
        function _parse_string( $string )
        {
            $value = trim( $string ) ;
            // numeric || string
            if ( is_numeric( $value ) ) return $value ;
            
            // tag
            $value = str_replace( array( '_&lttg&_' , '_&sctpp&_' , '_&sr&_' ) , array('_&lttgr&_' ,'_&sctppr&_' ,  '_&srr&_' ) , $value ) ;
            if ( preg_match('/^\'([^\']*)\'$/' , $value ) ) return $value ;
            $temp = array() ;
            $cut_value = $value ;
            if ( preg_match_all('/\'([^\']*)\'/' , $cut_value , $matchs ) )
            {
                $size = count( $matchs[0] ) ;               
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    $temp[$i]['rpl']  = '\''. md5( $matchs[0][$i] ) .'\'';
                    $temp[$i]['org']  = $matchs[0][$i] ;
                    $cut_value        = str_replace( $temp[$i]['org'] , $temp[$i]['rpl'] , $cut_value ) ;
                }
            }
            // check function
            if ( preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*) *\(/' , $cut_value , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                {
                    $fn_name = trim( $matchs[1][$i] ) ; //function name
                    if ( !$this->_check_fn( $fn_name ) )
                        $this->_error( 'HET error:Can not call to unregistered function ' . $fn_name . '() , please use HET::fn() to register a function.' ) ; 
                }
            }
            // variable
            if ( preg_match_all( '/\$([a-zA-Z_][a-zA-Z0-9_]*)([^ =\.\$]*)/' , $cut_value , $matchs ) )
            {
                $size = count( $matchs[0] ) ;
                for( $i = 0 ; $i < $size ; $i++ )
                    $cut_value =  str_replace( $matchs[0][$i] , '$this->_data[\''.trim($matchs[1][$i]).'\']'.trim($matchs[2][$i]) , $cut_value ) ;
            }
            // special language construct
            if ( preg_match_all("/($this->_slc) +([^\(])/i" , $cut_value , $matchs ) )
            {            
                $this->_error( 'HET error:Can not use special language construct:' . $matchs[1][0] ) ; 
            }
            if ( ! empty( $temp ) ) 
            {
                $size  = count($temp) ;
                $value = $cut_value ;
                foreach ( $temp as $tmp ) $value = str_replace( $tmp['rpl'] , $tmp['org'] , $value ) ;
                return $value ;
            }
            return $cut_value ;
        }
        
        function _get_existent_dir( $dir , &$existent_dir )
        {
            if ( $dir == '.' || $dir == '/' || $dir == '\\' || file_exists ( $dir ) ) 
            {
                $existent_dir = $dir ;
                return true ;
            }
            $this->_get_existent_dir( dirname( $dir ) , $existent_dir ) ; 
        }
        
        function _check_fn ( $fn_name )
        {
            return array_key_exists( strtolower( $fn_name ) , $this->_c_functions ) ;
        }
        
        function _make_dir ( $dir  )
        {
            $dir = str_replace( '\\' , '/' , $dir ) ; 
            if ( is_dir ( $dir ) ) return true ;
            $this->_get_existent_dir( $dir , $existent_dir ) ; 
            $parsed_dir = ( $existent_dir == '.' && substr( $dir , 0 , 2 ) != './' ) ? './' . $dir : $dir ; 
            $part_dirs  = explode ( '/' , substr( $parsed_dir , strlen( $existent_dir ) ) ) ; 
            $make_dir   = $existent_dir ;
            foreach ( $part_dirs as $part_dir )
            {
                if ( $part_dir != '' )
                {
                    $make_dir .= "/" . $part_dir ; 
                    if ( !is_dir( $make_dir ) )
                    {
                        if ( !@mkdir( $make_dir , 0777 ) )
                            $this->_error ( 'HETCompiler->_make_dir():Can not make dir ' . $make_dir ) ;
                    }
                }
            }
            if ( is_dir( $dir ) )  return true ;
            $this->_error ( 'HETCompiler->_make_dir():interior error.' ) ;
        }

        function create_file ( $file , $content , $mode = 'w' )
        {
            if ( !$handle = fopen( $file , $mode ) ) 
                return false ;
            if ( fwrite( $handle , $content ) === false )
                return false ;
            fclose( $handle );
            return true ;
        }
        
        function _check_env ()
        {
            get_magic_quotes_runtime() == '1' &&  set_magic_quotes_runtime(0) ;
        }
    }
}