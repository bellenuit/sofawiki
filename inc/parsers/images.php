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
	
		global $swRoot;
		$s = $wiki->parsedContent;
		
		// Image page
		
		if ($wiki->wikinamespace()=='Image')
		{
			
			
			
			
			$wiki->name = $wiki->namewithoutlanguage();
			$file = substr($wiki->name,6);
			// show all subsampled images
			if (isset($_GET['imagecacherefresh']))
			{
				
				
				
				$files = glob($swRoot.'/site/cache/*'.$file);
				
				foreach($files as $f)
						unlink($f);
				
			}
			else
			{
				global $swEditMenus;
				global $lang;
				global $swRoot;
				global $user;
				
				$s = '';
								
				if (isset($_POST['submitcrop']) && $_POST['file'] )
				{
					$path = $_POST['name'];
					$path = str_replace('Image:',$swRoot.'/site/files/',$path);
					
					
					
					$img = @ImageCreateFromJpeg($path); 
					
					//echo $path;
					//echo " swImageCopyResampled($back, $img,0,0,$_POST[cropleft],$_POST[croptop], $_POST[cropwidth], $_POST[cropheight])";
					
					
					$back = imagecrop($img,array('x' => $_POST['cropleft'],'y' => $_POST['croptop'], 'width' => $_POST['cropwidth'], 'height' => $_POST['cropheight']));
					
					//swImageCopyResampled($back, $img,0,0,0,0,100,100,100,100);
					
					$path1 = $swRoot.'/site/files/'.$_POST['file'];
					imagejpeg($back,$path1,100);
					
					$wiki2 = new swWiki;
					$wiki2->name ='Image:'.$_POST['file'];
					$wiki2->user = $user->name;
					$wiki2->content = '[[:'.$_POST['name'].']]'.PHP_EOL.'[[imagechecksum::'.md5_file($path1).']]';
					$wiki2->insert();

					$s .=  '<br/><a href="'.$wiki2->link('').'">Image:'.$_POST['file'].'</a>';
					$s .=  '<p><img src="site/files/'.$_POST['file'].'" width=320>';

					
					
				}
			
				if (isset($_REQUEST['checksum']))
				{			
					global $swStatus;
					global $swError;
					if (file_exists($swRoot.'/site/files/'.$file))
					{
						$checkis = md5_file($swRoot.'/site/files/'.$file);
		
						$checkshould = @$wiki->internalfields['imagechecksum'][0];
	
						if ($checkis == $checkshould)
							$swStatus = "checksum ok";
						elseif ( $checkshould == '')
							$swError = "no checksum";
						else
							$swError = "checksum error (is ".$checkis.'  should be '.$checkshould;
					}	
					else
						$swError = "file missing";
				}
				else
				{
					global $swAdditionalEditMenus;

					$swAdditionalEditMenus['viewmenu-checksum'] = '<a href="index.php?checksum=1&name='.$wiki->name.'">Checksum</a>';
					
				}

			
			}
			
			$fileinfo = pathinfo($file);
			$fileextension = strtolower($fileinfo['extension']);
			
			if (in_array($fileextension, array('jpg','jpeg','png','gif')))
			{
				
				$path = $swRoot.'/site/files/'.$file;
				switch($fileextension)
				{
					case 'jpg':
					case 'jpeg': $img = @imagecreatefromjpeg($path); break;
					case 'png': $img = @Imagecreatefrompng($path); break;
					case 'gif': $img = @imagecreatefromgif($path); break;
					default: $img = null;
				}
				
				if ($img)
				{
					$originalwidth = ImagesX($img);
					$originalheight = ImagesY($img);
					$scaledheight = min($originalheight,$originalheight * 640 / $originalwidth);
					$scaledwidth = $scaledheight*$originalwidth/$originalheight;
				
				
					$s .='<p><div style="position:relative"><img class="embeddimage" id="image" alt="" src="site/files/'.$file.'" style="width:'.$scaledwidth.'px; height:'.$scaledheight.'px; position:absolute; top:0px; left:0px;"><canvas id="imagecanvas" style="position:absolute; top:0px; left:0px;" onmouseleave="drawCrop()" /></div>';
					$s .= $wiki->parsedContent;
					
					if (in_array($fileextension, array('jpg','jpeg','png','gif')))
					{
					
					$s .= '<div style="position:relative"><h4>Crop</h4>';
					$s .= '<button type="button" onclick="setRatio(\'2:1\')">2:1</button>';
					$s .= '<button type="button" onclick="setRatio(\'16:9\')">16:9</button>';
					$s .= '<button type="button" onclick="setRatio(\'3:2\')">3:2</button>';
					$s .= '<button type="button" onclick="setRatio(\'4:3\')">4:3</button>';
					$s .= '<button type="button" onclick="setRatio(\'1:1\')">1:1</button>';
					$s .= '<button type="button" onclick="setRatio(\'2:3\')">2:3</button>';
					$s .= '<button type="button" onclick="setRatio(\'1:2\')">1:2</button>';
					$s .= '<button type="button" onclick="setRatio(\'free\')">Free</button> ';
					$s .= '<form method="post" action="index.php">';
					$s .= '<input type="hidden" name="name" value="'.$wiki->name .'">';
					$s .= 'W <input type="text" name="cropwidth" id="cropwidth" size=5 value="">';
					$s .= 'H <input type="text" name="cropheight" id="cropheight" size=5value="">';
					$s .= 'L <input type="text" name="cropleft" id="cropleft" size=5 value="">';
					$s .= 'T <input type="text" name="croptop" id="croptop" size=5 value="">';
					$s .= '<input type="text" name="file" id="cropfile" size=32 value="">';
					$s .= '<input type="submit" name="submitcrop" value="Save">';
					$s .= '</form>';
					
				}
				else
				{
					$scaledheight = $originalwidth = 0;
				}
				
				$savename = $wiki->name;
				if ($fileextension != 'jpg') $savename.= '.jpg';
				
				$s .= "<nowiki><script>
				
var canvas = document.getElementById('imagecanvas');	
var img = document.getElementById('image');
canvas.setAttribute('width',img.clientWidth);
canvas.setAttribute('height',img.clientHeight);
canvas.parentNode.style.height = img.clientHeight+'px';
var ctx = canvas.getContext('2d');	
ctx.width = img.clientWidth;
ctx.height = img.clientHeight;	
var xscale = ctx.width;
var yscale = ctx.height;
wfield = document.getElementById('cropwidth');
hfield = document.getElementById('cropheight');
tfield = document.getElementById('cropleft');
lfield = document.getElementById('croptop');
textfield = document.getElementById('cropfile');
var x = 0;
var y = 0;
var w = xscale;
var h = yscale;
var forceratio = 0;
var draghandles = [];
draghandles['center'] = {id:'center',x:xscale/2,y:yscale/2,isDragging:false};
draghandles['left'] = {id:'left',x:0,y:yscale/2,isDragging:false};
draghandles['right'] = {id:'right',x:xscale,y:yscale/2,isDragging:false};
draghandles['top'] = {id:'top',x:xscale/2,y:0,isDragging:false};
draghandles['bottom'] = {id:'bottom',x:xscale/2,y:yscale,isDragging:false};
var BB=canvas.getBoundingClientRect();
var offsetX=BB.left;
var offsetY=BB.top;
var startX = 0;
var startY = 0;
var forceratio = 0;
dragok = false;
canvas.onmousedown = down;
canvas.onmousemove = move;
canvas.onmouseup = up;
formscale = ".$originalwidth."/	xscale;	


function setRatio(r)
{
	forceratio = 1;
	switch(r)
	{
		case '2:1' : ratio = 2.0; break;
		case '16:9' : ratio = 16.0/9.0; break;
		case '3:2' : ratio = 1.5; break;
		case '4:3' : ratio = 4.0/3.0; break;
		case '1:1' : ratio = 1.0; break;
		case '2:3' : ratio = 2.0/3.0; break;
		case '1:2' : ratio = 0.5; break;
		default: ratio = xscale / yscale; forceratio = 0;
	}
	if (forceratio) forceratio = ratio;
	
	if (xscale > ratio * yscale)
		{ h = yscale; w = ratio * h; y = 0; x = (xscale - w) / 2; }
	else
		{ w = xscale; h = w / ratio; x = 0; y = (yscale - h) / 2; }

	x = Math.round(x);
	y = Math.round(y)
	w = Math.round(w);
	h = Math.round(h);
	
	s = draghandles['center']; s.x = x + w/2; s.y = y + h/2;
	s = draghandles['left']; s.x = x; s.y = y + h/2;
	s = draghandles['right']; s.x = x + w; s.y = y + h/2;
	s = draghandles['top']; s.x = x + w/2; s.y = y;
	s = draghandles['bottom']; s.x = x + w/2; s.y = y + h;
	
	drawCrop();
}

function move(e)
{
	e.preventDefault();
	e.stopPropagation();
	
	ctx.globalAlpha = 1.0;
	ctx.fillStyle = '#00FF00';
	
	var BB=canvas.getBoundingClientRect();
	var offsetX=BB.left;
	var offsetY=BB.top;
	
	var mx=parseInt(e.clientX-offsetX);
	var my=parseInt(e.clientY-offsetY);
	var dx=mx-startX;
    var dy=my-startY;
	
	if (dragok)
	{
		for (const key in draghandles)
		{
			 const s = draghandles[key];
			 
			 if (s.isDragging)
			 {				
			    switch(key)
		        {
			        case 'center': dx = Math.max(-x,Math.min(dx,xscale-x-w));
			        			   dy = Math.max(-y,Math.min(dy,yscale-y-h));
			        			   x = x + dx;
			        			   y = y + dy; break;
			        case 'left'  : x1 = Math.max(0,Math.min(x+dx,x+w));			        
			        			   w = w + x - x1;
			        			   x = x1;
			        			   if (forceratio)
			        			   {
			        			   		h1 = w / forceratio;
			        			   		y = y + (h - h1)/2;
			        			   		h = h1;	
			        			   		
			        			   		if (y < 0) y = 0;
			        			   		if (h > yscale) { h = yscale; w = yscale * forceratio ; }
			        			   		if (y + h > yscale) y = yscale -h;		        			   		
			        			   }
			        			   s.x = x; break;	
					case 'right' : w = Math.max(0,Math.min(w+dx,xscale-x));
			        			   if (forceratio)
			        			   {
			        			   		h1 = w / forceratio;
			        			   		y = y + (h - h1)/2;
			        			   		h = h1;	
			        			   		w1 = w;
			        			   		if (y < 0) y = 0;
			        			   		if (h > yscale) { h = yscale; w = yscale * forceratio ; }
			        			   		if (y + h > yscale) y = yscale -h;	
			        			   		x = x + w1 - w;	        			   		
			        			   }
			        			   s.x = x+w;
			        			   break;
			        case 'top' :   y1 = Math.max(0,Math.min(y+dy,yscale-h));
			       				   h = h + y - y1;
			        			   y = y1;
			        			   if (forceratio)
			        			   {
			        			   		w1 = h * forceratio;
			        			   		x = x + (w - w1)/2;
			        			   		w = w1;	
			        			   		
			        			   		if (x < 0) x = 0;
			        			   		if (w > xscale) { w = xscale; h = xscale / forceratio ;} 
			        			   		if (x + w > xscale) x = xscale-w;		        			   		
			        			   }
			        			   
			        			   s.y = y; 
			        			   break;	
			        case 'bottom' : h = Math.max(0,Math.min(h+dy,yscale-y));
			        			   s.y = y+h; 
			        			   if (forceratio)
			        			   {
			        			   		w1 = h * forceratio;
			        			   		x = x + (w - w1)/2;
			        			   		w = w1;	
			        			   		h1 = h;
			        			   		if (x < 0) x = 0;
			        			   		if (w > xscale) { w = xscale; h = xscale / forceratio ; }
			        			   		if (x + w > xscale) x = xscale - w;	
			        			   		y = y + h1 - h;	  		        			   		
			        			   }			        			   
			        			   break;
			    }
			    
			    
			    
			    x = Math.round(x);
				y = Math.round(y)
				w = Math.round(w);
				h = Math.round(h);
			    
			    draghandles['center'].x = x + w/2;
			    draghandles['center'].y = y + h/2;
				draghandles['top'].x = x + w/2;
				draghandles['top'].y = y;
				draghandles['bottom'].x = x + w/2;
				draghandles['bottom'].y = y + h;
				draghandles['left'].x = x;
			    draghandles['left'].y = y + h/2;
			    draghandles['right'].x = x + w;
			    draghandles['right'].y = y + h/2;

			 }
			 
		}
		
	}
	
	
	drawHandles();
	startX = mx;
	startY = my;
}

function down(e)
{
	e.preventDefault();
	e.stopPropagation();
	
	var BB=canvas.getBoundingClientRect();
	var offsetX=BB.left;
	var offsetY=BB.top;
	
	var mx=parseInt(e.clientX-offsetX);
	var my=parseInt(e.clientY-offsetY);
	
	dragok=false;
	for (const key in draghandles)
	{
		const s = draghandles[key];
		if (mx>s.x-10 && mx<s.x+10 && my>s.y-10 && my<s.y+10)
		{
			dragok=true; 
	        s.isDragging=true;
	        

		}
		else
		{
			s.isDragging=false;
		}
	}
	drawHandles();
	startX = mx;
	startY = my;
}

function up(e)
{
	e.preventDefault();
	e.stopPropagation();
	for (const key in draghandles)
	{
		const s = draghandles[key];
				
		s.isDragging=false;
	}
	
	dragok=false;
}


function drawCrop()
{
	
	wfield.setAttribute('value',Math.round(w*formscale));
	hfield.setAttribute('value',Math.round(h*formscale));
	tfield.setAttribute('value',Math.round(x*formscale));
	lfield.setAttribute('value',Math.round(y*formscale));
	textfield.setAttribute('value',w+'-'+h+'-'+x+'-'+y+'-".str_replace('Image:','',$savename)."');
	
	
	// fill outside
	ctx.globalAlpha = 0.5;
	ctx.fillStyle = '#FFFFFF';
	ctx.clearRect(0,0,ctx.width,ctx.height);
	ctx.fillRect(0,0,ctx.width,ctx.height);
	
	ctx.clearRect(x,y,w,h);
	
	
}


function drawHandles()
{
	drawCrop()
	
	ctx.globalAlpha = 1.0;
	ctx.strokeStyle = '#FFFFFF';
	ctx.lineWidth = 1.0;
	// thirds
	
	ctx.strokeRect(x,y,w/3,h);
	ctx.strokeRect(x+w*2/3,y,w/3,h);
	ctx.strokeRect(x,y,w,h/3);
	ctx.strokeRect(x,y+h*2/3,w,h/3);

	ctx.globalAlpha = 1.0;
	ctx.fillStyle = '#00FF00';	
	
	for (const key in draghandles)
	{
		 const s = draghandles[key];		 
		 
		 if (s.isDragging)
		 	ctx.fillStyle = '#00FF00';
		 else
		 	ctx.fillStyle = '#FFFFFF';
		 ctx.fillRect(s.x-10,s.y-10,20,20); 
		 
	}

}

</script></nowiki>";
				$files = glob($swRoot.'/site/files/*'.$file);
				
				if (count($files))
				{
					// $s .= '<h4>Crop</h4>';
					foreach($files as $f)
					{
						$p = str_replace($swRoot.'/','',$f);
						$l = str_replace('site/files/','',$p);
						$s .= '<p>[[:Image:'.$l.']]';
					}
				}
				
				}

				
				$files = glob($swRoot.'/site/cache/*'.$file);
				
				if (count($files))
				{
					$swAdditionalEditMenus['viewmenu-imagecacherefresh'] = '<a href="'.$wiki->link('editview','--').'&imagecacherefresh=1" rel="nofollow">'.swSystemMessage('Image Cache Refresh',$lang).'</a>';
					
					$s .= '<h4>Cache</h4><p>';
;
					foreach($files as $f)
					{
						$p = str_replace($swRoot.'/','',$f);
						$l = str_replace('site/cache/','',$p);
						$s .= '<p><a href="'.$p.'" target="_blank">'.$l.'</a>';
					}
				}
				
				
				
				$s .= '</div>';
				
				
				
				
				
				
				
				
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
			
			
			$s .= $wiki->content;
			
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
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="'.$path.'" width ="'.$width.'" height ="'.$height.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'" >';
				}
			}
			elseif ($height!= '' && $height > 0 && $width != '' && $width>0)
			{
				$path = swImageDownscale($val, $width,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="'.$path.'" width ="'.$width.'" height ="'.$height.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'" >';
				}
			}
			elseif ($width != '' && $width>0)
			{
				$path = swImageDownscale($val, $width,0);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="'.$path.'" width ="'.$width.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'"  width ="'.$width.'" >';
				}
			
			}
			elseif ($height!= '' && $height > 0)
			{
				$path = swImageDownscale($val, 0,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'"  src="'.$path.'"  height ="'.$height.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="'.$alttag.'"  src="site/files/'.$val.'"  height ="'.$height.'" >';
				}
				
			}
			else
			{
				$link = '<img class="embeddimage" alt="'.$alttag.'" src="site/files/'.$val.'" >';
			}
			if ($this->ignorelinks) $link = '';
			$s = str_replace($v[0], $link, $s); 
		}
		
		
		
		// lazy images
		
		preg_match_all("/\[\[Imagelazy:([^\]]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			
			
			$options = explode('|',$v[1]);
			
			$val = $options[0];
			if (isset($options[1])) $width = intval($options[1]); else $width = 0;
			if (isset($options[2])) $height = intval($options[2]); else $height = 0;
			if (isset($options[3])) $crop = $options[3]; else $crop = '';
			if (isset($options[4])) $alttag = $options[4]; else $alttag = '';
			
			$token = md5($val.date('Ymd',time())); 
			$path = 'imageapi.php?w='.$width.'&h='.$height.'&crop='.$crop.'&token='.$token.'&name='.$val;
			
			if ($width) $woption = ' width ="'.$width.'" '; else $woption = '';
			if ($height) $hoption = ' height ="'.$height.'" '; else $hoption = '';
			
			$link = '<img class="embeddimage" alt="'.$alttag.'" srclazy="'.$path.'"'.$woption.$hoption.' loading="lazy">';			
			
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
					$link = '<img class="embeddimage" alt="" src="'.$path.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'" >';
				}
			}
			elseif ($height!= '' && $height > 0)
			{
				$path = swImageDownscale($val, 0,$height);
				
				if ($path)
				{
					$link = '<img class="embeddimage" alt="" src="'.$path.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'"  width ="'.$width.'" height ="'.$height.'" >';
				}
				
			}
			else
			{
				$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'" >';
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
					$link = '<img class="embeddimage" alt="" src="'.$path.'" >';
				}
				else
				{
					$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'"  width ="'.$width.'" >';
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
			$link = '<img class="embeddimage" alt="" src="site/files/'.$val.'" >';
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
			$e = '';
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
			$e = '';
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
		
		
	    // Secure Download Links
		preg_match_all("/\[\[Download:([^\|]*)\]\]/U", $s, $matches, PREG_SET_ORDER);
		
		global $swMediaFileTypeDownload;
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			
			// extensions for download not in new window and filebased class for link
			$pos = strrpos($val,'.');
			
			$t = '';
			$e = '';
			if ($pos)
			{
				$e = substr($val,$pos).'.';
				if (stristr($swMediaFileTypeDownload.'.',$e))
					$t = "";
			}
			$e = str_replace('.','',$e);
			
			$link = '<a class="sw-'.$e.'" href="index.php?action=download&name=Image:'.$val.'" '.$t.' target="_blank">'.$val.'</a>';
			
			if ($this->ignorelinks) $link = '';
			$s = str_replace('[[Download:'.$val.']]', $link, $s); 
		}
		
		
		// Secure Download Links with alt text
		 preg_match_all("@\[\[Download:([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER); 
		
		foreach ($matches as $v)
		{
			$val = $v[1];
			$label = $v[3];
			
			$t = 'target="_blank"';
			
			
			// extensions for download not in new window and filebased class for link
			$pos = strrpos($val,'.');
			$t = '';
			$e = '';
			if ($pos)
			{
				$e = substr($val,$pos).'.';
				if (stristr($swMediaFileTypeDownload.'.',$e)) 
					$t = "";
			}
			$e = str_replace('.','',$e);
			
			$link = '<a class="sw-'.$e.'" ndex.php?action=download&name=Image:'.$val.'" '.$t.' target="_blank">'.$label.'</a>';
			if ($this->ignorelinks) $link = '';
			$s = str_replace('[[Download:'.$val.'|'.$label.']]', $link, $s); 
		}
	
		
		$wiki->parsedContent = $s;
				
		
		
	}

}

$swParsers['images'] = new swImagesParser;


?>