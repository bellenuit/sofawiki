<?php


// dba replacement functions as dba is not available on all PHP installations

define("CRLF", "\r\n");
define("LF", "\n");
define("TAB", "\t");

$swDBApool = array();
$swDBAhandler = 'sqlite3';

if (function_exists('dba_handlers'))
{
	if (!in_array('db4', dba_handlers())) $swDBAhandler = 'flatfile';
}
else
	$swDBAhandler = 'sqlite3';


function swDBA_open($file, $mode, $handler)
{
	global $swDBAhandler;
	
	if ($swDBAhandler == 'sqlite3')
	{
		try
		{
			return new swDBA($file,$mode);
		}
		catch (swDBAerror $err)
		{
			echotime('db busy');
			$err->notify();
			return;
		}
	}
	
	return @dba_open($file,$mode,$swDBAhandler);
}

function swDBA_firstkey($db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3') return $db->firstkey();
		
	return @dba_firstkey($db);
}

function swDBA_nextkey($db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3') return $db->nextkey();

	return @dba_nextkey($db);
}

function swDBA_exists($key,$db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
		return $db->exists($key);
	
	return @dba_exists($key,$db);
}


function swDBA_fetch($key,$db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
	{
		try
		{
			return $db->fetch($key);
		}
		catch (swDBAerror $err)
		{
			$err->notify();			
			return false;
		}
	}	
	
	return @dba_fetch($key,$db);
}

function swDBA_replace($key,$value,$db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
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
		
	return @dba_replace($key,$value,$db);
}

function swDBA_sync($db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
	{
		try
		{
			return $db->sync();
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			return false;
		}
	}
	return @dba_sync($db);
}

function swDBA_delete($key,$db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
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
	return @dba_delete($key,$db);
}

function swDBA_close($db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
	{
		try
		{
			return $db->close();
		}
		catch (swDBAerror $err)
		{
			$err->notify();
			return false;
		}

	}	
	return @dba_close($db);
}


function swDBA_count($db)
{
	global $swDBAhandler;
	if ($swDBAhandler == 'sqlite3')
		return $db->count();
	
	return 0; // there is no native function
}



class swDBA
{
	// using sqlite3 as container
	
	var $db;
	var $rows;
	var $journal;
	var $path;
	
	function __construct($path)
	{
		global $swRoot;
		$this->db = new SQLite3($path);
		$this->path = str_replace($swRoot,'',$path);
		if (!$this->db->busyTimeout(5000))  // sql tries to connect during 5000ms
		{
			throw new swDBAerror('swdba is busy');
		}
			
		$this->db->exec('PRAGMA journal_mode = DELETE'); // we do not use rollback
		if (!@$this->db->exec('CREATE TABLE IF NOT EXISTS kv (k text unique, v text)'))
		{
			throw new swDBAerror('swdba create table error '.$this->db->lastErrorMsg());
		}
		$this->journal = array();
	}
	
		
	function firstKey()
	{	
		
		$this->sync();
		
		$statement = $this->db->prepare('SELECT k FROM kv ORDER BY k');
		$this->rows = $statement->execute();

		if ($this->db->lastErrorCode())
		{
			throw new swDBAerror('swdba firstkey error '.$this->db->lastErrorMsg());
		}
		
		if ($r = $this->rows->fetchArray(SQLITE3_ASSOC))
		{
			//print_r($r);
			return $r['k'];
		}
		else
		{
			//echo "no fetch";
			return false;
		}
			
	}
	
	function nextKey()
	{
		
		if (!$this->rows)
		{
			throw new swDBAerror('swdba nextkey without firstkey error');
		}
		
		if ($r = $this->rows->fetchArray(SQLITE3_ASSOC))
		{
			return $r['k'];
		}
		else
			return false;
		
	}
	
	function exists($key)
	{
		if (isset($this->journal[$key])) 
		{
			if ($this->journal[$key] === false) return false; else return true;
		} 
		
		$statement = $this->db->prepare('SELECT count(k) as c FROM kv WHERE k = :key');
		$statement->bindValue(':key', $key);
		$result = $statement->execute();
		
		
		if ($this->db->lastErrorCode())
		{
			throw new swDBAerror('swdba exists error '.$this->db->lastErrorMsg());
		}
		
		$row = $result->fetchArray();
		
		if ($row['c']) return true;
		else return false;
		
	}
	
	function fetch($key)
	{
		if (isset($this->journal[$key]))
		{
			return $this->journal[$key];
		} 
		
		$statement = $this->db->prepare('SELECT v FROM kv WHERE k = :key');
		$statement->bindValue(':key', $key);
		$result = $statement->execute();
		
		if ($this->db->lastErrorCode())
		{
			throw new swDBAerror('swdba fetch error '.$this->db->lastErrorMsg());
		}
		
		$row = $result->fetchArray();
		return $row['v'];
	}

	function delete($key)
	{
		$this->journal[$key] = false;
	 	
	}
	
	function replace($key, $value)
	{
	 	$test = $this->fetch($key);
	 	if ($test == $value) return;
	 	
	 	$this->journal[$key] = $value;
	 	
		if (count($this->journal) >= 1000) $this->sync();
		
		return true;
	}
	
	function sync()
	{
		if (!count($this->journal)) return;
		
		
		
		echotime('sync '.count($this->journal));
		
		$lines = array();
		$lines[] = "PRAGMA synchronous=OFF; ";
		
		foreach($this->journal as $k=>$v)
		{
			$k = $this->db->escapeString($k);
			$v = $this->db->escapeString($v);
			if ($v === FALSE)
				$lines[]= "DELETE FROM kv WHERE k = '$k' ;";
			else
				$lines[]= "REPLACE INTO kv (k,v) VALUES ('$k','$v');";			
		}
		$q = join(PHP_EOL,$lines);
		$lines[] = "PRAGMA synchronous=FULL; ";
		
	
		
		$this->db->exec($q);

		
		if ($this->db->lastErrorCode())
		{
			throw new swDBAerror('swdba sync error '.$this->db->lastErrorMsg());
		}
		
		
		$this->journal = array();
		
		
		
		
	}
	
	function close()
	{
		$this->sync();
		// $this->db->close(); DO NOT CLOSE
	}
	
	function __destruct()
	{
		$this->sync();
		
		if (rand(0, 100) > 90 || true) $this->db->exec('PRAGMA optimize');
		
		$this->db->close();
	}	
	
	function listDatabases()
	{
		// list all open DB, needs a global array
	}
	
	function count()
	{
		$statement = $this->db->prepare('SELECT count(k) as c FROM kv');
		$result = $statement->execute();
		
		if ($this->db->lastErrorCode())
		{
			throw new swDBAerror('swdba fetch error '.$this->db->lastErrorMsg());
		}
		
		$row = $result->fetchArray();
		
		return $row['c'];
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




  



	
?>