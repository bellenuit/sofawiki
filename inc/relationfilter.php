<?php

if (!defined('SOFAWIKI')) die('invalid acces');


function swRelationTemplate($n)
{
	$wiki = new swWiki;
	$wiki->name = 'Template:'.$n;
	$wiki->lookup();
	if (!$wiki->revision)
		throw new swRelationError('Template page "'.$n.'" does not exist.',87);
	return $wiki->content;
}

function swRelationInclude($n)
{
	$wiki = new swWiki;
	$wiki->name = 'Template:'.$n;
	$wiki->lookup();
	if (!$wiki->revision)
		throw new swRelationError('Template page does not exist.',87);
	return $wiki->content;
}

function swRelationImport($url)
{
	
	// TO DO FILTER NAMESPACE HERE 
	global $user;
	global $swSearchNamespaces;
	global $swTranscludeNamespaces;
	
	$wiki = new swWiki;
	$wiki->name = $url;
	
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
	
	if (!$searcheverywhere && stristr($url,':'))
		{
			$dnf =explode(':',$url);
			
			$dns = swNameURL(array_shift($dnf));
			
			$nss = join(PHP_EOL,$ns);

			if (!stristr($nss,$dns) && !$user->hasright('view',$url)) 			
				return new swRelation('');
	}


	
	
	
	$wiki->lookup();
	if (!$wiki->revision)
		throw new swRelationError('Import page does not exist.',87);
		
	$list = swGetAllFields($wiki->content);
	
	// special characters
	$rel = new swRelation('',null,null);
	foreach(array_keys($list) as $raw)
	{
		if (count($list[$raw]))	
		{	
			$clean = $rel->cleanColumn($raw);
			if ($clean != $raw)
			{
				// print_r($list[$raw]);
				
				$list[$clean] = $list[$raw];
				unset($list[$raw]);
			}
		}
		else
			unset($list[$raw]);
		
	}
	
	
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
		if (count($v))
		for ($fi=count($v);$fi<$maxcount;$fi++)
		{
			$list2[$fi][$key] = $v[count($v)-1];
		}
	}
	
	//print_r($list2);
	
	$header = array_keys($list);
	$result = new swRelation($header,null,null);
	
	foreach ($list2 as $v) 
	{
		$d = $v;
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
	}
	
	return $result;

}



function swRelationVirtual($url)
{
	// TO DO FILTER NAMESPACE HERE 
	global $user;
	global $swSearchNamespaces;
	global $swTranscludeNamespaces;
	
	
	$wiki = new swWiki;
	$wiki->name = $url;
	

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
	
	if (!$searcheverywhere && stristr($url,':'))
		{
			$dnf =explode(':',$url);
			
			$dns = swNameURL(array_shift($dnf));
			
			$nss = join(PHP_EOL,$ns);

			if (!stristr($nss,$dns) && !$user->hasright('view',$url)) 			
				return new swRelation('');
	}
	$wiki->lookup();
	if (!$wiki->revision) throw new swRelationError('Virtual page does not exist.',87);

	
	$wiki->parsers[] = new swCacheparser;
	$wiki->parsers[] = new swTidyParser;
	$wiki->parsers[] = new swTemplateParser;
	$wiki->parsers[] = new swStyleParser;
	
	$wiki->parse();
	
	
	
	$list = swGetAllFields($wiki->parsedContent);
	
	//echo $wiki->parsedContent;
	
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
	$result = new swRelation($header,null,null);
	
	foreach ($list2 as $v) 
	{
		$d = $v;
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
	}
	
	return $result;

}


