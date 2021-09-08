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
		
		
		$s = $wiki->parsedContent;
		$key = "#CACHE";  
		
		//echotime('cacheparser2');

		if (substr($s,0,strlen($key))==$key)
		{
			
			   
			$pos = strpos($s," ") + strlen(" ");
			$pos2 = strpos($s."\n","\n",$pos);
			
			
			$expire = intval(substr($s,$pos,$pos2-$pos));
			$now = time();
			
			$cachename = 'cache'.$wiki->name.'/'.$lang;
			@mkdir($swRoot.'/site/cache/');
			$path = $swRoot.'/site/cache/'.md5($cachename);
			
			//echotime('keyword cache '.$path);
			//echotime(filemtime($path) .' '. $expire .' '.$now);
			
			if (file_exists($path) && filemtime($path) + $expire > $now && $action == 'view' && !(isset($_REQUEST['cacherefresh'])))
			{
				$s = file_get_contents($path);
				$wiki->parsedContent = $s;
				
				global $swEditMenus;
				global $user;
				if ($user->hasright('modify', $wiki->namewithoutlanguage()))
					$swEditMenus['cacherefresh'] = '<a href="index.php?action=view&cacherefresh=1&name='.swNameURL($wiki->namewithoutlanguage()).'&lang='.$lang.'" rel="nofollow">'.swSystemMessage('cacherefresh',$lang).'</a>';

				
				echotime('use cached');
				
				// mute other parsers
				
				return 2;
			}
			else
			{
				// if (isset($_REQUEST['cacherefresh']) && file_exists($path)) { unlink($path); echotime('cache deleted'); }
				
				$wiki->parsedContent = trim(substr($s,$pos2));
				
				
				if ($action=='view')
					return 1;
				else
					return 0;
				// add put cache to parsers
				
								
				
			}
		}
		return 0;
		
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