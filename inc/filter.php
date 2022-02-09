<?php
	
/**
 *	Provides functions for the query function.
 *
 *  <b>Starting Sofawiki 3.0.0, swQuery and swFilter are depreciated. Use swRelation and swRelationFilter.</b>
 *
 */

if (!defined('SOFAWIKI')) die('invalid acces');


/**
  * Returns a table based on the fields of the pages.
  *
  * @param $filter 
  * SELECT fieldlist (FROM namespace)? (WHERE field operator string|WHERE field operator expression)?
  * SELECT fieldlist FROM VIRTUAL pagename (WHERE field operator string|WHERE field dollaroperator expression)?
  * If a swVirtualLinkHook() function is present, the filter can access external data
  * @param $namespace is also limited by the namespaces the user has access to or that are transcludable
  * @param $filter always "filter"
  * @param $flags "refresh" or "internal"
  * @param $checkhint provide a limiting bitmap

  */

function swFilter($filter,$namespace,$mode='query',$flags='',$checkhint = NULL)
{
	
	global $swIndexError;
	global $swMaxSearchTime;
	global $swMaxOverallSearchTime;
	global $swStartTime;
	global $swOvertime;
	
	$verbose = 0;
	if (isset($_REQUEST['verbose'])) $verbose = 1;
	
	global $swRoot;
	global $db;
	$lastfoundrevision = 0;
	$goodrevisions = array();
	$bitmap = new swBitmap;
	$checkedbitmap = new swBitmap;
	$operator = '';
	$fields = array();
	$field = '';
	$term = '';
	$namefilter = '';
	$namefilter0 = ''; 
	$virtualmode = false;
	
	if ($swIndexError)
	{
		$swOvertime = true;
		return $goodrevisions;
	}
	
	if ($mode != 'query')
	{
		echotime('filter mode depreciated '.$mode);
		swNotify('filter','filtermodedepreciated','filter '.$mode,$filter);
	}
	// parse select string SELECT fields WHERE field operator term

	if (substr($filter,0,6) == 'SELECT')
	{
		$filter2 = substr($filter,7);
		
		if ($p = strpos($filter2,' FROM '))
		{
			//namefilter from query is not the same that the namespace that depends on 
			//the user rights
			$p+= strlen(' FROM ');
			if (!$p2 = strpos($filter2,' WHERE '))
				$p2 = strlen($filter2);
			$namefilter = $namefilter0 = substr($filter2,$p,$p2-$p);
			
			
			if (stristr($namefilter, 'VIRTUAL '))
			{
				$namefilter = str_replace('VIRTUAL ','',$namefilter);
				$virtualmode = true;
			}
			
			$filter2 = str_replace(' FROM '.$namefilter0,'',$filter2);
			
			$namefilter = trim($namefilter);
			$namefilter = swNameURL($namefilter);
			
			// the filter has to use now the more specific of the namespace and the namefilter
			
			
			
			// namefilter for main namespace only
			if ($namefilter == 'main:')
			{	
				$namespace = '';
				$namefilter = '';
			}
									
			// if the namespace is all, then the new namespace is the namefilter
			elseif (stristr($namespace,'*'))
				$namespace = $namefilter;
			
			// if the namefilter does not contain : then the new namespace is common namespace and all namespaces starting with namefilter
			if(!stristr($namefilter,':'))
			{
				$spaces = explode('|',$namespace);
				$newspaces = array();
				$newspaces[$namefilter] = $namefilter;
				foreach($spaces as $sp)
				{
					$sp = swNameURL($sp);
					$test = substr($sp,0,strlen($namefilter));
					if ($test == $namefilter)
						$newspaces[$sp] = $sp;
				}
				//echotime(print_r($newspaces,true));
				$namespace = join('|',$newspaces);
			}
			// if the namefilter contains : and is valid in the namespace then the new namespace is the namefilter
			else
			{
				$spaces = explode('|',$namespace);
				$newspaces = array();
				$ns = substr($namefilter,0,strpos($namefilter.':',':')); 
				$found = FALSE;
				foreach($spaces as $sp)
				{
					$sp = swNameURL($sp);
					if (stristr($ns,$sp) || stristr($namefilter,$sp)) $found = TRUE;
				}
				if ($found)
				{
					$namespace = $namefilter;
				}
				else
				{
					// if the namefilter contains : and is not valid, then an empty result is returned
					return(array('_error'=>'invalid name '.$namefilter));
				}
			}
			
			//echo $namespace;	
						
			
		}
		
		// implicit WHERE 
		if (!stristr($filter2,' WHERE')) $filter2 .= ' WHERE';
		
		if ($p = strpos($filter2,' WHERE'))
		{
			$fields = substr($filter2,0,$p);
			$fs = explode(',',$fields);
			$fields = array();
			foreach($fs as $f)	{ $fields[]=trim($f); }
			$fields = array_unique($fields);
			
			$query = substr($filter2,$p+strlen(' WHERE')+1);
			$words = explode(' ',$query);
			$wordlist = array();
			foreach($words as $w) { if ($w != '') $wordlist[] = $w; }
			if (count($wordlist)>0)
			{
				$field = $wordlist[0]; 
				if (count($wordlist)>1)
				{
					$operator = $wordlist[1];
					unset($wordlist[1]);
				}
				else
					$operator = '*';
				unset($wordlist[0]);
				if (count($wordlist)>0)
					$term = trim(join(' ',$wordlist));
				else
					$term = '';
			}
			else
			{
				// find all
				$field = '_name';
				$operator = '*';
				$term = '';
			}
		}
		else
		{
			// find all
			$field = '_name';
			$operator = '*';
			$term = '';
		}
	}
	
	echotime('filter '.$filter);
	
	
	$comparefields = false;
	if (substr($operator,0,1) == '$')
	{	
		$operator = substr($operator,1);
		$comparefields = true;
	}
	
	
	if (substr($filter,0,6) == 'FIELDS')
	{
		$fields = array('fields');
	}
	
	if ($operator == '~~'|| $operator == '*~'|| $operator == '~*'|| $operator == '*~*') 
		$term = swNameURL($term);
		
	if ($operator == 'r=' || $operator == 'IN')
	{
		$delimiter = substr($term,0,1);
		$pos = strpos($term,$delimiter,1);
		$pos2 = strpos($term,' HINT ',$pos);
		if ($pos2>0)
		{
			$hint = substr($term, $pos2 + strlen(' HINT '));
			$term = substr($term,0,$pos2);
		}
		else
			$hint = '';
	}

	// VIRTUAL
	if ($mode=='query' && $virtualmode)
	{
		echotime('virtual');
		$urlname = $namefilter;
		if (stristr($urlname,':')) // page has namespace
		{
			$spaces = explode('|',$namespace);
			$newspaces = array();
			$ns = substr($urlname,0,strpos($urlname.':',':')); 
			$found = FALSE; 
			foreach($spaces as $sp)
			{
				$sp = swNameURL($sp);
				if (stristr($ns,$sp) || stristr($urlname,$sp)) $found = TRUE;
			}
			if (!$found)
			{
				// echo "!"; print_r($spaces); echo $ns;
				return(array('_error'=>'invalid name '.$urlname.' ('.$namespace.')'));
			}
	    }
	    
	    $w = new swWiki;
		$w->name = $urlname;
	
	    if (function_exists('swVirtualLinkHook') && $hooklink = swVirtualLinkHook($urlname, $fields, $query)) 
	    {
  			
  			//echo "<p>l ".$hooklink.".";
  			if (!$s = swFileGetContents($hooklink))
  				return(array('_error'=>'invalid url '.$hooklink));
  			//echo "<p>s ".print_r($s).".";
  			
  			$w->content = $s;
		}
		else
		{
			$w->lookup();
			if ($w->revision == 0) return(array('_error'=>'unknown name '.$urlname));
		}
		
		$w->parsedContent = $w->content;
		$fp = new swTidyParser;
		
		
		$fp->dowork($w); 
		$fp = new swTemplateParser;
		$fp->dowork($w);
		
		
		
		$s = $w->parsedContent; //field output has nowiki tags
		$s = str_replace('<nowiki>','',$s);
		$s = str_replace('</nowiki>','',$s);
		
		$s = swUnescape($s);  
		
		$list = swGetAllFields($s);
		$list['_name'] = $w->name;
		
		
		
		$row = swQueryFieldlistCompare($w->revision,$list,$fields,$field,$operator,$term,$comparefields);
		
		//print_r($row);
		
		return $row;
	}
	
	
	
	// find already searched revisions
	$mdfilter = $filter;
	$mdfilter .= $namespace;
	$mdfilter .= $mode;
	$mdfilter = urlencode($mdfilter);
	$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
	$cachefile = $cachefilebase.'.txt';
	
	global $swDebugRefresh;
	if ($swDebugRefresh || stristr($flags,'refresh'))
		{ echotime('refresh'); if (file_exists($cachefile)) unlink($cachefile);}
	
	//echomem('before');
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
				/*
				foreach($goodrevisionchunk as $k=>$line)
				{
					$goodrevisionchunk[$k] = serialize($line);
				}
				*/
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
				//echomem('merged');
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
	// update changes for revisions that are not valid any more
	if ($filter != "FIELDS")
	{
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
	}
	$nowtime = microtime(true);	
	$dur = sprintf("%04d",($nowtime-$swStartTime)*1000);
	if ($dur>$swMaxOverallSearchTime) 
	{
		$swOvertime = true;
		echotime('overtime overall');
	}
		
		
	if (($tocheckcount > 0 || $cachechanged) && $dur<=$swMaxOverallSearchTime)
	{
		
				
		// if there is an existing search on a substring, we can exclude all urls with no result
		// a cron tab will create searches on all possible strings with 3 characters
		// we therefore test again all substrings with 3 characters

			if ($tocheckcount > 16 ) // small searches reduction is not interesting
			{
	
				
					// restrict on namefilter
					if ($namefilter)
					{
							
							
							
								echotime('namefilter '.$tocheck->countbits());
								$filter2 = 'SELECT _revision WHERE _name ~* '.$namefilter;
	
								$filtercache = swGetFilterCacheHeader($filter2,'*','query'); 
								
								$bm = unserialize($filtercache['bitmap']);
								$chbm = unserialize($filtercache['checkedbitmap']);
								
								
								if (is_a($chbm,'swBitmap') && is_a($bm,'swBitmap'))
								{
									for ($k=1;$k<=$maxlastrevision;$k++)
									{
										if ($tocheck->getbit($k) && $chbm->getbit($k) && !$bm->getbit($k) )
										{
											$tocheck->unsetbit($k);$checkedbitmap->setbit($k);$checkedcount++;
										}								
									}
								}
								$tocheckcount = $tocheck->countbits();
								echotime('- namefilter '.$tocheckcount);
							
					}


				// if it is query search, we can restrict to all revisions that have the field
				if ($tocheckcount > 16 && isset($field) && $mode == 'query' && substr($field,0,1) != '_')
				{
							
						$filter2 = 'SELECT _revision, '.$field.' WHERE '.$field.' *';
						if ($filter2 != $filter) // no recursion
						{
							$filtercache = swGetFilterCacheHeader($filter2,'*','query'); 
								
							$bm = unserialize($filtercache['bitmap']);
							$chbm = unserialize($filtercache['checkedbitmap']);
								
								
							if (is_a($chbm,'swBitmap') && is_a($bm,'swBitmap'))
							{
								for ($k=1;$k<=$maxlastrevision;$k++)
									{
											if ($tocheck->getbit($k) && $chbm->getbit($k) && !$bm->getbit($k) )
											{
												$tocheck->unsetbit($k);$checkedbitmap->setbit($k);$checkedcount++;
											}								
									}
							}
							$tocheckcount = $tocheck->countbits();
							echotime('- * '.$tocheckcount);
						}
						
					
				}	
				
				// search only in records which have the field
				// and use also the 3letter-trick on the field
				if ($tocheckcount > 16 && isset($field) && strlen($field)>=3 && $mode == 'query' && substr($field,0,1) != '_'  )
				{
						
					$field2 = swNameURL('[['.$field.'::');
					
					$gr = swGetBloomBitmapFromTerm($field2);
					$gr->redim($tocheck->length, true);
					$tocheck = $tocheck->andop($gr);
					$notgr = $gr->notop();
					$checkedbitmap = $checkedbitmap->orop($notgr);
					$tocheckcount = $tocheck->countbits();
						
						
				}
				
				// use the 3letter trick on the term (trigram)
				if ($tocheckcount > 16 && isset($term) && !$comparefields && strlen($term)>=3 && ( $operator == '==' || $operator == '*=' || $operator == '=*' 
				|| $operator == '*=*' 
				|| $operator == '~~'|| $operator == '*~'|| $operator == '~*'|| $operator == '*~*'))
				{
					
					if (strlen($term)>=3)
					{
						$term2 = swNameURL($term);
						$gr = swGetBloomBitmapFromTerm($term2);
						$gr->redim($tocheck->length, true);
						$tocheck = $tocheck->andop($gr);
						$notgr = $gr->notop();
						$checkedbitmap = $checkedbitmap->orop($notgr);
						$tocheckcount = $tocheck->countbits();
						
					}
				
				}
				
				// use hint on r=
				if ($tocheckcount > 16 && isset($term) && !$comparefields && strlen($term)>=3 && $operator == 'r=' && $hint != '')
				{
						$term2 = swNameURL($hint);
						
						$gr = swGetBloomBitmapFromTerm($term2);
						$gr->redim($tocheck->length, true);
						$tocheck = $tocheck->andop($gr);
						$notgr = $gr->notop();
						$checkedbitmap = $checkedbitmap->orop($notgr);
						$tocheckcount = $tocheck->countbits();
					
				}
				
			
			}
			
			$starttime = microtime(true);
			if ($swMaxSearchTime<500) $swMaxSearchTime = 500;
			if ($swMaxOverallSearchTime<2500) $swMaxOverallSearchTime = 2500;
			
			$overtime = false;
			
			
			
			if ($checkhint != NULL)
			{
				$checkhint->redim($tocheck->length,0); 
				$tocheck = $tocheck->andop($checkhint);
			}
			
			
			
			$toc = $tocheck->countbits();
			$checkedcount += $tocheckcount - $toc;
			if ($toc > 0 ) echotime('loop '.$toc);
			
			
			
			global $swMemoryLimit;

			//$handle = tmpfile();
			if ($toc>0) {
			for ($k=$maxlastrevision;$k>=1;$k--)
			{
				
				if (memory_get_usage()>$swMemoryLimit) 
				{
					echotime('overmemory '.memory_get_usage());
					$overtime = true;
					$swOvertime = true;
					break;
				}
				
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
					$swOvertime=true;
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
				$record = new swRecord;
				$record->revision = $k;
				$record->lookup();
				
				if ($record->error == '') $checkedbitmap->setbit($k); else continue;
				$urlname = swNameURL($record->name);
								
				// apply namefilter
				
				if ($namefilter != '' && substr($urlname,0,strlen($namefilter)) != $namefilter)
					{ $bitmap->unsetbit($k); continue; }
				
				
				// apply namespace
				
				if (stristr($urlname,':') && $namespace != '*') // page has namespace
				{
					$spaces = explode('|',$namespace);
					$newspaces = array();
					$ns = substr($urlname,0,strpos($urlname.':',':')); 
					$found = FALSE;
					foreach($spaces as $sp)
					{
						$sp = swNameURL($sp);
						if ($sp != '')
						{
								if (stristr($ns,$sp) || stristr($urlname,$sp)) $found = TRUE;
						}
					}
					if (!$found)
					{
						{ $bitmap->unsetbit($k); continue; }
					}
				}
				
				
				$content = $record->name.' '.$record->content;
				$row=array();
				
				$fieldlist = $record->internalfields;

				if ($filter == 'FIELDS')
				{
					$keys =array_keys($fieldlist);
					if (count($keys)>0)
					{
						$i = 0;
						foreach($keys as $key)
						{
							if (substr($key,0,1) != '_')
								$goodrevisionchunknew[$key] = $goodrevisions[$key] = serialize(array('_field'=>$key));
						}
						$touched = true;
					}
				}
				elseif (substr($filter,0,6) == 'SELECT')
				{
					
					
					$fieldlist['_revision'][] = $record->revision;
					$fieldlist['_status'][] = $record->status;
					$fieldlist['_name'][] = $record->name;
					$fieldlist['_url'][] = swNameURL($record->name);
					$fieldlist['_user'][] = $record->user;
					$fieldlist['_timestamp'][] = $record->timestamp;
					$fieldlist['_content'][] = $record->content;
					$fieldlist['_*'][] = $record->name."\n".$record->content;
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
					unset($key);
					unset($keys);
					
					if (!isset($fieldlist[$field])) { $bitmap->unsetbit($k); continue; }
					
					//echotime($operator.' '.$term);
					
					$row = swQueryFieldlistCompare($k,$fieldlist,$fields,$field,$operator,$term,$comparefields);
					if (isset($row['_error'])) return $row;
					
				}
				//print_r($row); 
				if (count($row)>0)
				{
					foreach($row as $primary=>$line)
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
			
				swSemaphoreSignal($cachefile);
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
				$header['mode'] = $mode;
				$header['namespace'] = $namespace;
				$header['overtime'] = $overtime ;
				$header['chunks'] = serialize($chunks);
				$header['bitmap'] = serialize($bitmap);
				$header['checkedbitmap'] = serialize($checkedbitmap);
				$row = array('_header'=>$header);
				swWriteRow($handle2, $row );
				fclose($handle2);
				swSemaphoreRelease($cachefile);
			}			
			
			echotime('good '.count($goodrevisions));
			echomem("filter");	
			} // if toc>0
	
	}
	
		
		//we sort to eliminate duplicates, but keep keys
		uasort($goodrevisions, 'swMultifieldSort');
		//echotime('sorted');
		
		$lastjoin='';
		foreach($goodrevisions as $k=>$v)
		{
			if ($v == $lastjoin)
				unset($goodrevisions[$k]);
			$lastjoin = $v;
		}
		//echotime('duplicates');

		$set = array();
		$i=0;
		foreach ($goodrevisions as $v) 
		{
		    unset($goodrevisions[$k]);
			$set[] = unserialize($v); 
		    $i++;
		}
		unset($goodrevisions);
		$goodrevisions = $set;
		unset($set);
		
		return $goodrevisions;
	
}

/**
  * Returns a table from a entire query.
  *
  * @param $args query lines
  * 
  * NB the actual code is in the swQueryFunction class
  */

function swQuery($args)
{
	$query = new swQueryFunction;
	$query->searcheverywhere = true;
	$query->outputraw = true;
	$table = $query->dowork($args);	
	if (is_array($table))
		// $tuple = array_pop($table); //why??
		$tuple = $table;
	else
		$tuple = array();
	return $tuple;
}

/**
  * Compares a tuple against an operation and a term and returns a tuple (the WHERE part in SELECT)
  *
  * @param $revision revision number for the key
  * @param $fieldlist all key-value pairs from the tuple
  * @param $fields the fields to include in the tuple
  * @param $field the field to check
  * @param $operator the comparison operator (==, <, >...)
  * @param term the value to check against
  * @param comparefields
  */


function swQueryFieldlistCompare($revision, $fieldlist,$fields,$field,$operator,$term,$comparefields)
{
	// 	
	$row = array();
	
	// normalize array, to a table, but using only used fields and field
	$maxcount = @count($fieldlist[$field]);
	foreach($fields as $v)
	{
		if (isset($fieldlist[$v]))
			$maxcount = max($maxcount,count($fieldlist[$v]));
	}	
	$fieldlist2 = array();
	foreach($fieldlist as $key=>$v)
	{
		for($fi=0;$fi<@count($v);$fi++)
		{
			$fieldlist2[$fi][$key] = $v[$fi];
		}
		if (@count($v)>0)
		for ($fi=@count($v);$fi<$maxcount;$fi++)
		{
			$fieldlist2[$fi][$key] = $v[@count($v)-1];
		}
	}
	
	// compare
	for ($fi=0;$fi<$maxcount;$fi++)
	{
		$onefieldvalue = @$fieldlist2[$fi][$field];
		if ($comparefields)
		{
			$term2 = swQueryTupleExpression($fieldlist2[$fi], $term);
			if (is_array($term2))
			{
				return $term2; //error
			}
			else
				$found = swFilterCompare($operator,array($onefieldvalue),$term2);
		}
		else
			$found = swFilterCompare($operator,array($onefieldvalue),$term);
			
		if ($found)
		{
						
			foreach ($fields as $f)
			{
				// we cannot return an error here, because the data might be an old revision with other data scheme
				if ($f == '_rating')
				{
						$content = $fieldlist[$field];
						$content = array_shift($content);
						$contenturl = swNameURL($content);
						$termurl = swNameURL($term);
						if ($field == '_*')
						{	
							$titleurl = substr($content,0,strpos($content,"\n"));
						}
						else
							$titleurl = '';
						
						//row value will be simple rating algorithm: 
						//counts the number of occurences and the position
						$rating = 0;
						$len0 = strlen($titleurl);
						$k = 1;
						
						//if in title, means before \n, it counts 10 times
						if ($titleurl = stristr($titleurl,$termurl))
						{
							$rating += strlen($titleurl) / $len0 / $k * 10 ;
							$titleurl = substr($titleurl,1);
							$k++;
						}
						
						$len0 = strlen($contenturl);
						$k = 1;
						
						if ($contenturl = stristr($contenturl,$termurl))
						{
							$rating += strlen($contenturl) / $len0 / $k ;
							$contenturl = substr($contenturl,1);
							$k++;
						}
						$rating = min(99.999,$rating);
						$row[$revision.'-'.$fi][$f] = sprintf('%06.3f',$rating);
				}
				else
					$row[$revision.'-'.$fi][$f] = @$fieldlist2[$fi][$f];
			}
		}
	}

	return $row;

}

/**
  * Compares as list of values against a term and returns if one of them matches
  *
  * @param $operator the comparison operator (==, <, >...)
  * @param $values the list of values
  * @param term the value to check against
  */


function swFilterCompare($operator,$values,$term)
{	
	if (!is_array($values))  // single value
		$values = array($values);
	
	$valuelist = array();
	
	if ($operator == '*')
	{
		if (count($values)>0) return true;
		return false;
	}
	
	if (substr($operator,0,1)=='!')
	{
		$op = substr($operator,1);
		$result = swFilterCompare($op,$values,$term);
		return !$result;
	}	
	
	foreach($values as $v)
	{
		$valuelist[] = swUnescape($v);
	}
	
	
	$term = swUnescape($term);
		
	switch ($operator)
	{
		
		case '=': foreach($valuelist as $v)
				   {	if (floatval($v)==floatval($term)) return true; } break;
		case '<': foreach($valuelist as $v)
				   {	if (floatval($v)<floatval($term)) return true; } break;
		case '>': foreach($valuelist as $v)
				   {	if (floatval($v)>floatval($term)) return true; } break;
		case '<=': foreach($valuelist as $v)
				   {	if (floatval($v)<=floatval($term)) return true; } break;
		case '>=': foreach($valuelist as $v)
				   {	if (floatval($v)>=floatval($term)) return true; } break;
		case '==': foreach($valuelist as $v)
				   {	if ($v==$term) return true; } break;
		case '=*': foreach($valuelist as $v)
				   {	if (substr($v,0,strlen($term))==$term) return true; } break;
		case '*=': foreach($valuelist as $v)
				   {	if (substr($v,-strlen($term))==$term) return true; } break;
		case '*=*': 
						foreach($valuelist as $v)
				   { if (stripos($v,$term) !== FALSE) return true; } 
				   break;
		case '<<': foreach($valuelist as $v)
				   {	if ($v<$term) return true; } break;
		case '>>': foreach($valuelist as $v)
				   {	if ($v>$term) { return true; } } break;
		case '<<=': foreach($valuelist as $v)
				   {	if ($v<=$term) return true; } break;
		case '>>=': foreach($valuelist as $v)
				   {	if ($v>=$term) return true; } break;
		case '~~': foreach($valuelist as $v)
				   {	$v = swNameURL($v);
						if ($v==$term) return true; } break;
		case '~*': foreach($valuelist as $v)
				   {	$v = swNameURL($v);
						if (substr($v,0,strlen($term))==$term) return true; } break;
		case '*~': foreach($valuelist as $v)
				   {	$v = swNameURL($v);
						if (substr($v,-strlen($term))==$term) return true; } break;
		case '*~*': foreach($valuelist as $v)
				   {    $v = swNameURL($v);
						if (stripos($v,$term) !== FALSE) return true; } break;						
		case '0': foreach($valuelist as $v)
				   {	if (trim($v) == '') return true; } break;
		case 'r=': foreach($valuelist as $v)
					{ if (preg_match($term, $v, $matches)) return true; }
					break;
		case 'IN':  $termlist = explode('::',$term);
					//print_r($termlist);
					foreach($valuelist as $v)
				   {	
					    foreach($termlist as $t)
					   		if (trim($v)==trim($t)) return true; } 
					break;
		default:   return false;
	}
	return false;
}


/**
  * Gets header of filter cache from file in /site/queries
  *
  * @param $filter
  * @param $namespace
  * @param $mode
  */


function swGetFilterCacheHeader($filter,$namespace,$mode='query')
{
	global $swRoot;
	
	// find already searched revisions
	$mdfilter = $filter;
	$mdfilter .= $namespace;
	$mdfilter .= $mode;
	$mdfilter = urlencode($mdfilter);
	$cachefilebase = $swRoot.'/site/queries/'.md5($mdfilter);
	$cachefile = $cachefilebase.'.txt';
	
	if (rand(0,100) < 1 || !file_exists($cachefile)) swFilter($filter,$namespace,$mode);
		
	if (file_exists($cachefile)) 
	{
		if ($handle = fopen($cachefile, 'r'))
		{
			while ($arr = swReadField($handle))
			{
				if (@$arr['_primary'] == '_header')
				{
					fclose($handle);
					return $arr;
				}
			}
			fclose($handle);
		}
	 }
}

/**
  * Evaluates a swQuery RPN expression based on values of a tuple and returns result as a string
  *
  * @param $row tuple
  * @param $term RPN expression
  * Allowed operators are: + - / * . (concat) :: (concat ::) ABS SIGN COPY POP SWAP
  */


function swQueryTupleExpression($row, $term) 
{
	// on success, returns a string
	// on error, returns an array
	// lexical analysis, to preserve quoted strings.
	
	$quotes = explode('"',$term);
	$arguments = array();
	$even = true;
	for($i=0;$i<count($quotes);$i++)
	{
		if ($even)
		{
			if ($quotes[$i])
			{
				$args = explode(" ",$quotes[$i]);
				foreach($args as $arg)
				{
					if ($arg != '')
						$arguments[]=$arg;
				}
			}
			elseif (count($arguments) && $i<count($quotes)-1)	// double quote
			{
				$arg = array_pop($arguments);
				$even = !$even;
				$i++;
				$arg.='"'.$quotes[$i];
				$arguments[] = $arg;
			}
		}
		else
		{
			$arguments[] = '"'.$quotes[$i];
		}
		$even = !$even;
	}
	$calcstack = array();
	foreach($arguments as $arg)
	{
		if (array_key_exists($arg,$row))
		{
			array_push($calcstack,$row[$arg]);
		}
		else
		{
			switch ($arg)
			{
				case '': break;
				case '+' : 
					  if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty error +');	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b+$a); break;
				case '-' : 
					  if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty error -');	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b-$a); break;
				case '*' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty *'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b*$a); break;
				case '/' :
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty /'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack);
					  if ($a == 0)  { return array('_error'=>'Expression division by zero'); 	}
					  array_push($calcstack,$b/$a); break;
				case '.' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty .'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b.$a); break;
				case '::' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty ::'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b.'::'.$a); break;
				case 'SUBSTR' : 
				      if (count($calcstack)<3) { return array('_error'=>'Expression Stack empty SUBSTR'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  $s= array_pop($calcstack); 
					  array_push($calcstack,substr($s,$b,$a)); break;
				case 'STRLEN' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty STRLEN'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,strlen($a)); break;
				case 'REPLACE' : 
				      if (count($calcstack)<3) { return array('_error'=>'Expression Stack empty REPLACE'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  $s= array_pop($calcstack); 
					  array_push($calcstack,str_replace($b,$a,$s)); break;
				case 'REGEX' : 
				      if (count($calcstack)<3) { return array('_error'=>'Expression Stack empty REGEX'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  $s= array_pop($calcstack); 
					  array_push($calcstack,preg_replace($b,$a,$s)); break;
				case 'TRIM' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty TRIM'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,trim($a)); break;
				case 'URLIFY' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty URLIFY'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,swNameURL($a)); break;
				case 'ABS' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty ABS'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,abs($a)); break;
				case 'SQRT' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty SQRT'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,sqrt(floatval($a))); break;
				case 'SIGN' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty SIGN');	}
				      $a = array_pop($calcstack); 
					  if ($a>0) $b=1; elseif($a<0) $b=-1; else $b=0;
					  array_push($calcstack,$b); break;
				case 'COPY' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty COPY'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,$a);
					  array_push($calcstack,$a);break;
				case 'POP' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty POP'); 	}
					  $a = array_pop($calcstack);  break;
				case 'RAND' : 
				      $a = rand(0,100);
					  array_push($calcstack,$a);break;
				case 'SWAP' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty SWAP'); 	}
					  $a = array_pop($calcstack); $b = array_pop($calcstack); 
					  array_push($calcstack,$a); array_push($calcstack,$b); break;
				default: 
				
				
						if (substr($arg,0,9) == "FUNCTION-")
						{
							$fn = substr($arg,9);
							global $swFunctions;
							if (!isset($swFunctions[$fn])) { return array('_error'=>'Fnction '.$fn.' does not exist'); } 
							$f2 = $swFunctions[$fn];
							$fargs =array();
							if ($f2->arity()<0) { return array('_error'=>'Invalid function '.$fn); } 
							$a = $f2->arity(); 
							while ($a>0)
							{
								
								if (count($calcstack)>0)
									$fargs[] = array_pop($calcstack); 
								else
									return array('_error'=>'Empty calc stack'); 
								$a--;
							}
							$fargs[] =  $fn;
							$fargs = array_reverse($fargs);
							//print_r($fargs);

							$fresult = $f2->dowork($fargs);
							
							array_push($calcstack,$fresult);
						}
						else
						switch(substr($arg,0,1))
						{
							case '-': case '0': case '1': case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9': 
								array_push($calcstack,$arg); break; //should check also all other letters.
							
							case '"': array_push($calcstack,substr($arg,1)); break;  
							
							default : array_push($calcstack,$arg); break; 
								
							
							
						}
			}
		}
	
	}
	if (count($calcstack) < 1) return array('_error'=>'Expression Stack empty error 44');
	return array_pop($calcstack); 

}

/**
  * Calculates and sets key for rows of a table using term.
  *
  * @param $set
  * @param $term
  */


function swQuerySetKey($set, $term)
{
	$set2 = array();
	if (!is_array($set)) return array('_error'=>'QuerySetKey Set is not array');
	if (!is_string($term)) return array('_error'=>'QuerySetKey Term is not string');
	foreach($set as $key=>$row)
	{
		$sortkey = swQueryTupleExpression($row,$term);
		if (is_array($sortkey)) //error
			return array('_error'=>'QuerySetKey sortkey Expression error');
		$set2[$sortkey] = $row;
	}
	return $set2;
}


/**
  * Makes sure that all rows have the same order of fields.
  * Input rows may have not all keys or not in the same order. Rowkey is maintained.
  *
  * @param $rows
  */

	
function swCleanTupleFieldOrder($rows)
{	
	$keys = array();
	foreach($rows as $k=>$row)
	{
		foreach ($row as $key=>$v)
		{
			$keys[$key] = 1;  
		}
	}
	
	$rows2 = array();
	
	foreach($rows as $k=>$row)
	{
		$row2 = array();
		foreach ($keys as $key=>$foo)
		{
			$row2[$key] = @$row[$key];
		}
		$rows2[$k] = $row2;
	}
	return $rows2;
}

/**
  * Makes sure that all rows in table are unique
  *
  * @param $set table
  */


function swUniqueTuples($set)
{
	
	// make sure that there are no duplicate tuples
	
	$set2 = array();
	foreach($set as $line)
	{
		$set2[join('::',$line)] = $line;
	}
	unset($set);
	
	// uasort($set2, 'swMultifieldSort'); // do not sort any more, only uniqueness
	$set3 = array();
	foreach($set2 as $line)
	{
		$set3[] = $line;
	}
	return $set3;

}


/**
  * Custom compare function for two arrays.
  *
  * Compares the array joined with "::"
  * @param $a
  * @param $b
  */


function swMultifieldSort($a,$b) { 

if (is_array($a)) $a = join('::',$a);
if (is_array($b)) $b = join('::',$b);
return $a>$b;

}

/**
  * Custom sort function for SwQuery
  *
  * @param $keys
  */


function swQuerySort($keys) { 

   $array = reset($keys);
   if (!is_array($array)) $array = array();
   array_shift($keys); 
   swQuerySortCompare($keys); 
   usort($array,"swQuerySortCompare");  
   return $array;
} 

/**
  * Custom compare function for SwQuery
  *
  * Flags are in the key
  * # numeric
  * ! desc
  * ? familyname
  * @ url
  * & case insensitive
  * @param $a
  * @param $b
  */


// from http://www.php.net/manual/fr/function.array-multisort.php 
// modified

// modifiers: ! DESC  # NUMERIC
function swQuerySortCompare($a,$b=NULL) { 
   static $keys; 
   if($b===NULL) return $keys=$a; 
      
   foreach($keys as $k) 
   { 
      $numeric = false;
      $desc = false;
      $familyname = false;
      $url = false;
      $nocase = false;
      if (stristr($k,'#')) { $numeric = true; $k = str_replace('#','',$k);}
      if (stristr($k,'!')) { $desc = true;  $k = str_replace('!','',$k) ;}
      if (stristr($k,'?')) { $familyname = true;  $k = str_replace('?','',$k) ;}
      if (stristr($k,'@')) { $url = true;  $k = str_replace('@','',$k) ;}
      if (stristr($k,'&')) { $nocase = true;  $k = str_replace('&','',$k) ;}
      
      if ($numeric)
      {
      	$fa = floatval(@$a[$k]);
      	$fb = floatval(@$b[$k]);
      	$p = 1;
      	if ($desc) $p = -1;
      	if ($fa > $fb) return $p;
      	if ($fa < $fb) return -$p;
      }
      else
      {
      	if ($familyname)
      	{
      		$ca = swGetFamilyName(@$a[$k]);
      		$cb = swGetFamilyName(@$b[$k]);
      	}
      	elseif ($url)
      	{
      		$ca = swNameURL(@$a[$k]);
      		$cb = swNameURL(@$b[$k]);
      	}
      	elseif ($nocase)
      	{
      		$ca = strtolower(@$a[$k]);
      		$cb = strtolower(@$b[$k]);
      	}
      	else
      	{
      		$ca = @$a[$k];
      		$cb = @$b[$k];
      	}
      	
      	if($ca !== $cb) 
      
         if ($desc)
         	return strcmp($cb,$ca); 
         else
         	return strcmp($ca,$cb); 
      }
    } 
   return 0; 
} 













