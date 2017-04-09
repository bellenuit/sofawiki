<?php

if (!defined('SOFAWIKI')) die('invalid acces');


function swImageDownscale($name, $destw=0, $desth=0)
{
    
    //  ImageCreateTrueColor not supported on local php distribution mac
	global $swRoot;

	$destw = sprintf('%03d',$destw);
	$path0 = $swRoot.'/site/files/'.$name;
	@mkdir($swRoot.'/site/cache/');
	$returnpath = 'site/cache/'.$destw.'-'.$name;
	if ($desth)
		$returnpath = 'site/cache/'.$destw.'-'.$desth.'-'.$name;
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
		
		$back = ImageCreateTrueColor($destw,$desth);
		ImageCopyResampled($back, $img, 0,0,0, 0, $destw, $desth, $sourcew, $sourceh);

		imagejpeg($back,$path1,90);
		
		//echotime('save '.$path1);
		
		imagedestroy($img);
		imagedestroy($back);
		return $returnpath;
	}

}




?>