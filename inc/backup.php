<?php

if (!defined("SOFAWIKI")) die("invalid acces");

function swExport($revisions)
{	
	global $swRoot;
	swSemaphoreSignal();
	if (!is_dir( "$swRoot/bak/")) mkdir ( "$swRoot/bak/",0777); 
	global $db;
	$today = date("Ymd",time());
	$result = "Export of SofaWiki revisions. <br/><br/>";
	
	$result .=  '<br/><br/>revisions';
	$dir = $swRoot.'/site/revisions';
	$files = array();
	foreach($revisions as $r)
	{
		$files[] = $dir.'/'.$r.'.txt';
	}
	natsort($files);
	
	foreach($files as $file)
	{
		$fn = str_replace($swRoot.'/site/revisions/','',$file);
		$thousand = (int)(str_replace('.txt','',$fn)/1000);
		$currentthousand = (int)($db->lastrevision/1000);
		$ziptest = 'export-'.$today.'.zip'; 
		if (!isset($zip))
		{
			$zip = $ziptest;
			$zipfile = new ZipArchive; 
			$zipfile->open($swRoot.'/bak/'.$zip, ZipArchive::CREATE);
		
		}
		if ($zip == $ziptest)
		{
			$zip = $ziptest;
			$result .=  '<br>'.$fn; 
			$zipfile -> addFile($file, $fn); 
		}
		else
			continue;
	}
	if (isset($zip))
	{
		$zipfile->close();
	}

	unset($zip);
	$result .=  '<br/><br/><a href="bak/'.$ziptest.'">'.$ziptest.'</a>';
	swSemaphoreRelease();
	return $result;

}

 
function swBackup($sitebackup, $logbackup, $revisionbackup, $filebackup)
{
	global $swRoot;
	swSemaphoreSignal();
	if (!is_dir( "$swRoot/bak/")) mkdir ( "$swRoot/bak/",0777); 
	global $db;
	$today = date("Ymd",time());
	$result = "Backup of SofaWiki site. <br/><br/>";
	
	if ($sitebackup)
	{
		$zipfile = new ZipArchive; 
		$filename = "site".".zip";
		$zipfile->open($swRoot.'/bak/'.$filename, ZipArchive::CREATE);
	
		$emptydirectories = array();
		$includeddirectories = array();
	
		$emptydirectories[] = "sofawiki/site/cache"; 
		$emptydirectories[] = "sofawiki/site/current";
		$emptydirectories[] = "sofawiki/site/files";
		$emptydirectories[] = "sofawiki/site/indexes";
		$emptydirectories[] = "sofawiki/site/logs";
		$emptydirectories[] = "sofawiki/site/queries";
		$emptydirectories[] = "sofawiki/site/revisions";
		$emptydirectories[] = "sofawiki/site/upload";
		
		$includeddirectories[] = "sofawiki/site";
		$includeddirectories[] = "sofawiki/site/functions";
		$includeddirectories[] = "sofawiki/site/skins";
		
		$files = array();
		
		
		foreach($emptydirectories as $dir)
		{
			$zipfile -> addEmptyDir($dir."/");
			$result .= "$dir<br/>";
		}
		
		foreach($files as $file)
		{
			$zipfile -> addFile($file, "sofawiki/$file"); 
			$result .= "&nbsp;$file<br/>";
		}
		
		
		foreach ($includeddirectories as $dir)
		{
			$zipfile -> addEmptyDir($dir."/");
			$result .= "$dir<br/>";
			$dir = substr($dir,strlen("sofawiki/"));
			$absolutedir = $swRoot.'/'.$dir;
			
			$formats = array("php","css","txt");
			
			foreach ($formats as $f)
			{
				$files = glob($absolutedir."/*.$f");
				if (is_array($files))
				{
					natsort($files);
					foreach($files as $file)
					{
						$zf = str_replace($swRoot,"sofawiki",$file);
						$zipfile -> addFile($file, $zf); 
						$result .= "&nbsp;$zf<br/>";
					}
				}
			}
		}
		
		
		
		$zipfile->close();
		unset($zip);
		
		$result .=  "<br/>$filename";
		
		$filename2 =  'site'.$today.'.zip';
		copy($swRoot.'/bak/'.$filename,$swRoot.'/bak/'.$filename2);
			
		$result .=  '<br/>'.$filename2;
	}
	else
	{
		$result .=  'Simple <a href="index.php?name=special:backup">site backup</a>'; 
		
	}
	
	// backup logs by month, do not if file already exists - one at a time
	
	if ($logbackup)
	{
	
		$result .=  '<br/><br/>logs';
		
		$dir = $swRoot.'/site/logs';
		$files = glob($dir.'/*.txt');
		natsort($files);
		
		foreach($files as $file)
		{
			$fn = str_replace($swRoot.'/site/logs/','',$file);
			$month = substr($fn,0,7);
			$currentmonth = date("Y-m",time());
			$ziptest = 'logs-'.$month.'.zip'; 
			if (file_exists($swRoot.'/bak/'.$ziptest) && $month != $currentmonth)
				continue;
			if (!isset($zip))
			{
				$zip = $ziptest;
				$zipfile = new ZipArchive; 
				$zipfile->open($swRoot.'/bak/'.$zip, ZipArchive::CREATE);
			}
			if ($zip == $ziptest)
			{
				$result .=  '<br>'.$fn; 
				$zipfile -> addFile($file, $fn); 
			}
			else
				continue;
		}
		if (isset($zip))
		{
			$zipfile->close();
		}
	}
	else
	{
		$result .=  '<br/><br/>Add <a href="index.php?name=special:backup&logs=1">logs option</a>  option to URL to backup logs (best once per month)'; 
	}
	
	
	unset($zip);
	// revisions by thousands, do not if file already exists - one at a time
	
	
	if ($revisionbackup)
	{
	
	
		$result .=  '<br/><br/>revisions';
		
		$dir = $swRoot.'/site/revisions';
		$files = glob($dir.'/*.txt');
		natsort($files);
		
		foreach($files as $file)
		{
			$fn = str_replace($swRoot.'/site/revisions/','',$file);
			$thousand = (int)(str_replace('.txt','',$fn)/1000);
			$currentthousand = (int)($db->lastrevision/1000);
			$ziptest = 'revisions-'.$thousand.'000.zip'; 
			if (file_exists($swRoot.'/bak/'.$ziptest) && $thousand != $currentthousand)
				continue;
			if (!isset($zip))
			{
				$zip = $ziptest;
				$zipfile = new ZipArchive; 
				$zipfile->open($swRoot.'/bak/'.$zip, ZipArchive::CREATE);
			
			}
			if ($zip == $ziptest)
			{
				$zip = $ziptest;
				$result .=  '<br>'.$fn; 
				$zipfile -> addFile($file, $fn); 
			}
			else
				continue;
		}
		if (isset($zip))
		{
			$zipfile->close();
		}
	}
	else
	{
		$result .=  '<br/><br/>Add <a href="index.php?name=special:backup&revisions=1">revisions option</a> to URL to backup revisions (best once per thousand)'; 
	}
	
	unset($zip);
	
	if ($filebackup)
	{
	
	
		$result .=  '<br/><br/>files';
		
		$dir = $swRoot.'/site/revisions';
		$files = glob($dir.'/*.txt');
		natsort($files);
		
		
		
		
		foreach($files as $file)
		{
			$fn = str_replace($swRoot.'/site/revisions/','',$file);
			$thousand = (int)(str_replace('.txt','',$fn)/100);
			$currentthousand = (int)($db->lastrevision/100);
			$ziptest = 'files-'.$thousand.'00.zip'; 
			if (file_exists($swRoot.'/bak/'.$ziptest) && $thousand != $currentthousand)
				continue;
			if (!isset($zip))
			{
				$zip = $ziptest;
				$zipfile = new ZipArchive; 
				if (!$zipfile->open($swRoot.'/bak/'.$zip, ZipArchive::CREATE))
					$result .= "<br>Zip File could not be created";
				$zipfile -> addEmptyDir('files');
			}
			if ($zip == $ziptest)
			{
				$record = new swRecord();
				$record->revision = str_replace('.txt','',$fn);
				$record->lookup();
				$wikiname = $record->name;
				
				if (strtolower(substr($wikiname,0,strlen('Image:')))==strtolower('Image:'))
				{
					$fn2 = substr($wikiname,strlen('Image:'));
					$file2 = $swRoot.'/site/files/'.$fn2;
					$zip = $ziptest;
					$result .=  '<br>'.$record->revision.' '.$fn2; 
					if (!file_exists($file2))
						$result .= ' ERROR file does not exist';
					elseif (!$zipfile->addFile($file2, 'files/'.$fn2))
						$result .= ' ERROR addFile';
				}
				else
				{
					//$result .=  '<br>NOT '.$r->revision; 
				}
			}
			else
				continue;
		}
		if (isset($zip))
		{
			//print_r($zipfile);
			if (!$zipfile->close())
				$result .= "<br>Zip File could not be closed ".$swRoot.'/bak/'.$zip;
			$result .=  '<br>'.$zip; 
		}
	}
	else
	{
		$result .=  '<br/><br/>Add <a href="index.php?name=special:backup&files=1">files option</a> to URL to backup files (best once per hundred)'; 
	}
	
	unset($zip);
	
	
	
	
	
	$result .=  '<br/><br/>';
	swSemaphoreRelease();
	return $result;
	
}


