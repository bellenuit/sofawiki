<?php

if (!defined('SOFAWIKI')) die('invalid acces');


function swImageDownscale($name, $destw=0, $desth=0, $crop='')
{
    
    
    
    //  ImageCreateTrueColor not supported on local php distribution mac
	global $swRoot;

	$destw = sprintf('%03d',$destw);
	$path0 = $swRoot.'/site/files/'.$name;
	@mkdir($swRoot.'/site/cache/');
	$returnpath = 'site/cache/'.$destw.'-'.$name;
	if ($desth)
	{
		$returnpath = 'site/cache/'.$destw.'-'.$desth.'-'.$name;
		if ($crop != '')
			$returnpath = 'site/cache/'.$destw.'-'.$desth.'-'.$crop.'-'.$name;
	}
	$path1 = $swRoot.'/'.$returnpath;
	
	if (file_exists($path1) && !array_key_exists('refresh',$_GET))
		return $returnpath;
	
	//echotime('hasnot '.$path1);
	
	echotime('renderimage '.$name);
	
	if (!file_exists($path0)) return;
		
	switch (substr($name,-4))
	{
		case '.jpg' : ;
		case 'jpeg' : $img = @ImageCreateFromJpeg($path0); 
					  if (!$img) //png labelled as jpg
					  	$img = @ImageCreateFromPNG($path0); 
					  	break;
		case '.png' : $img = @ImageCreateFromPNG($path0); break;
		case '.gif' : $img = @ImageCreateFromGIF($path0); break;
		
		case '.pdf' : return false;
		default: return false;
	}
	echotime('...');
	
	if ($img)
	{
		echotime('haveimage '.$name);
		
		$sourcew = ImagesX($img);
		$sourceh = ImagesY($img);
		if ($desth==0)
		$desth = $destw *$sourceh / $sourcew; 
		if ($destw==0)
		$destw = $desth *$sourcew / $sourceh; 
		
		// do not upscale!
		if ($destw > $sourcew * 1.5)
		{
			$desth = $sourceh;
			$destw = $sourcew;
		}
		
		$t = 0;
		$l = 0;
		
		if ( $destw / $desth < $sourcew / $sourceh && $crop != '')
		{
			/* crop maintains aspect ratio, but cuts in the source image
			
			|-------|------|
			|       |      |
			|-------|------|
					D      S
			*/
			
			switch(substr($crop,1))
			{
				case '1'; case 'l': $l = 0; break;
				case '2': $l = ($sourcew - $destw / $desth * $sourceh)/8; break;
				case '3': $l = ($sourcew - $destw / $desth * $sourceh)/4; break;
				case '4': $l = ($sourcew - $destw / $desth * $sourceh)*3/8; break;
				case '5': case 'c': $l = ($sourcew - $destw / $desth * $sourceh)/2; break;
				case '6': $l = ($sourcew - $destw / $desth * $sourceh)*5/8; break;
				case '7': $l = ($sourcew - $destw / $desth * $sourceh)*3/4; break;
				case '8': $l = ($sourcew - $destw / $desth * $sourceh)*7/8; break;
				case '9': case 'r': $l = $sourcew - $destw / $desth * $sourceh; break;
			}
			$sourcew = $destw / $desth * $sourceh;
		}
		elseif ( $destw / $desth > $sourcew / $sourceh && $crop != '')
		{			
			switch(substr($crop,0,1))
			{
				case '1': case 't': $t = 0; break;
				case '2': $t = ($sourceh - $desth / $destw * $sourcew)/8; break;
				case '3': $t = ($sourceh - $desth / $destw * $sourcew)/4; break;
				case '4': $t = ($sourceh - $desth / $destw * $sourcew)*3/8; break;
				case '5': case 'c': $t = ($sourceh - $desth / $destw * $sourcew)/2; break;
				case '6': $t = ($sourceh - $desth / $destw * $sourcew)*5/8; break;
				case '7': $t = ($sourceh - $desth / $destw * $sourcew)*3/4; break;
				case '8': $t = ($sourceh - $desth / $destw * $sourcew)*7/8; break;
				case '9': case 'b': $t = $sourceh - $desth / $destw * $sourcew; break;
			}
			$sourceh = $desth / $destw * $sourcew;
		}
		

		
		$back = ImageCreateTrueColor($destw,$desth);
		ImageCopyResampled($back, $img,0 ,0 , $l, $t, $destw, $desth, $sourcew, $sourceh);

		imagejpeg($back,$path1,90);
		
		//echotime('save '.$path1);
		
		imagedestroy($img);
		imagedestroy($back);
		return $returnpath;
	}

}






?>