<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Import';
$swParsedContent = 'Imports pages from the Upload directory as new revisions. It accept also Zip files.';

$files = glob($swRoot.'/site/upload/*.zip');
foreach($files as $file)
{
	unzip($file,$swRoot.'/site/upload');
	unlink($file);
}

$dirs = array_filter(glob($swRoot.'/site/upload/*'), 'is_dir');
foreach($dirs as $dir)
{
	$files = glob($dir.'/*.txt');
	foreach($files as $file)
	{
		$f0 = str_replace($dir.'/','',$file);
		rename($file,$swRoot.'/site/upload/'.$f0);
	}
	rmdir($dir);
}


$files = glob($swRoot.'/site/upload/*.txt');
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
		$s = file_get_contents($file);
		$w = new swWiki;
		$w->error = ''; 
		
		$w->revision = swGetValue($s,"_revision");
		$w->name = swGetValue($s,"_name");
		$w->user = swGetValue($s,"_user");
		$w->timestamp = swGetValue($s,"_timestamp");
		$w->status = swGetValue($s,"_status");
		$w->comment = swGetValue($s,"_comment");
		$w->encoding = swGetValue($s,"_encoding");
		$pos = strpos($s,"[[_ ]]");
		$w->content = substr($s,$pos+strlen("[[_ ]]"));
		
		
		$w->user = 'Import '.$user->name;
		
		if ($w->name != '')
		$w->insert();
		
		$swParsedContent .=  '<br/>Imported page: <a href="'.$w->link('').'"> '.$w->name.'</a>';
		
		unset($w);
		unlink($file);
		
		$found = true;
		
	}		
}

$wiki->name = 'Import';

if ($i>500) $swParsedContent .= '<br/><br/>Limited to 500 files. Reload to get the rest of it.'; 
if (!$found) $swParsedContent .= '<br/><br/>No text file found in site/upload/'; 

$swParseSpecial = false;



?>