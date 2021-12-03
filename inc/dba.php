<?php
	
include_once 'inc/semaphore.php';

// dba replacement functions as dba is not available on all PHP installations

define("CRLF", "\r\n");
define("LF", "\n");
define("TAB", "\t");

$swDBApool = array();

$swDBAhandler = 'db4';

if (function_exists('dba_handlers'))
{
	if (!in_array('db4', dba_handlers())) $swDBAhandler = 'flatfile';
}
else
	$swDBAhandler = 'flatfile';

$testmode = 1;


function swDBA_open($file, $mode, $handler)
{
	global $testmode;
	
	if ($testmode)
	{
		try
		{
			return new swDBA($file,$mode);
		}
		catch (swDBAerror $err)
		{
			$err->notify();
		}
	}
	
	global $swDBAhandler;
	return dba_open($file,$mode,$swDBAhandler);
}

function swDBA_nextkey($db)
{
	global $testmode;
	if ($testmode)
		return $db->nextkey();

	return dba_nextkey($db);
}

function swDBA_firstkey($db)
{
	global $testmode;
	if ($testmode)
		return $db->firstkey();
		
	return dba_firstkey($db);
}

function swDBA_fetch($key,$db)
{
	global $testmode;
	if ($testmode)
	{
		try
		{
			return $db->fetch($key);
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			return "swDBA error ".$err->getMessage();
		}
	}	
	
	return dba_fetch($key,$db);
}

function swDBA_replace($key,$value,$db)
{
	
	global $testmode;
	if ($testmode)
	{
		try
		{
			return $db->replace($key,$value);
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			return false;
		}
	}
	
	return dba_replace($key,$value,$db);
}

function swDBA_sync($db)
{
	global $testmode;
	if ($testmode)
	{
		try
		{
			return $db->sync();
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			// rename file because it is corrupt
			rename($db->path,$db->path.date("Y-m-d H:i:s").'.crpt');
			return false;
		}

	}

	return dba_sync($db);
}

function swDBA_delete($key,$db)
{
	
	global $testmode;
	if ($testmode)
	{
		try
		{
			return $db->delete($key);
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			return false;
		}
	}

	return dba_delete($key,$db);
}

function swDBA_close($db)
{
	global $testmode;
	if ($testmode)
	{
		try
		{
			return $db->close();
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			// rename file because it is corrupt
			rename($db->path,$db->path.date("Y-m-d H:i:s").'.crpt');
			return false;
		}

	}	
	return dba_close($db);
}

function swDBA_exists($key,$db)
{
	global $testmode;
	if ($testmode)
		return $db->exists($key);
	
	return dba_exists($key,$db);
}

function swDBA_count($db)
{
	global $testmode;
	if ($testmode)
		return $db->count();
	
	return 0;
}


class swDBA
{
	// file format
	/*
	key + tab + lengthofdata + CRLF + data + CRLF
	...
	key + tab + dataoffset 
	...
	"indexoffset" + CRLF
	offset + CRLF
	
	
	*/
	
	var $mode; // rd, rdt, wd, wdt, cd, cdt
	var $path;
	var $handle;
	var $index = [];
	var $keys = [];
	var $journal = [];
	var $indexoffset;
	var $keycursor;
	var $touched;
	
	
	
	function __construct($path, $mode)
	{
		global $swRoot;
		echotime('dba_open '.$mode.' '.str_replace($swRoot,'',$path));
		switch($mode)
		{
			case 'w':
			case 'wd':
			case 'wdt': if (!file_exists($path)) throw new swDBAerror('swDBA file does not exist '.$path);
						$this->handle = fopen($path,'c+');
						break;
			case 'c':
			case 'cd':  
			case 'cdt': if (file_exists($path)) throw new swDBAerror('swDBA file does already exist '.$path);
						$this->handle = fopen($path,'c+');
						break;
			case 'r':
			case 'rd':
			case 'rdt': if (!file_exists($path)) throw new swDBAerror('swDBA file does not exist '.$path);
						$this->handle = fopen($path,'r');
						break;
			default:  throw new swDBAerror('swDBA unknowm mode '.$mode);	
		}
		
		$this->mode = $mode;
		$this->path = $path;


		$this->keycursor = 0;
		$this->keys = [];
		$this->touched = false;
		
		if ($this->mode == 'cd' || $this->mode == 'cdt') { $this->touched = true; return; }
		
		$this->readIndex();
		
	}
	
