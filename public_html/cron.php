<?php

/*
	SofaWiki
	Matthias Buercher 2010 
	matti@belle-nuit.com
	
	index.php 
	main entry point
*/


error_reporting(E_ALL);
ini_set("display_errors", 1); 


// to keep session longer than some minutes use in .htaccess php_value session.cookie_lifetime 0 
session_start();

define('SOFAWIKIINDEX',true);
define('SOFAWIKICRON',true); 

include 'api.php';

if (!isset($_REQUEST['token']) || !isset($swCronToken) || $_REQUEST['token'] != $swCronToken)
{
	die("invalid acces");
}


swCron();

echo '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
echo $swDebug;
echo '</body><html>';

?>