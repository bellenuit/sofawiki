<?php

if (!defined("SOFAWIKI")) die("invalid acces");

// bloom filter to replace trigram. 

include_once "bitmap.php";
include_once "utilities.php";


function swFNVhash($s, $size, $prime, $offset) // Fowler-Noll-Vo hash function
{
	$hash = $offset;
	$list = str_split($s);
	foreach($list as $char)
	{
		$hash = $hash ^ ord($char); 
		$hash *= $prime; 
		$hash %= $size;
	}
	return $hash;
}


function swGetHashesFromTerm($s)
{

		$s = swNameURL($s);
		$l = strlen($s)-3; if ($l<0) return false;
		$list = array();
		
		// create trigram list
		for ($i=0;$i<=$l;$i++)
		{
			$t = substr($s,$i,3);
			//echo "($t)";
			$list[$t]= 1;
		}
		$list = array_keys($list);
		
		$hashes = array();
		
		foreach($list as $elem)
		{
			$hashes[] = swFNVhash($elem,1024,1103,331);
			$hashes[] = swFNVhash($elem,1024,1103,661);
			$hashes[] = swFNVhash($elem,1024,1103,859);
		}
		sort($hashes);
		$hashes = array_unique($hashes);
		//echotime(print_r($hashes,true));
		return $hashes;
	
}



function swGetBloomBitmapFromTerm($term)
{
	global $db;
	global $swBloomIndex;
	
	$bm = new swBitmap;
	$bm->init($db->lastrevision+1, true);
	
	echotime('bloom '.$term);
	
	if (!$swBloomIndex)
	{
		echotime('nobloomindex');
		return $bm;
	}
						 			
	$hashes = swGetHashesFromTerm($term);
	
	foreach($hashes as $h)
	{
		$hbm = new swBitmap;
		$hbm->init($db->lastrevision+1, true);
		$hbm->map = '';
				
		$blocks = floor(($db->lastrevision+1)/65536);
				
		for ($i = 0; $i<=$blocks; $i++)
		{
			$col = 0;
			$offset = ($i * 1024 + $h) * 8192 + $col;
			fseek($swBloomIndex,$offset); 
			$test = fread($swBloomIndex,8192); 
			$hbm->map .= $test;
		}
		$bm = $bm->andop($hbm);
	}
	
	$bm->map = substr($bm->map,0,strlen($db->bloombitmap->map));
	
	// add all non indexed	
	$notindexed = $db->bloombitmap->notop();
	$bm = $bm->orop($notindexed);
	
	echotime('bloom end');
		
	return $bm;
	
}



function swIndexBloom($numberofrevisions = 1000)
{
	
	swSemaphoreSignal();
	echotime('indexbloom');
	
	global $swRoot;
	global $db;
	global $swBloomIndex;
	global $swMaxSearchTime;
	global $swRamdiskPath;
	
	$path = $swRoot.'/site/indexes/bloom.raw';
	chmod($path,0777);
	$starttime = microtime(true);
	
	if (!$db->bloombitmap) return;
	$db->bloombitmap->redim($db->lastrevision+1);
	
	echotime('indexbloom2');
	
	if ($swRamdiskPath != '' && false) //ne marche pas,Permission denied
	{
		echotime('to ram');
		$path2 = $swRamdiskPath.'bloom.txt';
		$raw = file_get_contents($path);
		file_put_contents($path2,$raw);
		
		chmod($path2,0777);
		echotime('done');
		$fpt = fopen($path2,'c+');
	}
	else
		$fpt = fopen($path,'c+');
	
	$block = floor(($db->lastrevision+1)/65536);
	$fs = (($block + 1) * 1024) * 8192 ;
	fseek($fpt,$fs);
	fwrite($fpt," "); // write to force file size;
	
	// new try read all to bitmap
	echotime('to bitmap');
	$bitmap = new swBitmap;
	$raw = file_get_contents($path);
	$bitmap->init(strlen($raw)*8);
	$bitmap->map = $raw;
	echotime('done');
	
	$i = 0; $rev = 0;
	while ($i < $numberofrevisions) 
	{
		$rev++;
		if ($rev > $db->lastrevision) 
			break;
		if (!$db->indexedbitmap->getbit($rev)) continue;
		if ($db->bloombitmap->getbit($rev)) continue;
		
		
		
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>3*$swMaxSearchTime) { echotime('searchtime'); break;}
		
		$w = new swRecord;
		$w->revision = $rev;
		$w->error = '';
		$w->lookup();
		if ($w->error != '')
		{
			echotime($w->revision.' '.$w->error);
			continue;
		}
		
		$text = $w->name.' '.$w->content;
		$hashes = swGetHashesFromTerm($text);
		echotime('rev '.$rev.' gothashes '.count($hashes));
		$offsetmax = 0;
		
		if ($hashes)
		foreach($hashes as $h)
		{
			// file structure 
			// block of 1024 rows each 8192 bytes wide = 65536 values
			
			$block = floor($rev/65536);
			$col = floor(($rev % 65536)/8);
			$offset = ($block * 1024 + $h) * 8192 + $col;
			
			// code from bitmap class
			// sets nth bit to true
			$byte = $rev >> 3;
			$bit = $rev - ($byte << 3);
			$bitmask = 128 >> $bit;
			
			/*

			fseek($fpt,$offset);	
			$ch = fread($fpt,1);
			$ch = ord($ch);
			$ch = $ch | $bitmask;
			$ch = chr($ch);
			fseek($fpt,$offset);	
			fwrite($fpt,$ch);
			
			*/
			
			// new try read all to bitmap
			$p = $offset*8 + $bit;
			$bitmap->setbit($p);
			
			if ($offset>$offsetmax) $offsetmax = $offset;
			
			
		}
		
		
		$db->bloombitmap->setbit($rev);
		$i++;
		
	}
	// echo "offsetmax $offsetmax; ";
	@fclose($fpt);
	echotime('indexbloom end ');
	
	if ($swRamdiskPath != '' && false)
	{
		echotime('from ram');
		$raw = file_get_contents($path2);
		file_put_contents($path,$raw);
		echotime('done');
	}
	
	// new try read all to bitmap
	echotime('from bitmap');
	file_put_contents($path,$bitmap->map);
	echotime('done');
	
	
	
	return $i;
	 swSemaphoreRelease();
	
}

function swOpenBloom()
{
	global $swBloomIndex;
	global $swRoot;
	$path = $swRoot.'/site/indexes/bloom.raw';
	if (file_exists($path))
	{
		@fclose($swBloomIndex);
		$swBloomIndex = fopen($path,'r');
	}
}

$swBloomIndex = '';
swOpenBloom();




function swClearBloom()
{
	 swSemaphoreSignal();
	 global $swRoot;

	 @unlink($swRoot.'/site/indexes/bloom.raw');
	  @unlink($swRoot.'/site/indexes/bloombitmap.txt');
	 
	 swSemaphoreRelease();
}


?>