<?php

if (!defined('SOFAWIKI')) die('invalid acces');


function swRelationTemplate($n)
{
	$wiki = new swWiki;
	$wiki->name = 'Template:'.$n;
	$wiki->lookup();
	if (!$wiki->revision)
		throw new swRelationError('Template page does not exist.',87);
	return $wiki->content;
}


function swRelationVirtual($url)
{
	$wiki = new swWiki;
	$wiki->name = $url;
	$wiki->lookup();
	if (!$wiki->revision)
		throw new swRelationError('Virtual page does not exist.',87);
	
	$wiki->parsers[] = new swCacheparser;
	$wiki->parsers[] = new swTidyParser;
	$wiki->parsers[] = new swTemplateParser;
	
	$wiki->parse();
	
	$list = swGetAllFields($wiki->parsedContent);
	
	// normalize array, to a table, but using only used fields and field
	$maxcount = 1;
	foreach($list as $v)
	{
		$maxcount = max($maxcount,count($v));
	}	
	$list2 = array();
	foreach($list as $key=>$v)
	{
		for($fi=0;$fi<count($v);$fi++)
		{
			$list2[$fi][$key] = $v[$fi];
		}
		for ($fi=count($v);$fi<$maxcount;$fi++)
		{
			$list2[$fi][$key] = $v[count($v)-1];
		}
	}
	
	//print_r($list2);
	
	$header = array_keys($list);
	$result = new swRelation($header);
	
	foreach ($list2 as $v) 
	{
		$d = $v;
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
	}
	
	return $result;

}


