<?php
function myfunc( $string = '' )
{
    if ( $string ) { echo $string; }
    else{ echo 'No Param'; }
}

include ( 'lib/HET.class.php' ) ;

$page = new HET ;

$page->fn ( array( 'date' , 'myfunc' , 'number_format' , 'include' ) ) ; // ע�ắ��������ģ���е���

$data = array() ;
$data['title'] = 'HET Demo' ;
$data['_REQUEST'] = $_REQUEST ; // ��_REQUEST��ֵ����ģ�����ʹ��$_REQUEST

$data['students'] = array (
    array ( 'name' => 'Joey' , 'age' => 12 , 'score' => array( 
                                                 array ( 'subject' => 'Math' , 'score' => 'B' ) , 
                                                 array ( 'subject' => 'English' , 'score' => 'A' ) ,  
                                                 array ( 'subject' => 'Physical ' , 'score' => 'B+' ) , 
                                                 ) ) ,
    array ( 'name' => 'Mark' , 'age' => 11 , 'score' => array( 
                                                 array ( 'subject' => 'Math' , 'score' => 'A+' ) , 
                                                 array ( 'subject' => 'English' , 'score' => 'A' ) , 
                                                 array ( 'subject' => 'Physical ' , 'score' => 'B' ) ,   
                                                 ) ) ,
    array ( 'name' => 'David' , 'age' => 13 , 'score' => array( 
                                                 array ( 'subject' => 'Math' , 'score' => 'B+' ) , 
                                                 array ( 'subject' => 'English' , 'score' => 'B' ) , 
                                                 array ( 'subject' => 'Physical ' , 'score' => 'A+' ) ,   
                                                 ) ) ,

    ) ;

$page->out( 'templates/demo.html' , $data ) ;
?>