<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swCacheParser extends swParser
{
	function info()
	{
	 	return "Handles cache keyword #CACHE";
	}

	function dowork(&$wiki)
	{
	
		//echotime('cacheparser');

		global $swError;
		global $swOvertime;
		global $swRoot;
		global $action;;
		global $lang;
		
		if ($swError || $swOvertime) return;
		if ($action != 'view') return;
		
		$s = $wiki->parsedContent;
		$key = "#CACHE";  
		
		//echotime('cacheparser2');

		if (substr($s,0,strlen($key))==$key)
		{
			
			   
			$pos = strpos($s," ") + strlen(" ");
			$pos2 = strpos($s."\n","\n",$pos);
			
			
			$expire = substr($s,$pos,$pos2-$pos);
			$now = time();
			
			$cachename = 'cache'.$wiki->name.'/'.$lang;
			@mkdir($swRoot.'/site/cache/');
			$path = $swRoot.'/site/cache/'.md5($cachename);
			
			//echotime('keyword cache '.$path);
			//echotime(filemtime($path) .' '. $expire .' '.$now);
			
			if (file_exists($path) && filemtime($path) + $expire > $now)
			{
				$s = file_get_contents($path);
				$wiki->parsedContent = $s;
				echotime('use cached');
				
				// mute other parsers
				
				return true;
			}
			else
			{
				$wiki->parsedContent = trim(substr($s,$pos2));
				
				
				// add put cache to parsers
				
								
				
			}
		}
		
	}

}

$swParsers["cache"] = new swCacheParser;

class swPutCacheParser extends swParser
{
	function info()
	{
	 	return "Handles cache keyword #CACHE";
	}

	function dowork(&$wiki)
	{
		//echotime('putcacheparser');

		global $swError;
		global $swOvertime;
		global $swRoot;
		global $lang;
		global $action;
		
		if ($swError || $swOvertime) return;
		if ($action != 'view') return;
		
		//echotime('putcacheparser2');
		$cachename = 'cache'.$wiki->name.'/'.$lang;
		@mkdir($swRoot.'/site/cache/');
		$path = $swRoot.'/site/cache/'.md5($cachename);
		
		file_put_contents($path,$wiki->parsedContent);
		
		echotime('put cache');
		
	}

}

//$swParsers["putcache"] = new swPutCacheParser;



?>