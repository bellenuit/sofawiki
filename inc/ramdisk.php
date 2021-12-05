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
			$swRamDiskDB = swDBA_open($swRamDiskDBpath, 'c', 'db4');
			echotime('new db');
		}
		else
		{
			$swRamDiskDB = swDBA_open($swRamDiskDBpath, 'rdt', 'db4');
			echotime('open db');  // if it fails, it is false
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
	
	// echotime("swFileGet($path)");
	
	if (isset($swRamdiskPath) && $swRamdiskPath != '')
	{
		if ($swRamdiskPath=='memcache')
		{
			if (!$swMemcache) swInitRamdisk();
			if ($swRamDiskDB && stristr($path,$swRamDiskDBfilter))
			{
				$v = @$swMemcache->get($path);
				if ($v) 
				{  
					echotime('memcache sucess '.floor(strlen($s)/1024).' KB '.$path);
					return $v; 		
				}
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
				
				$pos = stripos($path,$swRamDiskDBfilter) + strlen($swRamDiskDBfilter);
				$path2 = substr($path,$pos);
				
				
				$v = swDBA_fetch($path2,$swRamDiskDB);
				if ($v)
				{
					return $v;
				}
				$s = file_get_contents($path);
				
				// only keep short files
				if (strlen($s) <= 10000)
					$swRamDiskJobs[$path2] = $s;
				
				if (isset($swRamDiskJobs) && count($swRamDiskJobs) % 100 == 0)
				{
					
					swUpdateRamDiskDB();
				}
				
				
				return $s;
			}
			$s = file_get_contents($path);
			// echotime('db not '.floor(strlen($s)/1024).' KB '.$path);
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
			
			if (isset($swRamDiskDB) and $swRamDiskDB) swDBA_close($swRamDiskDB);

			$swRamDiskDB = swDBA_open($swRamDiskDBpath, 'wdt', 'db4');
			if ($swRamDiskDB)
			{
				swDBA_delete($path,$swRamDiskDB);
				echotime('delete db ok');
				
			}
			else			
			{
				echotime('delete db failed '.$path);
			}
			swDBA_close($swRamDiskDB);
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
	global $swRamDiskDBfilter;
	global $db;
	global $swOvertime;
	
	$k = rand(0,$db->lastrevision/1000)*1000; //echo $k;
	
	global $swMemoryLimit;
	
	for($i=$k;$i<$k+2000;$i++)
	{ 
		if (memory_get_usage()>$swMemoryLimit) break;
		if ($db->indexedbitmap->getbit($i) && !$db->currentbitmap->getbit($i)) continue;
		$path = swGetPath($i);
		if (!file_exists($path)) continue;
		if (filesize($path)>4096) continue;
		
		$s = file_get_contents($path);
		$pos = stripos($path,$swRamDiskDBfilter) + strlen($swRamDiskDBfilter);
		$path2 = substr($path,$pos);
		$swRamDiskJobs[$path2] = $s;
		
		if (count($swRamDiskJobs)>500) swUpdateRamDiskDB();
				
	}
	if (count($swRamDiskJobs)) $swOvertime = true;
	swUpdateRamDiskDB();
	return true;

}

function swUpdateRamDiskDB()
{
	global $swRamDiskDB;
	global $swRamDiskDBpath;
	global $swRamDiskJobs;
	
	if (!@count($swRamDiskJobs)) return;
	
	if ($swRamDiskDB) swDBA_close($swRamDiskDB);
	$swRamDiskDB = swDBA_open($swRamDiskDBpath, 'wdt', 'db4');
	if ($swRamDiskDB)
	{
		foreach($swRamDiskJobs as $k=>$v)
		{	swDBA_replace($k,$v,$swRamDiskDB);
			//echotime('insert db '.$k);
		}
		$swRamDiskJobs = array();				
	}
	else
	{
		echotime('insert db failed');
	}
	swDBA_close($swRamDiskDB);
	swInitRamdisk();

}




?>