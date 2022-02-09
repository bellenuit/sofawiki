<?php
	
if (!defined('SOFAWIKI')) die('invalid acces');


// to do: clean up chunks older than 24h


	
if (isset($_FILES['uploadedfile']) && is_uploaded_file($_FILES['uploadedfile']['tmp_name']))
{
	 // move file to folder
	 
	 $filename = $_FILES['uploadedfile']['name'];
	 $newfile = $swRoot.'/site/uploadbig/'.$filename;
	 move_uploaded_file($_FILES['uploadedfile']['tmp_name'],$newfile);
	 
	 // check md5
	 
	 //echo filesize($newfile);
	 $handler = fopen($newfile,'r');
	 $s = fread($handler,filesize($newfile));
	 $checksum = md5($s);
	 if ($checksum == $filename)
	 {
		 echo 'upload ok '.$filename;
	 }
	 else
	 {
		 echo 'checksum error filename '.$filename.' checksum '.$checksum. ' length '.filesize($newfile);
		 unlink($newfile);
	 }
	 
	 exit();
}

if (isset($_POST['checkchunks']))
{
	$found = false;
	foreach($_POST['checkchunks'] as $chunk)
	{
		$file = $swRoot.'/site/uploadbig/'.$chunk;
		if (file_exists($file))
		{
			echo $chunk.PHP_EOL;
			$found = true;
		}
	exit();
}

if (isset($_POST['composechunks']) && isset($_POST['filename']))
{
	$found = false;
	
	$newfile = $swRoot.'/site/uploadbig/'.$_POST['filename'];
	
	$newhandler = fopen($newfile,'c');
	
	foreach($_POST['composechunks'] as $chunk)
	{
		$file = $swRoot.'/site/uploadbig/'.$chunk;
		if (!file_exists($file))
		{
			echo 'compose error '.$chunk;
			exit();
		}
		else
		{
			$handler = fopen($file,'r');
			$s = fread($handler,filesize($file));
			fwrite($newhandler,$s);
			fclose($handler);
		}
	}
	
	foreach($_POST['composechunks'] as $chunk)
	{
		$file = $swRoot.'/site/uploadbig/'.$chunk;
		unlink($file);
	}
	
	fclose($newhandler);
	echo 'OK <a href="site/uploadbig/'.$_POST['filename'].'">'.$_POST['filename'].'</a>';
	exit();
}


echo "not here";

exit();

