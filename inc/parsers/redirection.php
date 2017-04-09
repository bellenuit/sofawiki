<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swRedirectionParser extends swParser
{
	function info()
	{
	 	return "Handles directives #REDIRECT";
	}

	
	
	function dowork(&$wiki)
	{
		
	
		$s = $wiki->parsedContent;
		$key = "#REDIRECT"; 
		
		if (substr($s,0,strlen($key))==$key && stristr($s,'[[') && stristr($s,']]'))
		{
			
			$myname = $wiki->name;
			$pos = strpos($s,"[[") + strlen("[[");
			$pos2 = strpos($s,"]]",$pos);
			
			global $name;
			global $action;
			if (swNameURL($wiki->name) == swNameURL($name) && $action=='view') 
			{
 				// is dangerous because redirection may also be parsed from other page
 				$linkwiki = new swWiki;
 				$linkwiki->name = substr($s,$pos,$pos2-$pos);
 			
 				header ('HTTP/1.1 301 Moved Permanently');
 				
 				$link = $linkwiki->link('');
 				global $swBaseHrefFolder;
 				if (isset($swBaseHrefFolder))
 				$link = $swBaseHrefFolder.$link;
 				
 				header ('Location: '.str_replace("&amp;","&",$link));
 			
 				exit;
 			}

			$wiki->name = substr($s,$pos,$pos2-$pos);
			$wiki->revision = NULL;
			$wiki->lookupName();
			$wiki->lookup();
			
			//echo $wiki->name;
					
			global $swRedirectedFrom;
			$swRedirectedFrom = $myname;
			$wiki->parsedContent = 	$wiki->content;
			$wiki->originalName = $wiki->name;
			//echo $wiki->originalName;
			
		}
		
		
		
		
	}

}

$swParsers["redirection"] = new swRedirectionParser;


?>