function swRelationFilter($filter)
{
	
	global $swIndexError;
	global $swMaxSearchTime;
	global $swMaxOverallSearchTime;
	global $swStartTime;
	
	$verbose = 0;
	if (isset($_REQUEST['verbose'])) $verbose = 1;
	
	global $swRoot;
	global $db;
	$lastfoundrevision = 0;
	$goodrevisions = array();
	$bitmap = new swBitmap;
	$checkedbitmap = new swBitmap;
	$fields = array();
	
	if ($swIndexError) return new swRelation('');
	
	// parse query
	// currently, inline comma is not supported on hint.
	$pairs = explode(',',$filter);
	$fields = array(); 
	$getAllFields = false;
	foreach($pairs as $p)
	{
		
		$p = trim($p);
		$elems = explode(' ',$p);
		$f = array_shift($elems);
		$h = null;
		
		if (count($elems)>0)
		{
			$h = join(' ',$elems); 
			if (substr($h,0,1) != '"')
				throw new swExpressionError('filter missing start quote '.$f,88);
			if (substr($h,-1,1) != '"')
				throw new swExpressionError('filter missing end quote '.$f,88);
			$h = substr($h,1,-1);
		}
		if ($f == '*')	
			$getAllFields = true;
		else	
			$fields[$f] = $h;
	}
	
	// if * there must be at least one hint, we cannot return the entire database
	if ($getAllFields)
	{
		$found = false;
		foreach($fields as $hint)
		{
			if ($hint != '')
				$found = true;
		}
		if (!$found)
			throw new swExpressionError('filter missing at least one hint on * '.$f,88);
	}
	

	$header = array_keys($fields);

	
	
	$result = new swRelation($header);

	//return $result;
	
	// find already searched revisions
	$mdfilter = $filter;
	$mdfilter = urlencode($mdfilter);
	$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
	$cachefile = $cachefilebase.'.txt';
	
	global $swDebugRefresh;
	if ($swDebugRefresh)
		{ echotime('refresh'); if (file_exists($cachefile)) unlink($cachefile);}
	
	$chunks = array();
	if (file_exists($cachefile)) 
	{
		if ($handle = fopen($cachefile, 'r'))
		{
			// one file for header			
			while ($arr = swReadField($handle))
			{
				if (@$arr['_primary'] == '_header')
				{
					$bitmap = unserialize($arr['bitmap']);
					$checkedbitmap = unserialize($arr['checkedbitmap']);
					if (isset($arr['chunks']))
					$chunks = unserialize($arr['chunks']);
					unset($arr);
				}
			}
			fclose($handle);
			//echomem('primary');
			echotime('<a href="index.php?name=special:indexes&index=queries&q='.md5($mdfilter).'" target="_blank">'.md5($mdfilter).'.txt</a> ');
			
			// 1+ file for revisions
			$goodrevisions = array();
			foreach($chunks as $chunk)
			{
				$chunkfile = $cachefilebase.'-'.$chunk.'.txt';
				$s = file_get_contents($chunkfile);
				$goodrevisionchunk = unserialize($s);
				unset($s);
				if (is_array($goodrevisionchunk))
					$goodrevisions = array_merge($goodrevisions,$goodrevisionchunk);
				else
				{
					$goodrevisions = array(); //reset;
					$bitmap = new swBitmap;
					$checkedbitmap = new swBitmap;  
					unset($chunks); 
				}
			}
			echotime('cached '.count($goodrevisions));
			echomem('cached');
		}
	}
	
	$cachechanged = false;
	$db->init();
	
	if (!is_a($bitmap, 'swBitmap'))  $bitmap = new swBitmap;
	if (!is_a($checkedbitmap, 'swBitmap'))  $checkedbitmap = new swBitmap;

	$maxlastrevision = $db->lastrevision;
	$indexedbitmap = $db->indexedbitmap->duplicate();
	if ($indexedbitmap->length < $maxlastrevision) $db->RebuildIndexes($indexedbitmap->length); // fallback
	$bitmap->redim($maxlastrevision+1,false);
	$checkedbitmap->redim($maxlastrevision+1,false);
	$currentbitmap = $db->currentbitmap->duplicate();
	$deletedbitmap = $db->deletedbitmap->duplicate();
	$notchecked = $checkedbitmap->notop();
	$tocheck = $indexedbitmap->andop($notchecked);
	unset($notchecked);
	$tocheck = $currentbitmap->andop($tocheck);
	$tocheckcount = $tocheck->countbits();
	if ($tocheckcount>0) 
	{ 
		echotime('tocheck '.$tocheckcount); 
	}
	$checkedcount = 0;
	$nowtime = microtime(true);	
	$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
	if ($dur>$swMaxOverallSearchTime) 
		echotime('overtime overall');		
		
	if (($tocheckcount > 0 || $cachechanged) && $dur<=$swMaxOverallSearchTime)
	{
		// we create a superbitmap
		// fields with string must be present
		// hints must be present
		
		$list1 = array();
		foreach($fields as $f=>$h)
		{
			if (is_null($h))
			{
				$bloomlist[] = '[['.$f.'::]';
				if (!empty($h))
					$bloomlist[] = $h;
			}
		}
		$list2 = array();
		foreach($list1 as $l)
		{
			$v = swNameURL($l);
			$gr = swGetBloomBitmapFromTerm($v);
			$gr->redim($tocheck->length, true);
			$tocheck = $tocheck->andop($gr);
			$notgr = $gr->notop();
			$checkedbitmap = $checkedbitmap->orop($notgr);
			$tocheckcount = $tocheck->countbits();
		}
				
		$toc = $tocheck->countbits();
		$checkedcount += $tocheckcount - $toc;
		if ($toc > 0 ) echotime('loop '.$toc);
		
		$starttime = microtime(true);
		if ($swMaxSearchTime<500) $swMaxSearchTime = 500;
		if ($swMaxOverallSearchTime<2500) $swMaxOverallSearchTime = 2500;
		global $swOvertime;
		$overtime = false;
			
		if ($toc>0) 
		{
			for ($k=$maxlastrevision;$k>=1;$k--)
			{
				
				if (!$tocheck->getbit($k)) continue; // we already have ecluded it from the list
				if ($checkedbitmap->getbit($k)) continue; // it has been checked, should not happen here any more
			 	if(!$indexedbitmap->getbit($k)) continue; // it has not been indexed, should not happen here any more
				if(!$currentbitmap->getbit($k)) { $bitmap->unsetbit($k); continue; } // should not happen here any more
				if($deletedbitmap->getbit($k)) { $bitmap->unsetbit($k); $checkedbitmap->setbit($k); $checkedcount++; continue; }
				// should not happen here any more
				$checkedcount++;
				
				$nowtime = microtime(true);	
				$dur = sprintf("%04d",($nowtime-$starttime)*1000);
				if ($dur>$swMaxSearchTime) 
				{
					echotime('overtime '.$checkedcount.' / '.$tocheckcount);
					$overtime = true;
					break;
				}
				$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
				if ($dur>$swMaxOverallSearchTime) 
				{
					echotime('overtime overall '.$checkedcount.' / '.$tocheckcount);
					$overtime = true;
					if (!stristr($flags,'internal'))
							$swOvertime=true;
					break;
				}
				$record = new swRecord;
				$record->revision = $k;
				$record->lookup();
				
				if ($record->error == '') $checkedbitmap->setbit($k); else continue;
				$urlname = swNameURL($record->name);				
				
				$content = $record->name.' '.$record->content;
				$row=array();
				
				$fieldlist = $record->internalfields;

				{					
					$fieldlist['_revision'][] = $record->revision;
					$fieldlist['_status'][] = $record->status;
					$fieldlist['_name'][] = $record->name;
					$fieldlist['_url'][] = swNameURL($record->name);
					$fieldlist['_user'][] = $record->user;
					$fieldlist['_timestamp'][] = $record->timestamp;
					$fieldlist['_content'][] = $record->content;
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
					
					//if (!isset($fieldlist[$field])) { $bitmap->unsetbit($k); continue; } ???
					
					// normalize array, to a table, but using only used fields and field
					// $maxcount = count($fieldlist[$field]);
					$maxcount = 1;
					// print_r($fields);
					foreach($fields as $key=>$v)
					{
						if (isset($fieldlist[$key]))
						{
							$maxcount = max($maxcount,count($fieldlist[$key]));
						}
					}	
					$fieldlist2 = array();
					foreach($fieldlist as $key=>$v)
					{
						if (array_key_exists($key,$fields) or in_array($key,array('_revision','_url'))
						or ($getAllFields and substr($key,0,1) != '_') )
						{
						
							for($fi=0;$fi<count($v);$fi++)
							{
								$fieldlist2[$fi][$key] = $v[$fi];
							}
							for ($fi=count($v);$fi<$maxcount;$fi++)
							{
								$fieldlist2[$fi][$key] = $v[count($v)-1];
							}
						}
					}
					
					//print_r($fieldlist2);
					
					// select
					$rows = array();
					for ($fi=0;$fi<$maxcount;$fi++)
					{
						$revision = $fieldlist2[$fi]['_revision'];
						$found = true;
						foreach($fields as $key=>$hint)
						{
							if (!$found) continue;
							if (is_null($hint))
							{
								// pass
							}
							else
							{
								if (!array_key_exists($key,$fieldlist2[$fi]))
								{
									$found = false;
								}
								if (!empty($hint))
								{
									if(!isset($fieldlist2[$fi][$key])) $found = false;
									elseif(stripos(swNameURL($fieldlist2[$fi][$key]),$hint) === false)
									{
										$found = false;
									} 
								}
							}
						}
						if ($found)
							$rows[$revision.'-'.$fi] = $fieldlist2[$fi];

					}
					
					
					$maxcount = count($rows); 
										
					// extend missing
					foreach($fields as $key=>$hint)
					{
						
						for ($fi=0;$fi<$maxcount;$fi++)
						{
							$revision = $fieldlist2[$fi]['_revision'];
							if (isset($rows[$revision.'-'.$fi]) && !array_key_exists($key,$rows[$revision.'-'.$fi]))
							{
								$rows[$revision.'-'.$fi][$key] = '';
							}
						}
					}
								
				}

				if (count($rows)>0)
				{
					foreach($rows as $primary=>$line)
					{
						$goodrevisionchunknew[$primary] = $goodrevisions[$primary] = serialize($line);
					}
					$bitmap->setbit($k);

					$touched = true;
					
				}
			}
			
			
			// save to cache
			$bitmap->hexit();
			$checkedbitmap->hexit();
						
			echotime('checked '.$checkedcount);
			
			if (true) {
			
				swSemaphoreSignal();
				echotime("filter write");
					
				if (isset($touched))
				{
					if (!isset($chunks) || count($chunks)==0)
						$chunks = array('1');
					$chunk = array_pop($chunks); $chunks[] = $chunk;
					//echotime('grc '.count($goodrevisionchunk));
					if (isset($goodrevisionchunk) && count($goodrevisionchunk) > 10000)
					{
						$chunk++;
						$chunks[] = $chunk;
						$chunkfile = $cachefilebase.'-'.$chunk.'.txt';
						
						$goodrevisionchunk = $goodrevisionchunknew;
						
					}
					elseif(isset($goodrevisionchunk) && is_array($goodrevisionchunk) && isset($goodrevisionchunknew) && is_array($goodrevisionchunknew)) 
					{
						$goodrevisionchunk=$goodrevisionchunk + $goodrevisionchunknew;
						//echotime('grc2 '.count($goodrevisionchunk));
					}
					else
					{
						$goodrevisionchunk = @$goodrevisionchunknew;
					}
					$chunkfile = $cachefilebase.'-'.$chunk.'.txt';
					$handle2 = fopen($chunkfile, 'w');
					fwrite($handle2,serialize($goodrevisionchunk));
					fclose($handle2);
					unset($goodrevisionchunk);
					unset($goodrevisionchunknew);
				}
				
				
				$handle2 = fopen($cachefile, 'w');
				$header = array();
				$header['filter'] = $filter;
				$header['overtime'] = $overtime ;
				$header['chunks'] = serialize($chunks);
				$header['bitmap'] = serialize($bitmap);
				$header['checkedbitmap'] = serialize($checkedbitmap);
				$row = array('_header'=>$header);
				swWriteRow($handle2, $row );
				fclose($handle2);
				swSemaphoreRelease();
			}			
			
			echotime('good '.count($goodrevisions));
			echomem("filter");	
			} // if toc>0
	
	}
	
	//print_r($goodrevisions);
	
	// TO DO FILTER NAMESPACE HERE 
	global $user;
	global $swSearchNamespaces;
	global $swTranscludeNamespaces;
		
	$ns = array();
	$searcheverywhere = FALSE;
	foreach($swSearchNamespaces as $sp)
	{
		if (stristr($sp,'*')) $searcheverywhere = TRUE;
		$sp = swNameURL($sp);
		if (!stristr($sp,':')) $sp .= ':';
		if ($sp != ':') $ns[$sp]= $sp;
	}
	foreach($swTranscludeNamespaces as $sp)
	{
		if (stristr($sp,'*')) $searcheverywhere = TRUE;
		$sp = swNameURL($sp);
		if (!stristr($sp,':')) $sp .= ':';
		if ($sp != ':') $ns[$sp]= $sp;
	}
	
	//print_r($goodrevisions);
	
	// $searcheverywhere = true;
	
	$d = array();	
	
	foreach ($goodrevisions as $v) 
	{
		//print_r($v);
		
		$d = unserialize($v);
		
		$dn = $d['_url'];
		
		if (!$searcheverywhere && stristr($dn,':'))
		{
			$dnf =explode(':',$dn);
			$dns = array_shift($dnf);
			if (! in_array($dns, $ns) && !$user->hasright('view',$dn)) continue;
		}
		
		if (!in_array('_revision',$result->header)) unset($d['_revision']);
		if (!in_array('_url',$result->header)) unset($d['_url']);
		
		//print_r($d);
		
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
		
		//print_r($tp);
	}
	
	
	foreach($d as $key=>$val)
	{
		if (!in_array($key, $result->header))
			$result->addColumn($key);
	}
	
	//print_r($result);
	
	return $result;
	
}



?>