function swSnapShot($username)
{
	global $swRoot;
	global $db;
	swSemaphoreSignal();
	
	$result = "";
	$result .= "Backup of SofaWiki code. Site-specific files will not be included<br/><br/>";
	
	
	
	$zipfile = new ZipArchive; 
	$filename = 'snapshot.zip';
	$zipfile->open($swRoot.'/site/files/'.$filename, ZipArchive::CREATE);
	
	$emptydirectories = array();
	$includeddirectories = array();
	
	$emptydirectories[] = "sofawiki";
	$emptydirectories[] = "sofawiki/bak";
	$emptydirectories[] = "sofawiki/site";
	$emptydirectories[] = "sofawiki/site/cache";
	$emptydirectories[] = "sofawiki/site/current";
	$emptydirectories[] = "sofawiki/site/files";
	$emptydirectories[] = "sofawiki/site/functions";
	$emptydirectories[] = "sofawiki/site/indexes";
	$emptydirectories[] = "sofawiki/site/logs";
	$emptydirectories[] = "sofawiki/site/queries";
	$emptydirectories[] = "sofawiki/site/revisions";
	$emptydirectories[] = "sofawiki/site/skins";
	$emptydirectories[] = "sofawiki/site/upload";
	
	$includeddirectories[] = "sofawiki/inc";
	$includeddirectories[] = "sofawiki/inc/functions";
	$includeddirectories[] = "sofawiki/inc/parsers";
	$includeddirectories[] = "sofawiki/inc/skins";
	$includeddirectories[] = "sofawiki/inc/special";
	
	$files = array();
	$files[] = "index.php";
	$files[] = "api.php";
	$files[] = "cron.php";
	
	
	foreach($emptydirectories as $dir)
	{
		$zipfile->addEmptyDir($dir."/");
		$result .= "$dir<br/>";
	}
	
	foreach($files as $file)
	{
		$zipfile->addFile($swRoot.'/'.$file, 'sofawiki/'.$file); 
		$result .= '&nbsp;'.$file.'<br/>';
	}
	
	
	foreach ($includeddirectories as $dir)
	{
		$zipfile -> addEmptyDir($dir."/");
		$result .= "$dir<br/>";
		$dir = substr($dir,strlen("sofawiki/"));
		$absolutedir = "$swRoot/".$dir;
		$files = glob($absolutedir."/*.php");
		foreach($files as $file)
		{
			$zf = str_replace($swRoot,"sofawiki",$file);
			$zipfile -> addFile($file,$zf); 
			$result .= "&nbsp;$zf<br/>";
		}
		$files = glob($absolutedir."/*.css");
		if (is_array($files))
		{
			foreach($files as $file)
			{
				$zf = str_replace($swRoot,"sofawiki",$file);
				$zipfile -> addFile($file,$zf); 
				$result .= "&nbsp;$zf<br/>";
			}
		}
	
	}
	
	$today = date("Ymd",time());
	
	$zipfile->close();
	
	$wiki = new swWiki;
	$wiki->name ="Image:$filename";
	$wiki->user = $username;
	$wiki->content = str_replace("\\","","");
	if ($filename != "")
		$wiki->insert();
	
	$result .=  "<br/><a href='".$wiki->link("")."'>Image:$filename</a>";
	
	$filename2 =  'snapshot'.$today.'.zip';
	copy($swRoot.'/site/files/'.$filename,$swRoot.'/site/files/'.$filename2);
	global $swVersion;
	$filename3 = 'snapshot.'.$swVersion.'.zip';
	copy($swRoot.'/site/files/'.$filename,$swRoot.'/site/files/'.$filename3);
	copy($swRoot.'/site/files/'.$filename,$swRoot.'/bak/'.$filename2);
	copy($swRoot.'/site/files/'.$filename,$swRoot.'/bak/'.$filename3);
	
	$wiki->name ="Image:$filename2";
	$wiki->user = $username;
	$wiki->content = str_replace("\\","","");
	if ($filename2 != "")
		$wiki->insert();
		
	$result .=  "<br/><a href='".$wiki->link("")."'>Image:$filename2</a>";
	
	$wiki->name ="Image:$filename3";
	$wiki->user = $username;
	$wiki->content = str_replace("\\","","");
	if ($filename2 != "")
		$wiki->insert();
		
	$result .=  "<br/><a href='".$wiki->link("")."'>Image:$filename3</a>";
	
	$files = glob("$swRoot/site/files/snapshot.*.zip");
	$filename = "snapshot.txt";
	$fd = fopen("$swRoot/site/files/$filename", "wb");
	foreach($files as $file)
	{
		$file = str_replace("$swRoot/site/files/","",$file);
		$out = fwrite ($fd, $file."\n");
	}
	fclose ($fd);
	$wiki->name ="Image:$filename";
	$wiki->user = $username;
	$wiki->content = str_replace("\\","","");
	if ($filename != "")
		$wiki->insert();
	
	$result .=  "<br/><a href='".$wiki->link("")."'>Image:$filename</a>";
	swSemaphoreRelease();
	return $result;
	
}

 
?>