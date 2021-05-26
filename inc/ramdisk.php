<?php

/*

mounts a ramdisk and provides alternative path for cache files
is a pure reading cache, just duplicate files from harddisk

*/

if (!defined('SOFAWIKI')) die('invalid acces');

$swRamDiskDBpath = $swRoot.'/site/indexes/records.db';
$swRamDiskDBfilter = '/site/revisions/';
$swMemcache;

function swInitRamdisk()
{
	
	global $swRamdiskPath;
	global $swRamDiskDB;
	global $swRoot;
	global $swRamDiskDBpath;
	global $swMemcache;
	if (is_dir($swRamdiskPath)) 
	{
		 
	 $ramfree = sprintf('%0d',disk_free_space($swRamdiskPath)/1024/1024);
	 
	 echotime('ram free '. $ramfree. ' MB');
	
	 if ($ramfree < 16)
	 {
	 	// purge to prevent full ramdisk
	 	$files1 = glob($swRamdiskPath.'*.txt');
		shuffle($files);
		$files = array_slice($files,0,1000);
		foreach($files as $file) unlink($file);
		echotime('deleted 1000 files from ramdisk');
		$ramfree = sprintf('%0d',disk_free_space($swRamdiskPath)/1024/1024);
	 	echotime('ram free '. $ramfree. ' MB');
	 }
	 return;
	 
	}
	elseif ($swRamdiskPath=='memcache')
	{ 
		$swMemcache = new Memcache;
		if (!@$swMemcache->connect('localhost', 11211))
		{
			echotime('Could not connect do memcache');
		}

		$version = $swMemcache->getVersion();
		echotime('memcache server: '.$version);
		return;
	}
	elseif ($swRamdiskPath=='db')
	{
		if ($swRamDiskDB) return; 
		if (!file_exists($swRamDiskDBpath))
		{
			$swRamDiskDB = @dba_open($swRamDiskDBpath, 'c', 'db4');
			echotime('new berkeley db');
		}
		else
		{
			$swRamDiskDB = @dba_open($swRamDiskDBpath, 'rdt', 'db4');
			echotime('open berkeley db');  // if it fails, it is false
		}
		return;
	}
	
	echotime('no ramdisk');
	$swRamdiskPath = '';
		
}

function swFileGet($path)
{

	global $swRamdiskPath;
	global $swRamDiskDB;
	global $swRamDiskJobs;
	global $swRoot;
	global $swRamDiskDBpath; 
	global $swRamDiskDBfilter;
	global $swMemcache;
	
	if (isset($swRamdiskPath) && $swRamdiskPath != '')
	{
		
		if ($swRamdiskPath=='memcache')
		{
			if (!$swMemcache) swInitRamdisk();
			if ($swRamDiskDB && stristr($path,$swRamDiskDBfilter))
			{
				$v = @$swMemcache->get($path);
				if ($v) {  return $v; }
				$s = file_get_contents($path);
				$memcached->set($path,$s,60*60*24*30);
				return $s;			
			}
		}
		elseif ($swRamdiskPath=='db')
		{
			if (!$swRamDiskDB) swInitRamdisk();
			if ($swRamDiskDB && stristr($path,$swRamDiskDBfilter))
			{
				$v = @dba_fetch($path,$swRamDiskDB);
				if ($v) {  return $v; }
				
				$s = file_get_contents($path);
				$swRamDiskJobs[$path] = $s;
				
				if (count($swRamDiskJobs) % 50 == 0)
				{
					
					swUpdateRamDiskDB();
				}
				
				
				return $s;
			}
			$s = file_get_contents($path);
			return $s;
		}
		
		
		
		
		$hash = $swRamdiskPath.md5($path).'.txt';
		
		if (@file_exists($hash))
			return file_get_contents($hash);
		else
		{
			$s = file_get_contents($path);
			if ($s != '')
			{
				echotime('tocache '.basename($path));
				@file_put_contents($hash,$s);
			}
			return $s;
			
		}
		
	}
	else
		return file_get_contents($path);

}

function swUnlink($path)
{
	
	global $swRamdiskPath;
	global $swRoot;
	global $swRamDiskDBpath;
	global $swRamDiskDBfilter;
	global $swMemcache;
	
	if (file_exists($path))
		unlink($path);
	
	if (isset($swRamdiskPath) && $swRamdiskPath != '')
	{
		
		if ($swRamdiskPath=='memcached')
		{
			if (!stristr($path,$swRamDiskDBfilter)) return;
			if (!$swMemcache) return;
			@$swMemcache->delete($path);
			
		}
		elseif ($swRamdiskPath=='db')
		{
			if (!stristr($path,$swRamDiskDBfilter)) return;
			
			if (isset($swRamDiskDB) and $swRamDiskDB) dba_close($swRamDiskDB);

			$swRamDiskDB = @dba_open($swRamDiskDBpath, 'wdt', 'db4');
			if ($swRamDiskDB)
			{
				dba_delete($path,$swRamDiskDB);
				echotime('delete berkeley db ok');
				
			}
			else			
			{
				echotime('delete berkeley db failed '.$path);
			}
			@dba_close($swRamDiskDB);
			swInitRamdisk();
			return;
		}
		
		
		$hash = $swRamdiskPath.md5($path).'.txt';
		if (file_exists($hash))
			unlink($hash);
	}
	
}

function swIndexRamDiskDB()
{
	global $swRamDiskJobs;
	global $db;
	
	$s = microtime(true);
	$k = rand(1,$db->lastrevision);
	$d = 1;
	$c = @count($swRamDiskJobs);
	$list = array();
	
	for($i=0;$i<10000;$i++)
	{ 
		//echo $k.' ';
		$list[$k] = 1;
		$w = new swWiki;
		$w->revision = $k;
		$w->lookup();
		$c2 = @count($swRamDiskJobs); // check if last was empty
		if ($c2 > $c) { $d = 1; /*echo $k. ' ';*/} else $d *= 2; // slow step if empty, else open steps
		$d = $d % $db->lastrevision;
		$k = ($k + $d) % $db->lastrevision;
		while(array_key_exists($k,$list))
		{
			$k = rand(1,$db->lastrevision);
		}
		$c = $c2;
		$n = microtime(true);
		if ($n-$s > 10) $i = 10000;
	}
	swUpdateRamDiskDB();

}

function swUpdateRamDiskDB()
{
	global $swRamDiskDB;
	global $swRamDiskDBpath;
	global $swRamDiskJobs;
	
	if (!@count($swRamDiskJobs)) return;
	
	if ($swRamDiskDB) @dba_close($swRamDiskDB);
	$swRamDiskDB = @dba_open($swRamDiskDBpath, 'wdt', 'db4');
	if ($swRamDiskDB)
	{
		foreach($swRamDiskJobs as $k=>$v)
			@dba_replace($k,$v,$swRamDiskDB);
		echotime('insert berkeley db ok');	
		$swRamDiskJobs = array();				
	}
	else
	{
		echotime('insert berkeley db failed');
	}
	@dba_close($swRamDiskDB);
	swInitRamdisk();

}


if (isset($swRamdiskPath) && $swRamdiskPath != '')
	swInitRamdisk();


?>