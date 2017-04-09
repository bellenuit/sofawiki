<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swFirstValueFunction extends swFunction
{

	function info()
	{
	 	return "(field, template) gets the first value of a field in a page";
	}

	
	function dowork($args)
	{

		// uses ../utilities.php
		
		//print_r($args);

		global $name;	
		$field = $args[1];
		
		//echo "=$field=";
		
		if (count($args)>2)	
			$template = $args[2];
		else
			$template = '';
		
	
		global $wiki;
		
		if (isset($wiki->internalfields[$field]))
			$list = $wiki->internalfields[$field];
		else
			$list = array();
		
		$result = '';
		$elem = @$list[0];
		
			if ($elem != "" )
			{
				if ($template != "")
					$result = '{{'.$template.'|'.$elem.'}}';
				else
					$result = $elem;
			}		
		
		return $result;	
	}

}

$swFunctions["firstvalue"] = new swFirstValueFunction;


?>