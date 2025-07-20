<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swTinyPSimported = false;
$rpnFontURLs = [];

class swTinyPS extends swFunction
{

	function info()
	{
	 	return "(code) renders PostScript";
	}

	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		$id = rand(0,1000);
		
		$result = '<nowiki><tiny-ps id="tinyps'.$id.'" width="576" height="324" format="svg" textmode="1">'.@$args[1].'</tiny-ps>';
		
		// get all tables
		
	    // $result .= '<script>rpnExtensions = "if (typeof rpnTables === \"undefined\") rpnTables = {}; rpnTables = JSON.stringify(rpnTables)";</script>'; 
	           
        
        global $swTinyPSimported;
        global $rpnFontURLs;
        if (!$swTinyPSimported)
 			$result .= '
<script src="inc/skins/tinyps112.js"></script>
<script src="inc/skins/tinyps-extensions.js"></script>
<script>rpnFontURLs = '.json_encode($rpnFontURLs).';</script>';
 		else 
 		    $result .= '
 <script>tag = document.getElementById("tinyps'.$id.'"); tag.outerHTML = tag.outerHTML;</script>';
 		$swTinyPSimported = true;
 		$result .= '</nowiki>';
		
		return $result;		
		
	}

}

$swFunctions["tiny-ps"] = new swTinyPS;


class swTinyPSTable extends swFunction
{

	function info()
	{
	 	return "(tablename) adds a Relation table from memory to rpnTables";
	}

	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		$file = $args[1];
		$path = 'site/cache/'.$file;
		
		$r = new swRelation('');
		$enc = 'utf8';
		$r->setCSV($path,true,false,$enc);
		$j = $r->getJSON();
		$a = json_decode($j,true);
		$aa = $a['relation'];
		$j2 = json_encode($aa);
		
		
		$result = '<nowiki>';        
        global $swTinyPSimported;
        global $rpnFontURLs;
        if (!$swTinyPSimported)
 			$result .= '
<script src="inc/skins/tinyps112.js"></script>
<script src="inc/skins/tinyps-extensions.js"></script>
<script>rpnFontURLs = '.json_encode($rpnFontURLs).';</script>';

 		$swTinyPSimported = true;
 		
 		$ext = 'if (typeof rpnTables === "undefined") rpnTables = {};' 
 		. ' rpnTables["'.$file.'"] = '.$j2;
 		
 		
 		
 		$result .= '<script>rpnExtensions = rpnExtensions + "'. str_replace('"','\"',$ext) .'"</script>'; 
 		
 		
 		
 		$result .= '</nowiki>';
		
		return $result;		
		
	}

}

$swFunctions["tiny-ps-table"] = new swTinyPSTable;







?>