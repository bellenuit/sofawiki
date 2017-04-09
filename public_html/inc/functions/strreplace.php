<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swStrreplaceFunction extends swFunction
{

	function info()
	{
	 	return "(old, new, string) Emulates the PHP str_replace function";
	}

	
	function dowork($args)
	{
		$o = $args[1];		
		$n = $args[2];	
		$s = @$args[3];	
			return str_replace($o,$n,$s);
		
	}

}

$swFunctions["strreplace"] = new swStrreplaceFunction;


?>