<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Uploading File';
$swParsedContent = '';




$file = $_FILES['uploadedfile'];
$filename = trim($_POST['filename']);

if ($filename=="")
	$filename = $_FILES['uploadedfile']['name'];


if (is_uploaded_file($_FILES['uploadedfile']['tmp_name']))
	{
	   $swStatus .= "\nFile ".$filename.' uploaded. ';
	   
	   $newfile = $swRoot.'/site/files/'.$filename;
	   if (file_exists($newfile))
	   {
	   		if (isset($_POST['deleteexisting']))
	   			$swStatus .= 'Deleting existing file. ';
	   		else
	   		{
	   			$filename0 = $filename;
	   			$fields = explode('.',$filename0);
	   			if (count($fields)>1)
	   				$fext = array_pop($fields);
	   			$froot = join('.',$fields);
	   			
	   			$i=0;
	   			while (file_exists($newfile))
	   			{
	   				$i++;
	   				$filename = $froot.$i.'.'.$fext;
	   				$newfile = $swRoot.'/site/files/'.$filename;
	   			}
	   			$swStatus .= 'Renaming uploaded file as: '.$filename.'. ';
	   		}
	   }
	   
	   if (!move_uploaded_file($_FILES['uploadedfile']['tmp_name'],$newfile)) 
		  {
		  // if an error occurs the file could not
		  // be written, read or possibly does not exist
		  $swError .=  '<br/><span class="error">Error Uploading File. '.$newfile.'</span>';
	   }
	   else
	   {
			$swStatus .=  'OK. ';
	   }
	}
	else
	{
		$swError .=  'Error: File '.$filename.' not uploaded. ';
	}



$wiki->name ='Image:'.$filename;
$wiki->user = $user->name;
$wiki->content = str_replace("\\",'',$content)
.PHP_EOL.'[[imagechecksum::'.md5_file($newfile).']]';
if ($filename != "")
	$wiki->insert();

$swParsedContent .=  '<p><a href="'.$wiki->link('').'">Image:'.$filename.'</a>';
$swParsedContent .=  '<p><img src="site/files/'.$filename.'" style="width:100%">';





?>