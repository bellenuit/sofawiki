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
		global $action;
		global $lang;
		if (in_array($action,array('delete','edit','editmulti','protect','rename','unprotect'))) return;
	
		$s = $wiki->parsedContent;
		$key = "#REDIRECT"; 
		
		if (substr($s,0,strlen($key))==$key && stristr($s,'[[') && stristr($s,']]'))
		{
			
			$myname = $wiki->namewithoutlanguage();
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
 				
 				$link = $linkwiki->link('',$lang);
 				if (!stristr($link,'?')) $link .= '?';
 				$link .= '&redirectedfrom='.swNameURL($name);
 				global $swBaseHrefFolder;
 				if (isset($swBaseHrefFolder))
 				{
 					if (substr($link,0,2)=='./') $link = substr($link,2); // may be relative link
 					$link = $swBaseHrefFolder.$link;
 				}
 				
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