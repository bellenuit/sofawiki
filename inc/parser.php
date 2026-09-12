<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swParser
{
	var $lastparser = false;
	
	function info()
	{
		// stub
		return "generic";
	}
	
	function dowork(&$wiki)
	{
		// stub
		$wiki->ParsedContent .= " generic"; 
		return $wiki;
	}
}


?>