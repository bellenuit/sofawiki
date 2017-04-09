<?php

//this file is UTF-8 NO BOM

if (!defined("SOFAWIKI")) die("invalid acces");

class swTidyParser extends swParser
{

	function info()
	{
	 	return "Removes HTML tags";
	}


	function dowork(&$wiki)
	{
	 	
		
		$s = $wiki->parsedContent;
		
		//tabs and special spaces
		$s = str_replace("\t",' ',$s);
		$s = str_replace("\r",'',$s);
		$s = str_replace(" ",' ',$s); // invisible character option-space
		//$s = str_replace(chr(0xA0),' ',$s); // invisible character option-space
		
		// &nbsp;
		$s = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u',' ',$s);
		
		// preformatted text
		 $s = preg_replace('/^[ ](.*)(\n)/Um', "<pre>$1</pre>", $s); 
	
	
	
		//preserve < and << in query function
		
		$s = str_replace('<< ','*SPECIALDOUBLETAGWITHSPACE*',$s);
		$s = str_replace('<= ','*SPECIALTAGEQUEALWITHSPACE*',$s);
		$s = str_replace('< ','*SPECIALTAGWITHSPACE*',$s);
		$s = str_replace('<>','*SPECIALTAGCLOSED*',$s);
		// PHP 5.3.5 ignores self closing tags, so the normal syntax has been added
        $s = strip_tags($s,'<b><i><u><s><pre><sup><sub><tt><code><span><br><hr><br/><hr/><small><big><div><colon><pipe><close><leftsquare><rightsquare><leftcurly><rightcurly><space><null><lt><gt><backslash>');
		$s = str_replace('*SPECIALTAGWITHSPACE*','< ',$s);
		$s = str_replace('*SPECIALDOUBLETAGWITHSPACE*', '<< ',$s);
		$s = str_replace('*SPECIALTAGCLOSED*','<>',$s);
 		$s = str_replace('*SPECIALTAGEQUEALWITHSPACE*','<= ',$s);
       // preserve numbered entities
        $s = preg_replace('/&#([^\s&]*);/', "ENTITY$1ENTITYEND", $s);
        $s = str_replace("&","&amp;",$s);
        $s = preg_replace('/ENTITY(.*)ENTITYEND/U', "&#$1;", $s);
        
		
		$wiki->parsedContent = $s;
		
	}

}

$swParsers["tidy"] = new swTidyParser;


?>