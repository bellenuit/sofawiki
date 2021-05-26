<?php

/*

creates trigram indexes of the content for faster searches
the trigram indexes are simple line-separated array in the site/indexes folder

lasttrigram.txt : last revision indexed
aaa.txt 
...
zzz.txt : line arrays of revisions containing the trigram of the filename

the index uses all revisions, even the revisions that are not used any more

*/



if (!defined('SOFAWIKI')) die('invalid acces');

$swTrigramUseZip = true;


function getLastTrigram()
{
	global $db;
	$bm = $db->trigrambitmap;
	for($i=$bm->length;$i>0;$i--)
	{
		if ($bm->getbit($i)) return $i;
	}
	return 0;
}

function getTrigram($t)
{
	global $swRoot;
	global $db;
	if (strlen($t)<3) return false; 
	if (!stristr('abcdefghijklmnopqrstuvwxyz0123456789.-',substr($t,0,1))) return;  
	if (!stristr('abcdefghijklmnopqrstuvwxyz0123456789.-',substr($t,1,1))) return; 
	if (!stristr('abcdefghijklmnopqrstuvwxyz0123456789.-',substr($t,2,1))) return; 
	if (array_key_exists($t,$db->trigrams)) return $db->trigrams[$t];
	
	global $swTrigramUseZip;
	if (isset($swTrigramUseZip) && $swTrigramUseZip)
	{
		$zip = new swZipArchive();
		$s = '';
		if ($zip->open($swRoot.'/site/indexes/trigram.zip')  === TRUE)
		{
			$s = $zip->getFromName($t);
			$zip->close();
		}
		$list = explode(PHP_EOL,$s);
		
	}
	else
	{
		$file = $swRoot.'/site/trigram/'.$t.'.txt';
		if (file_exists($file))
			$list = file($file,FILE_IGNORE_NEW_LINES);
		else
			$list = array();
	}
	
	$bm = $db->trigrambitmap->duplicate();
	$bm = $bm->notop(); // include all files, that were not indexed
	foreach($list as $v)
	{
		$bm->setbit($v);
	}
	return $bm;
}



