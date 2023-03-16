<?php

if (!defined("SOFAWIKI")) die("invalid acces");

function swGetToken($s)
{
	global $swEncryptionSalt;
	global $swMainName;
	$timestamp = sprintf("%08x", time()); // seconds as hex string
	$token = $timestamp."|".md5($timestamp.$s.$swMainName.$swEncryptionSalt); // md5 as hex string
	return $token;
}

function swCheckToken($s,$token)
{
	global $swEncryptionSalt;
	global $swMainName;
	
	$list = explode('|',$token);
	
	// wrong format
	if (count($list) != 2) return false;
	
	$timestamp = $list[0];
	$time = hexdec($timestamp);
	$time0 = time();
	// token in future
	if ($time - $time0 > 0)  return false;
	// token too old - tolerance 6 hours
	if ($time - $time0 < -60*60*6)  return false;
		
	$test = $timestamp."|".md5($timestamp.$s.$swMainName.$swEncryptionSalt); // md5 as hex string
	
	// token invalid
	if ($test != $token) return false;
	
	return true;	
}