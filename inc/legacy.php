<?php

// moved some code here that works only with PHP5

if (!defined("SOFAWIKI")) die("invalid acces");


function swSetTimeZone($zone)
{

	if (phpVersion()>="5.1.0")
		date_default_timezone_set($zone);
	else
		; // do nothing 
	
}


function swException($message)
{

		// we do nothing. there should maybe a system with more quiet and more loudly exceptions
		
		/*
		global $username;
		global $name;
		global $action;
		global $query;
		global $lang;
		global $referrer;
		$time="";
		$label = "Exception";
		$receiver = "";
		swLog($username,$name,$action,$query,$lang,$referer,$time,$error,$label,$message,$receiver);
		*/
		
		echotime($message);
		global $swError;
		$swError = $message;
	
}


function stripos2($haystack,$needle,$offset=0)
{
	$haystack = strtolower($haystack);
	$needle = strtolower($needle);
	return strpos($haystack,$needle,$offset);
}


swSetTimeZone("Europe/Zurich");


?>