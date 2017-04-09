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

function swGetAllRevisionsFromName($name)
{	
	echotime('getallrevisions '.$name);
	
	global $swAllRevisionsCache;
	if (isset($swAllRevisionsCache[$name]))
		return $swAllRevisionsCache[$name];
	
	$r = new swRecord;
	$r->name = $name;
	$md = $r->md5Name();
	
	$list = array();
	global $swRoot;
	$path = $swRoot.'/site/indexes/urls.txt';
	if (file_exists($path) && $fpt = fopen($path,'r'))
	{
		$blocksize = 960;
		$count = filesize($path) / $blocksize;
		for ($i=0;$i<=$count;$i++)
		{
			@fseek($fpt, $blocksize*$i);
			
			if ($i==$count)
				$chunksize = filesize($path) - $blocksize*$i;
			else
				$chunksize = $blocksize;
			$chunkcount = $chunksize / 48;
			
			$chunk = @fread($fpt,$chunksize);
			
			for ($j=0;$j<$chunkcount;$j++)
			{
				$offset = $j*48;
				$rev = trim(substr($chunk,$offset,9));
				$st = substr($chunk,$offset+10,2);
				$mdc = substr($chunk,$offset+12,32);
			
				if ($mdc == $md)
					$list[] = $rev;
			}
			
			/*
			
			$rev = trim(substr(@fread($fpt,10),0,9));
			$st = @fread($fpt,2);
			$mdc = @fread($fpt,32);
			
			if ($mdc == $md)
				$list[] = $rev;
			*/
		}
	}
	echotime('got '. count($list));
	$swAllRevisionsCache[$name] = $list;
	return $list;
	
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



class swDB extends swPersistance //extend may be obsolete
{
	
	
	var $indexedbitmap;
	var $currentbitmap;
	var $deletedbitmap; 
	var $protectedbitmap;
	var $trigrambitmap;
	var $bloombitmap;
	var $shortbitmap;
	
	var $salt;
	var $hasindex = false;
	var $lastrevision = 0;
	var $persistance2 = '';
	var $trigrams = array();
	var $inited = false;
	var $currentupdaterev;
	var $pathbase;
	
	function init($force = false) 
	{
		global $swRoot; 
		global $swMaxRelaxedSearchTime;
 
		if ($force)
		{
			$swMaxRelaxedSearchTime = 10000;
			$this->inited = false;
			$this->hasindex = false;
			$this->close(); // update lastrevision.txt
		}

		if ($this->hasindex)
			return;
		
		
		if ($this->inited)
			return;
		$this->inited = true;
		
		//selfhealing
		$this->pathbase = "$swRoot/site/";

		if (!is_dir( "$swRoot/site/")) mkdir ( "$swRoot/site/",0777); // mode does not work
		if (!is_dir( $this->pathbase.'current/')) mkdir ( $this->pathbase.'current/', 0777); 
		if (!is_dir( $this->pathbase.'indexes/')) mkdir ( $this->pathbase.'indexes/', 0777);
		if (!is_dir( $this->pathbase.'queries/')) mkdir ( $this->pathbase.'queries/', 0777);
		if (!is_dir( $this->pathbase.'trigram/')) mkdir ( $this->pathbase.'trigram/', 0777);
		if (!is_dir( $this->pathbase.'files/')) mkdir ( $this->pathbase.'files/', 0777);
		if (!is_dir( "$swRoot/site/revisions/")) mkdir ( "$swRoot/site/revisions/", 0777);

		
		$bitmaperror = false;
		
		$this->indexedbitmap = new swBitmap;
		$this->indexedbitmap->persistance = $this->pathbase.'indexes/indexedbitmap.txt';
		if (file_exists($this->indexedbitmap->persistance))
			$this->indexedbitmap->open();
		else
			$bitmaperror = true;
		
		$this->lastrevision=$this->indexedbitmap->length-1;
				
		$this->currentbitmap = new swBitmap;
		//$this->currentbitmap->hasbak = true;
		$this->currentbitmap->persistance = $this->pathbase.'indexes/currentbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->currentbitmap->persistance))
			$this->currentbitmap->open();
		else
			$bitmaperror = true;
						
		$this->deletedbitmap = new swBitmap;
		//$this->deletedbitmap->hasbak = true;
		$this->deletedbitmap->persistance = $this->pathbase.'indexes/deletedbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->deletedbitmap->persistance))
			$this->deletedbitmap->open();
		else
			$bitmaperror = true;
			
		$this->protectedbitmap = new swBitmap;
		//$this->protectedbitmap->hasbak = true;
		$this->protectedbitmap->persistance = $this->pathbase.'indexes/protectedbitmap.txt';
		if ( $this->lastrevision>0 && file_exists($this->protectedbitmap->persistance))
			$this->protectedbitmap->open();
		else
			$bitmaperror = true;
			
		if ($bitmaperror)
			$this->rebuildBitmaps();
			
		$this->trigrambitmap = new swBitmap;
		$this->trigrambitmap->persistance = $this->pathbase.'indexes/trigrambitmap.txt';
		if (file_exists($this->trigrambitmap->persistance))
			$this->trigrambitmap->open();

		$this->bloombitmap = new swBitmap;
		$this->bloombitmap->persistance = $this->pathbase.'indexes/bloombitmap.txt';
		if (file_exists($this->bloombitmap->persistance))
			$this->bloombitmap->open();

		$this->shortbitmap = new swBitmap;
		$this->shortbitmap->persistance = $this->pathbase.'indexes/shortbitmap.txt';
		if (file_exists($this->shortbitmap->persistance))
			$this->shortbitmap->open();
			
		$lastwrite = $this->GetLastRevisionFolderItem($force);
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
		return;
	}
	
	function close()
	{
		global $swRoot; 
		echotime("db-close");
		$today = date("Y-m-d",time());
		
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

		
		if ($this->shortbitmap->touched)
		{
			$this->shortbitmap->touched = false;
			$this->shortbitmap->save();
		}
		
		
	}
	
	function UpdateIndexes($rev)
	{
		$this->lastrevision = $this->indexedbitmap->length-1;
		if ($this->indexedbitmap->getbit($rev)) { return true;} // do not twice in a request
		$r = new swRecord;
		$r->revision = $rev;
		$this->currentupdaterev = $rev;
		if (!$source = $r->readHeader()) return false;
		echotime('update '.$rev);
		$this->indexedbitmap->setbit($rev);
		if ($r->status == '') return false;
		if ($r->revision == 0) return false;
		
		// compare with the current version. 
		
		$c = $r->currentPath();
		$r2 = new swRecord;
		if (file_exists($c))
		{
			
			$r2->persistance = $c;
			$r2->open();
		}
		
		if ($r->revision > $r2->revision) { $newr = $r; $oldr = $r2; }
		elseif ($r->revision < $r2->revision) { $newr = $r2; $oldr = $r; }
		else { $newr = $r; $oldr = null ; }
		
		if ($oldr != null)
			$this->currentbitmap->unsetbit($oldr->revision);
		$this->currentbitmap->setbit($newr->revision);
		
		if ($r->status == 'deleted')
			$this->deletedbitmap->setbit($rev);
		else
			$this->deletedbitmap->unsetbit($rev);
		if ($r->status == 'protected')
			$this->protectedbitmap->setbit($rev);
		else
			$this->protectedbitmap->unsetbit($rev);
		
		$url = swNameURL($r->name);
		
		
		// format line fixed length, but compatible with text 
		// $rev pad to 9, add tab = 10
		// $status 1, add tab = 2
		// $md5 32
		// PHP_EOL can be 1 or 2, we prepad to 4
		// total 48
		
		$line = str_pad($rev,9,' ')."\t";
		$line .= substr($r->status,0,1)."\t";
		$line .= $r->md5Name(); // 32
		$line .= substr('    '.PHP_EOL,-4);
		
		global $swRoot;
		$path = $this->pathbase.'indexes/urls.txt';
		$fpt = fopen($path,'c');
		@fseek($fpt, 48*($rev-1));
		@fwrite($fpt, $line);
		@fclose($fpt);
		
		/*
		if (strlen($source) <= 512)
		{
			$r->writecurrent(true);
		}
		*/
		
		return true;

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
		global $swError, $swIndexError, $swOvertime;
	
		echotime("rebuild ".$lastindex);  
		swSemaphoreSignal();
		$path = $this->pathbase.'indexes/urls.txt';
		if (file_exists($path) && filesize($path)<48*($lastindex-1))
		{
			$fpt = fopen($path,'c');
			@fseek($fpt, 48*($lastindex-1));
			@fwrite($fpt, " ");
			@fclose($fpt);
		}
		
		global $swMaxOverallSearchTime;
		if ($swMaxOverallSearchTime<2500) $swMaxRelaxedSearchTime=2500;
		global $rebuildstarttime;
		if (!$rebuildstarttime)
		$rebuildstarttime = microtime(true);	
		$overtime = false;
		$c=0;
		for($r = $lastindex; $r>=1; $r--)
		{
			if ($this->indexedbitmap->getbit($r)) continue;
			$nowtime = microtime(true);	
			$dur = sprintf("%04d",($nowtime-$rebuildstarttime)*1000);
			if (intval($dur)>intval($swMaxOverallSearchTime))
			{
				echotime('overtime INDEX');
				$swOvertime = true;
				$swError = "Index incomplete. Please reload";
				$swIndexError = true;
				$overtime = true;
				break;
			}
			$this->UpdateIndexes($r);
			$c++;
		}
		$this->rebuildBitmaps();
		$swIndexError = false;
		echotime('indexes built '.$c.' open '.$r);	
		swSemaphoreRelease();
	}	
	
	function rebuildBitmaps()
	{
		echotime('rebuildbitmaps');  
		swSemaphoreSignal();
		
		$current = array();
		$path = $this->pathbase.'indexes/urls.txt';
		if (file_exists($path) && $fpt = fopen($path,'r'))
		{
			$count = filesize($path) / 48;
			
			$this->indexedbitmap->init($count);
			$this->currentbitmap->init($count);  
			$this->deletedbitmap->init($count); 
			$this->protectedbitmap->init($count);
			
			for ($i=0;$i<$count;$i++)
			{
				@fseek($fpt, 48*$i);
				$rev = trim(substr(@fread($fpt,10),0,9));
				$st = substr(@fread($fpt,2),0,1);
				$mdc = @fread($fpt,32);
			
				$error = false;
				switch($st)
				{
					case 'o':	$this->currentbitmap->setbit($rev);
								$this->deletedbitmap->unsetbit($rev);
								$this->protectedbitmap->unsetbit($rev);
								break;
					case 'd':	$this->currentbitmap->setbit($rev);
								$this->deletedbitmap->setbit($rev);
								$this->protectedbitmap->unsetbit($rev);
								break;
					case 'p':	$this->currentbitmap->setbit($rev);
								$this->deletedbitmap->unsetbit($rev);
								$this->protectedbitmap->setbit($rev);
								break;
					case '-':	break; // filled gap
					case '':	$error = true;
				}
				if (!$error)
				{
					$this->indexedbitmap->setbit($rev);
					if (isset($current[$mdc]))
					{
						$rold = $current[$mdc];
						$this->currentbitmap->unsetbit($rold);
					}
					$current[$mdc] = $rev;
				}
				
			}
		}
		swSemaphoreRelease();
		echotime('bitmaps built');
	}
	
	
}






?>