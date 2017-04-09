<?php

if (!defined("SOFAWIKI")) die("invalid acces");


class swFieldsParser extends swParser
{

	
	function info()
	{
	 	return "Cleans up internal fields";
	}
	
	function dowork(&$wiki)
	{
		$s = $wiki->parsedContent;
		// internal links
		if (strpos($s,"::")===FALSE) return; 
		
		preg_match_all("@\[\[([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $v)
		{
			
			if (strpos($v[0],"::")>0)
			{
				
				// if the field is alone in the line and there is no template, remove the newline also
				
				$v0 =$v[0]."\n\r"; 
				$s = str_replace($v0,"",$s);
				$v0 =$v[0]."\r\n"; 
				$s = str_replace($v0,"",$s);
				$v0 =$v[0]."\n"; 
				$s = str_replace($v0,"",$s);
				$v0 =$v[0]."\r"; 
				$s = str_replace($v0,"",$s);
				
				$s = str_replace($v[0],'',$s);
				
			}
			
		}
		$wiki->parsedContent = $s;
		
	}

}

$swParsers["fields"] = new swFieldsParser;


?>