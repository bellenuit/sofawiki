<?php

if (!defined('SOFAWIKI')) die('invalid acces');

class swImagesParser extends swParser
{
	var $ignorelinks;
	
	function info()
	{
	 	return 'Handles images';
	}

	

	function dowork(&$wiki)
	{
	
		$s = $wiki->parsedContent;
		
		// Image page
		
		if ($wiki->wikinamespace()=='Image')
		{
			
			
			
			
			$wiki->name = $wiki->namewithoutlanguage();
			$file = substr($wiki->name,6);
			// show all subsampled images
			if (isset($_GET['imagecacherefresh']))
			{
				
				global $swRoot;
				
				$files = glob($swRoot.'/site/cache/*'.$file);
				
				foreach($files as $f)
						unlink($f);
				
			}
			else
			{
				global $swEditMenus;
				global $lang;
				global $swRoot;
				$swEditMenus['imagecacherefresh'] = '<a href="'.$wiki->link('editview','--').'&imagecacherefresh=1" rel="nofollow">'.swSystemMessage('Image Cache Refresh',$lang).'</a>';
				
				if (file_exists($swRoot.'/site/files/'.$file))
				{
					$checkis = md5_file($swRoot.'/site/files/'.$file);
	
					$checkshould = @$wiki->internalfields['imagechecksum'][0];

					if ($checkis == $checkshould)
						$swEditMenus['imagechecksum'] = "checksum ok";
					elseif ( $checkshould == '')
						$swEditMenus['imagechecksum'] = "no checksum";
					else
						$swEditMenus['imagechecksum'] = "checksum error (is ".$checkis.'  should be '.$checkshould;
				}	
				else
					$swEditMenus['imagechecksum'] = "file missing";

			
			}
			
			if (substr($file,-4) == '.jpg' || substr($file,-5) == '.jpeg' || substr($file,-4) == '.png' || substr($file,-4) == '.gif')
			{
				$s ='<img class="embeddimage" alt="" src="site/files/'.$file.'"><p>'.$s;
				
				/*
				if (isset($_POST['submitconvert']))
				{
					
					$width = $_POST['width'];
					$height = $_POST['height'];
					$crop = $_POST['crop'];
					
					if ($width>0 || $height>0)
					{
						$newfile = swImageDownscale($file,$width,$height,$crop);
						
					}
					
					
				}
				
				
				$s .='<h4>Image Converter</h4>';
				$s .='<nowiki><form><method="post" action="index.php"><input type="hidden" name="action" value="search"><input type="hidden" name="name" value="'.$wiki->name.'"><table><tr><td>Width</td><td><input type="text" name="width" value="640" style="width:100px"></td></tr><tr><td>Height</td><td><input type="text" name="height" value="360" style="width:100px"></td></tr><tr><td>Crop</td><td><select name="crop"><option value="topleft">Top Left</option><option value="topcenter" SELECTED>Top Center</option><option value="topright">Top Right</option><option value="centerleft">Center Left</option><option value="centercenter">Center Center</option><option value="centerright">Center Right</option><option value="bottomleft">Bottom Left</option><option value="bottomcenter">Bottom Center</option><option value="topleft">Bottom Right</option></select></td></tr><tr><td><input type="submit" name="submitconvert" value="Convert"></td></tr></table></form></nowiki>';
				
				*/
			}
			else
			{
				$s ='<a href="site/files/'.$file.'">'.$file.'</a><p>'.$s;
			}
			
			
			
			
		}
		
		
		
		// Image Links with dual size width and height, crop and alt tag
		
		//preg_match_all("/\[\[Image:([^\|\]]*)(\|+)([^\]]*)(\|+)([^\]]*)(\|+)([^\]]*)(\|+)([^\]]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		preg_match_all("/\[\[Image:([^\]]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			
			
			$options = explode('|',$v[1]);
			
			$val = $options[0];
			if (isset($options[1])) $width = intval($options[1]); else $width = 0;
			if (isset($options[2])) $height = intval($options[2]); else $height = 0;
			if (isset($options[3])) $crop = $options[3]; else $crop = '';
			if (isset($options[4])) $alttag = $options[4]; else $alttag = '';
			
			//print_r($options);
			
			/*
			$val = $v[1];
			$width = intval($v[3]);
			$height = intval($v[5]);
			$crop = intval($v[7]);
			$alttag = $v[9];
			*/
			
			
			if ($height!= '' && $height > 0 && $width != '' && $width>0 && $crop !='')
			{
				
				$path = swImageDownscale($val,$width,$height,$crop);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'">';
				}
			}
			elseif ($height!= '' && $height > 0 && $width != '' && $width>0)
			{
				$path = swImageDownscale($val, $width,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'">';
				}
			}
			elseif ($width != '' && $width>0)
			{
				$path = swImageDownscale($val, $width,0);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'"  width ="'.$width.'">';
				}
			
			}
			elseif ($height!= '' && $height > 0)
			{
				$path = swImageDownscale($val, 0,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'"  src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'"  src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'">';
				}
				
			}
			else
			{
				$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'">';
			}
			if ($this->ignorelinks) $link = '';
			$s = str_replace($v[0], $link, $s); 
		}


		// Image Links with dual size width and height
		preg_match_all("/\[\[Image:([^\|\]]*)(\|+)([^\]]*)(\|+)([^\]]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			
			$val = $v[1];
			$width = $v[3];
			$height = $v[5];
			if ($height != '' && $height>0 && $width != '' && $width>0)
			{
				$path = swImageDownscale($val, $width,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="" src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'">';
				}
			}
			elseif ($height!= '' && $height > 0)
			{
				$path = swImageDownscale($val, 0,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="" src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'">';
				}
				
			}
			else
			{
				$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'">';
			}
			if ($this->ignorelinks) $link = '';
			$s = str_replace($v[0], $link, $s); 
		}
		
		// Image Links with with size
		preg_match_all("/\[\[Image:([^\|\]]*)(\|+)([^\]]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			
			$val = $v[1];
			$width = $v[3];
			if ($width != '' && $width>0)
			{
				$path = swImageDownscale($val, $width,0);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="" src="'.$path.'">';
				}
				else
				{
					$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'"  width ="'.$width.'">';
				}
				
			
			}
			if ($this->ignorelinks) $link = '';
			$s = str_replace($v[0], $link, $s); 
		}
		

		// Image Links original size
		preg_match_all("/\[\[Image:([^\|]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'">';
			if ($this->ignorelinks) $link = '';
			$s = str_replace($v[0], $link, $s); 
		}

		// Image Page Links
		preg_match_all("/\[\[:Image:([^\|]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			$linkwiki = new swWiki;
			$linkwiki->name = 'Image:'.$val;
			$linkwiki->lookup();
			if ($linkwiki->visible())
				$link = '<a href="index.php?name=Image:'.$val.'">Image:'.$val.'</a>';
			else	
				$link = '<a href="index.php?name=Image:'.$val.'" class="invalid">Image:'.$val.'</a>';
			if ($this->ignorelinks) $link = '';
			$s = str_replace('[[:Image:'.$val.']]', $link, $s); 
		}

		// Image Links with alt text
		 preg_match_all("@\[\[:Image:([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER); 
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			$label = $v[3];
			$link = '<a href="index.php?name=Image:'.$val.'">'.$label.'</a>';
			if ($this->ignorelinks) $link = '';
			$s = str_replace('[[:Image:'.$val.'|'.$label.']]', $link, $s); 
		}
		
		// Media Links
		preg_match_all("/\[\[Media:([^\|]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		global $swMediaFileTypeDownload;
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			
			// extensions for download not in new window and filebased class for link
			$pos = strrpos($val,'.');
			
			$t = '';
			if ($pos)
			{
				$e = substr($val,$pos).'.';
				if (stristr($swMediaFileTypeDownload.'.',$e))
					$t = "";
			}
			$e = str_replace('.','',$e);
			
			$link = '<a class="sw-'.$e.'" href="site/files/'.$val.'" '.$t.'>'.$val.'</a>';
			if ($this->ignorelinks) $link = '';
			$s = str_replace('[[Media:'.$val.']]', $link, $s); 
		}
		
		
		// Media Links with alt text
		 preg_match_all("@\[\[Media:([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER); 
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			$label = $v[3];
			
			$t = 'target="_blank"';
			
			
			// extensions for download not in new window and filebased class for link
			$pos = strrpos($val,'.');
			$t = '';
			if ($pos)
			{
				$e = substr($val,$pos).'.';
				if (stristr($swMediaFileTypeDownload.'.',$e)) 
					$t = "";
			}
			$e = str_replace('.','',$e);
			
			$link = '<a class="sw-'.$e.'" href="site/files/'.$val.'" '.$t.'>'.$label.'</a>';
			if ($this->ignorelinks) $link = '';
			$s = str_replace('[[Media:'.$val.'|'.$label.']]', $link, $s); 
		}
		
		
		$wiki->parsedContent = $s;
				
		
		
	}

}

$swParsers['images'] = new swImagesParser;


?>