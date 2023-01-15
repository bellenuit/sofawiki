<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swTuple
{
	private $pfields = array();
	private $phash;
	
	function __construct($list, $useServer = false)
	{
		$keys = array();
		$values = array();
		$this->pfields = array_clone($list);
		if (is_array($list))
			$keys = array_keys($list);
		sort($keys);
		foreach($keys as $k)
		{
			$values[$k] = $list[$k]; 
		}
		$s = join(PHP_EOL,$values);
		
		$this->phash = md5($s);
		
		if (strlen($s) > 1000 || $useServer)
		{
			if (swOpenTupleServer())
			{
				if (swTupleServerSet($this->phash,serialize($list))) 
				{
					$this->pfields = null;
				}
						
			}
		}
	}
	
	function arity()
	{
		return count($this->pfields);
	}
	
	function fields()
	{
		if(!$this->pfields)
		{
			if ($list = unserialize(swTupleServerGet($this->phash)))
			{
				return $list;
			}
		}
		
		return array_clone($this->pfields);
	}
	
	function hash()
	{
		return $this->phash;
	}
	
	function hasKey($k)
	{
		if(!$this->pfields)
		{
			if ($list = unserialize(swTupleServerGet($this->phash))) $this->pfields = $list;
		}
		
		return array_key_exists($k, $this->pfields);
		
	}

	function hasValues()
	{
		if(!$this->pfields)
		{
			if ($list = unserialize(swTupleServerGet($this->phash))) $this->pfields = $list;
		}
		
		foreach ($this->pfields as $k=>$v)
		{
			if ($v != "") return true;
		}
	}

	
	function sameFamily($t)
	{
		if ($this->arity() != $t->arity()) {  return false; }
		
		if(!$this->pfields)
		{
			if ($list = unserialize(swTupleServerGet($this->phash))) $this->pfields = $list;
		}		
		
		foreach($this->pfields as $k=>$e)
		{
			if (!array_key_exists($k, $t->pfields)) 
			{
				//echo $k;	
				return false;
			}
		}
		return true;
	}
	function value($s)
	{
		
		if(!$this->pfields)
		{
			if ($list = unserialize(swTupleServerGet($this->phash))) $this->pfields = $list;
		}
		
		$result = @$this->pfields[$s];
		return $result;
	}
	
}


/**
 *  Opens the tuple server.
 */

function swOpenTupleServer()
{
	global $swUseTupleServer;
	global $swTupleServer;
	global $swRoot;
	
	if (!$swUseTupleServer) return false;
	
	if ($swTupleServer) return true;
	
	echotime('openserver');
	
	$path = $swRoot.'/site/indexes/tuples.db';
	
	if (filesize($path) > 48*1024*1024) swClearTupleServer();

	if (file_exists($path))
		$swTupleServer = swDbaOpen($path, 'wdt'); 
	else
		$swTupleServer = swDbaOpen($path, 'c');	
		
	if ($swTupleServer) return true;
	
	return false;
}

function swTupleServerGet($key)
{
	global $swTupleServer;
	if (!$swTupleServer) return false;
	return swDbaFetch($key,$swTupleServer);
	
}

function swTupleServerSet($key,$value)
{
	global $swTupleServer;
	if (!$swTupleServer) return false;
	return swDbaReplace($key,$value,$swTupleServer);	
}

function swClearTupleServer()
{
	global $swRoot;

	@unlink($swRoot.'/site/indexes/tuples.db'); 	 
}

