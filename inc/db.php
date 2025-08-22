<?php
	
/**
 *	Contains the swDB class which is responsible for the indexes
 * 
 *  There is one global instance $db that handles the indexes.
 *  It does not handle writing of new records. This is done in inc/record.php.
 *  It observes changes and updates indexes based on need.
 *  Note that to look up a record by URL, the indexes are not used. 
 *  The hashes of the filenames in the current folder are the index for that.
 *
 *  General architecture 
 *  Document oriented database inspired from couchdb but using semantic metawiki syntax
 *  The database has no tables and no predefined fields.
 *  Fields are declared inline using the semantic mediawiki syntax [[fieldname::value]].
 *  Fieldnames cannot start with an underscore.
 *  Multiple occurences of the same field are allowed.
 *  The records are self-contained.
 *  The records are stored as revisioned files adding a header with the reserved fields.
 *  _id automatically generated integer
 *  _revision automatically generated integer
 *  _name the wiki name, can change over time
 *  _timestamp sever time
 * _status ok, request, protected, deleted
 * The filename is the revision.tx. The written files are never changed afterwards.
 * Every change creates a new revision,.
 * On insertion of a new revision, the database writes some indexes. 
 * These indexes are for performance only, they can be rebuild whenever needed from scratch.
 * Note: PHP needs write access to the site folders and subfolders.
*/

if (!defined('SOFAWIKI')) die('invalid acces');

$swAllRevisionsCache = array();
$swCurrentRevsisionCache = array();

/**
 * Holds the database indexes 
 *
 * $indexedbitmap revisions that have been indexed in urldb
 * $currentbitmap revisions that are the last version and have the status ok or protected (these are the only filters should check)
 * $deletedbitmap revisions with status deleted
 * $protectedbitmap revisions with status protected
 * $bloombitmap revisions with have been indexed by the bloom filter
 * $urldb database of url names and revision
 * All these objects are persistent in site/indexes/.
 * The bitmaps are serialized objects.
 * The urldb is a sqlite3 db.
 */

class swDB extends swPersistance //extend may be obsolete
{
	var $indexedbitmap; 
	var $currentbitmap;  
	var $deletedbitmap;  
	var $protectedbitmap; 
	var $bloombitmap;  
	var $fulltextbitmap;  
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
		if (defined('SOFAWIKICLI')) return;
		global $swRoot; 
		global $swOvertime;

// 		echotime('init '.$force);

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
		$this->pathbase = $swRoot.'/site/';
		if (!is_dir($this->pathbase)) 			   mkdir($this->pathbase,0777); // mode does not work
		if (!is_dir($this->pathbase.'cache/'))     mkdir($this->pathbase.'cache/', 0777); 
		if (!is_dir($this->pathbase.'current/'))   mkdir($this->pathbase.'current/', 0777); 
		if (!is_dir($this->pathbase.'indexes/'))   mkdir($this->pathbase.'indexes/', 0777);
		if (!is_dir($this->pathbase.'queries/'))   mkdir($this->pathbase.'queries/', 0777);
		if (!is_dir($this->pathbase.'files/'))     mkdir($this->pathbase.'files/', 0777);
		if (!is_dir($this->pathbase.'revisions/')) mkdir($this->pathbase.'revisions/', 0777);
		
		$bitmaperror = false;
		
		$this->indexedbitmap = new swBitmap;
		$this->indexedbitmap->persistance = $this->pathbase.'indexes/indexedbitmap.txt';
		if (file_exists($this->indexedbitmap->persistance))
		{
			$this->indexedbitmap->open();
		}
		else
		{
			$bitmaperror = 'indexed null';	
		}
		$this->lastrevision=$this->indexedbitmap->length-1;
				
		$this->currentbitmap = new swBitmap;
		$this->currentbitmap->persistance = $this->pathbase.'indexes/currentbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->currentbitmap->persistance))
		{
			$this->currentbitmap->open();
		}
		else
		{
			$bitmaperror = 'current null';
		}
			
		$this->protectedbitmap = new swBitmap;
		$this->protectedbitmap->persistance = $this->pathbase.'indexes/protectedbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->protectedbitmap->persistance))
		{
			$this->protectedbitmap->open();
		}
		else
		{
			$bitmaperror = 'protected null';
		}
			
		$this->deletedbitmap = new swBitmap;
		$this->deletedbitmap->persistance = $this->pathbase.'indexes/deletedbitmap.txt';
		if ($this->lastrevision>0 && file_exists($this->deletedbitmap->persistance))
		{
			$this->deletedbitmap->open();
		}
		else
		{
			$bitmaperror = 'deleted null';
		}

		$this->bloombitmap = new swBitmap;
		$this->bloombitmap->persistance = $this->pathbase.'indexes/bloombitmap.txt';
		if (file_exists($this->bloombitmap->persistance))
		{
			$this->bloombitmap->open();
		}
		