function swIndexTrigram($numberofrevisions = 1000, $refresh = false)
{
	echotime('trigram start ');
	swSemaphoreSignal();
	global $db;
	global $swRoot;
	global $swMaxSearchTime;
	if ($swMaxSearchTime<500) $swMaxSearchTime = 500;
	$db->init(); // provoke init
	$bitmap = $db->trigrambitmap;
	$ibitmap = $db->indexedbitmap;
	$cbitmap = $db->currentbitmap;
	
	if ($refresh)
		$bitmap = new swBitmap;
	if ($bitmap->length == 0)
		$refresh = true;
	
	if ($refresh)
		clearTrigrams();
	
	$lastrevision = $db->lastrevision;
	$lasttrigram = 0;
	$n=1;
	$trigramindex = array();
	$affected = array();
	
	$tocheck = $bitmap->notop();
	$tocheck->redim($cbitmap->length,true);
	$tocheck = $tocheck->andop($cbitmap);
	$countbits = $tocheck->countbits();
	
	if ($countbits == 0)
	{
		echotime('trigram complete '.$bitmap->countbits().' '.$cbitmap->countbits());
		return;
	}
	
	$starttime = microtime(true);
	echotime('trigram loop '.$countbits );
	
	$i = 1;
	$n = $tocheck->length;	
	$c = 0;
	echotime('to check '.$n);
	//print_r($tocheck->toarray());
	while($i <= $n)
	{
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>$swMaxSearchTime) { echotime('searchtime'); break;}
		if (count($trigramindex) > 5000) { echotime('affected '.count($trigramindex)); break;}
		
		$i++;
		if (!$tocheck->getbit($i)) continue;
		$c++;
		
		//index;
		$w = new swRecord;
		$w->revision=$i;
		$w->error = '';
		$w->lookup();
		if ($w->error != '')
		echotime($w->revision.' '.$w->error);
		
		$s = $w->name.' '.$w->content;
		$s = swNameURL($s);
		
		$trigrams = array();
		for($j=0;$j<strlen($s)-3;$j++)
		{
			$t = substr($s,$j,3);
			if (strlen($t)<3) continue;
			if (!stristr('abcdefghijklmnopqrstuvwxyz0123456789.-',substr($t,0,1))) continue;  // is more restrictive than NameURL, to be fixed in filter
			if (!stristr('abcdefghijklmnopqrstuvwxyz0123456789.-',substr($t,1,1))) continue;
			if (!stristr('abcdefghijklmnopqrstuvwxyz0123456789.-',substr($t,2,1))) continue;
			$trigrams[$t] = $t;
			$affected[$t] = true;
		}
		array_unique($trigrams);
		
		foreach($trigrams as $t)
			$trigramindex[$t][] = $i;
		if ($w->error == '') 
        	$bitmap->setbit($i);
		if ($c>$numberofrevisions) { echotime('numberofrevisions '.$c); break;}
		if (count($affected)>5000) { echotime('affected '.count($affected)); break;}
		
	}
	echotime('trigram save ');
	
	global $swTrigramUseZip;
	
	if (isset($swTrigramUseZip) && $swTrigramUseZip)
	{
		$zip = new swZipArchive;
		if ($zip->open($swRoot.'/site/indexes/trigram.zip',zipArchive::CREATE)  === TRUE)
		{
		
		}
		else
		{
			echotime('tigram.zip failed!');
			swSemaphoreRelease();
			return;
		}
	}
	
	
	$path0 = $swRoot.'/site/trigram/';

	if (!is_dir($path0)) mkdir($path0);
	$error = false;
	$fc = 0;
	foreach($trigramindex as $gram=>$revisions)
	{
		if (!count($revisions)) continue;
		
		if (isset($swTrigramUseZip) && $swTrigramUseZip)
		{
			$s = $zip->getFromName($gram);
			$s .= PHP_EOL.join(PHP_EOL,$revisions);
			$zip->addFromString($gram,$s);
			$fc++;
		}
		else
		{
			$path = $path0.$gram.'.txt';
			if ($f = @fopen($path,"a+"))
			{
				$s = '';
				foreach($revisions as $r) $s.=$r.PHP_EOL;
				@fwrite($f, $s);
				@fclose($f); 
				$fc++;
			}
			else
			{
				$error = true;
			}
		}
	
	}
	if (isset($swTrigramUseZip) && $swTrigramUseZip)
	{
		$zip->close();
		$bitmap->save();
		$db->trigrambitmap = $bitmap;
		echotime('trigram end '.$c.'/'.$cbitmap->countbits().' revisions '.$fc. ' trigrams');
	}
	elseif (!$error)
	{
		$bitmap->save();
		$db->trigrambitmap = $bitmap;
		echotime('trigram end '.$c.'/'.$cbitmap->countbits().' revisions '.$fc. ' trigrams');
	}
	else
	{
		echotime('trigram error');
	}
	swSemaphoreRelease();
	
}

function clearTrigrams()
{
	 swSemaphoreSignal();
	 global $swRoot;
	 $path0 = $swRoot.'/site/trigram/';


	global $swTrigramUseZip;
	if (isset($swTrigramUseZip) && $swTrigramUseZip)
	{
		@unlink($swRoot.'/site/indexes/trigram.zip');
		@unlink($swRoot.'/site/indexes/trigram.zip.journal.zip');
	}
	else
	{	 
		 $files = glob($path0.'/*.txt');
		 if (is_array($files))
		 foreach($files as $file)
		 {
			@unlink($file);
		 }
	 }
	swSemaphoreRelease();
}

function trigramlist()
{
	global $swRoot;
 	$path0 = $swRoot.'/site/trigram/';

	global $swTrigramUseZip;
	$list = array();
	if (isset($swTrigramUseZip) && $swTrigramUseZip)
	{
		$zip = new swZipArchive();
		if ($zip->open($swRoot.'/site/indexes/trigram.zip')  === TRUE)
		{
			for( $i = 0; $i < $zip->numFiles; $i++ )
			{
				$stat = $zip->statIndex( $i );
				$fn = $stat['name'];
				$key = sprintf('%05d',$stat['size']);
				$list[$key.' '.$fn] = $fn;
			}
			
		}
		else
		{
			echotime('trigram.zip error');
		}
	 
	}
	else
	{
		$files = glob($path0.'*.txt');
		  
		$list = array();
		foreach($files as $file)
		{
		   $key = sprintf('%05d',filesize($file));
		   $fn = str_replace($path0,'',$file);
		   $fn = substr($fn,0,-4);
		   $list[$key.' '.$fn] = $fn;
		 }
	 }
	 krsort($list);
	 return $list;
}



?>