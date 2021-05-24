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
		
		// echo "<nowiki><pre>NOWIKI: ".$s."</pre></nowiki>";
		
		$s = str_replace('{{}}','',$s);
		
		if (substr($s,0,strlen("#DISPLAYNAME"))=="#DISPLAYNAME")
		{
			$pos = strpos($s,"\n");
			if ($pos==0)
				$s = '';
			else
				$s = substr($s,$pos+1);
		}
				
		if (substr($s,0,strlen("#CACHE"))=="#CACHE")
		{
			$pos = strpos($s,"\n");
			if ($pos==0)
				$s = '';
			else
				$s = substr($s,$pos+1);

		}
		
		// remove templates and images
		$s = preg_replace("/\{\{(.+?)\}\}/u", "", $s);	
		$s = preg_replace("/\[\[Image:(.+?)\]\]/u", "", $s);
		$s = preg_replace("/\[\[Media:(.+?)\]\]/u", "", $s);
		$s = preg_replace("/\[\[Category:(.+?)\]\]/u", "", $s);
		
		// remove language links
		global $swLanguages;
		foreach($swLanguages as $l)
			$s = preg_replace("/\[\[".$l.":(.+?)\]\]/u", "", $s);
		
		
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
       	
       	
       	// remove templates
		$s = preg_replace("/{{.+?}}/", "", $s);
       	
       	// remove tags
		$s = preg_replace("/<\/?code.+?>/", "", $s);

		
		$wiki->parsedContent = $s;
		
	}

}
// normally not added
// $parsers["nowiki"] = new swStyleParser;



?>