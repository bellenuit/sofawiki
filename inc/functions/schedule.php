<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swScheduleFunction extends swFunction
{

	function info()
	{
	 	return "(startdate, enddate, content) displays content based on timestamp";
	}

	function arity()
	{
		return 3;
	}
	
	function dowork($args)
	{

		
		$startdate = trim($args[1]);
		if (!$startdate)
		{
			$startdate = "1901-01-01 00:00:00";
		}	
		$enddate = trim($args[2]);
		if (!$enddate)
		{
			$enddate = "2099-12-12 23:59:59";
		}
		if (strlen($enddate) == 10)
		{
			$enddate .= ' 23:59:59'; 
		}	
		
		
		
		$content = trim($args[3]);
		
		$ts = date("Y-m-d H:i:s",time());
		
		$show = false;
		
		if ($startdate <= $ts)
		{
			if ($enddate >= $ts)
			{
				return $content;
			}
		}
		
		
	}

}

$swFunctions["schedule"] = new swScheduleFunction;


?>