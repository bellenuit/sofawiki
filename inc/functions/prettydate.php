<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swPrettyDateFunction extends swFunction
{

	function info()
	{
	 	return "(date) formats text pretty";
	}

	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{

		
		$s = $args[1];
		if (stristr($s,"-"))
		{
			// date given is sql
			$y = substr($s,0,4);
			$m = substr($s,5,2);
			$d = substr($s,8,2);
		}
		else
		{
			// date given is roman
			$list = explode(".",$s);
			$y = $list[2];
			$m = $list[1];
			$d = $list[0];
		}
	

		if ($d < 10)
			$d = str_replace('0','',$d);
		if ($m < 10)
			$m = str_replace('0','',$m);
		
		$yy = date('Y',time());
		if ($y == $yy)
			return trim($d.'.'.$m.'.');
		else
			return trim($d.'.'.$m.'.'.$y);
	}

}

$swFunctions["prettydate"] = new swPrettyDateFunction;


?>