function swRelationFilter($filter, $globals = array(), $refresh = false)
{
	// Filter syntax: field hint? (, field hint?)*
	// Fields can be all normal fields and all internal fields
	// Some special internal fields: _namespace, _word, paragraph
	// Wildcard field * and _content can be used, if at least one field has a hint (else it would return the entire website)
	// Hints can be expressions
	// Without hint, the fields is always included in the result even if it does not exist
	// With a wildcard hint *, the field must be present
	// With a text hint, the url-version of the hint must be present in the url-version of the field
	// If the hint as spaces, all words must be present, but not necessarily in that order (AND)
	// If the hint has pipes, at least one of the part must be present (OR)
	// If a fields is present multiple times, there are multiple results. Fields with less occurences are padded
	// If the hint has the form /!.+/ the cache captures all values, but returns only exact matches (keep only one index file, but search fast in it, works only with sqlite3, no binary logic can be used however.
	
	
	if (!trim($filter)) return new swRelation('',null,null);
	
	
	
	
	global $swIndexError;
	global $swMaxSearchTime;
	global $swMaxOverallSearchTime;
	global $swStartTime;
	global $swOvertime;
	$overtime = false;
	
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
	
	if (substr($filter,0,5)=='index')
	{
		$filter2 = substr($filter,5);
		$filter2 = trim($filter2);
		
		return swRelationIndexSearch($filter2,$globals);		
	}

	$pairs = explode(',',$filter);
	
	// parse query
	// currently, inline comma is not supported on hint.
	
	$fields = array(); 
	$getAllFields = false;
	$getContent = false;
	$newpairs = array();
	$namefilter	= nulL;
	$namespacefilter = nulL;
	foreach($pairs as $p)
	{
		
		$p = trim($p);
		$elems = explode(' ',$p);
		$f = array_shift($elems);
		$h = null;
		
		if (count($elems)>0)
		{
			$h = join(' ',$elems); 
			
			$xp = new swExpression();
			$xp->compile($h);
			
			$h = $xp->evaluate($globals);			
		}
		if ($f == '*')	$getAllFields = true;
		elseif ($f == '_content' && $h=='')
		{
			$getContent = true;
			$hors2 = $fields[$f] = null;
		}	
		elseif ($h == '*')
		{
			$fields[$f] = "*";
			$hors2 = '';
		}
		elseif 	($h == '')
		{
			$hors2 = $fields[$f] = null;
		}
		elseif (substr($h,0,1) == '!')
		{
			$fields[$f] = '!'.swNameURL(substr($h,1));
			$hors2 = '';
		}
			// make each individual url but keep spaces as separator
		else
		{
			$hors = explode('|',$h);
			$hors2 = array();
			foreach ($hors as $hor)
			{
				$hands = explode(' ',$hor);
				$hands2 = array();
				
				//print_r($hands2);
				
				foreach($hands as $hand)
				{
					$hands2[] = swNameURL($hand);
				}
				
				$hors2[] = $hands2;
			}
			$fields[$f] = $hors2;
		}	
		if ($h == null)
			$newpairs[] = $f;
		elseif (substr($h,0,1) == '!')
			$newpairs[] = $f.' "!"';
		else
			$newpairs[] = $f.' "'.$h.'"';
		if ($f == '_name')
			$namefilter = $hors2;
		if ($f == '_namespace')
			$namespacefilter = $hors2;
		
	}
	
	
	
	// print_r($fields);
	
	$filter = join(', ',$newpairs); // needed values for cache.
	echotime('filter '.$filter);
	
	// if * there must be at least one hint, we cannot return the entire database
	if ($getAllFields || $getContent)
	{
		$found = false;
		foreach($fields as $hint)
		{
			if ($hint)
				$found = true;
		}
		if (!$found)
			throw new swExpressionError('filter missing at least one hint on * or when using _content',88);
	}
	

	$header = array_keys($fields);
	
	
	$result = new swRelation($header,null,null);

	//return $result;
	
	// find already searched revisions
	$mdfilter = $filter;
	$mdfilter .= 'v2'; // create new hashes for swdba
	$mdfilter = urlencode($mdfilter);
	$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
	//echotime($mdfilter);
	$bdbfile = $cachefilebase.'.db';
	//echo $bdbfile; return;
	
	if ($refresh)
		{ echotime('refresh'); if (file_exists($bdbfile)) unlink($bdbfile);}
	
	$bdbrwritable = true;
	$firstrun = ! file_exists($bdbfile);
	
	if (file_exists($bdbfile))
		$bdb = swDbaOpen($bdbfile, 'wdt');
	else
	{
		$bdb = swDbaOpen($bdbfile, 'c');
	}
	if (!$bdb)
	{
		// try read only
		$bdb = swDbaOpen($bdbfile, 'rdt');
		
				
		if (!$bdb)
			throw new swExpressionError('db failed '.md5($mdfilter),88);
			
		$bdbrwritable = false;
		echotime("bdb readonly");

	}
	if ($bdbrwritable)
		swDbaReplace('_filter',$filter,$bdb);
	
	
	// echo $bdbfile;
	
	echotime('<a href="index.php?name=special:indexes&index=queries&q='.md5($mdfilter).'.db" target="_blank">'.md5($mdfilter).'.db</a> ');

	
	if ($s = swDbaFetch('_bitmap',$bdb))
	{
		$bitmap = @unserialize($s);
		if ($bitmap === FALSE) $bitmap = new swBitmap;
	}
	else 
		$bitmap = new swBitmap;
		
	if ($s = swDbaFetch('_checkedbitmap',$bdb))
	{
		$checkedbitmap = @unserialize($s);
		if ($checkedbitmap === FALSE) $checkedbitmap = new swBitmap;
	}
	else 
		$checkedbitmap = new swBitmap;	
		
	$cached = $bitmap->countbits();
	echotime('cached '. $cached);
	
	$db->init();
	
	$maxlastrevision = $db->lastrevision;
	if ($db->indexedbitmap->length < $maxlastrevision) $db->RebuildIndexes($db->indexedbitmap->length); // fallback
		
	$bitmap->redim($maxlastrevision+1,false);
	$checkedbitmap->redim($maxlastrevision+1,false);
	
	$tocheckbitmap = $checkedbitmap->notop();
	$tocheckbitmap = $tocheckbitmap->andop($db->indexedbitmap);
	
	$tocheckbitmap = $tocheckbitmap->andop($db->currentbitmap);

	$tocheckcount = $tocheckbitmap->countbits();
	
	echotime('tocheck '.$tocheckcount); 
	
	
	global $swMonogramIndex;
	swOpenMonogram();
	
	
	
	if (isset($swMonogramIndex) && $firstrun)
	{		;
		$notinlabels = array('_paragraph', '_word');
		$notinvalues = array('_paragraph', '_word');
		
		$bigbloom = new swBitmap();
		
		$bm = swGetMonogramBitmapFromTerm('_checkedbitmap','');
		$bm->redim($db->indexedbitmap->length,false);
		$notcheckd = $bm->notop(); // echo $bm->countbits();
		
		$bigbloom->init($bm->length,true); // echo $bigbloom->countbits();
		
		foreach($fields as $field=>$hors)
		{
			if ($hors || count($fields)==1) 
			{				
				if (! in_array($field,$notinlabels))
				{
					$gr = swGetMonogramBitmapFromTerm($field, '*'); 
					$bigbloom = $bigbloom->andop($gr);
				}
				
				if ($field == '_paragraph') $field = '_content';
				if ($field == '_word') $field = '_content';
				
				if (! in_array($field,$notinvalues) && is_array($hors)) // array excludes '*' and '!abc'
				{
					
					$bor = new swBitmap();
					$bor->init($bigbloom->length,false);
					
					foreach($hors as $hor)
					{
						$band = new swBitmap();
						$band->init($bigbloom->length,true);
						
						foreach($hor as $hand)
						{
							if ($hand != '')
							{
								$gr = swGetMonogramBitmapFromTerm($field,$hand); 
								$gr->redim($bigbloom->length, true);
								$band = $band->andop($gr); 
							}
						}
	
						
						$bor = $bor->orop($band);
						
						
					}
					$bigbloom = $bigbloom->andop($bor);	
					
					// echo $bigbloom->countbits();
				}
			}
		}
		
		$bigbloom = $bigbloom->orop($notcheckd);	
		
		$bigbloom->redim($tocheckbitmap->length,true);
				
		$tocheckbitmap = $tocheckbitmap->andop($bigbloom);

		
			
		$nottocheck = $bigbloom->notop();
		
		$checkedbitmap = $checkedbitmap->orop($nottocheck);
		
		echotime('monogram '.$tocheckbitmap->countbits());

	}
	
	
	
	$dur = 0; // check always at least 50 records
		
	if ($tocheckcount > 0 && $dur<=$swMaxOverallSearchTime)
	{
		
		
		if ((!$cached || $tocheckcount > 50) && ($namefilter || $namespacefilter))
		{
			
			
			//global $swDbaHandler;
			//$urldbpath = $db->pathbase.'indexes/urls.db';
			//if (file_exists($urldbpath))
			//$urldb = swDbaOpen($urldbpath, 'rdt', $swDbaHandler);
			
			
			global $db;
			if (!$db->urldb)
			{
				echotime('urldb failed');
			}
			else
			{				
				
				
				
				$r0 = swDbaFirstKey($db->urldb);		
				
				do 
				{
					
					if (substr($r0,0,1) == ' ') continue; // url
					
					$n = $r0;
					
					
										
					if (!stristr($n,':')) $n= 'main:'.$n;
										
					if ($namespacefilter)
					{
						$orfound = false;
						
						foreach($namespacefilter as $hor)
						{
							$andfound = true;				
							foreach($hor as $hand)
							{																
								if ($hand && !strstr($n,$hand.':')) $andfound = false;
							}						
							if ($andfound) $orfound = true;
						}
						
						if (!$orfound)
						{	
							$line = swDbaFetch($r0,$db->urldb);
							$fs = explode(' ',$line);
							$st = array_shift($fs);
							$r = array_shift($fs);
							
							$tocheckbitmap->unsetbit($r);
							$checkedbitmap->setbit($r);
							continue;
						}
						else
						{
							// echo 'found '.$n;
						}
						
					}
					
					if ($namefilter)
					{
						$orfound = false;
						
						foreach($namefilter as $hor)
						{
							$andfound = true;
							
							foreach($hor as $hand)
							{
								if ($hand && !strstr($n,$hand)) $andfound = false;
							}	
							
							if ($andfound) $orfound = true;
						}
						
						if (!$orfound)
						{
							$line = swDbaFetch($r0,$db->urldb);
							$fs = explode(' ',$line);
							$st = array_shift($fs);
							$r = array_shift($fs);
							
							$tocheckbitmap->unsetbit($r);
							$checkedbitmap->setbit($r);
						}
						else
						{
							// echo 'found '.$n;
						}
						
					}
				
				} while ($r0 = swDbaNextKey($db->urldb));
				
			
			} // else db failed		

			$tocheckcount = $tocheckbitmap->countbits();
			echotime('namefilter '.$tocheckcount); 	
			
		}
		
		
				
		if (!$cached) //bloom
		{
			
			$bigbloom = new swBitmap();
		
			$bigbloom->init($db->bloombitmap->length,true);
		
		
			// only external fields that must be present
			// if there is only one field, it must always be present
			
			/* special fields an bloom
	
			exclude for value
			_displayname, _length, _namespace, _status
			
			exclude for label
			_displayname, _length, _namespace, _template, _content, _short, _paragraph, _word, _status
			
			
			_category can stay, beause it must be present as [category]
			
			
			*/
			
			
	
			$notinlabels = array('_displayname', '_length', '_namespace', '_template', '_content', '_short', '_paragraph', '_word','_any', '_status', '_name', '_revision');
			$notinvalues = array('_displayname', '_length', '_namespace', '_status');
			
			// echo " bigbefore ".$bigbloom->length;
			
			foreach($fields as $field=>$hors)
			{
				
				// echo $bigbloom->length;
				if ($hors || count($fields)==1) 
				{
					
					if (! in_array($field,$notinlabels))
					{
						// echo ' ,'.$field;
						$gr = swGetBloomBitmapFromTerm('-'.$field.'-'); // field has always [[ and :: or ]]
						
						$tocheckcount = $gr->countbits();
		
						
						// echo ' field '.$field.' '.$gr->length;
						$bigbloom = $bigbloom->andop($gr);
					}
					
					if (! in_array($field,$notinvalues) && is_array($hors))
					{
						// echo ' .'.$field;
						$bor = new swBitmap();
						$bor->init($bigbloom->length,false);
						
						foreach($hors as $hor)
						{
							$band = new swBitmap();
							$band->init($bigbloom->length,true);
							
							foreach($hor as $hand)
							{
								if ($hand != '' && strlen($hand)>2)
								{
									$gr = swGetBloomBitmapFromTerm($hand);
									
									$tocheckcount = $gr->countbits();
									echotime('bloom '.$hand.' '. $tocheckcount); 	
									
									$gr->redim($bigbloom->length, true);
									$band = $band->andop($gr);
								}
							}
		
							
							$bor = $bor->orop($band);
							
						}
						$bigbloom = $bigbloom->andop($bor);	
					}
				}
				//echo ' bbl '.$bigbloom->length;
			}
			// echo ' bigafter '.$bigbloom->length;
			
			
			
			$bigbloom->redim($tocheckbitmap->length,true);
			
			//$tocheckcount = $tocheckbitmap->countbits();
			//echotime('bloom0 '.$tocheckcount); 	
			
			//$tocheckcount = $bigbloom->countbits();
			//echotime('bigbloom '.$tocheckcount.print_r($bigbloom->toarray(),true)); 		
	
			
			$tocheckbitmap = $tocheckbitmap->andop($bigbloom);	
			
			$tocheckcount = $tocheckbitmap->countbits();

			$nottocheck = $bigbloom->notop();
			
			//echo " big ".$bigbloom->length;
			//echo "(".$bigbloom->getbit($bigbloom->length-1).")";
			
			$checkedbitmap = $checkedbitmap->orop($nottocheck);
			
			// echo " big ".$bigbloom->length;
			
			// always check the newest revisions
			/*
			for($i=$maxlastrevision;$i>=$maxlastrevision-8;$i--)
			{
				$tocheckbitmap->setbit($i);
			}
			*/
			
			
			$tocheckcount = $tocheckbitmap->countbits();
			echotime('bloom '.$tocheckcount); 	
			
					
		}
		if (!$cached && array_key_exists('_paragraph',$fields))
		{
			$hors = $fields['_paragraph']; //?
			
		}	
		
		
		
		$starttime = microtime(true);
		
		if ($tocheckcount>0) 		
		{
			//echotime('loop '.$tocheckcount);
			if ($swMaxSearchTime<500) $swMaxSearchTime = 500;
			if ($swMaxOverallSearchTime<2500) $swMaxOverallSearchTime = 2500;
			$checkedcount = 0;
			$checkedlength = 0;
			
			
			// print_r($fields);
			global $swMemoryLimit;
			$allrows = array();
			
			
			
			for ($k=$maxlastrevision;$k>=1;$k--)
			{
				if (memory_get_usage()>$swMemoryLimit)
				{
					echotime('overmemory '.memory_get_usage());
					$overtime = true;
					$swOvertime = true;
					break;
				}
				
				
				if (!$tocheckbitmap->getbit($k)) continue; // we already have ecluded it from the list
				if ($checkedbitmap->getbit($k)) continue; // it has been checked, should not happen here any more
			 	if(!$db->indexedbitmap->getbit($k)) continue; // it has not been indexed, should not happen here any more
				if(!$db->currentbitmap->getbit($k)) { $checkedbitmap->setbit($k); $bitmap->unsetbit($k); $checkedcount++; continue; } // should not happen here any more
				if($db->deletedbitmap->getbit($k)) { $checkedbitmap->setbit($k); $bitmap->unsetbit($k); $checkedcount++; continue; }
				// should not happen here any more
				$checkedcount++;
	
				$nowtime = microtime(true);	
				$dur = sprintf("%04d",($nowtime-$starttime)*1000);
				if (($dur>$swMaxSearchTime && $checkedcount >= 10) || $dur>$swMaxSearchTime * 2 )  //check at least 10 records
				{
					echotime('overtime '.$checkedcount.' / '.$tocheckcount);
					$overtime = true;
					$swOvertime = true;
					break;
				}
				$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
				if (($dur>$swMaxOverallSearchTime && $checkedcount >= 10) || $dur>$swMaxOverallSearchTime * 2) //check at least 10 records
				{
					echotime('overtime overall '.$checkedcount.' / '.$tocheckcount);
					$overtime = true;
					$swOvertime=true;
					break;
				}
				$record = new swWiki;
				$record->revision = $k;
				//echotime('lookup '.$k);
				$record->lookup();
				//echotime('read '.$record->name);
				
				if ($record->error == '') $checkedbitmap->setbit($k); else continue;
				$urlname = swNameURL($record->name);				
				
				$content = $record->name.' '.$record->content;
				$checkedlength += strlen($content);
				$row=array();
				
				$fieldlist = $record->internalfields;
				
				// special characters
				$rel = new swRelation('',null,null);
				foreach(array_keys($fieldlist) as $raw)
				{
					if (count($fieldlist[$raw]))	
					{	
						$clean = $rel->cleanColumn($raw);
						if ($clean != $raw)
						{
							$fieldlist[$clean] = $fieldlist[$raw];
							unset($fieldlist[$raw]);
						}
					}
					else
						unset($fieldlist[$raw]);
					
				}

				$fieldist0 = $fieldlist; //makes a copy
				
				

				{					
					$fieldlist['_revision'][] = $record->revision;
					$fieldlist['_status'][] = $record->status;
					$fieldlist['_name'][] = $record->name;
					$fieldlist['_displayname'][] = $record->getdisplayname();
					$fieldlist['_url'][] = swNameURL($record->name);
					$fieldlist['_user'][] = $record->user;
					$fieldlist['_timestamp'][] = $record->timestamp;
					
					
					// the following cost, so we do them only if they are in the query
					
					
					if (array_key_exists('_content',$fields)) $fieldlist['_content'][] = $record->content;
					if (array_key_exists('_length',$fields)) $fieldlist['_length'][] = strlen($record->content);
					if (array_key_exists('_short',$fields)) $fieldlist['_short'][] = substr($record->content,0,160);
					if (array_key_exists('_paragraph',$fields)) $fieldlist['_paragraph'] = explode(PHP_EOL, $record->content);
									
					if (array_key_exists('_paragraph',$fields) || array_key_exists('_trigram',$fields) || array_key_exists('_trigram32',$fields) || array_key_exists('_bigram',$fields) ||  array_key_exists('_bigramw',$fields) || array_key_exists('_bmc',$fields) || array_key_exists('_bmt',$fields) )
					{
						$s = preg_replace("/[0123456789:\/.]/","-", $record->content);
						$s = swNameURL($s);
						
						if (array_key_exists('_paragraph',$fields))
						{
							$fieldlist['_word'] = explode('-', $s);
							$fieldlist['_word'] = array_values(array_filter($fieldlist['_word'], function ($var){return strlen($var)>=3;})); 
						}
					
						if (array_key_exists('_trigram',$fields))
						{
							$cs = strlen($s); 
							$trigrams = array();
							for($i=0;$i<$cs-2;$i++)
							{
								$trigrams[substr($s,$i,3)] = '1';
							}
							$fieldlist['_trigram'] = array_keys($trigrams);
						}
						
						if (array_key_exists('_trigram32',$fields))
						{
							$cs = strlen($s); 
							$trigrams = array();
							for($i=0;$i<$cs-2;$i++)
							{
								$tr = substr($s,$i,3);
								if (strstr($tr,'-')) continue;
								if (!isset($trigrams[$tr])) $trigrams[$tr]=0;
								$trigrams[$tr]++;
							}
							
							//rsort($trigrams,SORT_NUMERIC);
							uasort($trigrams, function($a, $b) { return $b - $a; });
							$trigrams = array_slice($trigrams,0,64, true);
							$trigrams = array_keys($trigrams);
							sort($trigrams,SORT_STRING );
							$trigrams = array(join(' ',$trigrams));
							//print_r($trigrams);
							$fieldlist['_trigram32'] = $trigrams;
						}
						
						if (array_key_exists('_bigram',$fields))
						{
							$cs = strlen($s); 
							$bigrams = array();
							for($i=0;$i<$cs-1;$i++)
							{
								$tr = substr($s,$i,2);
								if (strstr($tr,'-')) continue;
								if (!isset($bigrams[$tr])) $bigrams[$tr]=0;
								$bigrams[$tr]++;
							}
							
							//rsort($trigrams,SORT_NUMERIC);
							uasort($bigrams, function($a, $b) { return $b - $a; });
							// $bigrams = array_slice($bigrams,0,count($bigrams)/2, true);
							$bigrams = array_keys($bigrams);
							sort($bigrams,SORT_STRING );
							$bigrams = array(join(' ',$bigrams));
							//print_r($trigrams);
							$fieldlist['_bigram'] = $bigrams;
						}
						if (array_key_exists('_bigramstat',$fields))
						{
							
							$fieldlist['_bigramw'] = array(swBigramStat($s));
						}
						if (array_key_exists('_bmc',$fields) && array_key_exists('_bmt',$fields))
						{
							$cs = strlen($s); 
							$bigrams = array();
							for($i=0;$i<$cs-1;$i++)
							{
								$tr = substr($s,$i,2);
								if (strstr($tr,'-')) continue;
								if (!isset($bigrams[$tr])) $bigrams[$tr]=0;
								$bigrams[$tr]++;
							}
							
							$fieldlist['_bmt'] = array_keys($bigrams);
							$fieldlist['_bmc'] = array_values($bigrams);
						}
					}
					
					if (array_key_exists('_bloom',$fields)) $fieldlist['_bloom'] = explode(PHP_EOL, $record->content);
					
					
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
					
					//if (!isset($fieldlist[$field])) { $bitmap->unsetbit($k); continue; } ???
					
					// normalize array, to a table, but using only used fields and field
					// $maxcount = count($fieldlist[$field]);
					$maxcount = 1;

					foreach($fields as $key=>$v)
					{
						if (isset($fieldlist[$key]))
						{
							$maxcount = max($maxcount,count($fieldlist[$key]));
						}
					}
					$maxcountall = 1;
					foreach($fieldist0 as $key=>$v)
					{
						$maxcountall = max($maxcountall,count($fieldist0[$key]));
						
					}
	
					$fieldlist2 = array();
					foreach($fieldlist as $key=>$v)
					{
						if (array_key_exists($key,$fields) or in_array($key,array('_revision','_url'))
						or ($getAllFields and substr($key,0,1) != '_') )
						{
						
							for($fi=0;$fi<count($v);$fi++)
							{
								$fieldlist2[$fi][$key] = swUnescape($v[$fi]);
																
							}
							for ($fi=count($v);$fi<$maxcount;$fi++)
							{	
								if (count($v) > 0)
									$fieldlist2[$fi][$key] = swUnescape($v[count($v)-1]);
							}
							
							
						}
					}
					
					// print_r($fieldlist2);
					
					// select
					$rows = array();
					for ($fi=0;$fi<$maxcount;$fi++)
					{
						$revision = $fieldlist2[$fi]['_revision'];
						$found = true;
												
						foreach($fields as $key=>$hint)
						{
							$fieldfound = false;
							if ($hint== null)
							{								
								if (!array_key_exists($key,$fieldlist2[$fi]))
									$fieldlist2[$fi][$key] = '';
								
								$fieldfound = true;
								
							}
							elseif (array_key_exists($key,$fieldlist2[$fi]))
							{
								if ($hint=='*' || (!is_array($hint) && (substr($hint,0,1) == '!')))
								{
									$fieldfound = true;
								}
								else
								{									
									$flv =  swNameURL($fieldlist2[$fi][$key]);
									// echo $flv.' ';
									
									$orfound = false;
									
									
									foreach($hint as $hor)
									{
										$andfound = true;
										
										foreach($hor as $hand)
										{
											
											if ($hand != '' && !strstr($flv,$hand)) $andfound = false;
										}
										
										if ($andfound) $orfound = true;
									}
									
									if ($orfound) 
									{
										$fieldfound = true;
									}

									
								}
								
							}
							
							if (!$fieldfound) $found = false;
							
							
						}
						
						
						
						if ($found)
						{
							$rows[$revision.'-'.$fi] = $fieldlist2[$fi];
						}
						

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
				
				
				
				

				if (count($rows)>0 && $bdbrwritable)
				{
					
					
					foreach($rows as $primary=>$line)
					{
						$linehascontent = false;
						foreach($line as $key=>$value)
						{

							//print_r($line);
							if (array_key_exists($key,$fields) || ( $key != '_revision' && $key != '_url') )
							{
								if ($value) $linehascontent = true;
							}
						}
						
						if ($linehascontent) swDbaReplace($primary,serialize($line),$bdb); // use less memory
					}
					$bitmap->setbit($k);
					
				}
				$checkedbitmap->setbit($k);
				
				
			}
			
	
			echotime('cachefile '.floor($checkedlength/1024).' KB');
			echomem("relationfilter");	
		}
	
	}
	
	
		
	
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

	
	
	if ($bdbrwritable)
	{	
		// dba_replace('_filter',$filter,$bdb);
		swDbaReplace('_overtime',serialize($overtime),$bdb);
		swDbaReplace('_bitmapcount',$bitmap->countbits(),$bdb);
		swDbaReplace('_checkedbitmapcount',$checkedbitmap->countbits(),$bdb);
	$bitmap->hexit();
		swDbaReplace('_bitmap',serialize($bitmap),$bdb);
	$checkedbitmap->hexit();
		swDbaReplace('_checkedbitmap',serialize($checkedbitmap),$bdb);
		swDbaReplace('_header',serialize($header),$bdb);
		
	}

	//echotime('sync');
	
	
	if (!swDbaSync($bdb))
	{
		
		// roll back?
	}
	
	$d = array();
	
	$keycount = swDbaCount($bdb);	
	
	$key = swDbaFirstKey($bdb);

	//echotime('userrights');
	
	while($key)
	{
		//echotime('key '.$key);
		
		if (substr($key,0,1)=='_')
		{
			$key = swDbaNextKey($bdb); 
			continue;
		}
		
		$keys = explode('-',$key);
		$kr = $keys[0];
		
		if (!$db->currentbitmap->getbit($kr))
		{
			//echotime('delete');
			//swDbaDelete($key,$bdb);
			//$bitmap->unsetbit($kr);
			
			$key = swDbaNextKey($bdb);
			continue;
		}
		
		$d = @unserialize(swDbaFetch($key,$bdb)); // can be wrong
		$dn = @$d['_url'];
		
		if (!$searcheverywhere && stristr($dn,':'))
		{
			$dnf =explode(':',$dn);
			$dns = swNameURL(array_shift($dnf));
			$nss = join(PHP_EOL,$ns);
			if (!stristr($nss,$dns) && !$user->hasright('view',$dn))
			{
				$key = swDbaNextKey($bdb);
				continue;
			}
		}
				
		if (!in_array('_revision',$result->header)) unset($d['_revision']);
		if (!in_array('_url',$result->header)) unset($d['_url']);
		
		if (!empty($d))
		{
			$tp = new swTuple($d);
			$result->tuples[$tp->hash()] = $tp;
		}

				
		$key = swDbaNextKey($bdb);
	}
	
	//echotime('header');
	
	
	swDbaClose($bdb);
	
	
	if ($d)
	foreach($d as $key=>$val)
	{
		if (!in_array($key, $result->header))
			$result->addColumn($key);
	}
	
	// print_r($result);
	//echotime('filter end');
	
	//print_r($result);
	
	return $result;
	
}

/**
 *   Extracts fields from logs
 *   
 */

function swRelationLogs($filter, $globals = array(), $refresh = false)
{
	global $swRoot;
	global $swMemoryLimit;
	if (!isset($swMemoryLimit)) $swMemoryLimit = 100000000;
	global $swMaxSearchTime;
	global $swMaxOverallSearchTime;
	global $swStartTime;
	global $swOvertime;
	
	
	if (!$filter)
		throw new swExpressionError('Logs filter empty',88);
		
	$fields = array();
	$pairs = explode(',',$filter);	
	
	if (substr($filter, 0, 5) == 'stats') $pairs = explode(',','file '.substr($filter,6));
	
	$filters2 = array();
	foreach($pairs as $pair)
	{
		$words = explode(' ',trim($pair));
		
		if (isset($words[1]))
		{
			$xp = new swExpression();
			$xp->compile($words[1]);
			$words[1] = $xp->evaluate($globals);
		}
		else
		{
			$words[1] = '';
		}
		$fields[$words[0]] = $words[1];	
		$filters2[] = $words[0].' "'.$words[1].'"';
	}
	
	if (substr($filter, 0, 5) == 'stats') $dd = $fields['file'];

	// print_r($fields);
	
	$filter2 = join(', ',$filters2);
	
	$root = $swRoot.'/site/logs/';
	
	echotime('logs '.$filter2);
	
	$files = glob($root.'*.txt');
	rsort($files);
	
	$mdfilter = 'logs '.$filter2;
	$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
	$bdbfile = $cachefilebase.'.db';
	
	
	
	if ($refresh)
	{
		echotime('refresh');
		if (file_exists($bdbfile)) unlink($bdbfile);
	}
	if (file_exists($bdbfile))
		$bdb = swDbaOpen($bdbfile, 'wdt');
	else
	{
		$bdb = swDbaOpen($bdbfile, 'c');
		swDbaReplace('_filter',$filter,$bdb);
	}
	if (!$bdb)
	{
		// try read only
		$bdb = swDbaOpen($bdbfile, 'rdt');
		
				
		if (!$bdb)
			throw new swExpressionError('db failed '.md5($mdfilter),88);
			
		$bdbrwritable = false;
		echotime("bdb readonly");
	}
	
	echotime('<a href="index.php?name=special:indexes&index=queries&q='.md5($mdfilter).'.db" target="_blank">'.md5($mdfilter).'.db</a> ');

	$tdodayfile = $root.date('Y-m-d',time()).'.txt';
	
	$startTime = microtime(true);	
	
	$hintfunction = new XpHint;
	
	$counter = 0;	
	
	
		
	
	foreach($files as $file)
	{
		
		$shortfile = str_replace($root,'',$file);
		
		if (memory_get_usage()>$swMemoryLimit)
		{ 
			$swOvertime = true;
			break;
		}
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$startTime)*1000);
		if ($dur > $swMaxSearchTime)
		{ 
			$swOvertime = true;
			break;
		}
		$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
		if ($dur > $swMaxOverallSearchTime) 
		{ 
			$swOvertime = true;
			break;
		}
		
		if (substr($filter, 0, 5) == 'stats')
		{	
			$fields = array('file'=>$dd,'day'=>'', 'time'=>'','name'=>'','user'=>'');
		}

			
		$d = array();
		
		if (stristr($file,'/deny-')) continue;
				
		if ($file !== $tdodayfile && swDbaExists($file,$bdb)) continue;

		$foundfile = false;
		if (array_key_exists('file',$fields))
		{
			if (!$fields['file'])
			{
				$foundfile = true;
			}
			else
			{			
				$stack = array();

				$stack[] = $fields['file'];
				$stack[] = $shortfile;
				$foundfile = $hintfunction->run($stack);
			}
		}
		else
		{
			$foundfile = true;
		}

		$rows = array();
		if ($foundfile)
		{
			
			if ($filter == 'file') // only filelist
			{
				
				$rows = array();
				$values = array();
				$values['file'] = $shortfile;
				$rows[] = $values;
				swDbaReplace($file,serialize($rows),$bdb);
				continue;
			}
			
						
			// echo $shortfile.' '.$fields['file'].' ';
			$handle = @fopen($file, 'r');
			$hits = 0;
			$totaltime = 0;
			
			while($handle && ($line = fgets($handle, 4096)) !== false)
			{
				
				$values0 = swGetAllFields($line);
				foreach($values0 as $k=>$v)
				{
					$values[$k] = $v[0];
				}
				$values['day'] = substr($values['timestamp'],0,10);
				
				$found = true;
				$values1 = array(); 
			
				foreach($fields as $k=>$v)
				{
					if ($k=='file')
					{
						$values1[$k] = $shortfile;
						
					}
					else
					{
						if (isset($values[$k]))
						{
							$values1[$k] = $values[$k];
						}
						else
						{
							$found = false;
						}
						if ($found && $v)
						{
							$stack = array();

							$stack[] = $v;
							$stack[] = $values[$k];
							
							$found = $hintfunction->run($stack);
						}
					}
				}
				
				if ($found)
				{
					$rows[] = $values1;	
					$hits++;
					$totaltime += intval($values['time']);
				}
				
						
			}
			
			if (substr($filter, 0, 5) == 'stats')
			{
				$ud = array();
				$nd = array();
				foreach($rows as $row)
				{
					$row['name'] = swNameURL($row['name']);
					//$row['user'] = swNameURL($row['user']);
					
					if (isset($ud[$row['user']])) $ud[$row['user']]++; else $ud[$row['user']]=1;
					
					$nd[$row['name']][$row['user']]=1;
						
					$day = $row['day'];
				}
				$nd2 = array();
				foreach($nd as $k=>$v)
				{
					$nd2[$k] = array_sum($v);
				}
				
				$uc = count($ud);
				$vp = array_sum($nd2); 
				
				$fields = array('file','category','key','value');
				
				$rows = array();
				
				$rows []= array('file'=>$shortfile, 'category'=>'stat','key'=>'day','value'=>$day);
				$rows []= array('file'=>$shortfile, 'category'=>'stat','key'=>'hits','value'=>$hits);
				$rows []= array('file'=>$shortfile, 'category'=>'stat','key'=>'totaltime','value'=>round($totaltime/1000));
				$rows []= array('file'=>$shortfile, 'category'=>'stat','key'=>'visited_pages','value'=>$vp);
				$rows []= array('file'=>$shortfile, 'category'=>'stat','key'=>'unique_users','value'=>$uc);
				
				
				
				foreach($nd2 as $k=>$v)
				{
					$rows []= array('file'=>$shortfile, 'category'=>'name','key'=>$k,'value'=>$v);
				}
				
				foreach($ud as $k=>$v)
				{
					$rows []= array('file'=>$shortfile, 'category'=>'user','key'=>$k,'value'=>$v);
					
					
				}
				
				
				
				
				
			}
			
			swDbaReplace($file,serialize($rows),$bdb);
			if (rand(0,1000)>990) swDbaSync($bdb);
		}
		
		
		
		
		
		
		
	}
	
	echotime('logs sync');
	
	
	if (! swDbaSync($bdb))
	{
		echotime('dbasync failed');
		// roll back?
	}
	
	echotime('logs build');
	
	$result = new swRelation('');
	$key = swDbaFirstKey($bdb);
	
	$columns = array();
	
	if (substr($filter, 0, 5) == 'stats') 
	{
		$globalrows = array();
		$globalrows['hits']= 0;
		$globalrows['totaltime']= 0;
		$globalrows['visited_pages'] = array();
		$globalrows['unique_users'] = array();
	}
	
	while($key)
	{	
		if (substr($key,0,1)=='_') { $key = swDbaNextKey($bdb); continue;}
		$rows = @unserialize(swDbaFetch($key,$bdb));
		
		if (is_array($rows))
		foreach($rows as $d)
		{
			if (!empty($d))
			{
				if (memory_get_usage()>$swMemoryLimit)
				{
					echotime('overmemory logs '.memory_get_usage().' '.$filter);
					throw new swExpressionError('overmemory logs');
				}
				
				$ignore = false;
				
				if (substr($filter, 0, 5) == 'stats')
				{
					
				    switch($d['category'])
					{
						case 'stat':  if ($d['key']=='hits') $globalrows['hits']+=$d['value'];
									  if ($d['key']=='totaltime') $globalrows['totaltime']+=$d['value'];
									  
									  break; 
					    case 'name':  if (isset($globalrows['visited_pages'][$d['key']]))
											$globalrows['visited_pages'][$d['key']] += $d['value'];
									  else
									  		$globalrows['visited_pages'][$d['key']] = $d['value'];
									  $ignore = true;
									  break;
					    case 'user':  if (isset($globalrows['unique_users'][$d['key']]))
											$globalrows['unique_users'][$d['key']] += $d['value'];
									  else
									  		$globalrows['unique_users'][$d['key']] = $d['value'];
									  $ignore = true;
									  break;
					}
			    }
				
				if ($ignore) continue;
				
				$tp = new swTuple($d);
				$result->tuples[$tp->hash()] = $tp;
				
				foreach($d as $k=>$v)
				{
					if (!in_array($k,$columns)) $columns[] = $k;
				}
				
								
			}
		}
		
		
		
		$result->header = $columns;
		$key = swDbaNextKey($bdb); 
		
	}
	
	;
	
	if (substr($filter, 0, 5) == 'stats')
	{

		echotime('logs stats');
		
		$d = array('file'=>$dd, 'category'=>'stat','key'=>'day','value'=>$dd);
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
		$d = array('file'=>$dd, 'category'=>'stat','key'=>'hits','value'=>$globalrows['hits']);
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
		$d = array('file'=>$dd, 'category'=>'stat','key'=>'totaltime','value'=>$globalrows['totaltime']);
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
		$d = array('file'=>$dd, 'category'=>'stat','key'=>'visited_pages','value'=>array_sum($globalrows['visited_pages']));
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
		$d = array('file'=>$dd, 'category'=>'stat','key'=>'unique_users','value'=>count($globalrows['unique_users']));
		$tp = new swTuple($d);
		$result->tuples[$tp->hash()] = $tp;
		
		// echotime('visited_pages sort');
		
		uasort($globalrows['visited_pages'], function($a, $b) {return $b-$a;});
		
		// echotime('visited_pages tuples '.count($globalrows['visited_pages']));
		
		$i = 0;
		foreach($globalrows['visited_pages'] as $k=>$v)
		{
			$i++; 
			if ($i>1000) break;
			$d = array('file'=>$dd, 'category'=>'name','key'=>$k,'value'=>$v);
			$tp = new swTuple($d, true);
			$result->tuples[$tp->hash()] = $tp;
		}
		
		// echotime('unique_users sort ');
		
		uasort($globalrows['unique_users'], function($a, $b) {return $b-$a;});
		
		// echotime('unique_users tuples'.count($globalrows['unique_users']));
		
		$i = 0;
		foreach($globalrows['unique_users'] as $k=>$v)
		{
			$i++; 
			if ($i>1000) break;
			$d = array('file'=>$dd, 'category'=>'user','key'=>$k,'value'=>$v);
			$tp = new swTuple($d, true);
			$result->tuples[$tp->hash()] = $tp;
		}	
		
		
	}
	echotime('logs done');
	
	if (memory_get_usage()>$swMemoryLimit/10) echomem('relationfilter');
	return $result;
}


