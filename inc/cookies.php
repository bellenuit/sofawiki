<?php
	
/**
 *	Provides functions to deal with cookies.
 *
 *  The functions are used by index.php and api.php
 *  The calls to the functions are in the api.php after including site/configuration.php
 *  and only if api.php is invoked by index.php. 
 *  This order allows to access cookie functions in configuration.php
 */

if (!defined("SOFAWIKI")) die("invalid acces");

/**
 * Returns parameters either from the $_COOKIE, $_GET and $_POST.
 *
 * If $_GET or $_POST deliver a value, it is saved to $_COOKIE
 * Typical use cases are $lang, $skin and $username
 * Uses the global $swCookiePrefix from site/configuration.php to set a cookie name.
 * $swCookiePrefix allows to have multiple wikis on the same domain.
 *
 * @param $id
 * @param $default value if neither COOKIE nor GET nor POST are set.
 * @param $period lifetime of the cookie in seconds
 * @param $refresh if true (by default), cookie gets new fresh period each time
 */


function swHandleCookie($id,$default,$period=9000000,$refresh = true)
{

	
	global $swCookiePrefix;
	$key= $swCookiePrefix.'-'.$id;

	if (array_key_exists($id, $_POST)) 
	{
		$result =  $_POST[$id];
		if (!setcookie($key, $result, time() + $period)) echotime('headerssent post cookie '.$id); 
	}
	elseif (array_key_exists($id, $_GET)) 
	{
		$result =  $_GET[$id];
		if (!setcookie($key, $result, time() + $period)) echotime('headerssent get cookie '.$id); 
		
	}
	elseif (array_key_exists($key, $_COOKIE)) 
	{
		$result =  $_COOKIE[$key];
		// we refresh the cookie here to make it live longer
		if ($refresh) if (!setcookie($key, $result, time() + $period)) echotime('headerssent refresh cookie '.$id);
	}
	else
	{
		$result = $default;
	}
	
	return $result;
	
}

/**
 * Returns parameters from $_COOKIE.
 *
 * Respects the global $swCookiePrefix.
 *
 * @param $id
 */

function swGetCookie($id)
{
	global $swCookiePrefix;
	$key= $swCookiePrefix.'-'.$id;
	if (array_key_exists($key, $_COOKIE)) 
		return $_COOKIE[$key];
}

/**
 * Sets a $_COOKIE value.
 *
 * Respects the global $swCookiePrefix.
 *
 * @param $id
 * @param $value
 * @period lifetime of the cookie in seconds
 */


function swSetCookie($id,$value,$period = 9000000)
{
	global $swCookiePrefix;
	$key= $swCookiePrefix.'-'.$id;
	if (!setcookie($key, $value, time() + $period)) echotime('headerssent set cookie '.$id); 
}




?>