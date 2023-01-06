<?php

if (!defined("SOFAWIKI")) die("invalid acces");


class swUploadZoneFunction extends swFunction
{

	function info()
	{
	 	return "(name, prefix) provides a dropzone to upload a file to a given filename for any user having access to that page";
	}

	
	function dowork($args)
	{

		if (isset($args[1])) $filename = $args[1]; else $filename = '';	
		if (isset($args[2])) $prefix = $args[2]; else $prefix = '';	
		
		global $swMaxBigFileSize;
		global $lang;
		global $swRoot;
		
		
		// clean up chunks older than 24h
		foreach (glob($swRoot.'/site/uploadbig/*') as $path)
		{ 
		    $fn = basename($path);
		    if (strlen($fn)==32)  // md5 is 32 
		    {
			    //echo $filename.' ';
			    $now = time();
			    $fd = filemtime($path);
			    $d = $now - $fd;
			    if ($d > 86400)
			    {
				   // echo 'unlink '.$filename.' ';
				    unlink($path);
			    }
		    }
		}

		// Most is async with callbacks.
		// Read the file locally in chunks of 1 MB and create a joblist of MD5 fingerprints
		// Set the uploadlist to the joblist.
		// Check which chunks are already present on the server and remove them from the uploadlist. (Resiliant re-upload.)
		// Upload the first chunk. If the server replies ok, remove it from the uploadlist.
		// Upload all other chunks.
		// Check if all chunks are on the server.
		// Ask the server to compose the file
	
		$result = '<nowiki><div id="editzone" class="editzone specialuploadbig">
		<div class="editheader">'.swSystemMessage("upload-big",$lang).'</div>
		<form action="#" method="post" enctype="multipart/form-data" onsubmit="event.stopPropagation(); event.preventDefault(); parseFile(thefile,thename,\''.$prefix.'\',thecomment); return false;">
		<input type="hidden" name="MAX_FILE_SIZE" value="'.$swMaxBigFileSize.'" />
		<input type="hidden" name="action" value="uploadbigfile" />
		<input type="submit" value="Upload" disabled="disabled"  />
		<input type="file" name="uploadedfile" onchange="thefile = event.target.files[0]; 
if (thefile.size > '.$swMaxBigFileSize.') alert(\'file too big: \' + thefile.size); else
this.previousElementSibling.disabled = false;" />
		<p id="status" width=100%></p>';
		
		if ($filename)
		{
			$result .= PHP_EOL.'<p>Filename</p>
		<input type="text" name="filename" value="'.$filename.'" size="30" readonly/>';
		}
		else
		{
			$result .= PHP_EOL.'<p>Filename</p>
		<input type="text" name="filename" value="" size="30" oninput="thename=\''.$prefix.'\'+event.target.value;"/>
		<p>';
		}
		
		$result .= PHP_EOL.'<script>thename = "'.$filename.'" </script>';
		
		$result .= PHP_EOL.'<p>Comment</p> 
<textarea name="content" rows="5" cols="50" oninput="thecomment=event.target.value;"/></textarea></form>
		<script>thecomment = ""</script>';
		
		if ($filename)
		{
			$result .= '<div id="help">'.str_replace('{swMaxBigFileSize}', round($swMaxBigFileSize/1024/1024,0), swSystemMessage("uploadbig-help-nofilename",$lang)).'</div><!-- help -->';
		}
		else
		{
			$result .= '<div id="help">'.str_replace('{swMaxBigFileSize}', round($swMaxBigFileSize/1024/1024,0), swSystemMessage("uploadbig-help",$lang)).'</div><!-- help -->';

		}
		
			
		$result .= PHP_EOL.'</div>
		<pre id="progress" width=100% style="display:none"></pre>
		<pre id="console" width=100% style="display:none"></pre>
		<script src="inc/skins/upload.js"></script>
		<script src="inc/skins/md5.js"></script></nowiki>';
		
		
		return $result;
		
		
		
	}

}

$swFunctions["uploadzone"] = new swUploadZoneFunction;


function swHandleUploadFile($file, $filename, $content='',$deleteexisting=false)
{
	global $swStatus;
	global $swRoot;
	global $swError;
	global $user;
	
	if ($filename == "") $filename = $file['name'];
	
	$newfile = $swRoot.'/site/files/'.$filename;
	
	if (is_uploaded_file($file['tmp_name']))
	{
	   $swStatus .= "\nFile ".$filename.' uploaded. ';
	   	   
	   if (file_exists($newfile))
	   {
	   		if ($deleteexisting)
	   		{
	   			$swStatus .= 'Deleting existing file. ';
	   		}
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
	   
	   if (!move_uploaded_file($file['tmp_name'],$newfile)) 
		  {
		  // if an error occurs the file could not
		  // be written, read or possibly does not exist
		  $swError .=  'Error Uploading File. '.$newfile.' ';
	   }
	   else
	   {
			$swStatus .=  'OK. ';
			
			$wiki= new swWiki;
	$wiki->name ='Image:'.$filename;
	$wiki->user = $user->name;
	$wiki->content = str_replace("\\",'',$content)
	.PHP_EOL.'[[imagechecksum::'.md5_file($newfile).']]';
	if ($filename != "")
			$wiki->insert();		
	   }
	   
	   
	   
	
	
		return $filename;
	}
	else
	{
		$swError .=  'Error: File '.$filename.' not uploaded. ';
	}
	
	
	
	return $filename;

}


?>