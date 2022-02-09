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
	
		$result = '<nowiki><div id="editzone" class="editzone">
		<div class="editheader">'.swSystemMessage("upload-big",$lang).'</div>
		<form action="#" method="post" enctype="multipart/form-data" onsubmit="event.stopPropagation(); event.preventDefault(); parseFile(thefile,thename,\''.$prefix.'\',thecomment); return false;">
		<input type="hidden" name="MAX_FILE_SIZE" value="'.$swMaxBigFileSize.'" />
		<input type="hidden" name="action" value="uploadbigfile" />
		<input type="submit" value="Upload" disabled="disabled"  />
		<input type="file" name="uploadedfile" onchange="thefile = event.target.files[0]; this.previousElementSibling.disabled = false;" />
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



?>