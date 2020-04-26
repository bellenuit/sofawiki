<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swDisplaynameParser extends swParser
{
	function info()
	{
	 	return "Handles displayname keyword #DISPLAYNAME";
	}

	
	
	function dowork(&$wiki)
	{
	
		$s = $wiki->parsedContent;
		$key = "#DISPLAYNAME"; 
		
		
		if (substr($s,0,strlen($key))==$key)
		{
			
			
			$pos = strpos($s," ") + strlen(" ");
			$pos2 = strpos($s."\n","\n",$pos);
			
			$wiki->displayname = substr($s,$pos,$pos2-$pos);
			$s = substr($s,$pos2);
			$s = ltrim($s); 
			$wiki->parsedContent = $s;
		}
		else
		{
			
		}
		
	}

}

$swParsers["displayname"] = new swDisplaynameParser;


?>