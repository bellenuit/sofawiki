<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swNoWikiParser extends swParser
{
	var $preservelinks = false;
	
	function info()
	{
	 	return "Removes all wikitext";
	}


	function dowork(&$wiki)
	{

		$s = $wiki->parsedContent;
		
		if (substr($s,0,strlen("#DISPLAYNAME"))=="#DISPLAYNAME")
		{
			$pos = strpos($s,"\n");
			$s = substr($s,$pos+1);
		}
		
		// remove templates and images
		$s = preg_replace("/\{{([-\.\w\/\: \|,\'\p{Latin}\p{N}]+)\}}/u", "", $s);
		$s = preg_replace("/\[\[Image:([-\.\w\/\: \|,\'\p{Latin}\p{N}]+)\]\]/u", "", $s);
		$s = preg_replace("/\[\[Media:([-\.\w\/\: \|,\'\p{Latin}\p{N}]+)\]\]/u", "", $s);
		$s = preg_replace("/\[\[Category:([-\.\w\/\: \|,\'\p{Latin}\p{N}]+)\]\]/u", "", $s);
		
		global $swLanguages;
		foreach ($swLanguages as $v)
		{
			$s = preg_replace("/\[\[".$v.":([-\.\w\/\: \|,\'\p{Latin}\p{N}]+)\]\]/u", "", $s);
		}
		
		// remove table code
		$s = preg_replace("/^\{\|(.*)$/", "", $s);
		$s = preg_replace("/^\|\-(.*)$/", "", $s);
		$s = preg_replace("/^\|\}(.*)$/", "", $s);
		$s = preg_replace("/^\| /", "", $s);
		$s = preg_replace("/\|\|/", "", $s);
		
		// remove fields 
		$s = preg_replace("/\[\[([^\]]*)::([^\]]*)\]\]/u", "", $s);
		
		// links
		if (!$this->preservelinks)
		{
     		$s = preg_replace("/\[\[([^\]]*)\|([^\]]*)\]\]/u", "$2", $s);
     		$s = str_replace('[', "", $s);
     		$s = str_replace(']', "", $s);
     		$s = str_replace('{', "", $s);
     		$s = str_replace('}', "", $s);
     	}

		// remove linefeeds \n
		$s = str_replace("\r"," ",$s);
		$s = str_replace("\n"," ",$s);
	
		// remove headers 
		
		$s = preg_replace("/====([^=]*)====/u", " $1 ", $s);
		$s = preg_replace("/===([^=]*)===/u", " $1 ", $s);
		$s = preg_replace("/==([^=]*)==/u", " $1 ", $s);

     	
     	// list
     	$s = str_replace('****', "", $s);
     	$s = str_replace('***', "", $s);
     	$s = str_replace('**', "", $s);
     	$s = str_replace('*', "", $s);
     	
				
		// bold and italics
        $s = str_replace("'''", "", $s);
        $s = str_replace("''", "", $s);
       // $s = str_replace("'", "", $s); french apostrophe must stay!
        
        // hr
       	$s = str_replace("----", " ", $s);

		
		$wiki->parsedContent = $s;
		
	}

}
// normally not added
// $parsers["nowiki"] = new swStyleParser;



?>