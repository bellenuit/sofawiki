<?php

if (!defined('SOFAWIKI')) die('invalid acces'); 



include_once $swRoot.'/inc/special/update.php';

if (swGetArrayValue($_REQUEST,'submitdecompress',false))
{
	
	
	mkdir($swRoot.'/siteinstall');
	mkdir($swRoot.'/siteinstall/revisions');
	mkdir($swRoot.'/siteinstall/files');
	mkdir($swRoot.'/siteinstall/logs');
	$files = glob($swRoot.'/restore/*');
	foreach ($files as $f)
	{
		if (stristr($f,'revisions') && stristr($f,'.zip'))
			unzip($f,$swRoot.'/siteinstall/revisions');
		if (stristr($f,'files') && stristr($f,'.zip'))
			unzip($f,$swRoot.'/siteinstall/files');
		if (stristr($f,'logs') && stristr($f,'.zip'))
			unzip($f,$swRoot.'/siteinstall/logs');
	}
}
if (swGetArrayValue($_REQUEST,'submitinstall',false))
{
	
	$files = rglob($swRoot.'/siteinstall/sofawiki/siteinstall/*');
	foreach ($files as $f)
	{
		$newfile = str_replace($swRoot.'/siteinstall/sofawiki/',$swRoot.'/site/',$f);
		
		if ( is_dir($f) )
		{
			mkdir($newfile);
			echo '<p>mkdir '.$newfile;
		}
		elseif (rename($f,$newfile))
		{
			echo '<p>rename '.$newfile;
		}
		else
		{ 
			if( file_exists($f) )
				echo '<p><b>error</b> rename '.$newfile; 
			// else it was probably already used
		}
	}
	
	$files = rglob($swRoot.'/siteinstall/*');
	arsort($files);
	$files[] = $swRoot.'/siteinstall';
	foreach ($files as $f)
	{	
		@unlink($f);
		@rmdir($f);
		echo '<p>remove '.$f;
	}
	
	die('<p>Installation complete. <a href="index.php?">Reload index page now</a>');
}



$swParsedName = 'Special:Restore '.$n; 

$swParsedContent = '<p>Restore Sofawiki from Restore';
$swParsedContent .= '<br/>Use with care</p>';

$swParsedContent .= '<p>Put the zip files into a restore folder in the root.
<br/><b>Decompress</b> the zip file into a folder siteinstall in the SofaWiki root. Check that all files are there.
<br/><b>Install</b>: Replace the site/revisions, site/files, site/logs folder by the versions in the siteinstall folder
and delete the siteinstall folder
</p>';


if (!swGetArrayValue($_REQUEST,'submitdecompress',false))

$files = glob("$swRoot/restore/*");

if (count($files)>0)
{
	$swParsedContent .= "<p>Files in restore folder</p>";
	
	foreach ($files as $f)
	{	
		$f = str_replace($swRoot.'/restore/','',$f);
		$swParsedContent .= "\n$f<br>";
	}

	$swParsedContent .= "\n<form method='get' action='index.php'><p>";
	$swParsedContent .= "\n<input type='hidden' name='name' value='special:restore'>";
	$swParsedContent .= "\n<input type='submit' name='submitdecompress' value='Decompress' />";
	$swParsedContent .= "\n</p></form>";
}
else
{
	$swParsedContent .="\nError: <b>restore</b> folder missing or empty";
}



$files = rglob("$swRoot/siteinstall/*");

if (count($files)>0)
{
	$swParsedContent .= "<p>Decompressed files</p>";
	
	foreach ($files as $f)
	{	
		$f = str_replace($swRoot.'/restore/','',$f);
		$swParsedContent .= "\n$f<br>";
	}

	
	$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
	$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:restore'>";
	$swParsedContent .= "\n<input type='submit' name='submitinstall' value='Install' style='color:red'/>";
	$swParsedContent .= "\n</p></form>";
}






$swParseSpecial = false;



?>