<?php

// 只考虑jpg,png,gif格式
// $srcImgPath 源图象路径
// $targetImgPath 目标图象路径
// $targetW 目标图象宽度
// $targetH 目标图象高度

require '../system/common.php';

$deal_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal where 1");
foreach($deal_list as $key=>$deal){
	$tmp_update_sql = "update ".DB_PREFIX."deal set ";
	$tmp_update_array = array();
	for($i=0;$i<9;$i++){
		if(!empty($deal['imghead_'.$i])){
			$srcImgPath = '.'.$deal['imghead_'.$i];
			$pathArray = explode('/',$srcImgPath);
			$fileName = $pathArray[6];
			$fileArray = explode('.',$fileName);
			$targetImgPath = $pathArray[0].'/'.$pathArray[1].'/'.$pathArray[2].'/'.$pathArray[3].'/'.$pathArray[4].'/'.$pathArray[5].'/'.$fileArray[0].'_100X100.'.$fileArray[1];
			
			$newpathArray = explode('/',$targetImgPath);
			img2thumb($srcImgPath,$targetImgPath,100,0,1,0);
			$tmp_update_array[] = " `imghead_".$i."_thumb` = './".$newpathArray[1]."/".$newpathArray[2]."/".$newpathArray[3]."/".$newpathArray[4]."/".$newpathArray[5]."/".$newpathArray[6]."' ";
		}
	}
	$tmp_update_sql .= implode(',',$tmp_update_array);
	$tmp_update_sql .= " where id = ".$deal['id'];
	echo $tmp_update_sql.'<br>';
	$GLOBALS['db']->query($tmp_update_sql);
}


function fileext($file)
{
    return pathinfo($file, PATHINFO_EXTENSION);
}

function img2thumb($src_img, $dst_img, $width = 75, $height = 75, $cut = 0, $proportion = 0)
{
    if(!is_file($src_img))
    {
        return false;
    }
	
	
	
    $ot = fileext($dst_img);
    $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
    $srcinfo = getimagesize($src_img);
    $src_w = $srcinfo[0];
    $src_h = $srcinfo[1];
    $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
    $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);
 
    $dst_h = $height;
    $dst_w = $width;
    $x = $y = 0;
 
    /**
     * 缩略图不超过源图尺寸（前提是宽或高只有一个）
     */
    if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0))
    {
        $proportion = 1;
    }
    if($width> $src_w)
    {
        $dst_w = $width = $src_w;
    }
    if($height> $src_h)
    {
        $dst_h = $height = $src_h;
    }
 
    if(!$width && !$height && !$proportion)
    {
        return false;
    }
    if(!$proportion)
    {
        if($cut == 0)
        {
            if($dst_w && $dst_h)
            {
                if($dst_w/$src_w> $dst_h/$src_h)
                {
                    $dst_w = $src_w * ($dst_h / $src_h);
                    $x = 0 - ($dst_w - $width) / 2;
                }
                else
                {
                    $dst_h = $src_h * ($dst_w / $src_w);
                    $y = 0 - ($dst_h - $height) / 2;
                }
            }
            else if($dst_w xor $dst_h)
            {
                if($dst_w && !$dst_h)  //有宽无高
                {
                    $propor = $dst_w / $src_w;
                    $height = $dst_h  = $src_h * $propor;
                }
                else if(!$dst_w && $dst_h)  //有高无宽
                {
                    $propor = $dst_h / $src_h;
                    $width  = $dst_w = $src_w * $propor;
                }
            }
        }
        else
        {
            if(!$dst_h)  //裁剪时无高
            {
                $height = $dst_h = $dst_w;
            }
            if(!$dst_w)  //裁剪时无宽
            {
                $width = $dst_w = $dst_h;
            }
            $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
            $dst_w = (int)round($src_w * $propor);
            $dst_h = (int)round($src_h * $propor);
            $x = ($width - $dst_w) / 2;
            $y = ($height - $dst_h) / 2;
        }
    }
    else
    {
        $proportion = min($proportion, 1);
        $height = $dst_h = $src_h * $proportion;
        $width  = $dst_w = $src_w * $proportion;
    }
 
    $src = $createfun($src_img);
    $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
 
    if(function_exists('imagecopyresampled'))
    {
        imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
    else
    {
        imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
    $otfunc($dst, $dst_img);
    imagedestroy($dst);
    imagedestroy($src);
    return true;
}


function makeThumbnail($srcImgPath,$targetImgPath,$targetW,$targetH)
{
	$imgSize = GetImageSize($srcImgPath);
	$imgType = $imgSize[2];
	//@ 使函数不向页面输出错误信息
	switch ($imgType)
	{
		case 1:
			$srcImg = @ImageCreateFromGIF($srcImgPath);
			break;
		case 2:
			$srcImg = @ImageCreateFromJpeg($srcImgPath);
			break;
		case 3:
			$srcImg = @ImageCreateFromPNG($srcImgPath);
			break;
	}
	 //取源图象的宽高
	$srcW = ImageSX($srcImg);
	$srcH = ImageSY($srcImg);
	if($srcW>$targetW || $srcH>$targetH)
	{
		$targetX = 0;
		$targetY = 0;
		if ($srcW > $srcH)
		{
			$finaW=$targetW;
			$finalH=round($srcH*$finaW/$srcW);
			$targetY=floor(($targetH-$finalH)/2);
		}
		else
		{
			$finalH=$targetH;
			$finaW=round($srcW*$finalH/$srcH);
			$targetX=floor(($targetW-$finaW)/2);
		}
		  //function_exists 检查函数是否已定义
		  //ImageCreateTrueColor 本函数需要GD2.0.1或更高版本
		if(function_exists("ImageCreateTrueColor"))
		{
			$targetImg=ImageCreateTrueColor($targetW,$targetH);
		}
		else
		{
			$targetImg=ImageCreate($targetW,$targetH);
		}
		$targetX=($targetX<0)?0:$targetX;
		$targetY=($targetX<0)?0:$targetY;
		$targetX=($targetX>($targetW/2))?floor($targetW/2):$targetX;
		$targetY=($targetY>($targetH/2))?floor($targetH/2):$targetY;
		//背景白色
		$white = ImageColorAllocate($targetImg, 255,255,255);
		ImageFilledRectangle($targetImg,0,0,$targetW,$targetH,$white);
		/*
			   PHP的GD扩展提供了两个函数来缩放图象：
			   ImageCopyResized 在所有GD版本中有效，其缩放图象的算法比较粗糙，可能会导致图象边缘的锯齿。
			   ImageCopyResampled 需要GD2.0.1或更高版本，其像素插值算法得到的图象边缘比较平滑，
														 该函数的速度比ImageCopyResized慢。
		*/
		if(function_exists("ImageCopyResampled"))
		{
			ImageCopyResampled($targetImg,$srcImg,$targetX,$targetY,0,0,$finaW,$finalH,$srcW,$srcH);
		}
		else
		{
			ImageCopyResized($targetImg,$srcImg,$targetX,$targetY,0,0,$finaW,$finalH,$srcW,$srcH);
		}
		switch($imgType) {
			case 1:
				ImageGIF($targetImg,$targetImgPath);
				break;
			case 2:
				ImageJpeg($targetImg,$targetImgPath);
				break;
			case 3:
				ImagePNG($targetImg,$targetImgPath);
				break;
		}
		ImageDestroy($srcImg);
		ImageDestroy($targetImg);
	}
	else //不超出指定宽高则直接复制
	{
		copy($srcImgPath,$targetImgPath);
		ImageDestroy($srcImg);
	}
}
?>