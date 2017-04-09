<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swCssFunction extends swFunction
{

	function info()
	{
	 	return "(denominator, style) adds css to the header of the current page"; // the skin must support ist
	}

	
	function dowork($args)
	{

		
		$denominator = $args[1];
		$style = $args[2];
		
		global $swParsedCSS;
		
		
		
		$swParsedCSS .= $denominator . '{' . $style . '}'.PHP_EOL;

		
		return "";
		
	}

}

$swFunctions["css"] = new swCssFunction;


?>