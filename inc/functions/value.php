<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swValueFunction extends swFunction
{

	function info()
	{
	 	return "(field, separator, template, header, footer) gets the values of a field of the current page";
	}

	
	function dowork($args)
	{

		// uses ../utilities.php

		global $name;	
		$field = $args[1];
		
		if (count($args)>2)
			$separator = $args[2];
		else
			$separator = '';
		if (count($args)>3)	
			$template = $args[3];
		else
			$template = '';
		if (count($args)>4)	
			$header = $args[4];
		else
			$header = '';
		if (count($args)>5)	
			$footer = $args[5];
		else
			$footer = '';
		
	
		global $wiki;
		$list = '';
		if ($field=="name")
			$list = array($wiki->nameshortwithoutlanguage());
		else
		{
			if (array_key_exists($field,$wiki->internalfields)) 
				$list = $wiki->internalfields[$field];
		}
		
		if (!is_array($list)) return "";
		
		foreach ($list as $elem)
		{
			if ($template != "")
				$results[] = '{{'.$template.'|'.swHTMLSanitize($elem).'}}';
			else
				$results[] = $elem;
		}
		
		
		
		$result = join($separator,$results);
		
		if (trim($result) != "")
			$result = $header.$result.$footer;

		
		return $result;
		
	}

}

$swFunctions["value"] = new swValueFunction;


?>