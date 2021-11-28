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
		return new swDBA($file,$mode);
	
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
		return $db->fetch($key);
		
	
	return dba_fetch($key,$db);
}

function swDBA_replace($key,$value,$db)
{
	
	global $testmode;
	if ($testmode)
		return $db->replace($key,$value);
	
	return dba_replace($key,$value,$db);
}

function swDBA_sync($db)
{
	global $testmode;
	if ($testmode)
		return $db->sync();

	return dba_sync($db);
}

function swDBA_delete($key,$db)
{
	
	global $testmode;
	if ($testmode)
		return $db->delete($key);
	

	return dba_delete($key,$db);
}

function swDBA_close($db)
{
	global $testmode;
	if ($testmode)
		return $db->close();
	
	return dba_close($db);
}

function swDBA_exists($key,$db)
{
	global $testmode;
	if ($testmode)
		return $db->exists($key);
	
	return dba_exists($key,$db);
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
		echotime('dba_open '.$mode.' '.$path);
		switch($mode)
		{
			case 'w':
			case 'wd':
			case 'wdt': if (!file_exists($path)) throw new Exception('swDBA file does not exist '.$path);
						$this->handle = fopen($path,'c+');
						break;
			case 'c':
			case 'cd':  
			case 'cdt': if (file_exists($path)) throw new Exception('swDBA file does already exist '.$path);
						$this->handle = fopen($path,'c+');
						break;
			case 'r':
			case 'rd':
			case 'rdt': if (!file_exists($path)) throw new Exception('swDBA file does not exist '.$path);
						$this->handle = fopen($path,'r');
						break;
			default:  throw new Exception('swDBA unknowm mode '.$mode);	
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
			throw new Exception('swDBA invalid indexoffset '.$this->indexoffset.'< 0');
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
		
		$this->keys = array_keys([$this->index]);
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
		if (!count($this->keys)) return false;
		if (count($this->keys) <= $this->keycursor+1) return false; // are we one off? check
		
		$this->keycursor++;
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
			throw new Exception('swDBA invalid offset '.$offset);
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
				throw new Exception('swDBA fetch invalid line ('.$line.') for key ('.$key.') at offset '.$offset);
			}
			$size = intval($fields[1]);
		}
		else
		{
			throw new Exception('swDBA fetch invalid line ('.$line.') '.$this->path.' '. ftell($this->handle));
		}
		
		$value = fread($this->handle, $size);
		return $value;		
	}

	function delete($key)
	{
	 	if ($this->mode == 'r' || $this->mode == 'rt') 
	 		throw new Exception('swDBA delete write not allowed');

	 	if (!in_array($key,$this->keys)) return false;
	 	
	 	$this->journal[$key] = FALSE;
	 	$this->touched = true;
	 	
	 $this->keys = array_diff($this->keys, array($key));
	 	
	 	// write is delayed to sync to make it once for many deletes
	 	
	 	// delete must be written (as content of length -1) to be able to reconstruct the index from content	 	
	}
	
	function replace($key, $value)
	{
	 	if ($this->mode == 'r' || $this->mode == 'rt') 
	 		throw new Exception('swDBA replace write not allowed');
	 	
	 	if (strstr($key,TAB)) throw new Exception('swDBA replace invalid key with tab '.$key);
	 	if (strstr($key,PHP_EOL)) throw new Exception('swDBA replace invalid key with newline '.$key);
	 	
	 	$this->journal[$key] = $value;
	 	$this->touched = true;
	 	
	 	if (!in_array($key, $this->keys)) $this->keys[] = $key;
	 	
	 	// write is delayed to sync to make it once for many deletes
	}
	
	function sync()
	{
	 	if (!$this->touched) return;
	 	
	 	echotime('dba_sync '.$this->path);
	 	
	 	
	 	
	 	if ($this->mode == 'r' || $this->mode == 'rt') 
	 		throw new Exception('swDBA replace sync not allowed');
	 		
	 		
	 	swSemaphoreSignal($this->path); 
	 	
	 	// before changing, we need to read the Index again, because someone else may have changed the file
	 	$this->readIndex();
	 	
	 	fseek($this->handle,$this->indexoffset);
	 	
	 	// print_r($this->journal);
	 	
	 	$stream = array();
	 	$streamoffset = 0;
	 	
	 	foreach($this->journal as $k=>$v)
	 	{
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
		
		swSemaphoreRelease($this->path); 
		
		$this->keys = array_keys($this->index);
		
		$this->touched = false;
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
	
	function list()
	{
		// list all open DB, needs a global array
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