function swRelationToTable($q)
{
	$lh = new swRelationLineHandler;
	$s = $lh->run($q); 
	$result = array();
	if (count($lh->errors)) { $result[] = array('error'=>trim(strip_tags($s))); return $result; }
	if (!count($lh->stack)) return array();
	$r = array_pop($lh->stack);
	
	unset($lh); // we do not need the entire stack any more
	
	foreach($r->tuples as $t)
	{
		$result[] = $t->fields();
	}
	
	return $result;
}



function swRelationSearch($term, $template="")
{
	
	global $lang;
	$term = swSimpleSanitize($term);
		
	if ($template && $template != 1)
	{
		$print = '
project _name, _paragraph
template "'.$template.'"';
	}
	else
	{
		$print = '
update _name = link(_name,_displayname).tag("br")._paragraph 
project _name

label _name ""
print linegrid 50';
	}
	
	
	
	$singlequote = "'";
	$results = swSystemMessage('results',$lang);
	$results1 = swSystemMessage('result',$lang);


	global $swSearchNamespaces;
	
	$spaces = array_filter($swSearchNamespaces);
	if (!in_array('main',$spaces)) $spaces[] = 'main';
	$namespace = join('|',$spaces); // filter removes empty values
	if (trim($namespace)=='main') $namespace = "main";
	if (stristr($namespace,'*')) $namespace = '*';
	$namespace = strtolower($namespace);

// note when result is empty, script fails with union, because aggregation has other arity than normal column

	$q = '

filter _namespace "'.$namespace.'", _name, _displayname, _paragraph "'.$term.'"
write "paragraphs"
filter _namespace "'.$namespace.'", _name "'.$term.'", _displayname, _paragraph
union

select trim(_paragraph) !== "" and substr(_paragraph,0,1) !== "#" and substr(_paragraph,0,2) !== "{{" and substr(_paragraph,0,6) !== "<code>"  and substr(_paragraph,0,2) !== "{|"

update _name = substr(_name,0,-3) where substr(_name,-3,1) == "/" 

project _name, _paragraph count, _paragraph first
rename _paragraph_first _paragraph
order _paragraph_count 9

project _name, _paragraph

// filter _namespace "main", _name, _paragraph "'.$term.'"
read "paragraphs"
extend row = rownumber
project inline _name, row min
select row = row_min
project _name, _paragraph 

join left

// remove wiki styles
update _paragraph = resume(_paragraph,9999,1)

// create a query to be split
set v = "'.$term.'" ." "
set c = length(v)
set i = 1
set l = 0

// set query in paragraph bold
while i < c
if substr(v,i,1) == " " or substr(v,i,1) == "|"
if i > l
set s = substr(v,l,i-l)
set bold = _lt."b"._gt  // we need tags, as apostrophs could be in the text
set unbold = _lt."#b"._gt // regex does not support the slash
set unbold2 = _lt."/b"._gt
update _paragraph = regexreplacemod(_paragraph,s,bold.s.unbold,"i")
update _paragraph = replace(_paragraph,unbold,unbold2)
end if
set l = i + 1

end if
set i = i + 1
end while

'.$print.'


echo " "';
global $swUseFulltext;
if ($swUseFulltext)
{
$q= 'fulltext "'.$term.'"
extend t = link(url,title).tag("br").tag("nowiki",body)
project t
label t ""
print linegrid 50';
}

$lh = new swRelationLineHandler;
$s = $lh->run($q);




return $s;


}

