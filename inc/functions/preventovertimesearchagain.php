<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swPreventOvertimeSearchAgainFunction extends swFunction
{

	function info()
	{
	 	return "() prevents overtime search again feature"; // the skin must support ist
	}

	
	function dowork($args)
	{
		global $swPreventOvertimeSearchAgain;
		
		$swPreventOvertimeSearchAgain = 1;
		
		echotime('preventovertimesearchagain');
		
		return ''	;	
	}

}

$swFunctions["preventovertimesearchagain"] = new swPreventOvertimeSearchAgainFunction;


?>