	function readIndex()
	{
		// read index
		fseek($this->handle,-32,SEEK_END);
		
		$tail = fread($this->handle,32); 
		$key = 'indexoffset'.PHP_EOL;
		$tail = stristr($tail,$key);
		$tail = substr($tail,strlen($key));
		$this->indexoffset = intval($tail);
		
		
		if ($this->indexoffset < 0)
			throw new swDBAerror('swDBA invalid indexoffset '.$this->indexoffset.'< 0');
	//	if ($this->indexoffset > filesize($path))
	//		throw new Exception('swDBA invalid indexoffset '.$this->indexoffset.' >'.filesize($path));
		
		fseek($this->handle,$this->indexoffset);
		$found = false;
		do 
		{
			$line = fgets($this->handle);
			if (strstr($line,TAB)) 
			{
				$fields = explode(TAB,$line);
				$this->index[$fields[0]] = intval($fields[1]);
			}
			else
				$found = true;
		} while(!$found);
		
		$this->keys = array_keys($this->index);
		$this->keycursor = 0;
		
		// print_r($this->index);

	}
	
	function firstKey()
	{	
		
		if (!count($this->keys)) return false;
			
		$this->keycursor = 0;
		
		return $this->keys[$this->keycursor];
	}
	
	function nextKey()
	{
		$this->keycursor++;
		
		//echotime($this->keycursor);
		
		//echotime(count($this->keys));
		
		if (count($this->keys) <= $this->keycursor+1) return false; 
		
		//echotime('.');
		
		return $this->keys[$this->keycursor];
	}
	
	function exists($key)
	{
	 	if (isset($this->journal[$key]))
	 	{
		 	if ($this->journal[$key] === FALSE) return false;
		 	else return true;
	 	}
	 	return (isset($this->index[$key]));
	 }

	
	function fetch($key, $includejournal = true)
	{
		// print_r($this->journal);
		
		if ($includejournal && isset($this->journal[$key])) return $this->journal[$key];
		
		if (!isset($this->index[$key])) return false;
		
		$offset = $this->index[$key];

		if ($offset < 0)
			throw new swDBAerror('swDBA invalid offset '.$offset);
	//	if ($offset > filesize($path))
	//		throw new Exception('swDBA invalid offset '.$offset);
			
		fseek($this->handle, $offset);
		$line = fgets($this->handle);
		if (strstr($line,TAB)) 
		{
			$fields = explode(TAB,$line);
			if ($fields[0] != $key)
			{
				print_r($this->index);
				throw new swDBAerror('swDBA fetch invalid line ('.$line.') for key ('.$key.') at offset '.$offset);
			}
			$size = intval($fields[1]);
		}
		else
		{
			throw new swDBAerror('swDBA fetch invalid line ('.$line.') '.$this->path.' '. ftell($this->handle));
		}
		
		$value = fread($this->handle, $size);
		return $value;		
	}

	function delete($key)
	{
	 	if ($this->mode == 'r' || $this->mode == 'rt') 
	 		throw new swDBAerror('swDBA delete write not allowed');

	 	if (!in_array($key,$this->keys)) return false;
	 	
	 	$this->journal[$key] = FALSE;
	 	$this->touched = true;
	 	
	 	$this->keys = array_diff($this->keys, array($key));
	 	
	 	return true;
	 	
	 	// write is delayed to sync to make it once for many deletes
	 	
	 	// delete must be written (as content of length -1) to be able to reconstruct the index from content	 	
	}
	
	function replace($key, $value)
	{
	 	if ($this->mode == 'r' || $this->mode == 'rt') 
	 		throw new swDBAerror('swDBA replace write not allowed');
	 	
	 	if (strstr($key,TAB)) throw new swDBAerror('swDBA replace invalid key with tab '.$key);
	 	if (strstr($key,PHP_EOL)) throw new swDBAerror('swDBA replace invalid key with newline '.$key);
	 	
	 	$v = $this->fetch($key);
	 	if (!strcmp($v,$value)) return true; // 0 = binary equal 
	 	
	 	/*echotime($key);
	 	echotime($value);
	 	echotime($v);*/
	 	
	 	$this->journal[$key] = $value;
	 	$this->touched = true;
	 	
	 	if (!in_array($key, $this->keys)) $this->keys[] = $key;
	 	
	 	return true;
	 	
	 	// write is delayed to sync to make it once for many deletes
	}
	
