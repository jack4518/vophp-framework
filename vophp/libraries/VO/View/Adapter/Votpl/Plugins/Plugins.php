<?php
/**
 * 加载模板插件
 */
//include 'D:\www\gosion\vophp\libraries\VO\View\Adapter\Votpl\Plugins\Modifier\Truncate.php';
function loadPlugins($plugins=array()){
  if($plugins){
    foreach($plugins as $plugin){
    	$arr = explode('_', $plugin[0]);
    	$type = ucfirst(strtolower($arr[0]));
    	$func = ucfirst(strtolower($arr[1]));
	    $file = dirname(__FILE__) . DS . $type . DS . $func . '.php';
	    if(file_exists($file)){
	    	include_once $file;
	    }else{
	    	echo 'Plugin:' . $file . ' is not exists.';
	    	exit;
	    }
    }
  }
}