function swBigramStat($s)
{
	
	$s = preg_replace("/[0123456789:\/.]/","-", $s);
	$s = swNameURL($s); 
	$cs = strlen($s); 
	$bigrams = array();
	for($i=0;$i<$cs-1;$i++)
	{
		$tr = substr($s,$i,2);
		if (strstr($tr,'-')) continue;
		if (!isset($bigrams[$tr])) $bigrams[$tr]=0;
		$bigrams[$tr]++;
	}
	uasort($bigrams, function($a, $b) { return $b - $a; });
	$bigrams2 = array();
	foreach($bigrams as $bigramkey=>$bigramvalue)
	{
		$bigrams2[] = $bigramkey.'='.$bigramvalue;
	}
	
	return join(' ',$bigrams2);
}

function swRelationIndexSearch($filter, $globals = array())
{
	//echo $filter;
	echotime('filter index');
	
	global $swDbaHandler;
	if ($swDbaHandler !== 'sqlite3')
	{
		throw new swRelationError('Filter index works only with SQLite3 database handler',87);
	}
	
	
	
	// searches through all user defined 
	$fields = array();
	// we need a state machine to parse the filter, because the values in quotes might contain spaces
	
	$state = 'start';
	
	$characters = preg_split('//u', $filter);
	$buffer = ''; $key = '';
	foreach($characters as $ch)
	{
		switch ($state)
		{
			case 'start':			switch ($ch)
									{
										case ' ':  	break;
										default :	$buffer = $ch;
													$state = 'field';
									}
									break;
			case 'field':			switch ($ch)
									{
										case ' ':	$key = $buffer;
													$buffer = '';
													$state = 'value';
													break;
										case ',':	$key = $buffer;
													$fields[$buffer] = '';
													$key = '';
													$buffer = '';
													$state = 'start';
													break;
										default:	$buffer .= $ch;
									}
									break;
			case 'value':			switch ($ch)
									{
										case ',':	$fields[$key] = $buffer;
													$key = '';
													$buffer = '';
													$state = 'start';
													break;
										case '"':	$buffer .= $ch;
													$state = 'quotedvalue';
													break;
										default: 	$buffer .= $ch;	
									}
									break;
			case 'quotedvalue':		switch ($ch)
									{
										case '"':	$buffer .= $ch;
													$state = 'value';
													break;
										default: 	$buffer .= $ch;	
									}
									break;
		}
		
	}
	if ($buffer)
	{
		switch ($state)
		{
			case 'field':	$fields[$buffer] = '';
							break;
			case 'start':	break;
			default:		$fields[$key] = $buffer;
		}
	}
	
	//print_r($fields);
	
	// check all fields
	$relation = new swRelation('revision, row, key, value');
	
	foreach($fields as $k=>$f)
	{
		if (!$relation->validName($k)) 
		{
			throw new swRelationError('Invalid field name "'.$k.'"',87);
		}
		if (substr($k,0,1) == '_' && $k !== '_name')
		{
			throw new swRelationError('Invalid underscore name for filter index "'.$k.'"',87);
		}
	}
	
	// resolve expressions
	foreach($fields as $k=>$v)
	{
		$xp = new swExpression();
		$xp->compile($v);
		$fields[$k] = trim($xp->evaluate($globals));
	}
	
	// index fields 
	global $swRoot;
	global $db;
	$path = $swRoot.'/site/indexes/fields.db';
	try
	{
		$fielddb = new SQLite3($path);
		if (! $fielddb)
		{
			throw new swDbaError('fields.db construct db not exist '.$fielddb->lastErrorMsg().' path '.$path);
		}

	}
	catch (Exception $err)
	{
		echo 'fields.db open errror '.$err->getMessage().' '.$path; return;
	}
	
	if (!$fielddb->exec('CREATE TABLE IF NOT EXISTS fields (revision, row, key, value)'))
	{
			throw new swDbaError('swDba create table error '.$fielddb->lastErrorMsg());
	}
	if (!$fielddb->exec('CREATE TABLE IF NOT EXISTS aux (key, value)'))
	{
			throw new swDbaError('swDba create table error '.$fielddb->lastErrorMsg());
	}
	if (!$fielddb->exec('CREATE INDEX IF NOT EXISTS revisions ON fields (revision, row)'))
	{
			throw new swDbaError('swDba create table error '.$fielddb->lastErrorMsg());
	}
	if (!$fielddb->exec('CREATE INDEX IF NOT EXISTS vals ON fields (key, value)'))
	{
			throw new swDbaError('swDba create table error '.$fielddb->lastErrorMsg());
	}
	
	$checkedbitmap = new swBitmap;
	$c = $db->currentbitmap->length;
	$checkedbitmap->redim($c);
	$result = $fielddb->query("SELECT DISTINCT revision FROM fields");
	while ($row = $result->fetchArray(SQLITE3_ASSOC))
	{
		$checkedbitmap->setbit($row['revision']);
	}
	
	$journal = array();
	
	$starttime = microtime(true);
	global $swMaxSearchTime;
	global $swOvertime;
	
	$l= 0;
	
	echotime('start '.$checkedbitmap->countbits().'/'.$db->currentbitmap->countbits());
	
	for ($i = 1; $i<= $c; $i++)
	{
		
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>$swMaxSearchTime)
		{ 
			$swOvertime = true;
			echotime('searchtime fields'); 
			break;
		}
		
		if (count($journal)>10000)
		{
			$q = 'BEGIN;'.PHP_EOL.join(PHP_EOL,$journal).PHP_EOL.'COMMIT;';
			$fielddb->exec($q);
			echotime('synced '.count($journal));
			$journal = array();
		}

		
		if ($db->currentbitmap->getbit($i) && !$checkedbitmap->getbit($i))
		{
			// we need to index
			$record = new swWiki;
			$record->revision = $i;
			$record->lookup();
		
			$fieldlist = $record->internalfields;
			
			foreach($fieldlist as $key=>$value)
			{
				if (substr($key,0,1)=='_') unset($fieldlist[$key]);
			}
			
			$fieldlist['_name'][] = $record->name;
			
			// normalize
			$maxrows = 0;
			foreach($fieldlist as $f) $maxrows = max(count($f),$maxrows);
			
			
			
			foreach($fieldlist as $key=>$list)
			{
				
				$j = 1; $lastv = '';
				foreach($list as $v)
				{
					$lastv = $v = $fielddb->escapeString($v);
					$journal[]= "INSERT INTO fields (revision,row,key,value) VALUES ('$i','$j','$key','$v');";
					$j++;
				}
				
				while($j <= $maxrows)
				{
					$journal[]= "INSERT INTO fields (revision,row,key,value) VALUES ('$i','$j','$key','$lastv');";
					$j++;
				}
				

			}
			
			$checkedbitmap->setbit($i);
			
			//echo $i.' ';
						
			$l++;
		}
		elseif (!$db->currentbitmap->getbit($i) && $checkedbitmap->getbit($i))
		{
			$journal[]= "DELETE FROM fields WHERE revision = '$i';";
			$checkedbitmap->unsetbit($i);
			echotime(-$i);
			$l++;
		}
		
	}
	
	echotime('lookup '.$l.' '.$checkedbitmap->countbits().'/'.$db->currentbitmap->countbits());
	
	if (count($journal))
	{
		$q = 'BEGIN;'.PHP_EOL.join(PHP_EOL,$journal).PHP_EOL.'COMMIT;';
		$fielddb->exec($q);
		echotime('synced '.count($journal));
	}
	
	// query database
	$qstart = "SELECT t1.value as _name ";
	$qbody = "FROM fields t1 ";
	$qend = "WHERE t1.key = '_name' ";
	$first = ''; $t = 1;
	
	$headerfields=array_keys($fields);
	uasort($fields, function($a, $b) { return strlen($b) - strlen($a); }); // with the joins, we start with the more restrictive, leaving also left joins for columns without values at the end.
	
	foreach($fields as $k=>$v)
	{
		$t++;
		$qstart .= ", t$t.value AS $k ";
		if ($v)
			$qbody .= "JOIN fields t$t ON t1.revision = t$t.revision AND t1.row = t$t.row ";
		else
			$qbody .= "LEFT JOIN fields t$t ON t1.revision = t$t.revision AND t1.row = t$t.row ";
		$qend .= "AND t$t.key = '$k' ";
		if ($v) $qend .= "AND t$t.value = '$v' ";	
	}
	$first = '';
		
	$q = $qstart . $qbody.$qend;
	
	echotime($q);
	
	// echo $q;
	
	$result = $fielddb->query($q);
	

	
	
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
	$relation->header = $headerfields;
	$first = true;
	while ($row = $result->fetchArray(SQLITE3_ASSOC))
	{
		
		
		$dn = @$d['_name'];
		
		if (!$searcheverywhere && stristr($dn,':'))
		{
			$dnf =explode(':',$dn);
			$dns = swNameURL(array_shift($dnf));
			$nss = join(PHP_EOL,$ns);
			if (!stristr($nss,$dns) && !$user->hasright('view',$dn))
			{
				continue;
			}
		}
		if (!array_key_exists('_name',$fields)) unset($row['_name']);
		
		$tp = new swTuple($row, true);
		$relation->tuples[$tp->hash()] = $tp;
	}
		
	return $relation;
	
	
}


?>