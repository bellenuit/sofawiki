<?php

/*
SOFA DB
- document oriented database inspired from couchdb but using semantic metawiki syntax
- the database has no tables and no predefined fields.
- fields are declared inline using the semantic mediawiki syntax [[fieldname::value]]
  fieldnames cannot start with an underscore
- multiple occurences of the same field are allowed
- the records are self-contained
- the records are stored as revisioned files adding a header with the reserved fields 
  _id automatically generated integer
  _revision automatically generated integer
  _name the wiki name, can change over time
  _timestamp sever time
  _status ok, request, protected, deleted
- the filename is the revision. the written files are never changed afterwards.
- on insertion of a new revision, the database writes some indexes. 
  these indexes are for performance only, they can be rebuild whenever needed from scratch
  - index to find the most recent ok revision of an id (or discard it if there is a more recent delete)
  - index for each field
  - fulltext index wordbased on each field
- queries can be done
  - individually return a sdRecord by id, by revision or by name
  - return a list of all revisions of an id
  - return a list of all revisions (or only current ids) that are conform to a filter
    filter use regex and are applied on all revisions.
    the filter results are saved, so that the next time only new revisions have to be applied for the same filter
    - filename as md5 of request
    - fields _query, _maxrevision, _timestamp
    
REQUIREMENTS
- php needs write access to the folders parsers, queries and revisions
 
*/

if (!defined('SOFAWIKI')) die('invalid acces');

$swAllRevisionsCache = array();





class swDB extends swPersistance //extend may be obsolete
{
	
	
	var $indexedbitmap;  // revisions that have been indexed in urldb
	var $currentbitmap;  // revisions that are currently active (last version, ok or protected) these are the only one the database needs to access in relation filter
	var $deletedbitmap;  // revision with status deleted
	var $protectedbitmap; // revisions with status protected
	var $bloombitmap; // revisions that have been indexed by bloom 
	//var $shortbitmap;
	var $urldb;
	var $touched = false;
	
