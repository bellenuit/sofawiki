<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swNameURLfunction extends swFunction
{

	function info()
	{
	 	return "(s) translates string to URL";
	}

	
	function dowork($args)
	{
		$s = $args[1];		
		return swNameURL($s);		
	}

}

$swFunctions["nameurl"] = new swNameURLfunction;


?>