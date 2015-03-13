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
class VO_Image_Imagick extends VO_Image_Abstract{
	
	/**
	 * 文件类型定义,并指出了输出图片的函数
	 */
    private $_allow_type = array(
        'jpg'  => array( 'output' => 'imagejpeg' ),
        'gif'  => array( 'output' => 'imagegif' ),
        'png'  => array( 'output' => 'imagepng' ),
        'wbmp' => array( 'output' => 'image2wbmp' ),
        'jpeg' => array( 'output' => 'imagejpeg' )
	);

	/**
	 * imagicK扩展句柄
	 */
	private $_imagick = null;

	/**
	 * 当前文件的图片类型
	 */
	private $_image_type = null;
	
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		
	}
	
  /**
	 * 析构函数
	 */
	public function __destruct()
	{
	    if($this->_imagick !==null ){
          $this->_imagick->destroy();
	    } 
	}	
	
	/**
	 * 获取单一实例
	 * @return VO_Image_Gd
	 * @return null
	 */
	public static function getInstance(){
		static $instance = null;
		if( !$instance instanceof VO_Image_Imagick ){
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
		$this->_imagick = new Imagick($source);
		$this->_image_type = strtolower($this->_imagick->getImageFormat());
		//var_dump($source);
		//var_dump($this->_image_type);
		if(!file_exists($source)){
			$this->triggerError('文件[' . $source . ']不存在!');
           	exit;
		}
		if( $this->isAllowImage($this->_image_type) ){
			//创建目标文件夹
			$dir = dirname($target);
			if( !VO_Filesystem_Folder::exists($dir) ){
				VO_Filesystem_Folder::create($dir, 0777);
			}
		}else{
			$this->triggerError('非法的文件类型!');
           	exit;
		}
	}

	/**
     * 检查图片类型是否合法
     * @param string $img_type 文件类型
     * @return boolean
     */
	public function isAllowImage($type){
        if( !array_key_exists($type, $this->_allow_type) ){
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
        return $this->_image_type;
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
		if($width==null) $width = $this->_imagick->getImageWidth()-$x;
		if($height==null) $height = $this->_imagick->getImageHeight()-$y;
		if($width<=0 || $height<=0) return;
		if($this->_image_type == 'gif'){
			$image = $this->_imagick;
			$canvas = new Imagick();
			$images = $image->coalesceImages();
			foreach($images as $frame){
				$img = new Imagick();
				$img->readImageBlob($frame);
				$img->cropImage($width, $height, $x, $y);
				 
				$canvas->addImage( $img );
				$canvas->setImageDelay( $img->getImageDelay() );
				$canvas->setImagePage($width, $height, 0, 0);
			}
			$image->destroy();
			$this->_imagick = $canvas;
		}else{
			$this->_imagick->cropImage($width, $height, $x, $y);
		}
		$this->_saveFile($target);
	    return $target;
	}
	
	/**
	 * 生成指定大小的缩略图
	 * @param $source  源图片位置
	 * @param $dest 缩略图片的目标地址
	 * @param int $width 缩略图片宽度
	 * @param int $height 缩略图片高度
	 * @param $fit: 适应大小方式
	 * 	'force': 把图片强制变形成 $width X $height 大小
	 * 	'scale': 按比例在安全框 $width X $height 内缩放图片, 输出缩放后图像大小 不完全等于 $width X $height
	 * 	'scale_fill': 按比例在安全框 $width X $height 内缩放图片，安全框内没有像素的地方填充色, 使用此参数时可设置背景填充色 $bg_color = array(255,255,255)(红,绿,蓝, 透明度) 透明度(0不透明-127完全透明))
	 * 	 其它: 智能模能 缩放图像并载取图像的中间部分 $width X $height 像素大小
	 *   $fit = 'force','scale','scale_fill' 时： 输出完整图像
	 *   $fit = 图像方位值 时, 输出指定位置部分图像 
	 *   字母与图像的对应关系如下:
	 *   north_west   north   north_east
	 *   west         center        east
	 *   south_west   south   south_east
	 * @param $fill_color: 图像填充颜色
	 */
	public function resizeImage($source, $target, $width=10, $height=10, $fit='center', $fill_color=array(255,255,255,0)){
		$this->init($source, $target);
		if(empty($height)){
			$img_width = $this->_imagick->getImageWidth();  
            $img_height = $this->_imagick->getImageHeight();  
            $height = $img_height*($width/$img_width);  
		}
		switch($fit){
	        case 'force':
        	    if($this->_image_type=='gif'){
        	        $image = $this->_imagick;
        	        $canvas = new Imagick();
        	        
        	        $images = $image->coalesceImages();
            	    foreach($images as $frame){
            	        $img = new Imagick();
            	        $img->readImageBlob($frame);
                        $img->thumbnailImage( $width, $height, false );
 
                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                    }
                    $image->destroy();
	                $this->_imagick = $canvas;
        	    }else{
        	        $this->_imagick->thumbnailImage( $width, $height, false );
        	    }
	            break;
	        case 'scale':
	            if($this->_image_type=='gif'){
        	        $image = $this->_imagick;
        	        $images = $image->coalesceImages();
        	        $canvas = new Imagick();
            	    foreach($images as $frame){
            	        $img = new Imagick();
            	        $img->readImageBlob($frame);
                        $img->thumbnailImage( $width, $height, true );
 
                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                    }
                    $image->destroy();
	                $this->_imagick = $canvas;
        	    }else{
        	        $this->_imagick->thumbnailImage( $width, $height, true );
        	    }
	            break;
	        case 'scale_fill':
	            $size = $this->_imagick->getImagePage(); 
	            $src_width = $size['width'];
	            $src_height = $size['height'];
	            
                $x = 0;
                $y = 0;
                
                $dst_width = $width;
                $dst_height = $height;
 
	    		if($src_width*$height > $src_height*$width){
					$dst_height = intval($width*$src_height/$src_width);
					$y = intval( ($height-$dst_height)/2 );
				}else{
					$dst_width = intval($height*$src_width/$src_height);
					$x = intval( ($width-$dst_width)/2 );
				}
 
                $image = $this->_imagick;
                $canvas = new Imagick();
                
                $color = 'rgba('.$fill_color[0].','.$fill_color[1].','.$fill_color[2].','.$fill_color[3].')';
        	    if($this->_image_type=='gif'){
        	        $images = $image->coalesceImages();
            	    foreach($images as $frame){
            	        $frame->thumbnailImage( $width, $height, true );
 
            	        $draw = new ImagickDraw();
                        $draw->composite($frame->getImageCompose(), $x, $y, $dst_width, $dst_height, $frame);
 
                        $img = new Imagick();
                        $img->newImage($width, $height, $color, 'gif');
                        $img->drawImage($draw);
 
                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                        $canvas->setImagePage($width, $height, 0, 0);
                    }
        	    }else{
        	        $image->thumbnailImage( $width, $height, true );
        	        
        	        $draw = new ImagickDraw();
                    $draw->composite($image->getImageCompose(), $x, $y, $dst_width, $dst_height, $image);
                    
        	        $canvas->newImage($width, $height, $color, $this->get_type() );
                    $canvas->drawImage($draw);
                    $canvas->setImagePage($width, $height, 0, 0);
        	    }
        	    $image->destroy();
	            $this->_imagick = $canvas;
	            break;
			default:
				$size = $this->_imagick->getImagePage(); 
			    $src_width = $size['width'];
	            $src_height = $size['height'];
	            
                $crop_x = 0;
                $crop_y = 0;
                
                $crop_w = $src_width;
                $crop_h = $src_height;
                
	    	    if($src_width*$height > $src_height*$width){
					$crop_w = intval($src_height*$width/$height);
				}else{
				    $crop_h = intval($src_width*$height/$width);
				}
                
			    switch($fit){
			    	case 'north_west':
			    	    $crop_x = 0;
			    	    $crop_y = 0;
			    	    break;
        			case 'north':
        			    $crop_x = intval( ($src_width-$crop_w)/2 );
        			    $crop_y = 0;
        			    break;
        			case 'north_east':
        			    $crop_x = $src_width-$crop_w;
        			    $crop_y = 0;
        			    break;
        			case 'west':
        			    $crop_x = 0;
        			    $crop_y = intval( ($src_height-$crop_h)/2 );
        			    break;
        			case 'center':
        			    $crop_x = intval( ($src_width-$crop_w)/2 );
        			    $crop_y = intval( ($src_height-$crop_h)/2 );
        			    break;
        			case 'east':
        			    $crop_x = $src_width-$crop_w;
        			    $crop_y = intval( ($src_height-$crop_h)/2 );
        			    break;
        			case 'south_west':
        			    $crop_x = 0;
        			    $crop_y = $src_height-$crop_h;
        			    break;
        			case 'south':
        			    $crop_x = intval( ($src_width-$crop_w)/2 );
        			    $crop_y = $src_height-$crop_h;
        			    break;
        			case 'south_east':
        			    $crop_x = $src_width-$crop_w;
        			    $crop_y = $src_height-$crop_h;
        			    break;
        			default:
        			    $crop_x = intval( ($src_width-$crop_w)/2 );
        			    $crop_y = intval( ($src_height-$crop_h)/2 );
	            }
	            
	            $image = $this->_imagick;
	            $canvas = new Imagick();
	            
	    	    if($this->_image_type=='gif') {
        	        $images = $image->coalesceImages();
            	    foreach($images as $frame){
            	        $img = new Imagick();
            	        $img->readImageBlob($frame);
                        $img->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
                        $img->thumbnailImage( $width, $height, true );
                        
                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                        $canvas->setImagePage($width, $height, 0, 0);
                    }
        	    }else{
        	        $image->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
        	        $image->thumbnailImage( $width, $height, true );
        	        $canvas->addImage( $image );
        	        $canvas->setImagePage($width, $height, 0, 0);
        	    }
        	    $image->destroy();
	            $this->_imagick = $canvas;
	        
	    }
	    $this->_saveFile($target);
	    return $target;
	}

	/**
	 * 添加水印文字
	 * @param string	$source	源图片位置
	 * @param string	$dest	生成水印图的目标地址
	 * @param string	$text	水印文字
	 * @param int 		$x		水印文字X位置
	 * @param int 		$y 		水印文字Y位置
	 * @param int 		$angle  水印文字的角度
	 * @param int 		$style  水印文字的样式
	 * @return 生成水印图后的目标文件路径
	 */
	public function addWaterText($source, $target, $text, $x = 0 , $y = 0, $angle=0, $style=array()){
		$this->init($source, $target);
		$draw = new ImagickDraw();
		if(isset($style['font'])) $draw->setFont($style['font']);
		if(isset($style['font_size'])) $draw->setFontSize($style['font_size']);
		if(isset($style['fill_color'])) $draw->setFillColor($style['fill_color']);
		if(isset($style['under_color'])) $draw->setTextUnderColor($style['under_color']);
		if($this->_image_type == 'gif'){
			foreach($this->_imagick as $frame){
				$frame->annotateImage($draw, $x, $y, $angle, $text);
			}
		}else{
			$this->_imagick->annotateImage($draw, $x, $y, $angle, $text);
		}
		$this->_saveFile($target);
	    return $target;
	}

	/**
	 * 获取当前图片的宽度
	 * @param string $source	图片源地址
	 * @param string $target	图片目标地址
	 */
	public function get_width($source){
		$size = $this->image->getImagePage();
		return $size['width'];
	}
	
	public function get_height(){
		$size = $this->image->getImagePage();
		return $size['height'];
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

	/**
	 * 保存图片到指定路径
	 * @param	bool	$dest_file 	目标文件名(物理全路径)
	 */
    private function _saveFile($dest_file){
        if($this->_image_type == 'gif'){
	        $this->_imagick->writeImages($dest_file, true);
	    }else{
		    $this->_imagick->writeImage($dest_file);
		}
	}

	/**
	 * 保存图片到指定路径
	 * @param	bool	$dest_file 	目标文件名(物理全路径)
	 */
    public function writeFile($dest_file){
        $this->_saveFile($dest_file);
	}	

	/**
	 * 输出图像
	 * @param	bool	$header 	是否输出头信息
	 */
	public function output($header = true){
		if($header){
			header('Content-type: ' . $this->_image_type);
		}
		echo $this->image->getImagesBlob();	
	}		
}