	function sync()
	{
	 	if (!$this->touched) return true;
	 	
	 	global $swRoot;
		
	 	echotime('dba_sync '.str_replace($swRoot,'',$this->path).' '.count($this->journal));
	 	
	 	
	 	
	 	if ($this->mode == 'r' || $this->mode == 'rt') 
	 		throw new swDBAerror('swDBA replace sync not allowed');
	 		
	 		
	 	//swSemaphoreSignal($this->path); 
	 	
	 	$locktimeout = 5;
	 	$i = 0;
	 	$lock = false;
	 	while ($i<$locktimeout)
	 	{
		 	if (flock($this->handle,LOCK_EX)) { $lock = true; break; }
		 	else echotime('dba_sync wait '.$this->path);
	 	}
	 	if (!$lock)
	 	{
		 	echotime('dba_sync failed '.$this->path);
		 	return false;
	 	}
	 	
	 	
	 	
	 	// before changing, we need to read the Index again, because someone else may have changed the file
	 	$this->readIndex();
	 	
	 	fseek($this->handle,$this->indexoffset);
	 	
	 	// print_r($this->journal);
	 	
	 	$stream = array();
	 	$streamoffset = 0;
	 	
	 	// echotime('dba_sync start');
	 	$f = true;
	 	
	 	foreach($this->journal as $k=>$v)
	 	{
		 	if ($f) echotime('dba_sync '.$k);
		 	$f = false;
		 	
		 	if ($v === false) // delete
		 	{
			 	// if it is already deleted do nothing
			 	if (!isset($this->index[$k])) continue;
			 	
			 	$size = -1;
			 	$content = '';
			 	
			 	unset($this->index[$k]);
			 	
		 	}
		 	else
		 	{
			 	$p = ftell($this->handle);
			 	$current = $this->fetch($k, false);  // fetch changes position!
			 	fseek($this->handle, $p);
			 	if ($v == $current) continue;
			 	
			 	$size = strlen($v);
			 	$content = $v;
			 	
			 	$this->index[$k] = $this->indexoffset + $streamoffset; 
		 	}
		 	
		 	$s = $k.TAB.$size.PHP_EOL.$content.PHP_EOL;
		 	$stream [] = $s;
		 	$streamoffset += strlen($s);		 	
	 	}
	 	
	 	//echotime('dba_sync write');
	 	
	 	foreach($stream as $s)
	 		fwrite($this->handle,$s);
	 	
	 	$this->journal = array();
	 	
	 	$this->indexoffset = ftell($this->handle);
		
		ksort($this->index);
		
		foreach($this->index as $k=>$v)
		{
			fwrite($this->handle, $k.TAB.$v.PHP_EOL);
		}

		fwrite($this->handle, 'indexoffset'.PHP_EOL.$this->indexoffset.PHP_EOL);
		
		fflush($this->handle);
		
		flock($this->handle,LOCK_UN);
		
		//swSemaphoreRelease($this->path); 
		
		$this->keys = array_keys($this->index);
		
		$this->touched = false;
		
		echotime('sync end');
		
		return true;
	}

	function close()
	{
		$this->sync();
	}
	
	function __destruct()
	{
		$this->close();
	}
	
	// stub
	function index()
	{
		// rebuild index from content, if 
	}
	
	function valid()
	{
		// valid if second last line is "indexoffset" and the all lines from indexoffset on are tab separated
	}
	
	function optimize()
	{
		// rewrite from index, removing the overhead of deleted values
	}
	
	function listDatabases()
	{
		// list all open DB, needs a global array
	}
	
	function count()
	{
		return count($this->keys);
	}
	

		
}

class swDBAerror extends Exception
{
	function notify()
	{
		global $username;
		global $name;
		global $action;
		global $query;
		global $lang;
		$error = $this->getMessage();
		$message = $this->getFile().' '.$this->getLine();
		
		swLog($username,$name,$action,$query,$lang,'','',$error,'',$message,'');
	}
}


// unittest
/*

echo '<pre>';

$t = microtime();

function RandomString($n,$eol=false)
{
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($eol)
        $characters = '012345 789abcdef hijklmnopq stuvwxyzABCDEFG IJKLMNOPQRSTUVWX '.PHP_EOL;
        $randstring = '';
        for ($i = 0; $i < $n; $i++) {
            $randstring .= $characters[rand(0, strlen($characters)-1)];
        }
        return $randstring;
}

if (file_exists($swRoot.'/site/indexes/test.db'))
$testdb = new swDBA($swRoot.'/site/indexes/test.db','wd');
else
$testdb = new swDBA($swRoot.'/site/indexes/test.db','cd');

echo $testdb->mode.PHP_EOL;



for($i=1;$i<5;$i++)
{
	$k = RandomString(5,false); 
	$v = RandomString(80,true);
	$testdb->replace($k,$v);
}



$testdb->close();

echo file_get_contents($swRoot.'/site/indexes/test.db');
echo (microtime()-$t).PHP_EOL;
// $testdb = new swDBA($swRoot.'/site/indexes/test.db','wd');
$key = $testdb->firstkey();
echo $testdb->fetch($key).PHP_EOL;
$testdb->close();
echo '.'.PHP_EOL;
$testdb = new swDBA($swRoot.'/site/indexes/test.db','wd');

$i = $testdb->index;


exit;
*/

  



	
?>