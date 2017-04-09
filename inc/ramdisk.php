<?php

/*

mounts a ramdisk and provides alternative path for cache files
is a pure reading cache, just duplicate files from harddisk

*/

if (!defined('SOFAWIKI')) die('invalid acces');


function swInitRamdisk()
{
	
	global $swRamdiskPath;
	global $swRoot;
	if (is_dir($swRamdiskPath)) 
	{
		 
	 $ramfree = sprintf('%0d',disk_free_space($swRamdiskPath)/1024/1024);
	 
	 echotime('ram free '. $ramfree. ' MB');
	
	 if ($ramfree < 16)
	 {
	 	// purge to prevent full ramdisk
	 	$files1 = glob($swRamdiskPath.'*.txt');
		shuffle($files);
		$files = array_slice($files,0,1000);
		foreach($files as $file) unlink($file);
		echotime('deleted 1000 files from ramdisk');
		$ramfree = sprintf('%0d',disk_free_space($swRamdiskPath)/1024/1024);
	 	echotime('ram free '. $ramfree. ' MB');
	 }
	 return;
	 
	}
	
	echotime('no ramdisk');
	$swRamdiskPath = '';
		
}

function swFileGet($path)
{

	global $swRamdiskPath;
	
	if (isset($swRamdiskPath) && $swRamdiskPath != '')
	{
		
		$hash = $swRamdiskPath.md5($path).'.txt';
		
		if (file_exists($hash))
			return file_get_contents($hash);
		else
		{
			$s = file_get_contents($path);
			if ($s != '')
			{
				echotime('tocache '.basename($path));
				file_put_contents($hash,$s);
			}
			return $s;
			
		}
		
	}
	else
		return file_get_contents($path);

}

function swUnlink($path)
{
	
	global $swRamdiskPath;
	
	if (file_exists($path))
		unlink($path);
	
	if (isset($swRamdiskPath) && $swRamdiskPath != '')
	{
		$hash = $swRamdiskPath.md5($path).'.txt';
		if (file_exists($hash))
			unlink($hash);
	}
}


if (isset($swRamdiskPath) && $swRamdiskPath != '')
	swInitRamdisk();


?>