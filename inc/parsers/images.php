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
				
				$checkis = md5_file($swRoot.'/site/files/'.$file);
// 				print_r($wiki->internalfields['imagechecksum']);
				$checkshould = @$wiki->internalfields['imagechecksum'][0];
// 				echo $checkshould;
				if ($checkis == $checkshould)
					$swEditMenus['imagechecksum'] = "checksum ok";
				elseif ( $checkshould == '')
					$swEditMenus['imagechecksum'] = "no checksum";
				else
					$swEditMenus['imagechecksum'] = "checksum error (is ".$checkis.'  should be '.$checkshould;
			
			}
			
			if (substr($file,-4) == '.jpg' || substr($file,-5) == '.jpeg' || substr($file,-4) == '.png' || substr($file,-4) == '.gif')
			{
				$s ='<img class="embeddimage" alt="" src="site/files/'.$file.'"><p>'.$s;
			}
			else
			{
				$s ='<a href="site/files/'.$file.'">'.$file.'</a><p>'.$s;
			}
			
			
			
			
		}
		
		
		
		// Image Links with dual size width and height and alt tag
		preg_match_all("/\[\[Image:([^\|\]]*)(\|+)([^\]]*)(\|+)([^\]]*)(\|+)([^\]]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			
			$val = $v[1];
			$width = $v[3];
			$height = $v[5];
			$alttag = $v[7];
			if ($height!= '' && $height > 0 && $width != '' && $width>0)
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