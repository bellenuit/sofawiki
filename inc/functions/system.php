<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swSystemMessageFunction extends swFunction
{
	function info()
	{
	 	return "(msg, lang) Returns System Message";
	}
	
	function dowork($args)
	{
		
		$msg = @$args[1];
		$lang = @$args[2];
		$styled = @$args[3];
		
		return swSystemMessage($msg,$lang,$styled);
		
	}	
}

$swFunctions["systemmessage"] = new swSystemMessageFunction;


class swDiffFunction extends swFunction
{
	function info()
	{
	 	return "(old, new) returns Diff as HTML";
	}
	
	function dowork($args)
	{
		
		$old = @$args[1];
		$new = @$args[2];
		$html = @$args[3];
		
		return htmlDiff($old,$new);
		
	}	
}

$swFunctions["diff"] = new swDiffFunction;



?>