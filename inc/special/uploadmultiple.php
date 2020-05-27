<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Upload multiple';
$swParsedContent = 'All files in the Upload directory will be moved to the file directory and image pages will be created';

$files = glob($swRoot.'/site/upload/*.*');
natsort($files);
$found = false;
$i=0;
foreach($files as $file)
{
	
	// limit to 500
	
	$i++;
	if ($i>500) continue;
	
	if (substr($file,0,1) != '.')
	{
		$shortname = str_replace($swRoot.'/site/upload/','',$file);
		$file2 = $swRoot.'/site/files/'.$shortname;
		rename($file,$file2);

		$wiki->name ='Image:'.$shortname;
		$wiki->user = $user->name;
		$wiki->content = '[[imagechecksum::'.md5_file($file2).']]';
		if ($file != '')
			$wiki->insert();

		$swParsedContent .=  '<br/><a href="'.$wiki->link('').'">Image:'.$file.'</a>';
		
		$found = true;
		
	}		
}

$wiki->name = 'Upload multiple';

if ($i>100) $swParsedContent .= '<br/><br/>Limited to 500 files. Reload to get the rest of it.'; 
if (!$found) $swParsedContent .= '<br/><br/>No file found'; 

$swParseSpecial = false;



?>