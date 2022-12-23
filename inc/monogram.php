<?php
	
/**
 *	Provides an efficient probability index filter for fields
 *
 *  
 *  The monogram index is used by the relationfilter function.
 *  It is better suited than the bloom filter for short length data.
 *  3.8.4 monogram uses more exensive digrams
 */

if (!defined("SOFAWIKI")) die("invalid acces");


/**
 *  Opens the monogram database.
 */

function swOpenMonogram()
{
	global $swMonogramIndex;
	global $swMonogramIndexWritable;
	global $swRoot;
	$path = $swRoot.'/site/indexes/monogram.db';

	if (file_exists($path))
		$swMonogramIndex = swDbaOpen($path, 'wdt'); 
	else
		$swMonogramIndex = swDbaOpen($path, 'c');	
	if ($swMonogramIndex)
	{
		$swMonogramIndexWritable = true;
	}
	else
	{
		// try read only
		$swMonogramIndex = swDbaOpen($path, 'rdt');
		
		$swMonogramIndexWritable = false;
		
		if (!$swMonogramIndex)
			throw new swExpressionError('monogram failed');
	}
}


/**
 *  Resets the monogram database.
 */


function swClearMonogram()
{
	global $swRoot;

	@unlink($swRoot.'/site/indexes/monogram.db'); 	 
}


/**
 *  Indexes 1000 revisions for the monogram index.
 *
 *  For each revision, the field values are indexed. 
 *  A value is indexed as bitmap of each character (monogram) in url syntax a-z0-9- (uncompressed 36bit per revision)
 *  The data is stored in a Sqlite3 database.
 */
function swIndexMonogram($numberofrevisions = 10000, $continue = false)
{
	
	echotime('indexmonogram '.$numberofrevisions);
	
	global $swMonogramIndex;
	global $swMonogramIndexWritable;
	global $swMaxSearchTime;
	global $db;
	global $swOvertime ;
	global $swMemoryLimit;
	
	if (!$swMonogramIndex) swOpenMonogram();
	
	if (!$swMonogramIndexWritable)
	{
		echotime('monogram readonly');
		return;
	}
// return;
	if ($s = swDbaFetch('_checkedbitmap',$swMonogramIndex))
	{	
		//echo $s;
		$checkedbitmap = unserialize($s);
	}
	else
	{
		$checkedbitmap = new swBitmap;
	}
	
	$l = swGetLastRevision();
	
	$starttime = microtime(true);
	
	$counter = 0;
	
	$bitmaps = array();
	
	
	
	
	
	for($i = $l;$i>0;$i--)
	{
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>$swMaxSearchTime) 
		{ 
			echotime('searchtime'); 
			$overtime = true;
			$swOvertime = true;
			break;
		}
		if (memory_get_usage()>$swMemoryLimit)
		{
			echotime('overmemory '.memory_get_usage());
			$overtime = true;
			$swOvertime = true;
			break;
		}

		
		if ($checkedbitmap->getbit($i)) continue;
		if (!$db->indexedbitmap->getbit($i)) continue;
		$checkedbitmap->setbit($i);
		if (!$db->currentbitmap->getbit($i)) continue;
		
		$counter++;
		
		$record = new swWiki;
		$record->revision = $i;
		$record->lookup();
		
		$fieldlist = $record->internalfields;
		
		// all fields exept _content and _word which can be derived from _content
		$fieldlist['_revision'][] = $record->revision;
		$fieldlist['_status'][] = $record->status;
		$fieldlist['_name'][] = $record->name;
		$fieldlist['_displayname'][] = $record->getdisplayname();
		$fieldlist['_url'][] = swNameURL($record->name);
		$fieldlist['_user'][] = $record->user;
		$fieldlist['_timestamp'][] = $record->timestamp;
		$fieldlist['_content'][] = $record->content; // probably does not make sense		
		$fieldlist['_length'][] = strlen($record->content);
		$fieldlist['_short'][] = substr($record->content,0,160);  // probably does not make sense		
				
		$ns = swNameURL($record->wikinamespace());
		if ($ns == '') $ns = 'main';
		$fieldlist['_namespace'][] = $ns;
		
		$keys =array_keys($fieldlist);
		foreach($keys as $key) 
		{
			if (substr($key,0,1) != '_')
			{
				$fieldlist['_field'][] = $key;	
				foreach($fieldlist[$key] as $v)
					$fieldlist['_any'][] = $v; // to do avoid duplicates
			}
		}
		
// 		return;
				
		foreach($fieldlist as $k=>$vs)
		{	
			foreach($vs as $v)
			{
				$vu = swNameURL($v);
				for($ci=0;$ci<strlen($vu)-1;$ci++)
				{
					$c = substr($vu,$ci,2);
					if (!isset($bitmaps[$k.' '.$c])) $bitmaps[$k.' '.$c] = new swBitmap;
					$bitmaps[$k.' '.$c]->setbit($i);
				}
				
			}
			if (!isset($bitmaps[$k.' *'])) $bitmaps[$k.' *'] = new swBitmap;
			$bitmaps[$k.' *']->setbit($i); // field present
		}
// 		return;
	}
