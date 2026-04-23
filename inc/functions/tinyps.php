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
		
		
		
		$result = '<nowiki><tiny-ps id="tinyps'.$id.'" width="640" height="360" format="svg" textmode="1"></tiny-ps>';
		
		// get all tables
			           
        
        global $swTinyPSimported;
        global $rpnFontURLs;
        
        $fonts = json_encode($rpnFontURLs);
        
        if (!$swTinyPSimported)
 			$result .= '

<script>rpnFontURLs = '.$fonts.'</script>
<script src="inc/skins/tinyps126.js"></script>
<script src="inc/skins/tinyps-extensions.js"></script>';

 		    $result .= '
<script>tag = document.getElementById("tinyps'.$id.'"); tag.innerHTML = `'.$args[1].'`;</script>';
 		$swTinyPSimported = true;
 		$result .= '</nowiki>';
		
		return $result;		
		
	}

}

$swFunctions["tiny-ps"] = new swTinyPS;







?>