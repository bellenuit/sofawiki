<?php 

// This file must be text encoding UTF8 no BOM not to get problems with cookies

/*

This code protects atomic file write operations.
A PHP thread creates a file when starting to write. 
It deletes the file once finished.

If the file cannot created, it waits. 
The waiting has a timeout, because the other thread might not have closed the sempahore.

To prevent a thread blocking itself, a global variable is also set to override the semaphore.

Before this, the script was protected by SESSION, which blocks the acesss. This level is however not needed.
We just need to protect the write operation


*/
$swSempahore = false;
$swSemaphoreTimeOut = 15;

function swSemaphoreSignal($path='') 
{
	global $swSempahore;
	global $swRoot;
	global $swSemaphoreTimeOut;
	
	$md5path = md5($path);
	
	$file = $swRoot.'/site/indexes/'.$md5path.'semaphore.txt';
	
	if ($swSempahore) return;
	
	$i=0;
	while ($i<$swSemaphoreTimeOut)
	{
		if ($handle = @fopen($file,"x"))  // returns false if file already exists
		{
			$swSempahore = true;	
			fclose($handle);
			return;
		}
		echotime("semaphore wait ".$md5path);
		sleep(1);
		$i++;
	}
	echotime("semaphore overruled");
	global $username;
	global $name;
	global $action;
	global $query;
	global $lang;
	global $referer;
	$time = 0;
	$error = "semaphore overruled";
	$message = "";
	$receiver = "";
	$label = "";
	swLog($username,$name,$action,$query,$lang,$referer,$time,$error,$label,$message,$receiver);
	swSemaphoreRelease();
}


function swSemaphoreRelease($path='')
{
	global $swSempahore;
	global $swRoot;
	
	$md5path = md5($path);
	
	$file = $swRoot.'/site/indexes/'.$md5path.'semaphore.txt';

	
	
	@unlink($file);
	$swSempahore = false;
}


?>