		$this->fulltextbitmap = new swBitmap;
		$this->fulltextbitmap->persistance = $this->pathbase.'indexes/fulltextbitmap.txt';
		if (file_exists($this->fulltextbitmap->persistance))
		{
			$this->fulltextbitmap->open();
		}

					
		$urldbpath = $this->pathbase.'indexes/urls.db';
		if (file_exists($urldbpath))
		{
			$this->urldb = new swDba($urldbpath,'wdt');
		}
		else
		{
			$this->urldb = new swDba($urldbpath,'c');	
		}

		$lastwrite = $this->getLastRevisionFolderItem($force || $this->lastrevision < 200);
		
		// 95% full 
		if ($lastwrite > 0 && $lastwrite - $this->indexedbitmap->countbits() > 100 ) $bitmaperror = 'index less 100';
		if ($this->currentbitmap->countbits()==0 ) $bitmaperror = 'current 0';
		if ($this->lastrevision == 0) $bitmaperror = 'lastrevision 0';

		echotime('db-init '.$this->lastrevision.'/'.$lastwrite);
		
		// always cleaning latest changes
		for($i=$lastwrite-16; $i<=$lastwrite;$i++)
		{
			if (!$this->indexedbitmap->getbit($i)) { $this->updateIndexes($i);}
		}
			
		if ($this->lastrevision < $lastwrite || $force)
		{
			$this->rebuildIndexes($lastwrite);
		}
		else
		{
			$this->hasindex = true;
		}
		
		if ($bitmaperror && !$swOvertime)
		{
			echotime($bitmaperror);
			$this->rebuildIndexes($this->lastrevision);
		}
		
		return;
	}
	
	function close()
	{
		global $swRoot; 
		
		if (!$this->touched) return;
		
		echotime('db-close');
		
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
		if ($this->fulltextbitmap->touched)
		{
			$this->fulltextbitmap->touched = false;
			$this->fulltextbitmap->save();
		}
		
		global $swOvertime; 
		if (!$swOvertime && rand(0,100)>80)		
			swIndexBloom(16);
					
		$this->touched = false;
		
	}
	
	function __destruct()
	{
		$this->close();
	}	
		
	function getLastRevisionFolderItem($force=false)
	{
		global $swRoot;
		$path = $swRoot.'/site/revisions';
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
	
	function rebuildIndexes($lastindex=0)
	{
		// we read all file statuses to get create the url.db
		
		echotime('indexes start '.$lastindex); 
		
		global $swError, $swIndexError, $swOvertime, $swMemoryLimit;
		global $rebuildstarttime;
		
		if (!$rebuildstarttime) $rebuildstarttime = microtime(true);
			
		$c=0;
		for($r = $lastindex; $r>=1; $r--)
		{
			if ($this->indexedbitmap->getbit($r)) continue;
			$nowtime = microtime(true);	
			$dur = sprintf('%04d',($nowtime-$rebuildstarttime)*1000);
			if (intval($dur)>10000 || memory_get_usage()>$swMemoryLimit)
			{
				echotime('overtime INDEX '.$c.' '.$r);
				$swOvertime = true;
				$swError = 'Index incomplete. Please reload ';
				$swIndexError = true;
				echotime('incomplete '.$lastindex.' '.$r);
				break;
			}
			$this->updateIndexes($r, true); // topdown
			$c++; 
		}
		if (!$swIndexError)
		{
			 $this->rebuildBitmaps(); 
			 $swIndexError = false;
			 echotime('indexes built '.$c);
		}
		
		
		if (!$this->urldb->sync())
		{
			echotime('overtime INDEX sync errror');
			$swOvertime = true;
			$swError = 'Index incomplete. Please reload ';
			$swIndexError = true;
		}
	}
	
	
	function updateIndexes($rev, $topdown = false)
	{
		
		
		
		
		$this->lastrevision = max($this->indexedbitmap->length-1,$rev);
		
		if ($this->indexedbitmap->getbit($rev)) return true;  // do not twice in a request, cheaper than swDbaExists
		
		if ($this->urldb->exists(' '.$rev))
		{ 
			$this->indexedbitmap->setbit($rev);
			return true;
		} // already done
		
		$r = new swRecord;
		$r->revision = $rev;
		$this->currentupdaterev = $rev;
		

		$this->indexedbitmap->setbit($rev);
		if (!$source = $r->readHeader()) return false;
		
// 		echotime( 'update '.$rev, true);
		
		
		if ($r->status == '') return false;
		if ($r->revision == 0) return false;
		
		$url = swNameURL($r->name);		
		$status = substr($r->status,0,1);

		if ($this->urldb->exists($url))
		{
			$line = $this->urldb->fetch($url);
			if ($line)
			{
				$revs = explode(' ',$line);
				
				if (count($revs)>=2) // should always be the case 
				{
					$oldstatus = $revs[0];
					$oldrev = $revs[1];   
					$firststatus = $revs[count($revs)-1];
					
					if ($rev > $oldrev)
					{
						// remove old status, add new status and rev at start
						array_shift($revs);
						array_unshift($revs,$rev);
						array_unshift($revs,$status);
						
						// unset current bit for oldrev
						$this->currentbitmap->unsetbit($oldrev);
						// set current bit for new rev if o or p
						switch ($status)
						{
							case 'o' : 	$this->currentbitmap->setbit($rev); break;
							case 'p' : 	$this->currentbitmap->setbit($rev); $this->protectedbitmap->setbit($rev); break;
							case 'd' : 	$this->deletedbitmap->setbit($rev); break;

						}	
					}
					elseif ($rev < $firststatus)
					{
						$revs[] = $rev;
						$this->currentbitmap->unsetbit($rev);
					}
					else
					{
						array_shift($revs);
						$revs[] = $rev;
						$revs = array_unique($revs);
						rsort($revs,SORT_NUMERIC);
						array_unshift($revs,$oldstatus);
					}
					
					// current status one letter, then all revision in reverse order
					// o 4323 2332 1123
					// d 4371 3322
					// p 6781
					$line = join(' ',$revs);
					
// 					echotime('replace start', true);
					
					$this->urldb->replace($url,$line);  // url index
					$this->urldb->replace(' '.$rev, $url); // inverse index starts with space (possible because url cannot start with space)
				}
			}
		}
		else
		{
			$line = $status.' '.$rev;
			
// 			echotime('replace2 start', true); 
			$this->urldb->replace($url,$line);
			$this->urldb->replace(' '.$rev, $url);
// 			echotime('replace2 end', true);
		
			if ($status == 'p') $this->protectedbitmap->setbit($rev);
			if ($status == 'd') $this->deletedbitmap->setbit($rev);
					
			if ($status == 'o' || $status == 'p')
				$this->currentbitmap->setbit($rev);
			else
				$this->currentbitmap->unsetbit($rev);
		}
		
		$this->touched = true;	
// 		echotime('update end', true);
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
		$this->fulltextbitmap->init($this->lastrevision);
			
		$key = $this->urldb->firstKey();		
		do 
		{
		  $line = $this->urldb->fetch($key);
		  
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
	
		  	
		} while ($key = $this->urldb->nextKey());
			
		$this->touched = true;
			
		echotime('bitmaps '.$this->lastrevision);
	}
	
	
}

