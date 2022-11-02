<?php
	
if (!defined('SOFAWIKI')) die('invalid acces');

ini_set('max_execution_time',180);

if (!is_dir($swRoot.'/site/uploadbig/')) @mkdir($swRoot.'/site/uploadbig/');

$deletelaterfile = $swRoot.'/site/uploadbig/deletelater.txt';

if (file_exists($deletelaterfile)) 
{
	$s = file_get_contents($deletelaterfile);
	$deletelater = array();
	if ($s) $deletelater = unserialize($s);
	for ($i = 0;$i < 100; $i++)
	{
		if (count($deletelater))
		{
			$chunk = array_pop($deletelater);
			$file = $swRoot.'/site/uploadbig/'.$chunk;
			if (file_exists($file)) unlink($file);
		}
	}
	file_put_contents($deletelaterfile, serialize($deletelater));	
}



//  Entry point 1 - upload chunks
//  Receive one chunk of 1 MB, save it to the temporary folder with md5 as name,
//  Check integrity with MD5. Delete chunk if there is a checksum error.
	
if (isset($_FILES['uploadedfile']) && is_uploaded_file($_FILES['uploadedfile']['tmp_name']))
{	 
	 $filename = $_FILES['uploadedfile']['name'];
	 $newfile = $swRoot.'/site/uploadbig/'.$filename;
	 move_uploaded_file($_FILES['uploadedfile']['tmp_name'],$newfile);
	 
	 $checksum = md5_file($newfile);
	 if ($checksum == $filename)
	 {
		 echo 'upload ok '.$filename;
	 }
	 else
	 {
		 echo 'checksum error filename '.$filename.' checksum '.$checksum. ' length '.filesize($newfile);
		 unlink($newfile);
	 }
	 
	 $_FILES = null;
	 
	 exit();
}

//  Entry point 2 - check completeness
//  Reveive a list of chunks MD5 and return the list of them that are present on the server.


if (isset($_POST['checkchunks']))
{
	$found = array();
	
	$checkchunks = explode(',',$_POST['checkchunks']);
	foreach($checkchunks as $chunk)
	{
		$file = $swRoot.'/site/uploadbig/'.$chunk;
		if (file_exists($file))
		{
			$found[] = $chunk;
		}
	}
	if (count($found))
		echo json_encode($found);
		
	$_POST = null;
	
	exit();
}

//  Entry point 3 - compose the original file
//  Reveive an ordered list of MD5. Concatenate the files and save it as a filename
//  Move the file to site/files/ and create the Image page
//  Return link to Image page


if (isset($_POST['composechunks']) && isset($_POST['filename']))
{
	

	
	$found = false;
	
	$filename = swSimpleSanitize($_POST['filename']); 
	$fields = explode('.',$filename);
	$extension = array_pop($fields);
	$filename = join('-',$fields);
	$filename = swNameUrl($filename).'.'.$extension;
	$start = $_POST['start'];
	$limit = 20;
	
	if(!$filename) 
	{		
		echo 'compose error no filename';
		exit();
	}	
	
	$comment == '';
	if (isset($_POST['comment'])) $comment = swSimpleSanitize($_POST['comment']);

	$uploadtime = '';
	if (isset($_POST['uploadtime'])) $uploadtime = $_POST['uploadtime'];
	
	$newfile = $swRoot.'/site/uploadbig/'.$filename;
	
	$newhandler = fopen($newfile,'c');
	
	$composechunks = explode(',',$_POST['composechunks']);
	
	$i = 0;
	$offset = 0;
	foreach($composechunks as $chunk)
	{
		
		
		$file = $swRoot.'/site/uploadbig/'.$chunk;
		if (!file_exists($file))
		{
			echo 'compose error chunk '.$chunk;
			exit();
		}
		else
		{
			if ($i < $start)
			{
				$offset += filesize($file);
				$i++;
				continue;
			}
			
			if ($i >= $start + $limit)
			{
				echo 'limit '.$i;
				exit();
			}
			
			$handler = fopen($file,'r');
			
			fseek($newhandler,$offset);
			
			stream_copy_to_stream($handler,$newhandler);
			
			$offset += filesize($file);
			
			/*
			fseek($newhandler,$offset);
			$s = fread($handler,filesize($file));
			fwrite($newhandler,$s);
			
			*/
			
			fclose($handler);
			
			 
			$i++;
			
		}
	}
	
	$i = 0;

	$deletelater = array();
	if (file_exists($deletelaterfile)) 
	{
		$s = file_get_contents($deletelaterfile);
		if ($s) $deletelater = unserialize($s);
	}
	
	foreach($composechunks as $chunk)
	{
		// server cannot delete more than 1000 files at a reasonable time. we need to postpone this.
		if ($i<1000)
		{
			$file = $swRoot.'/site/uploadbig/'.$chunk;
			unlink($file);
		}
		else
		{			
			$deletelater[] = $chunk;
		}
		
		$i++;
	}
	file_put_contents($deletelaterfile, serialize($deletelater));
	
	
	fclose($newhandler);
	
	// move file
	$newfile2 = $swRoot.'/site/files/'.$filename;
	if (file_exists($newfile2)) unlink($newfile2);
	rename($newfile,$newfile2);
	
	
	$filewiki = new swWiki;
	$filewiki->name ='Image:'.$filename;
	$filewiki->user = $user->name;
	$filewiki->content = '[[imagechecksum::'.md5_file($newfile2).']]
[[filesize::'.filesize($newfile2).']]
[[uploadtime::'.$uploadtime.']]	
[[comment::'.$comment.']]';
	$filewiki->insert();
	
	
	
	echo 'OK '.$filename;
	
	if ($user->hasright('view','image:')) echo '<br><a href="index.php?name=Image:'.$filename.'" target="_blank">Image:'.$filename.'</a>';
	
	$_POST = null;
	
	exit();
}


echo 'not here<br>'.print_r($_POST);

exit();

