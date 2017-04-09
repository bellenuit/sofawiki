<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swParser
{
	
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