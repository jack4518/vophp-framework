<?php
/**
 * VOPHP 后台文件管理模型
 * 
 * @copyright Copyright (c) 2010 ViSong INC. (http://www.vophp.com)
 * @license	http://www.vophp.com/user_guide/license
 * @link	http://www.vophp.com/
 * @author JackChen
 * @package VO
 * @since version 1.0
 * @date 2010-05-19
 * */
require_once(VO_LIB_DIR .  DS . 'Image' . DS . 'Abstract.php');
class VO_Image_Gd extends VO_Image_Abstract{
	
	// 文件类型定义,并指出了输出图片的函数
    private $_allow_type = array(
        'jpg'  => array( 'output' => 'imagejpeg' ),
        'gif'  => array( 'output' => 'imagegif' ),
        'png'  => array( 'output' => 'imagepng' ),
        'wbmp' => array( 'output' => 'image2wbmp' ),
        'jpeg' => array( 'output' => 'imagejpeg' )
	);
	
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
	/**
	 * 获取单一实例
	 * @return VO_Image_Gd
	 * @return null
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Image_Gd ){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 初始化检测图片
	 * @param string $source  源图片路径
	 * @param string $target　目标图片路径
	 */
	public function init($source, $target ){
		$type = $this->getImageType($source);
		if( $this->isAllowImage($type) ){
			//创建目标文件夹
			$dir = dirname($target);
			if( !VO_Filesystem_Folder::exists($dir) ){
				VO_Filesystem_Folder::create($dir, 0777);
				//chmod($dir, 0777);
			}
		}else{
			$this->triggerError('非法的文件类型!');
           	exit;
		}
	}
	
    //初始化图象
    public function getIm($source){
    	$im = '';
    	$type = $this->getImageType($source);
		switch($type){
			case 'jpg':
			case 'jpeg': $im = imagecreatefromjpeg($source); break;
			case 'gif': $im = imagecreatefromgif($source);   break;
			case 'png': $im = imagecreatefrompng($source);   break;
		}
		return $im;
    }
	
	/**
     * 检查图片类型是否合法
     * @param string $img_type 文件类型
     * @return boolean
     */
	public function isAllowImage($imageType){
        if( !array_key_exists($imageType, $this->_allow_type) ){
            return false;
        }
        return true;
	}
	
	/**
     * 取得图片类型
     * @param string $file_path 文件路径
     * @return string  图片文件的类型
     */
    public function getImageType($file_path){
        $typeList = array(
        	'1' => 'gif',
        	'2' => 'jpg',
        	'3' => 'png',
        	'4' => 'swf',
        	'5' => 'psd',
        	'6' => 'bmp',
        	'15' => 'wbmp'
        );
		$imgInfo = @getimagesize( $file_path );
		$type = $imgInfo[2];
		if( isset($typeList[$type]) ){
			return $typeList[$type];
		}
	}
	
	/**
	 * 从指定位置截取图片
	 * @param $source  源图片位置
	 * @param $dest 截取后图片的目标地址
	 * @param int $width 截取图片宽度
	 * @param int $height 截取图片高度
	 * @param int $x  截取图片X坐标
	 * @param int $y  截取图片y坐标
	 */
	public function cropImage($source, $target, $width=10, $height=10, $x=0, $y=0){
		$this->init($source, $target);
		$type = $this->getImageType($source);
		$im = $this->getIm($source);
		$newImage = imagecreatetruecolor( $width, $height);
		list($sourceWidth,$sourceHeight)=getimagesize($source);
		if( function_exists('imagecopyresampled') ){
			imagecopyresampled( $newImage, $im, 0, 0, $x, $y, $sourceWidth, $sourceHeight, $sourceWidth, $sourceHeight);
		}else{
			imagecopyresized( $newImage, $im, 0, 0, $x, $y, $sourceWidth, $sourceHeight, $sourceWidth, $sourceHeight);
		}
		ImageJpeg ( $newImage, $target);
		imagedestroy($newImage);
		imagedestroy($im);
	}
	
	/**
	 * 生成指定大小的缩略图
	 * @param $source  源图片位置
	 * @param $dest 缩略图片的目标地址
	 * @param int $width 缩略图片宽度
	 * @param int $height 缩略图片高度
	 */
	public function resizeImage($source, $target, $width=10, $height=10){
		$this->init($source, $target);
		list($sourceWidth,$sourceHeight)=getimagesize($source);
		$result = $this->getImageResize($sourceWidth, $sourceHeight, $width, $height);;
		$newWidth = $result['width'];
		$newHeight = $result['height'];
		$newImage = imagecreatetruecolor( $newWidth, $newHeight);
		$im = $this->getIm($source);
		if( function_exists('imagecopyresampled') ){
			imagecopyresampled( $newImage, $im, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
		}else{
			imagecopyresized( $newImage, $im, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
		}
		ImageJpeg ( $newImage, $target);
		imagedestroy($newImage);
		imagedestroy($im);
	}
	
	/**
	 * 将CMYK颜色的图片转换成RGB颜色的图片
	 * @param string $source	图片源地址
	 * @param string $target	图片目标地址
	 */
	public function cmykToRgb($source, $target=''){
		if(false == extension_loaded('imagick')){
			return 'imagick extension is disable';
		}else{
			$imk = new Imagick($source);
			$color_space = $imk->getimagecolorspace();
			if($color_space == Imagick::COLORSPACE_CMYK){
				$ext = substr($source, strrpos($source, '.')+1);
				$format = 'jpeg';
				if(!empty($ext)){
					switch(strtoupper($ext)){
						case 'JPG' :
						case 'JPEG' :
							$format = 'jpeg';
							break;
						case 'PNG' :
							$format = 'png';
							break;
					}
				}
				if(empty($target)){
					$target = $source;
				}
				$imk->setImageColorspace(Imagick::COLORSPACE_SRGB);
				$imk->setImageFormat($format);
			    $imk->writeImage($target);
			}else{
				return 'image ' . $source . ' is not CMYK color';
			}
		}
	}
}
