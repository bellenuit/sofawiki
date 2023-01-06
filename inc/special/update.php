<?php

if (!defined('SOFAWIKI')) die('invalid acces'); 


function wgets($FromLocation,$ToLocation)
{

 $VerifyPeer=false; $VerifyHost=false; 
// Initialize CURL with providing full https URL of the file location
$Channel = curl_init($FromLocation);
 
// Open file handle at the location you want to copy the file: destination path at local drive
$File = fopen ($ToLocation, 'w');
 
// Set CURL options
curl_setopt($Channel, CURLOPT_FILE, $File);
 
// We are not sending any headers
curl_setopt($Channel, CURLOPT_HEADER, 0);
 
// Disable PEER SSL Verification: If you are not running with SSL or if you don't have valid SSL
curl_setopt($Channel, CURLOPT_SSL_VERIFYPEER, $VerifyPeer);
 
// Disable HOST (the site you are sending request to) SSL Verification,
// if Host can have certificate which is invalid / expired / not signed by authorized CA.
curl_setopt($Channel, CURLOPT_SSL_VERIFYHOST, $VerifyHost);
 
// Execute CURL command
curl_exec($Channel);
 
// Close the CURL channel
curl_close($Channel);
 
// Close file handle
fclose($File);
 
	// return true if file download is successfull
	return file_exists($ToLocation);
}

function rglob($pattern)
{
	// used non recursive solution
	$patternlist = array($pattern);
	$donelist = array();
	$list = array();
	while (count($patternlist)>0)
	{
		$p = array_shift($patternlist);
		$donelist[$p] = true;
		$files = glob($p);
		if (!is_array($files)) $files = array();
		if (count($files)>0)
		{
			foreach ($files as $f)
			{	
				$list[$f] = $f;
				$nextpattern = $f."/*";
				if (!isset($donelist[$nextpattern]))
				$patternlist[] = $nextpattern;
			}
		}
		
	}
	asort($list);
	return $list;
}


$file = swGetArrayValue($_REQUEST,'file');
$n = "";
if (swGetArrayValue($_REQUEST,'submitdownload',false) && $file !='')
{
	$serverfile = 'https://www.sofawiki.com/site/files/'.$file;
	$localfile = $swRoot.'/'.$file;
	wgets($serverfile,$localfile);
	$n = 'downloaded '.$file;
}
if (swGetArrayValue($_REQUEST,'submitdecompress',false) && $file !='')
{
	@mkdir($swRoot.'/install/');
	unzip($swRoot.'/'.$file,$swRoot.'/install/');
	$n = 'decompressed '.$file;
}
if (swGetArrayValue($_REQUEST,'submitinstall',false))
{
	
	$files = rglob($swRoot.'/install/sofawiki/inc/*');
	foreach ($files as $f)
	{
		$newfile = str_replace($swRoot.'/install/sofawiki/',$swRoot.'/',$f);
		
		if ( is_dir($f) )
		{
			@mkdir($newfile);
			@chmod($newfile,0775);
			echo '<p>mkdir '.$newfile;
		}
		elseif (rename($f,$newfile)   )
		{
			chmod($newfile,0664);
			echo '<p>rename '.$newfile;  
		}
		else
		{ 
			if( file_exists($f) )
				echo '<p><b>error</b> rename '.$newfile; 
			// else it was probably already used
		}
	}
	
	if (rename($swRoot.'/install/sofawiki/api.php',$swRoot.'/api.php'))
	{ chmod($swRoot.'/api.php',0664);
	echo '<p>rename api.php'; }
	else
	{ echo '<p><b>error</b> rename api.php'; }
	if (rename($swRoot.'/install/sofawiki/index.php',$swRoot.'/index.php') )
	{ 	chmod($swRoot.'/index.php',0664);
		echo '<p>rename index.php'; }
	else
	{ echo '<p><b>error</b> rename index.php'; } 
	if (rename($swRoot.'/install/sofawiki/cron.php',$swRoot.'/cron.php') )
	{ chmod($swRoot.'/cron.php',0664);
		echo '<p>rename cron.php'; }
	else
	{ echo '<p><b>error</b> rename cron.php'; }
	
	
	$files = rglob($swRoot.'/install/*'); 
	if (!is_array($files)) $files = array();
	arsort($files);
	$files[] = $swRoot.'/install';
	foreach ($files as $f)
	{	
		@unlink($f);
		@rmdir($f);
		echo '<p>remove '.$f;
	}
	
	die('<p>Installation complete. <a href="index.php?name=special:special-pages">Reload special page now</a>');
}



