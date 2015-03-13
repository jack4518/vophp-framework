<?php
/**
 * 带运算的验证码
 * @author JackChen
 *
 */
class Ext_Validatecode_Code{

	/**
	 * 构造函数
	 * @return Ext_ValidateCode_Code
	 */
	public function __construct(){
		//session_start();
	}
	/**
	 * 获取单一实例
	 * @return Ext_ValidateCode_Code
	 */
	public static function getInstance(){
		static $instance = null;
		if (! ($instance instanceof Ext_Validatecode_Code) )
			$instance = new self();
		return $instance ;
	}
	
	/**
	 * 获取随机验证码
	 * @param int $length
	 * @param int $mode
	 * @return string
	 */
	public function getCode($length = 32, $mode = 0){
		switch ($mode) {
			case '1':
				$str = '123456789';
			break;
			case '2':
				$str = 'abcdefghijklmnopqrstuvwxyz';
			break;
			case '3':
				$str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			case '4':
				$str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			break;
			case '5':
				$str = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
			break;
			case '6':
				$str = 'abcdefghijklmnopqrstuvwxyz1234567890';
			break;
			default:
				$str = 'ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
			break;
		}
	
		$result = '';
		$l = strlen($str)-1;
		for($i = 0;$i < $length;$i ++){
			$num = rand(0, $l);
			$result .= $str[$num];
		}
		return $result;
	}
	
	
	/**
	 * 生成验证图片
	 * @param string $randStr   验证码字符
	 * @param int $imgW  验证码图片宽度
	 * @param int $imgH  验证码图片高度
	 * @param string $fontName  字体名称
	 */
	public function createAuthNumImg($randStr,$imgW=100,$imgH=40,$fontName){
		header ("content-type: image/png");
		$image = imagecreate($imgW , $imgH);
		
		$color_white = imagecolorallocate($image , 255 , 255 , 255);
		$color_gray  = imagecolorallocate($image , 228 , 228 , 228);
		$color_black = imagecolorallocate($image , 238 , 59 , 118);
		for ($i = 0 ; $i < 1000 ; $i++){
			imagesetpixel($image , mt_rand(0 , $imgW) , mt_rand(0 , $imgH) , $color_gray);
		}
		imagerectangle($image , 0 , 0 , $imgW - 1 , $imgH - 1 , $color_gray);
		for ($i=10;$i<$imgH;$i+=10){
			imageline($image, 0, $i, $imgW, $i, $color_gray);
		}
		//imagettftext($image,16,5,3,25,$color_black,$fontName,$randStr);
		$textcolor = imagecolorallocate($image, 0, 0, 255);


		for($i=1;$i<8;$i++){
		    $linecolor = imagecolorallocate($image,rand(0,255),rand(0,255),rand(0,255));
		    imageline($image,rand(0,60),rand(0,10),rand(10,100),rand(10,30),$linecolor);
		}

		$this->_writeNoise($image, $imgW, $imgH);
		
		// 把字符串写在图像的x为10,Y为5的位置
		imagestring($image, 5, 10, 5, $randStr, $color_black);
		imagepng($image);
		imagedestroy($image);
	}
	
	/**
	 * 画杂点
	 * 往图片上写不同颜色的字母或数字
	 */
	protected function _writeNoise($image, $w, $h) {
		for($i = 0; $i < 10; $i++){
			//杂点颜色
		    $noiseColor = imagecolorallocate(
		                      $image, 
		                      mt_rand(150,225), 
		                      mt_rand(150,225), 
		                      mt_rand(150,225)
		                  );
			for($j = 0; $j < 5; $j++) {
				$letter = $this->getCode(1, 0);
				// 绘杂点
			    imagestring(
			        $image,
			        5, 
			        mt_rand(-10, $w), 
			        mt_rand(-10, $w), 
			        $letter, // 杂点文本为随机的字母或数字
			        $noiseColor
			    );
			}
		}
	}
	
	/**
	 * 生成验证码图片
	 */
	public function showValidateCode(){
		$width = 80;
		$height = 28;
		$a = $this->getCode(1,1);
		$b = $this->getCode(1,1);
		$c = $this->getCode(1,1);
		$passport = $a . "+" . $b . "+" . $c . "=?";
		$total = $a + $b + $c;
		VO_Session::set('validateCode', $total);
		//$font = COMMON_SITE_PATH . "/static/fonts/lhandw.ttf";
		$font = '';
		$this->createAuthNumImg($passport,$width,$height,$font);
	}
}