/**
 * Returns the number of the last revision. 
 *
 * Made a global function to replace global $db declaration in code blocks.
 */

function swGetLastRevision()
{
	global $db;
	return $db->lastrevision;
}

/**
 * Returns current revision for a name
 *
 * @param name
 */


function swGetCurrentRevisionFromName($name)
{	
 	//echotime('getcurrentrevision '.$name);
	
	global $swCurrentRevisionCache;
	if (isset($swCurrentRevisionCache[$name]))
		return $swCurrentRevisionCache[$name];
	global $db;	
	
	$url= swNameURL($name);
	
	
	
	global $db;
	
	if ($db->urldb->exists($url))
	{
		$s = $db->urldb->fetch($url);
		$revs = explode(' ',$s);
		$status = $revs[0];
		$current = $revs[1];
		
		if ($status == 'o' || $status == 'p')
		{
			$swCurrentRevisionCache[$name] = $current;
			return $current;
		}
	}
	
	
	
}	

/**
 * Returns most recent revision for a name
 *
 * @param name
 */


function swGetLastRevisionFromName($name)
{	
 	//echotime('getcurrentrevision '.$name);
	
	global $swLastRevisionCache;
	if (isset($swLastRevisionCache[$name]))
		return $swLastRevisionCache[$name];
	global $db;	
	
	$url= swNameURL($name);
	
	global $db;
	
	if ($db->urldb->exists($url))
	{
		$s = $db->urldb->fetch($url);
		$revs = explode(' ',$s);
		$status = $revs[0];
		$current = $revs[1];
		
		$swLastRevisionCache[$name] = $current;
		return $current;
		
	}
	
	
	
}	


/**
 * Returns all revisions for a name to build a history
 *
 * @param name
 */


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
	
	if ($db->urldb->exists($url))
	{
		$s = $db->urldb->fetch($url);
		$revs = explode(' ',$s);
		$status = array_shift($revs);
		rsort($revs,SORT_NUMERIC);
	}
	
	$swAllRevisionsCache[$name] = $revs;
	return $revs;
	
}	

/**
 * Returns the file path for a revision
 *
 * @param revision
 * @param current 
 */

function swGetPath($revision, $current = false)
{
	if (is_array($revision))
	{
		debug_print_backtrace(); //should not happen
		exit;
	}	

	global $swRoot;
	if ($current) 
	{
		return $swRoot.'/site/current/'.$revision.'.txt';  // not clear if this is still useful
	}
	else
		return $swRoot.'/site/revisions/'.$revision.'.txt';
}

?>