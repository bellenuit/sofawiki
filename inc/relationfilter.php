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
			$dns = array_shift($dnf);
			if (! in_array($dns, $ns) && !$user->hasright('view',$url)) return array();
	}


	
	
	$wiki = new swWiki;
	$wiki->name = $url;
	$wiki->lookup();
	if (!$wiki->revision)
		throw new swRelationError('Import page does not exist.',87);
		
	$list = swGetAllFields($wiki->content);
	
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



function swRelationVirtual($url)
{
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
	
	if (!$searcheverywhere && stristr($url,':'))
		{
			$dnf =explode(':',$url);
			$dns = array_shift($dnf);
			if (! in_array($dns, $ns) && !$user->hasright('view',$url)) return array();
	}
	
	
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
	
	// parse query
	// currently, inline comma is not supported on hint.
	$pairs = explode(',',$filter);
	$fields = array(); 
	$getAllFields = false;
	$newpairs = array();
	foreach($pairs as $p)
	{
		
		$p = trim($p);
		$elems = explode(' ',$p);
		$f = array_shift($elems);
		$h = null;
		
		if (count($elems)>0)
		{
			$h = join(' ',$elems); 
			if (substr($h,0,1) != '"' && substr($h,-1,1) != '"')
			{
				$h = '"'.$globals[$h].'"';
			}
			elseif (substr($h,0,1) != '"')
				throw new swExpressionError('filter missing start quote '.$f,88);
			elseif (substr($h,-1,1) != '"')
				throw new swExpressionError('filter missing end quote '.$f,88);
			$h = substr($h,1,-1);
			if ($h=="") $h = '*';
		}
		if ($f == '*')	
			$getAllFields = true;
		else	
			$fields[$f] = $h;
		
		if ($h == null)
			$newpairs[] = $f;
		else
			$newpairs[] = $f.' "'.$h.'"';
			
		if ($f == '_name')
			$namefilter = swNameURL($h);
		
	}
	// print_r($fields);
	$filter = join(', ',$newpairs); // needed values for cache.
	echotime('filter '.$filter);
	
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

	
	
	$result = new swRelation($header,null,null);

	//return $result;
	
	// find already searched revisions
	$mdfilter = $filter;
	$mdfilter = urlencode($mdfilter);
	$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
	$cachefile = $cachefilebase.'.txt';
	
	
	if ($refresh)
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
	
	foreach($goodrevisions as $k=>$v)
	{
		$kr = substr($k,0,strpos($k,'-')); 
		if($indexedbitmap->getbit($kr) && !$currentbitmap->getbit($kr))
		{
			$bitmap->unsetbit($kr);
			unset($goodrevisions[$k]);
			$cachechanged = true; 
			$checkedcount++;
		}
		if($deletedbitmap->getbit($kr))
		{
			$bitmap->unsetbit($kr);
			unset($goodrevisions[$k]);
			$cachechanged = true; 
			$checkedcount++;
		}
		if (!$indexedbitmap->getbit($kr))
		{
			$db->UpdateIndexes($kr); // fallback
		}
	}

	
	
	$nowtime = microtime(true);	
	$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
	if ($dur>$swMaxOverallSearchTime) {
		
		echotime('overtime overall');	
		$swOvertime = true;
	}	
		
	if (($tocheckcount > 0 || $cachechanged) && $dur<=$swMaxOverallSearchTime)
	{
		// apply namefilter
		
		
		if (@$namefilter && $filter != '_name, _revision')
		{
			$namerelation = swRelationFilter('_name, _revision', $globals);
			$tuples = $namerelation->tuples;
			
			foreach($tuples as $t)
			{
				$f = $t->fields();
				if (stripos(swNameURL($f['_name']),$namefilter) === false) 
				{
					$tocheck->unsetbit($f['_revision']);
				
				}
			}			
		}
		
		
		
		// we create a superbitmap
		// fields with string must be present
		// hints must be present
		
		$bloomlist = array();
		foreach($fields as $f=>$h)
		{
			if (substr($f,0,1)!='_') // _ fields are in header or implicit
				$bloomlist[] = '--'.swNameURL($f).'--'; // -- represents [[ or :: or ]]
			if (!empty($h) && !in_array($f,array('_revision','_status','_user','_timestamp','_timestamp','_name','_namespace','_displayname')))
			{
				// split words
				$hlist = explode(' ',$h);
				//print_r($hlist);
				foreach($hlist as $elem)
					$bloomlist[] = swNameURL($elem);
			}
		}
		//print_r($bloomlist);
		foreach($bloomlist as $v)
		{
			if (strlen(swNameURL($v))<3) continue; // bloom needs at least3 characters
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

		// print_r($fields);
			
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
					$swOvertime = true;
					break;
				}
				$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
				if ($dur>$swMaxOverallSearchTime) 
				{
					echotime('overtime overall '.$checkedcount.' / '.$tocheckcount);
					$overtime = true;
					$swOvertime=true;
					break;
				}
				$record = new swWiki;
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
							// echo $key.$hint;
							if (!$found) continue;
							if ($hint=='')
							{
								// pass
							}
							else
							{
								if (!array_key_exists($key,$fieldlist2[$fi]))
								{
									$found = false; 
								}
								elseif ($hint != "*")
								{
									if(!isset($fieldlist2[$fi][$key])) $found = false;
									else 
									{
										$hlist = explode(' ',$hint);
										// print_r($hlist);
										foreach($hlist as $elem)
										{
											//echo "<p>".$elem.' '.$fieldlist2[$fi][$key];
											if (stripos(swNameURL($fieldlist2[$fi][$key]),swNameURL($elem)) === false)
												$found = false; 
										}
										
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
				$header['mode'] = 'relation';
				$header['overtime'] = $overtime ;
				$header['chunks'] = serialize($chunks);
				$header['bitmap'] = serialize($bitmap);
				$header['checkedbitmap'] = serialize($checkedbitmap);
				$header['bitmapcount'] = $bitmap->countbits();
				$header['checkedbitmapcount'] = $checkedbitmap->countbits();
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
		$d = unserialize($v);
		
		$dn = @$d['_url'];
		
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


function swRelationSearch($term, $start=1, $limit=25, $template="")

{

	
global $lang;
$previous = ' <nowiki><a href=\'index.php?action=search&start='.($start-$limit).'&query='.$term.'\'>'.swSystemMessage('previous',$lang).'</a></nowiki>';
$next = ' <nowiki><a href=\'index.php?action=search&start='.($start+$limit).'&query='.$term.'\'>'.swSystemMessage('next',$lang).'</a></nowiki>';	
$results = swSystemMessage('results',$lang);
$results1 = swSystemMessage('result',$lang);

if ($template && $template != 1)
	$print = '
project _name, _paragraph
template "'.$template.'"';
else
	$print = '
if length(trim(_paragraph)) > 0
	update _name = "* [["._name."]]<br>"
else
	update _name = "* [["._name."]]<br>"
end if
project _name, _paragraph	
print raw';


global $swSearchNamespaces;
$namespace = 'main|'.join('|',array_filter($swSearchNamespaces)); // filter removes empty values
if (trim($namespace)=='main|') $namespace = "main";
if (stristr($namespace,'*')) $namespace = '.*';
$namespace = strtolower($namespace);
	
$singlequote = "'";
$q = '
set v = "'.swSimpleSanitize($term).'"

// find title in name
filter _namespace, _name v
extend _paragraph_count = 100
project _name, _paragraph_count, _namespace

// find title in text
filter _namespace, _name, _paragraph v
project _name, _paragraph count, _namespace
union

// namespaces
select _namespace regex "'.$namespace.'"

// if there are sublanguage pages, they should add to the score, but only the main page should be linked
extend _nameint = regexreplace(_name,"\/\w\w","")

// score
project _nameint, _paragraph_count sum
rename _nameint _name, _paragraph_count_sum rating
order rating 9

// add counter
dup
project _name count
set nc = _name_count
set start = '.$start.'
set limit = '.$limit.'
set ende = min(start+limit-1,nc)
if nc > 1 
set ncs = start. " - " . ende . " / ". nc 
else
set ncs = nc . " '.$results1.'"
end if

set other = 0

if '.$start.' > 1 
set ncs = ncs . "'.$previous.'"
set other = 1
end if

if '.($start+$limit-1).' < nc 
set ncs = ncs . "'.$next.'"
set other = 1
end if

echo ncs

pop

// show results with interesting paragraph
limit '.$start.' '.$limit.'

filter _namespace "main", _name, _paragraph v
extend row = rownumber
project inline _name, row min
select row = row_min
project _name, _paragraph 

join left

// remove wiki styles
update _paragraph = trim(nowiki(_paragraph))

// create a query to be split
set v = v ." "
set c = length(v)
set i = 1
set l = 0

// set query in paragraph bold
while i < c
if substr(v,i,1) == " "
if i > l
set s = substr(v,l,i-l)
update _paragraph = regexreplacemod(_paragraph,s,"'.$singlequote.$singlequote.$singlequote .'".s."'.$singlequote.$singlequote.$singlequote .'","i")
end if
set l = i + 1
end if
set i = i + 1
end while

'.$print.'


echo " "
echo ncs

// print

echo " "';




$lh = new swRelationLineHandler;

return $lh->run($q);


}


?>