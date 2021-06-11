<?php

if (!defined("SOFAWIKI")) die("invalid acces");

// bloom filter to replace trigram. 

include_once "bitmap.php";
include_once "utilities.php";


/* on design

http://pages.cs.wisc.edu/~cao/papers/summary-cache/node8.html
false positive is minmized with
k = ln(2)*m/n
where
k = number of hashes used per trigram
m = bitdepth of bloom filter
n = number of trigrams

or n = ln(2)*m/k

our design
ln2 ~~ 0.7
k = 3
m = 1024

-> n = 240

it is set therefore for 240 trigrams per revision
false positives: 15%

if the text is longer, false positive rises to 25%

if we would to have less than 10% for 500 length, we would need double the bit depth, but not necessarily more hashes per trigram.

note that the trigram has also its probability there.

*/





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
		
		//echotime('s '.$s);
		
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

function swGetHashesFromRevision($rev)
{
	
}



function swGetBloomBitmapFromTerm($term)
{
	global $db;
	global $swBloomIndex;
	
	$bm = new swBitmap;
	
	// echo " gbbft ";
	
	$bm->init($db->bloombitmap->length, true);
	
	if (strlen(swNameURL($term))<3) return $bm;
	
	// echotime('bloom '.$term);
	
	if (!$swBloomIndex)
	{
		echotime('nobloomindex');
		return $bm;
	}
						 			
	$hashes = swGetHashesFromTerm($term);
	
	
	
	foreach($hashes as $h)
	{
		$hbm = new swBitmap;
		$hbm->init($db->bloombitmap->length, true);
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
	//$notindexed = $db->bloombitmap->notop();
	// $bm = $bm->orop($notindexed);
	
	//echotime('bloom end');
		
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
	if (file_exists($path)) chmod($path,0777);
	$starttime = microtime(true);
	
	if (!$db->bloombitmap) return;
	$db->bloombitmap->redim($db->lastrevision+1);
		
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
	$rev = $db->lastrevision;
	
	$revisionpath = $swRoot.'/site/revisions/';
	
	while ($i < $numberofrevisions) 
	{
		$rev--;
		if ($rev < 1) break;
		//$rev++;
		if ($rev > $db->lastrevision) 
			break;
		
		if (!$db->indexedbitmap->getbit($rev)) continue;
		if ($db->bloombitmap->getbit($rev)) continue;
		
		
		
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>3*$swMaxSearchTime) { echotime('searchtime'); break;}
		
		/*
		
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
		
		*/
		
		$text = swFileGet($revisionpath.$rev.'.txt');
		
		$hashes = swGetHashesFromTerm($text);
		//echotime('rev '.$rev.' gothashes '.count($hashes));
		$offsetmax = 0;
		
		if ($hashes)
		foreach($hashes as $h)
		{
			// file structure 
			// block of 1024 rows each 8192 bytes wide = 65536 values
			
			$block = floor($rev/65536);
			$col = floor(($rev % 65536)/8);
			$offset = ($block * 1024 + $h) * 8192 + $col;
			
			// sets nth bit to true
			$byte = $rev >> 3;
			$bit = $rev - ($byte << 3);
			
			// new try read all to bitmap
			$p = $offset*8 + $bit;
			$bitmap->setbit($p);
			
			if ($offset>$offsetmax) $offsetmax = $offset;
			
			
		}
		
		
		$db->bloombitmap->setbit($rev);
		
		
		
		$i++;
		
	}
	
		
		
	// echo "offsetmax $offsetmax; ";
	
	echotime('indexbloom end '.$rev);
	
	if ($swRamdiskPath != '' && false)
	{
		echotime('from ram');
		$raw = file_get_contents($path2);
		file_put_contents($path,$raw);
		echotime('done');
	}
	
	// new try read all to bitmap
	echotime('from bitmap '.$bitmap->length);
	
	$minblock = floor($rev/65536);
	$fileoffset = $minblock * 1024 * 8192;
	$stream = substr($bitmap->map,$fileoffset);
	echotime('offset '.$fileoffset);
	
	fseek($fpt,$fileoffset);
	fwrite($fpt,$stream);
	@fclose($fpt);
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