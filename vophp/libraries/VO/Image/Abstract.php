<?php
class VO_Image_Abstract{
	/**
	 * 得到等比例缩放后的宽高
	 * @param int $width
	 * @param int $height
	 * @param int(reference) $w
	 * @param int(reference) $h
	 * 
	 * @return array
	 */
	protected function getImageResize($width, $height, $w, $h){
		$resizewidth_tag = false;
		$resizeheight_tag = false;
		$ratio = 1;
		if($width >= $height){
			//原图小于缩略图大小，则无需缩放
			if($width<$w){
				$w = $width;
				$h = $height;
			}else{
				//以目标宽度为缩放参照
				//$h = 0;
				$widthratio = $w/$width;
            	$resizewidth_tag = true;
			}
		}else{
			//原图小于缩略图大小，则无需缩放
			if($height<$h){
				$w = $width;
				$h = $height;
			}else{
				//以目标高度为缩放参照
				$heightratio = $h/$height;
            	$resizeheight_tag = true;
			}
		}
		
		if($resizewidth_tag && !$resizeheight_tag){
            $ratio = $widthratio;
		}
        if($resizeheight_tag && !$resizewidth_tag){
            $ratio = $heightratio;
        }

        $w = $width * $ratio;
        $h = $height * $ratio;
        
		$result = array(
			'width' => $w,
			'height' => $h,
		);
		return $result;
	}
}