$swParsedName = 'Special:Update '.$n; 

$swParsedContent = '<p>Update SofaWiki version from https://www.sofawiki.com/';
$swParsedContent .= '<br/>Use with care</p>';

$swParsedContent .= '<p>The update has 3 steps:
<br/><b>Download</b> the zip file from the remote server to your server into the SofaWiki root. 
<br/><b>Decompress</b> the zip file into a folder install in the SofaWiki root. Check that all files are there.
<br/><b>Install</b>: Replace the inc folder and the index.php and the api.php file by the versions in the install folder
and delete the install folder
</p>';


$swParsedContent .= '<p>Available versions</p>';

$serverfile = "https://www.sofawiki.com/site/files/snapshot.txt";
$localfile = $swRoot.'/snapshot.txt';

wgets($serverfile,$localfile);


$filelist = explode("\n",file_get_contents($swRoot.'/snapshot.txt'));

$swParsedContent .= "\n<form method='get' action='index.php'><p>";
$swParsedContent .= "\n<select name='file'>";
arsort($filelist);
foreach ($filelist as $f)
{	
	if ($f !=  "" && !preg_match("/[a-z]\.zip/",$f))
	$swParsedContent .= "<option value='$f'>$f</option>";
}
$swParsedContent .= "\n</select>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:update'>";
$swParsedContent .= "\n<input type='submit' name='submitdownload' value='Download' />";
$swParsedContent .= "\n</p></form>";

$swParsedContent .= '<p>Development versions</p>';

$serverfile = "https://www.sofawiki.com/site/files/snapshot.txt";
$localfile = $swRoot.'/snapshot.txt';

wgets($serverfile,$localfile);


$filelist = explode("\n",file_get_contents($swRoot.'/snapshot.txt'));

$swParsedContent .= "\n<form method='get' action='index.php'><p>";
$swParsedContent .= "\n<select name='file'>";
arsort($filelist);
foreach ($filelist as $f)
{	
	if ($f !=  "" && preg_match("/[a-z]\.zip/",$f))
	$swParsedContent .= "<option value='$f'>$f</option>";
}
$swParsedContent .= "\n</select>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:update'>";
$swParsedContent .= "\n<input type='submit' name='submitdownload' value='Download' />";
$swParsedContent .= "\n</p></form>";





$files = glob("$swRoot/snapshot*.zip");

if (count($files)>0)
{
	$swParsedContent .= "<p>Downloaded versions</p>";

	$swParsedContent .= "\n<form method='get' action='index.php'><p>";
	$swParsedContent .= "\n<select name='file'>";
	arsort($files);
	foreach ($files as $f)
	{	
		$f = str_replace("$swRoot/","",$f);
		$swParsedContent .= "<option value='$f'>$f</option>";
	}
	$swParsedContent .= "\n</select>";
	
	$swParsedContent .= "\n<input type='hidden' name='name' value='special:update'>";
	$swParsedContent .= "\n<input type='submit' name='submitdecompress' value='Decompress' />";
	$swParsedContent .= "\n</p></form>";
}








$files = rglob("$swRoot/install/*");



if (count($files)>0)
{
	$swParsedContent .= "<p>Decompressed files</p>";
	
	foreach ($files as $f)
	{	
		$swParsedContent .= "$f\n";
	}

	
	$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
	$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:update'>";
	$swParsedContent .= "\n<input type='submit' name='submitinstall' value='Install' style='color:red'/>";
	$swParsedContent .= "\n</p></form>";
}





$swParseSpecial = false;



?>