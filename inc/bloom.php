<?php
	
/**
 *	Provides a bloom index of all revisions indicating if a trigram might be present.
 *
 *  
 *  The bloom index is used by the filter functions.
 *  The file initializes also the bloom index.
 */

if (!defined("SOFAWIKI")) die("invalid acces");


/**
 *  Opens the bloom file.
 */

function swOpenBloom()
{
	global $swBloomIndex;
	global $swRoot;
	$path = $swRoot.'/site/indexes/bloom.raw';
	if (file_exists($path))
	{
		if ($swBloomIndex) @fclose($swBloomIndex);
		$swBloomIndex = fopen($path,'r');
	}	
}


/**
 *  Resets the bloom file.
 */


function swClearBloom()
{
	global $swRoot;

	@unlink($swRoot.'/site/indexes/bloom.raw');
	@unlink($swRoot.'/site/indexes/bloombitmap.txt');	 	 
}


/**
 *  Indexes 1000 revisions for the bloom index.
 */

function swIndexBloom($numberofrevisions = 20000, $continue = false)
{
	
	echotime('indexbloom '.$numberofrevisions); 
	
	global $swRoot;
	global $db;
	global $swBloomIndex;
	global $swMaxSearchTime;
	global $swOvertime ;
	
	$path = $swRoot.'/site/indexes/bloom.raw';
	if (file_exists($path)) chmod($path,0777);
	$starttime = microtime(true);
	
	if (!$db->bloombitmap) return;
	$db->bloombitmap->redim($db->lastrevision+1);
	$db->bloombitmap->save();
	
	swSemaphoreSignal('bloom1');	
	$fpt = fopen($path,'c+');
	$block = floor(($db->lastrevision+1)/65536);
	$fs = (($block + 1) * 1024) * 8192 ;
	fseek($fpt,$fs);
	fwrite($fpt," "); // write to force file size;
	@fclose($fpt);
	swSemaphoreRelease('bloom1');
	
	// new try read all to bitmap
	$bitmap = new swBitmap;
	$raw = file_get_contents($path);
	$bitmap->init(strlen($raw)*8);
	$bitmap->map = $raw;
	
	$i = 0; $rev = 0;
	$rev = $db->lastrevision+1; // start +1, because we rev-- at beginning
	
	$revisionpath = $swRoot.'/site/revisions/';
	
	while ($i < $numberofrevisions) 
	{
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>$swMaxSearchTime) 
		{ 
			echotime('searchtime'); 
			$swOvertime = true;
			break;
		}

		
		$rev--;
		if ($rev < 1) break;
		if ($rev > $db->lastrevision) break; // should not happen
		
		if (!$db->indexedbitmap->getbit($rev)) continue;
		if (!$db->currentbitmap->getbit($rev)) 
		{ 
			$db->bloombitmap->setbit($rev);
			continue;
		}
		if ($db->bloombitmap->getbit($rev)) continue;
		
		// sometimes the bloombitmap is corrupt or empty, but the bloom is actually there for the current revision
		// in this case no need to read the file. 
		// we check if some bits for this revisions are already set
		// if this is the case, we simply set the bloom bitmap and go on
		$found = false;
		for($h = 0;$h<1024;$h++)
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
			
			if ($bitmap->getbit($p))
			{
				$found = true;
				$h=1024;
			}
		}
		if ($found)
		{
			$db->bloombitmap->setbit($rev);
			continue;
		}
		//end check bloombitmap
		
				
		$text = swFileGet($revisionpath.$rev.'.txt');
		
		$hashes = swGetHashesFromTerm($text);

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
	
	/*
	if ($continue)
	{
		global $swOvertime;
		if ($rev >= 1) ; $swOvertime = true;
	}
	*/
	// new try read all to bitmap
	
	$minblock = floor($rev/65536);
	$fileoffset = $minblock * 1024 * 8192;
	$stream = substr($bitmap->map,$fileoffset);
	
	swSemaphoreSignal('bloom2');	
	$fpt = fopen($path,'c+');
	fseek($fpt,$fileoffset);
	fwrite($fpt,$stream);
	@fclose($fpt);
	swSemaphoreRelease('bloom2');
	
	$db->bloombitmap->save();
	
	return $i;	 
}

/**
 *  Calculates the hashes for a given term
 *
 *  @link http://pages.cs.wisc.edu/~cao/papers/summary-cache/node8.html
 *  False positive is minmized with k = ln(2)*m/n
 *  Where
 *  k = number of hashes used per trigram
 *  m = bitdepth of bloom filter
 *  n = number of trigrams
 *  or n = ln(2)*m/k
 *  Our design: ln2 ~~ 0.7, k = 3, m = 1024, eg n = 240
 *  It is optimal therefore for 240 trigrams per revision.
 *  False positives 15%.
 *  If the text is longer, false positives will rise to 25%.
 *  To get less than 10% false positive for 500 characters, it would need to double the bit length with the same number of hashes.
 */


function swGetHashesFromTerm($s)
{
		$s = swNameURL($s);
		
		$l = strlen($s)-3; if ($l<0) return false;
		
		$list = array();
		
		// create trigram list
		for ($i=0;$i<=$l;$i++)
		{
			$t = substr($s,$i,3);
			$list[$t]= 1;
		}
		$list = array_keys($list);
		
		$hashes = array();
		
		foreach($list as $elem)
		{
			$hashes[] = swFnvHash($elem,1024,1103,331);
			$hashes[] = swFnvHash($elem,1024,1103,661);
			$hashes[] = swFnvHash($elem,1024,1103,859);
		}
		sort($hashes);
		$hashes = array_unique($hashes);
		return $hashes;
}

/**
 *  Calculates one single hash with the Fowler-Noll-Vo hash function
 */

function swFnvHash($s,$size,$prime,$offset)  
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

/**
 *  Returns a bitmap with all probable revisions for a given term 
 */

function swGetBloomBitmapFromTerm($term)
{
	global $db;
	global $swBloomIndex;
	
	$bm = new swBitmap;
	$bm->redim($db->indexedbitmap->length, true);
	
	if (!$swBloomIndex || strlen(swNameURL($term))<3)
	{
		
		return $bm;
	}
						 			
	$hashes = swGetHashesFromTerm($term);
	
	global $swMemoryLimit;
	 	
	foreach($hashes as $h)
	{
		if (memory_get_usage()>$swMemoryLimit) break;
		
		
		$hbm = new swBitmap;
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
	
	$notchecked = $db->bloombitmap->notop();
	$notchecked->redim($db->indexedbitmap->length, true);

	$bm = $bm->orop($notchecked);
	
	return $bm;
	
}

$swBloomIndex = '';
swOpenBloom();









