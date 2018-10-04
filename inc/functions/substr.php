<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swSubstrFunction extends swFunction
{

	function info()
	{
	 	return "(s, start, length) Emulates the PHP substr function";
	}

	function arity()
	{
	 	return 3;
	}

	
	function dowork($args)
	{
		$s = $args[1];		
		$start = intval($args[2]);	
		$ende = @$args[3];	
	
		//print_r($args);
	
		if ($ende == '')
			return substr($s,$start);
		else
			return substr($s,$start,intval($ende));
		
	}

}

$swFunctions["substr"] = new swSubstrFunction;


?>