// 	return;
	foreach($bitmaps as $k=>$bm)
	{
		if ($s = swDbaFetch($k,$swMonogramIndex))
		{
			$bm0 = @unserialize($s);
		}
		else
		{
			$bm0 = new swBitmap;
		}
		$bm = $bm->orop($bm0);
		
		$bm->hexit(); // save for db
		swDbaReplace($k,serialize($bm),$swMonogramIndex);
	}
// 	return;
	$checkedbitmap->hexit(); // save for db
	swDbaReplace('_checkedbitmap',serialize($checkedbitmap),$swMonogramIndex);
		
	swDbaSync($swMonogramIndex);
// 	return;
	
	return $counter;	 
}

/**
 *  Returns an array with 0 with all probable revisions for a given field and term and 1 the checked bitmap.
 *  
 *  Finds for each characters all revisions that have the character.
 *  All results are logically combined as and-operation.
 *  The construction of the index is inherently independent of the position of the characters.
 *  This makes it suited for any substring searches. The search finds all positives.
 *  The downsize is that it may also find false positives, the longer the indexed value is.s
 *  This makes the monogram filter suited for field search, the bloom filter is still useful for longer text.
 */

function swGetMonogramBitmapFromTerm($field, $term)
{
	global $swMonogramIndex;
	global $db;
	
	if (!$swMonogramIndex) swOpenMonogram();
	
	$result = array();
	
	//echo "($field) ($term)";
	
	
	if ($s = swDbaFetch('_checkedbitmap',$swMonogramIndex))
	{
		$checkedbitmap = @unserialize($s);
	}
	else
	{
		$checkedbitmap = new swBitmap;
	}
	$notchecked = $checkedbitmap->notop();
	$notchecked->redim($db->indexedbitmap->length, true);
	
	if ($field == '_checkedbitmap')
	{
		return $checkedbitmap;
	}
	
	
	$bitmap = new swBitmap;
	$bitmap->redim($db->indexedbitmap->length, true);
	
	
	if ($term == '*' || !$term)
	{
		if ($s = swDbaFetch($field.' *',$swMonogramIndex))
			{
				$bc = unserialize($s);
				$bc = $bc->orop($notchecked);
				// echo $bc->countbits().' ';
				$bitmap = $bitmap->andop($bc); 
	
			}
			else
			{
				$bc = new swBitmap;
				$bc->redim($db->indexedbitmap->length, true);
				$bc = $bc->orop($notchecked);
				$bitmap = $bitmap->andop($bc);
			}

	}
	else
	{
		$vu = swNameURL($term);
		for($ci=0;$ci<strlen($vu)-1;$ci++)
		{
			$c = substr($vu,$ci,2);
			
			if ($s = swDbaFetch($field.' '.$c,$swMonogramIndex))
			{
				$bc = unserialize($s);
				//echo $bc->countbits().' ';
				$bc = $bc->orop($notchecked);
				$bitmap = $bitmap->andop($bc);  // does not work ??
				$found = true;
	
			}
			else
			{
				$bc = new swBitmap;
				$bc->redim($db->indexedbitmap->length, false);
				$bc = $bc->orop($notchecked);
				//echo $bc->countbits().'- ';
				$bitmap = $bitmap->andop($bc);
			}
		}
	}
	// echo $bitmap->countbits().' ';
	return $bitmap;	
}

/**
 *  Returns a list of fields (key) indexed with monogram as well as the letters present (value)
 */

function swMonogramFields()
{
	global $swMonogramIndex;
	
	if (!$swMonogramIndex) swOpenMonogram();

	
	$list = array();
 	$key = swDbaFirstKey($swMonogramIndex);
 	do 
 	{
		 $ks = explode(' ',$key);
		 
		 
		 if (count($ks)>1)
		 {
			 $list[$ks[0]][] = $ks[1];
		 }	
 	}
 	while ($key = swDbaNextKey($swMonogramIndex));
 	

 	return $list;
}








