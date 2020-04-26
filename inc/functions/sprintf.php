<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swSprintfunction extends swFunction
{

	function info()
	{
	 	return "(format, text) formats text like PHP sprintf";
	}

	function arity()
	{
	 	return 2;
	}
	
	function dowork($args)
	{

		// uses ../utilities.php
		
		

		global $name;	
		$format = $args[1];
		
		if (count($args)>2)	
			$text = $args[2];
		else
			$text = '';
		
		$result = sprintf($format,$text);	
		
		return $result;	
	}

}

$swFunctions["sprintf"] = new swSprintfunction;


?>