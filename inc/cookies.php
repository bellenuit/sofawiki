<?php

if (!defined("SOFAWIKI")) die("invalid acces");



function handleCookie($id,$default,$period=9000000)
{
	
	
	
	global $swCookiePrefix;
	$key= $swCookiePrefix."-".$id;
	$result = $default;
	
	if (array_key_exists($key, $_COOKIE)) 
	{
		$result =  $_COOKIE[$key];
	}
	if (array_key_exists($id, $_GET)) 
	{
		$result =  $_GET[$id];
		$cs = setcookie($key, $result, time() + $period); 
		
	}
	if (array_key_exists($id, $_POST)) 
	{
		$result =  $_POST[$id];
		$cs = setcookie($key, $result, time() + $period); 
	}
	return $result;
	
}

function swGetCookie($id)
{
	global $swCookiePrefix;
	$key= $swCookiePrefix."-".$id;
	if (array_key_exists($key, $_COOKIE)) 
		return $_COOKIE[$key];
}
function swSetCookie($id,$value,$period = 9000000)
{
	global $swCookiePrefix;
	$key= $swCookiePrefix."-".$id;
	setcookie($key, $value, time() + $period); 
}




?>