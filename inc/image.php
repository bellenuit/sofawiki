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
		if (!intval($desth)) $desth = $destw *$sourceh / $sourcew;  
		if (!intval($destw)) $destw = $desth *$sourcew / $sourceh; 
		
		if (!intval($destw)) $destw = $sourcew;
		if (!intval($desth)) $desth = $sourceh;
		
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
		
		if ($crop == 'auto')
		{
			$back = swAutoCropResize2($img,$destw,$desth); //echo 'auto';
		}
		elseif ($crop == 'analyze')
		{
			$back = swAutoCropResize2($img,$destw,$desth,true); //echo 'auto';
		}
		else
		{
			$back = ImageCreateTrueColor($destw,$desth);
			swImageCopyResampled($back, $img,0 ,0 , $l, $t, $destw, $desth, $sourcew, $sourceh);  //echo 'normal';
		}

		if ($destw > 360)
			imagejpeg($back,$path1,90);
		else
			imagejpeg($back,$path1,100);


		
		//echotime('save '.$path1);
		
		imagedestroy($img);
		imagedestroy($back);
		return $returnpath;
	}

}

function swBlackLine($im, $line, $vertical = true)
{
	$sum[0] = $sum[1] = $sum[2] = 0;
	$sum2[0] = $sum2[1] = $sum2[2] = 0;
	$n = 0;
	if ($vertical)
	{
		$h = ImagesY($im);
		for($y = 0; $y < $h; $y++)
		{
			$rgb = imagecolorat($im, $line, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			
			if ($r > 4) return false;
			if ($g > 4) return false;
			if ($b > 4) return false;
		}
	}
	else
	{
		$h = ImagesX($im);
		for($x = 0; $x < $h; $x++)
		{
			$rgb = imagecolorat($im, $x, $line);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			
			if ($r > 4) return false;
			if ($g > 4) return false;
			if ($b > 4) return false;
		}
	
	}
	return true;
}

function swCropBlack($img)
{
	$sourcew = ImagesX($img);
	$sourceh = ImagesY($img);
	
	$l = 0; 
	while(swBlackLine($img,$l,true) && $l < $sourcew/2) $l++;
	$r = $sourcew-1; 
	while(swBlackLine($img,$r,true) && $r > $sourcew/2) $r--;
	$t = 0; 
	while(swBlackLine($img,$t,false) && $t < $sourceh/2) $t++;
	$b = $sourceh-1; 
	while(swBlackLine($img,$b,false) && $b > $sourceh/2) $b--;
	
	if ( $r-$l == $sourcew-1 && $b - $t == $sourceh-1)
		return $img;
	$back	 = ImageCreateTrueColor($r-$l-6,$b-$t-6);
	ImageCopy($back,$img,0,0,$l+3,$t+3,$r-$l,$b-$t);
	return $back;
}


function swLineEnergy($im, $line, $vertical = true)
{
	$sum[0] = $sum[1] = $sum[2] = 0;
	$sum2[0] = $sum2[1] = $sum2[2] = 0;
	$n = 0;
	if ($vertical)
	{
		$h = ImagesY($im);
		for($y = 0; $y < $h; $y++)
		{
			$rgb = imagecolorat($im, $line, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			
			$o = floor(3*$y/$h);
			
			$sum[$o] += $r + $g + $b;
			$sum2[$o] += $r*$r+ $g*$g + $b*$b;
			$n+=3;
		}
	}
	else
	{
		$h = ImagesX($im);
		for($x = 0; $x < $h; $x++)
		{
			$rgb = imagecolorat($im, $x, $line);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			
			$o = floor(3*$x/$h);
			
			$sum[$o] += $r + $g + $b;
			$sum2[$o] += $r*$r+ $g*$g + $b*$b;
			$n+=3;
		}
	
	}
	for($o=0;$o<3;$o++)
		$energy[$o] = pow($sum2[$o]/($n/3) - $sum[$o]/($n/3)*$sum[$o]/($n/3),0.5);
	return max($energy);
}

function swSquareEnergy($im, $x0, $y0)
{
	$sum = 0;
	$sum2 = 0;
	$n = 0;
	for ($x = $x0; $x < $x0 + 16; $x++)
	{
		for($y = $y0; $y < $y0 + 16; $y++)
		{
			$rgb = imagecolorat($im, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			
			$sum += $r + $g + $b;
			$sum2 += $r*$r+ $g*$g + $b*$b;
			$n+=3;
		}
	}
	$energy = pow($sum2/$n - ($sum*$sum)/($n*$n),0.5);
	return $energy;
}


function swAutoCropResize($img0, $destw, $desth)
{
	
	$img = swCropBlack($img0);
	
	$sourcew = ImagesX($img);
	$sourceh = ImagesY($img);
	$l = 0;
	$t = 0;
	$scale = 144 ;
// 	return $img;


	
	$proxy	 = ImageCreateTrueColor($scale,$scale); 
    swImageCopyResampled($proxy, $img,0 ,0 , 0, 0, $scale, $scale, $sourcew, $sourceh);
// 	return $proxy;
// 	echo 'after';
	
	if ( $destw / $desth < $sourcew / $sourceh)
	{
		// too wide. 
// 		echo "wide"; 
		$crop = $sourcew - $sourceh * $destw / $desth;
		$crop *= $scale / $sourcew; 
// 		echo " crop $crop";
		$cursor1 = 0;
		$cursor2 = $scale-1; // $sourcew - 1;
		
		for($i = 0; $i < $crop; $i++)
		{
// 			echo " cursor $cursor1 $cursor2";
			if (!isset($energy[$cursor1]))
				$energy[$cursor1] = swLineEnergy($proxy, $cursor1, true);
			if (!isset($energy[$cursor2]))			
				$energy[$cursor2] = swLineEnergy($proxy, $cursor2, true);
				
// 			$energy1 = 0; for ($j=0; $j<=$cursor1; $j++) $energy1 += $energy[$j];
// 			$energy2 = 0; for ($j=$cursor2; $j<=$sourcew-1; $j++) $energy2 += $energy[$j];
				
// 			if ($energy1<$energy2)
			if ($energy[$cursor1]<$energy[$cursor2])
				$cursor1++;
			else
				$cursor2--;			
		}
// 		print_r($energy);
		$cursor1 *= $sourcew / $scale;  
		$l = ceil($cursor1);
		$sourcew = $destw / $desth * $sourceh;		
	}
	else
	{
		// too tall
		$crop = $sourceh - $sourcew * $desth / $destw;
		$crop *= $scale / $sourceh;
		
		$cursor1 = 0;
		$cursor2 = $scale-1; // $sourceh - 1; 
		
		for($i = 0; $i < $crop; $i++)
		{
			if (!isset($energy[$cursor1]))
				$energy[$cursor1] = swLineEnergy($proxy, $cursor1, false);
			if (!isset($energy[$cursor2]))			
				$energy[$cursor2] = swLineEnergy($proxy, $cursor2, false);
			if ($energy[$cursor1]<$energy[$cursor2])
				$cursor1++;
			else
				$cursor2--;			
		}
		$cursor1 *= $sourceh / $scale;
		$t = ceil($cursor1);
		$sourceh = $desth / $destw * $sourcew;		
	}
	
	$back = ImageCreateTrueColor($destw,$desth);
	swImageCopyResampled($back, $img,0 ,0 , $l, $t, $destw, $desth, $sourcew, $sourceh);
// 	echo 'proxy';
	return $back;
}

function swAutoCropResize2($img0, $destw, $desth, $analyze = false)
{
	
	$img = swCropBlack($img0);
	
	$sourcew = ImagesX($img);
	$sourceh = ImagesY($img);
	$l = 0;
	$t = 0;

	$scale = 144;

	
	$proxy	 = ImageCreateTrueColor($scale,$scale); 
    swImageCopyResampled($proxy, $img,0 ,0 , 0, 0, $scale, $scale, $sourcew, $sourceh);
    
    $sumx = $sumy = $n = 0;
    
    
    for($x = 0; $x < 144; $x+=16)
    {
    	for($y=0; $y < 144; $y+=16)
    	{
    		$e = swSquareEnergy($proxy,$x,$y);
    		$sumx += $e*$x;
    		$sumy += $e*$y;
    		$n += $e;
    		$c	 = imagecolorallocatealpha($proxy,255,0,0,max(0,127-$e));
    		imagefilledrectangle($proxy,$x,$y,$x+16,$y+16,$c);
    	}
    }
    
    $centerx = $sumx / $n + 8;
    $centery = $sumy / $n + 8;
    
    $c	 = imagecolorallocatealpha($proxy,255,255,255,0);
    imagefilledellipse($proxy,$centerx,$centery,6,6,$c);
    
    if ($analyze)
    	return $proxy;
    	
    $centerx *= $sourcew / 144;
    $centery *= $sourceh / 144;
    
    /*
    
    0----l----centerx----r---0
    
    */    
	
	if ( $destw / $desth < $sourcew / $sourceh)
	{
        // too wide
		$crop = $sourcew - $sourceh * $destw / $desth;
		$sourcew2 = $sourcew - $crop;
		$l = $centerx - $sourcew2/2;
		$l = floor(max(0,min($crop,$l)));
		$sourcew = $destw / $desth * $sourceh;
		
	}
	else
	{
		// too tall
		$crop = $sourceh - $sourcew * $desth / $destw;
		$sourceh2 = $sourceh - $crop;
		$t = $centery - $sourceh2/2;
		$t = floor(max(0,min($crop,$t)));
		$sourceh = $desth / $destw * $sourcew;
	}
	
	$back = ImageCreateTrueColor($destw,$desth);
	swImageCopyResampled($back, $img,0 ,0 , $l, $t, $destw, $desth, $sourcew, $sourceh);
// 	echo 'proxy';
	return $back;
}


function swImageCopyResampled($back,$img,$destx, $desty,$sourcex, $sourcey, $destw, $desth, $sourcew, $sourceh)
{
	// bigger -> linear

		$scalexy = ($destw + $desth) / ($sourcew + $sourceh);

	
	if ($scalexy > 1 || true) // bicubic rescale not ready yet
	{

		ImageCopyResampled($back,$img,$destx, $desty,$sourcex, $sourcey, $destw, $desth, $sourcew, $sourceh);
	}
	else
	{
		// first scale down then  place
		
		$img2 = imagescale($img,$destw,$desth,IMG_BICUBIC);
		ImageCopy($back,$img2,$destx, $desty,$sourcex, $sourcey, $destw, $desth);	
		
	}
	
}


?>