	var $salt;
	var $hasindex = false; 
	var $lastrevision = 0;
	var $persistance2 = '';
	var $inited = false;
	var $currentupdaterev;
	var $pathbase;
	

	
	function init($force = false) 
	{
		
		global $swRoot; 
		global $swRamdiskPath;
		global $swOvertime;

		echotime('init '.$force);
		
		if (isset($swRamdiskPath) && $swRamdiskPath != '')
			swInitRamdisk();

 
		if ($force)
		{
			$this->inited = false;
			$this->hasindex = false;
			$this->close(); // save bitmaps to update lastrevision.txt
		}

		if ($this->hasindex) return;
		if ($this->inited) return;
		
		$this->inited = true;
		
		//selfhealing
		$this->pathbase = "$swRoot/site/";

		if (!is_dir( "$swRoot/site/")) mkdir ( "$swRoot/site/",0777); // mode does not work
		if (!is_dir( $this->pathbase.'current/')) mkdir ( $this->pathbase.'current/', 0777); 
		if (!is_dir( $this->pathbase.'indexes/')) mkdir ( $this->pathbase.'indexes/', 0777);
		if (!is_dir( $this->pathbase.'queries/')) mkdir ( $this->pathbase.'queries/', 0777);
		if (!is_dir( $this->pathbase.'files/')) mkdir ( $this->pathbase.'files/', 0777);
		if (!is_dir( "$swRoot/site/revisions/")) mkdir ( "$swRoot/site/revisions/", 0777);

		
		$bitmaperror = false;
		
		$this->indexedbitmap = new swBitmap;
		$this->indexedbitmap->persistance = $this->pathbase.'indexes/indexedbitmap.txt';
		if (file_exists($this->indexedbitmap->persistance))
			$this->indexedbitmap->open();
		else
			$bitmaperror = 'indexed null';	
			
		$this->lastrevision=$this->indexedbitmap->length-1;
				
		$this->currentbitmap = new swBitmap;
		$this->currentbitmap->persistance = $this->pathbase.'indexes/currentbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->currentbitmap->persistance))
			$this->currentbitmap->open();
		else
			$bitmaperror = 'current null';
						
			
		$this->protectedbitmap = new swBitmap;
		$this->protectedbitmap->persistance = $this->pathbase.'indexes/protectedbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->protectedbitmap->persistance))
			$this->protectedbitmap->open();
		else
			$bitmaperror = 'protected null';
			
		$this->deletedbitmap = new swBitmap;
		$this->deletedbitmap->persistance = $this->pathbase.'indexes/deletedbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->deletedbitmap->persistance))
			$this->deletedbitmap->open();
		else
			$bitmaperror = 'deleted null';

		$this->bloombitmap = new swBitmap;
		$this->bloombitmap->persistance = $this->pathbase.'indexes/bloombitmap.txt';
		if (file_exists($this->bloombitmap->persistance))
			$this->bloombitmap->open();
			
			
		$urldbpath = $this->pathbase.'indexes/urls.db';
		global $swDBAhandler;
		if (file_exists($urldbpath))
			$this->urldb = swDBA_open($urldbpath, 'wdt', $swDBAhandler);
		else
			$this->urldb = swDBA_open($urldbpath, 'c', $swDBAhandler);	

		$lastwrite = $this->GetLastRevisionFolderItem($force || $this->lastrevision < 200);
		
		// 95% full 
		if ($lastwrite > 0 && $this->indexedbitmap->countbits() / $lastwrite < 0.95 ) $bitmaperror = 'index less 95';
		if ($this->currentbitmap->countbits()==0 ) $bitmaperror = 'current 0';
		if ($this->lastrevision == 0) $bitmaperror = 'lastrevision 0';
		
		

		echotime("db-init ".$this->lastrevision."/" .$lastwrite);
		
		// always cleaning latest changes
		for($i=$lastwrite-16; $i<=$lastwrite;$i++)
		{
			if (!$this->indexedbitmap->getbit($i)) { $this->updateindexes($i);}
		}
			
		if ($this->lastrevision < $lastwrite || $force)
		{
			$this->RebuildIndexes($lastwrite);
		}
		else
		{
			$this->hasindex = true;
		}
		
		if ($bitmaperror && !$swOvertime)
		{
			echotime($bitmaperror);
			$this->RebuildIndexes($this->lastrevision);
		}
		
		return;
	}
	
	function close()
	{
		global $swRoot; 
		
		if (!$this->touched) return;
		
		echotime("db-close");
		
		if ($this->indexedbitmap->touched)
		{
			$this->indexedbitmap->touched = false;
			$this->indexedbitmap->save();
		}
		if ($this->currentbitmap->touched)
		{
			$this->currentbitmap->touched = false;
			$this->currentbitmap->save();
		}
		if ($this->deletedbitmap->touched)
		{
			$this->deletedbitmap->touched = false;
			$this->deletedbitmap->save();
		}
		if ($this->protectedbitmap->touched)
		{
			$this->protectedbitmap->touched = false;
			$this->protectedbitmap->save();
		}
		
		if ($this->bloombitmap->touched)
		{
			$this->bloombitmap->touched = false;
			$this->bloombitmap->save();
		}

		/*
		if ($this->shortbitmap->touched)
		{
			$this->shortbitmap->touched = false;
			$this->shortbitmap->save();
		}
		*/
//		global $testmode; 
//		if ($testmode) $this->urldb->close();
		
		global $swOvertime; 
		if (!$swOvertime && rand(0,100)>80)		
			swIndexBloom(16);
		
		$this->touched = false;
		
	}
	
