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
	
	
	$isindex = false;
	if (substr($filter,0,5)=='index')
	{
		$filter2 = substr($filter,5);
		$filter2 = trim($filter2);
		if (stristr($filter2,' ')) throw new swExpressionError('filter index invalid field',88);
		$indexkey = $filter2;
		$offsetrelevant = false;
		$cacheoffsetrelevant = false;
		$filter2 .= ' "*"';
		$isindex = true;
		$pairs = array($filter2);
		$pairs[] = '_revision';
		$pairs[] = '_offset';
		
	}
	else
		$pairs = explode(',',$filter);
	
	// print_r($pairs);
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
			
			// print_r($globals);
			
			$h = $xp->evaluate($globals);
			
			
			
		}
		if ($f == '*')	
			$getAllFields = true;
		elseif ($f == '_content' && $h=='')
		{
			$getContent = true;
			$hors2 = $fields[$f] = null;
		}	
		elseif ($h == '*')
		{
			$hors2 = $fields[$f] = "*";
		}
		elseif 	($h == '')
		{
			$hors2 = $fields[$f] = null;
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
		else
			$newpairs[] = $f.' "'.$h.'"';
		if ($f == '_name')
			$namefilter = $hors2;
		if ($f == '_namespace')
			$namespacefilter = $hors2;
		
	}
	
	
	
	// print_r($fields);
	if (!$isindex)
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
	
	if ($refresh)
		{ echotime('refresh'); if (file_exists($bdbfile)) unlink($bdbfile);}
		
	/*	
	// if we do not already have a cache, then we try to read all indexes first
	if (! $isindex && !file_exists($bdbfile))
	{
		
		
		$q = array();
		$fs = array();
		$first = true;
		$validindex = true;
		//print_r($pairs);
		
		foreach($pairs as $p)
		{
			$p = trim($p);
			$elems = explode(' ',$p);
			$key = array_shift($elems);
			$hint = join(' ',$elems);
			
			$fs[] = 'index '.$key;
			$q[] = 'filter index '.$key;
			if (trim($hint)) $q[] = 'select hint('.$key.', '.$hint.')';
			if (!$first) $q[] = 'join natural';
			$first = false;
			
			if (substr($key,0,1)=='_') 
			{
				switch ($key)
				{
					case "_name" : 
					case "_word" :
					case "_template ": break;
					
					default: $validindex = false;
					
				}
			}
			
		}

		if ($validindex)
		{
			global $swDebugRefresh;
			$dr = $swDebugRefresh;
			$swDebugRefresh = false;
			
			$indextable = swRelationToTable(join(PHP_EOL,$q));
			
			foreach($fs as $f)
			{
				$mdfilter = $f;
				$mdfilter .= 'v2'; // create new hashes for swdba
				$mdfilter = urlencode($mdfilter);
				$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
				//echotime($mdfilter);
				$ff = $cachefilebase.'.db';
				
				$fdb = swdba_open($ff, 'wdt', 'db4');
				if ($s = swdba_fetch('_checkedbitmap',$fdb))
				{
					$fb = @unserialize($s);
					if (isset($indexcheckedbitmap))
					{
						$indexcheckedbitmap->andop($fb);
					}
					else
					{
						$indexcheckedbitmap = $fb;
					}						
				}
				else echotime($ff);
				
			}
			
			$swDebugRefresh = $dr;
			
			echotime('useindex '.count($indextable).'/'.@count($indexcheckedbitmap));

		}
		
				
	}
	*/
	
	
	
	
	
	
	$bdbrwritable = true;
	$firstrun = ! file_exists($bdbfile);
	
	if (file_exists($bdbfile))
		$bdb = swDbaOpen($bdbfile, 'wdt', 'db4');
	else
	{
		$bdb = swDbaOpen($bdbfile, 'c', 'db4');
	}
	if (!$bdb)
	{
		// try read only
		$bdb = swDbaOpen($bdbfile, 'rdt', 'db4');
		
				
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
	
	if ($isindex)
	{
		$key = swDbaFirstKey($bdb);
		while(substr($key,0,1)=='_' && $key) $key = swdba_nextkey($bdb);
		if ($key)
		{
			$d = @unserialize(swDbaFetch($key,$bdb));
			if (isset($d['_offset'])) 
			{
				$offsetrelevant = true;
				$cacheoffsetrelevant = true;
			}
		}
		else
		{
			$cacheoffsetrelevant = true; // no valid record, so we do not care
		}
	}
	
		
	if (isset($indextable))	
	{
		//print_r($indextable);
		foreach($indextable as $row)
		{
			//echotime(print_r($row,true));
			$revision = $row['_revision'];
			$offset = $row['_offset'];
			unset($row['_revision']);
			unset($row['_offset']);
			
			swDbaReplace($revision.'-'.$offset,serialize($row),$bdb);
			$bitmap->setbit($revision);
			$checkedbitmap->setbit($revision);	
		}
		if (isset($indexcheckedbitmap))
		{
			$frs = $indexcheckedbitmap->toarray();
			foreach($frs as $revision)
			{
				$checkedbitmap->setbit($revision);	
			}
		}
		$cached = $bitmap->countbits();
		echotime('indexed '. $cached);
	}
	
	else	
	{
		$cached = $bitmap->countbits();
		echotime('cached '. $cached);
	}
	
	$db->init();
	$maxlastrevision = $db->lastrevision;
	if ($db->indexedbitmap->length < $maxlastrevision) $db->RebuildIndexes($db->indexedbitmap->length); // fallback
	
	//echo " indexed ".$db->indexedbitmap->length;
	//echo " checked ".$checkedbitmap->length;
	//echo $checkedbitmap->getbit($checkedbitmap->length-1);
	// echotime('after init');
	
	
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
	{		
		$notinlabels = array('_paragraph', '_word');
		$notinvalues = array('_paragraph', '_word');
		
		$bigbloom = new swBitmap();
		
		$bms = swGetMonogramBitmapFromTerm('_checkedbitmap','');
		$bm = $bms[0];
		$notcheckd = $bm->notop();
		
		$bigbloom->init($bm->length,true);
		
		foreach($fields as $field=>$hors)
		{
			if ($hors || count($fields)==1) 
			{				
				if (! in_array($field,$notinlabels))
				{
					$grs = swGetMonogramBitmapFromTerm($field, '*'); 
					$gr = $grs[0];
					$bigbloom = $bigbloom->andop($gr);
				}
				
				if ($field == '_paragraph') $field = '_content';
				if ($field == '_word') $field = '_content';
				
				if (! in_array($field,$notinvalues) && is_array($hors))
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
								$grs = swGetMonogramBitmapFromTerm($field,$hand); 
								$gr = $grs[0];
								$gr->redim($bigbloom->length, true);
								$band = $band->andop($gr);
							}
						}
	
						
						$bor = $bor->orop($band);
						
					}
					$bigbloom = $bigbloom->andop($bor);	
				}
			}
		}
		
		$bigbloom = $bigbloom->orop($notcheckd);	
		
		$bigbloom->redim($tocheckbitmap->length,true);
		
		echotime($bigbloom->countbits().' '.$bigbloom->length);
		echotime($tocheckbitmap->countbits().' '.$tocheckbitmap->length);
		
		$tocheckbitmap = $tocheckbitmap->andop($bigbloom);
		echotime($tocheckbitmap->countbits().' '.$tocheckbitmap->length);

		
			
		$nottocheck = $bigbloom->notop();
		
		$checkedbitmap = $checkedbitmap->orop($nottocheck);
		
		echotime('monogram '.$tocheckbitmap->countbits().' of '.$tocheckbitmap->length);

	}
	

	
	$dur = 0; // check always at least 50 records
		
	if ($tocheckcount > 0 && $dur<=$swMaxOverallSearchTime)
	{
		
		
		if ((!$cached || $tocheckcount > 50) && ($namefilter || $namespacefilter))
		{
			
			$urldbpath = $db->pathbase.'indexes/urls.db';
			if (file_exists($urldbpath))
			$urldb = swDbaOpen($urldbpath, 'rdt', 'db4');
			if (!@$urldb)
			{
				echotime('urldb failed');
			}
			else
			{				
				
				$r0 = swDbaFirstKey($urldb);		
				
				do 
				{
					
					if (substr($r0,0,1) == ' ') continue; // url
					
					$n = $r0;
					
					
										
					if (!stristr($n,':')) $n= 'main:'.$n;
										
					if ($namespacefilter and $namespacefilter != '*')
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
							$line = swDbaFetch($r0,$urldb);
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
					
					if ($namefilter and $namefilter != '*')
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
							$line = swDbaFetch($r0,$urldb);
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
				
				} while ($r0 = swDbaNextKey($urldb));
				
			
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
						echotime('bloom -'.$field.'- '. $tocheckcount); 	
						
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
			echotime('bloom1 '.$tocheckcount); 	
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
			$hors = $fields['_paragraph'];
			
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
					$fieldlist['_content'][] = $record->content;
					$fieldlist['_length'][] = strlen($record->content);
					$fieldlist['_short'][] = substr($record->content,0,160);
				
					
					$fieldlist['_paragraph'] = explode(PHP_EOL, $record->content);
					
					
					$s = preg_replace("/[0123456789:\/.]/","-", $record->content);
					$fieldlist['_word'] = explode('-', swNameURL($s));
					$fieldlist['_word'] = array_values(array_filter($fieldlist['_word'], function ($var){return strlen($var)>=3;})); 
					
				
					//print_r($fieldlist['_word']);
					
					
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
					// print_r($fields);
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
					//echo $record->revision.' '.$maxcountall.'; ';
					if ($isindex)
					{
						$maxcount = $maxcountall;
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
								if (count($v) > 0)
									$fieldlist2[$fi][$key] = $v[count($v)-1];
							}
							
							if ($isindex)
							{
								if (count(array_unique($v))>1) {$offsetrelevant = true; }
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
								if ($hint=='*')
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
							if ($isindex)
							{
								if ($fieldlist2[$fi][$indexkey])
								{
									$fieldlist2[$fi]['_offset'] = $fi;
									$fieldlist2[$fi]['_revision'] = $revision;
									$rows[$revision.'-'.$fi] = swEscape($fieldlist2[$fi]);
								}
							}
							else
							
								$rows[$revision.'-'.$fi] = swEscape($fieldlist2[$fi]);
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
								if ($value) 
								{
									$linehascontent = true;
								}
							}
						}
						
						if ($linehascontent) 
						{
							if ($isindex)
							{
								$allrows[$primary] = $line; // delay offsetrelevant
							}
							else
							{
								swDbaReplace($primary,serialize($line),$bdb); // use less memory
							}
						}
					}
					$bitmap->setbit($k);
				}
				$checkedbitmap->setbit($k);
				
			}
			
			// if isindex and all _offset point to the same value, we will drop the _offset column for indexes
			if ($isindex)
			{
				if (!$offsetrelevant)
				{
					echotime('offsetnotrelevant');
					$allrows2 = array();
					foreach($allrows as $primary=>$line)
					{
						if (substr($primary,-2,2) === '-0')
						{
							unset($line['_offset']);
							$allrows2[$primary] = $line;
						}
					}
					$allrows = $allrows2;
					
					// print_r($allrows);
					
					
				}
				
				foreach($allrows as $primary=>$line) 
				{
					
					swDbaReplace($primary,serialize($line),$bdb);
				}
				
			}
	
			echotime('checked '.$checkedcount);
			echotime('length '.floor($checkedlength/1024/1024).' MB');
			echomem("filter");	
		}
	
	}
	
	if ($isindex)
	{
		
		if ($offsetrelevant)
		{
			if ($cacheoffsetrelevant)
			{
				// everything is ok
			}
			else
			{
				// we need to set offset for each which is really complicated as we do not have information on max, so we need to reset all old data. bad case
				echotime('lateoffsetrelevant');
				
				swDbaClose($bdb);
				unset($bdbfile);
				$bdb = swDbaOpen($bdbfile, 'c', 'db4');
				foreach($allrows as $primary=>$line) swDbaReplace($primary,serialize($line),$bdb);
				$bitmap == new swBitmap;
				$checkedbitmap == new swBitmap;
				
				foreach($allrows as $key=>$line)
				{
					$keys = explode('-',$key);
					$kr = $keys[0];
					
					$bitmap->setbit($kr);
					$checkedbitmap->setbit($kr);
				}
				
			}
		}
		else
		{
			unset($fields['_offset']);
			$header = array_keys($fields);
			$result = new swRelation($header,null,null);

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
	
	
	swDbaSync($bdb);
	
	$d = array();	
	
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
		$bdb = swDbaOpen($bdbfile, 'wdt', 'db4');
	else
	{
		$bdb = swDbaOpen($bdbfile, 'c', 'db4');
		swDbaReplace('_filter',$filter,$bdb);
	}
	if (!$bdb)
	{
		// try read only
		$bdb = swDbaOpen($bdbfile, 'rdt', 'db4');
		
				
		if (!$bdb)
			throw new swExpressionError('db failed '.md5($mdfilter),88);
			
		$bdbrwritable = false;
		echotime("bdb readonly");
	}

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
				$stack[] = $shortfile;
				$stack[] = $fields['file'];
				$hintfunction->run($stack);
				$foundfile = array_pop($stack);
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
			
			while($handle && ($line = fgets($handle, 4096)) !== false)
			{
				
				$values0 = swGetAllFields($line);
				foreach($values0 as $k=>$v)
				{
					$values[$k] = $v[0];
				}
				
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
							$stack[] = $values[$k];
							$stack[] = $v;
							$hintfunction->run($stack);
							$found = array_pop($stack) && $found;
						}
					}
				}
				
				if ($found)	$rows[] = $values1;			
			}
		}
		
		swDbaReplace($file,serialize($rows),$bdb);
	}
	
	echotime(str_replace($root,'',$file));
	
	swDbaSync($bdb);
	
	$result = new swRelation('');
	$key = swDbaFirstKey($bdb);
	
	$columns = array();
	
	while($key)
	{	
		if (substr($key,0,1)=='_') { $key = swDbaNextKey($bdb); continue;}
		$rows = @unserialize(swDbaFetch($key,$bdb));
		
		if (is_array($rows))
		foreach($rows as $d)
		{
			if (!empty($d))
			{
				// print_r($d); break;
				
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
	
	return $result;
}


function swRelationToTable($q)
{
	$lh = new swRelationLineHandler;
	$s = $lh->run($q.PHP_EOL.'print raw','','',false); 
	
	// '<div class="relation">'.
	// '</div>'
	
	//$s = substr($s,strlen('<div class="relation">'.PHP_EOL));
	//$s = substr($s,0,-strlen('</div>'));
	
	//echo PHP_EOL."swRelationToTable".PHP_EOL;
	//echo '('.$s.')';
	//echo PHP_EOL;

	$lines = explode(PHP_EOL,$s); // $lines

	$header = array_shift($lines);
	
	// print_r($header);
	
	$fields = explode("\t",$header);
	
	
	$result = array();
	$i = 0;
	foreach($lines as $line)
	{
		$linefields = explode("\t",$line);
		// print_r($linefields);
		foreach($fields as $field)
			$result[$i][$field] = array_shift($linefields);
		$i++;
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
update _name = "<br>[["._name."|"._displayname."]]<br>". _paragraph 
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
set bold = _singlequote._singlequote._singlequote
update _paragraph = regexreplacemod(_paragraph,s,bold.s.bold,"i")
end if
set l = i + 1
end if
set i = i + 1
end while

'.$print.'


echo " "';

$lh = new swRelationLineHandler;
$s = $lh->run($q);

return $s;


}


?>