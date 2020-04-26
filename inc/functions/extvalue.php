<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swExtValueFunction extends swFunction
{

	var $searcheverywhere = false;

	function info()
	{
	 	return "(name, field, separator, template, header, footer) gets the values of a field in another page";
	}

	
	function dowork($args)
	{

		// uses ../utilities.php
		
		
		if (count($args)>1)	
			$name =  $args[1];	
		else
			return '';
		
		if (count($args)>2)	
			$field = $args[2];
		else
			return '';
		
		if (count($args)>3)	
			$separator = $args[3];
		else
			$separator = '';
		if (count($args)>4 && $args[4] != '')	
			$template = $args[4];
		else
			$template = '';
		if (count($args)>5)
			$header = $args[5];
		else
			$header = '';
		if (count($args)>6)	
			$footer = $args[6];
		else
			$footer = '';
		
	
		
		$transcludenamespaces= array();
		global $swTranscludeNamespaces;
		foreach($swTranscludeNamespaces as $k=>$v)
			$transcludenamespaces[swNameURL($v)]= 1;
		if (!$this->searcheverywhere && stristr($name,':') 
		&& !array_key_exists('*',$transcludenamespaces))
		{
			$fields = explode(':',$name);
			if (!array_key_exists(swnameURL($fields[0]),$transcludenamespaces))
			return $name;  // access error
		}
		
		$wiki = new swWiki;
		$wiki->name = $name;
		$wiki->lookup();
		
		$list = array();
		if (isset($wiki->internalfields[$field]))
			$list = $wiki->internalfields[$field];
		
		if (!is_array($list) || count($list)==0) return "";
		
		$results = array();
		
		foreach ($list as $elem)
		{
			if ($elem != "" )
			{
				if ($template != "")
					$results[] = '{{'.$template.'|'.swHTMLSanitize($elem).'}}';
				else
					$results[] = $elem;
			}
		}
		
		
		
		$result = join($separator,$results);
		
		if (trim($result) != "")
			$result = $header.$result.$footer;
		
		return $result;	
	}

}

$swFunctions["extvalue"] = new swExtValueFunction;


?>