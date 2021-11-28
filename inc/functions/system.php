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
	
	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		//print_r($args);
		
		$old = @$args[1];
		$new = @$args[2];
	
		
		$result =  htmlDiff($old,$new);
		
		//echo $result;
		
		return $result;
	
		
	}	
}

$swFunctions["diff"] = new swDiffFunction;

class swNowikifunction extends swFunction
{
	function info()
	{
	 	return "(text) envelopes text in nowiki tags";
	}
	
	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		
		//print_r($args);
		
		$text = @$args[1];
		$result =  '<nowiki>'.$text.'</nowiki>';
		
		//echo $result;
		
		return $result;
	
		
	}	
}

$swFunctions["nowiki"] = new swNowikifunction;




?>