	function __destruct()
	{
		$this->close();
	}
	
		
	function GetLastRevisionFolderItem($force=false)
	{
		global $swRoot;
		$path = "$swRoot/site/revisions";
		$maxf = 0;
		
		
		// performance: normally just check the next 100;
		if (rand(0,100) > 1 && !$force)
		{
			$maxf = $this->lastrevision;
			$m2 = $maxf-10;
			for ($i=$m2+1;$i<$m2+100;$i++)
			{
				if (file_exists($path.'/'.$i.'.txt'))
					$maxf =$i;
			}
			return $maxf;
		}
		
		$dir = opendir($path);
		while($file = readdir($dir))
    	{
			if($file != '..' && $file != '.')
			{
				if (substr($file,-4)=='.txt')
				{
					$f = (int)(substr($file,0,-4));
					$maxf = max($f,$maxf);
				}
			}
		}
		return $maxf;
	}
	
	function RebuildIndexes($lastindex=0)
	{
		// we read all file statuses to get create the url.db
		
		echotime("indexes start ".$lastindex); 
		
		global $swError, $swIndexError, $swOvertime, $swMemoryLimit;
		global $rebuildstarttime;
		
		if (!$rebuildstarttime) $rebuildstarttime = microtime(true);
			
		$c=0;
		for($r = $lastindex; $r>=1; $r--)
		{
			if ($this->indexedbitmap->getbit($r)) continue;
			$nowtime = microtime(true);	
			$dur = sprintf("%04d",($nowtime-$rebuildstarttime)*1000);
			if (intval($dur)>10000 || memory_get_usage()>$swMemoryLimit)
			{
				echotime('overtime INDEX '.$c.' '.$r);
				$swOvertime = true;
				$swError = "Index incomplete. Please reload";
				$swIndexError = true;
				break;
			}
			$this->UpdateIndexes($r);
			$c++; 
		}
		if (!$swIndexError)
		{
			 $this->rebuildBitmaps(); 
			 $swIndexError = false;
			 echotime('indexes built '.$c);	
			 //swIndexBloom(10);
		}
		swDBA_sync($this->urldb);
		
		
	}
	
	
	function UpdateIndexes($rev)
	{
		
		$this->lastrevision = max($this->indexedbitmap->length-1,$rev);
		
		if ($this->indexedbitmap->getbit($rev)) { return true;} // do not twice in a request, cheaper than swdba_exists
		
		if (swDBA_exists(' '.$rev,$this->urldb)) { $this->indexedbitmap->setbit($rev); return true;} // already done

		
		$r = new swRecord;
		$r->revision = $rev;
		$this->currentupdaterev = $rev;
		if (!$source = $r->readHeader()) return false;
		$this->indexedbitmap->setbit($rev);
		if ($r->status == '') return false;
		if ($r->revision == 0) return false;
		
		$url = swNameURL($r->name);		
		$status = substr($r->status,0,1);

		if (swDBA_exists($url,$this->urldb))
		{
			$line = swDBA_fetch($url,$this->urldb);
			if ($line)
			{
				$revs = explode(' ',$line);
				
				if (count($revs)>=2) // should always be the case 
				{
				
					// echo $line;
					$oldstatus = array_shift($revs); 
					$oldrev = array_shift($revs);   
					
					if ($rev < $oldrev)
					{
						$status = $oldstatus;
					}
					$revs[] = $oldrev;
					$revs[] = $rev;
					$revs = array_unique($revs);
					rsort($revs,SORT_NUMERIC);
					
					// current status one letter, then all revision in reverse order
					// o 4323 2332 1123
					// d 4371 3322
					// p 6781
					$line = $status.' '.join(' ',$revs);
					
					swDBA_replace($url, $line, $this->urldb);  // url index
					swDBA_replace(' '.$rev, $url, $this->urldb);  // inverse index starts with space (possible because url cannot start with space)
					
					// set bits for last revision
					$rlast = array_shift($revs);
					if ($status == 'p') $this->protectedbitmap->setbit($rlast);
					if ($status == 'd') $this->deletedbitmap->setbit($rlast);
					
					if ($status == 'o' || $status == 'p')
						$this->currentbitmap->setbit($rlast);
					else
						$this->currentbitmap->unsetbit($rlast);

					
					// unset bits for older revisions
					while ($rother = array_shift($revs))
					{
						$this->currentbitmap->unsetbit($rother);
						$this->protectedbitmap->unsetbit($rother);
						$this->deletedbitmap->unsetbit($rother);
					}
					
					
				}
			}
		}
		else
		{
			$line = $status.' '.$rev;
			swDBA_replace($url,$line, $this->urldb);
			swDBA_replace(' '.$rev, $url, $this->urldb);
		
			if ($status == 'p') $this->protectedbitmap->setbit($rev);
			if ($status == 'd') $this->deletedbitmap->setbit($rev);
					
			if ($status == 'o' || $status == 'p')
				$this->currentbitmap->setbit($rev);
			else
				$this->currentbitmap->unsetbit($rev);

			
		}
		
		$this->touched = true;
		
		return true;

	}

	
	function rebuildBitmaps()
	{
		echotime('bitmaps');  
		
		// we read all urldb to reconstruct bitmaps. This is rather fast
		
		$this->indexedbitmap->init($this->lastrevision); // index false by default
		$this->currentbitmap->init($this->lastrevision);  
		$this->deletedbitmap->init($this->lastrevision); 
		$this->protectedbitmap->init($this->lastrevision);
			
		$key = swDBA_firstKey($this->urldb);		
		do 
		{
		  $line = swDBA_fetch($key,$this->urldb);
		  
		  if (substr($line,0,1)==' ') continue; // is revision key
		  
		  if ($line)
		  {
			  $fields = explode(' ',$line);
			  
			  if (count($fields)>=2) // should always be the case
			  {
			
			  	$status = array_shift($fields);
			  	$rev = array_shift($fields);
			  
			  	switch($status)
			  	{
				 	 case 'o' :	$this->currentbitmap->setbit($rev); break;
				 	 case 'p' :	$this->currentbitmap->setbit($rev); 
				 	 			$this->protectedbitmap->setbit($rev); break;
				 	 case 'd' : $this->deletedbitmap->setbit($rev); break;
				 	 default  :	$error = true;	
			  	}			  	
			  	$this->indexedbitmap->setbit($rev);	
			  	
			  	// older revisions
			  	foreach($fields as $rev)
			  	{
				  	$this->indexedbitmap->setbit($rev);	
			  	}
			  			  
			  }
		  }
	
		  	
		} while ($key = swDBA_nextKey($this->urldb));
			
		$this->touched = true;
			
		echotime('bitmaps '.$this->lastrevision);
	}
	
	
}


function swGetLastRevision()
{
	global $db;
	return $db->lastrevision;
}


function swGetAllRevisionsFromName($name)
{	
	echotime('getallrevisions '.$name);
	
	global $swAllRevisionsCache;
	if (isset($swAllRevisionsCache[$name]))
		return $swAllRevisionsCache[$name];
	global $db;	
	
	$url= swNameURL($name);
	
	$revs = array();	
	global $db;
	
	if (swDBA_exists($url,$db->urldb))
	{
		$s = swDBA_fetch($url,$db->urldb);
		$revs = explode(' ',$s);
		$status = array_shift($revs);
		rsort($revs,SORT_NUMERIC);
	}
	
	
	$swAllRevisionsCache[$name] = $revs;
	return $revs;
		
	
	$r = new swRecord;
	$r->name = $name;
	$md = $r->md5Name();
	
}	

function swGetPath($revision, $current = false)
{
	if (is_array($revision))
		{
			debug_print_backtrace();
			exit;
		}
		

	global $swRoot;
	if ($current)
	{
		return $swRoot.'/site/current/'.$revision.'.txt';
	}
	else
		return $swRoot.'/site/revisions/'.$revision.'.txt';
}

?>