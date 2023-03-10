<?php
	
/**
 *	Provides a functions to execute an async request.
 *
 *  Used for cron to be executed after the index.php script returns to the client
 *
 *  Inspiration: https://mindsers.blog/fr/post/appel-dune-fonction-asynchrone-en-php/
 */

if (!defined("SOFAWIKI")) die("invalid acces");

function swAsyncCurl($addr,$data){ 
    $post_string = json_encode($data); 
    $url = parse_url($addr); 
    $errno = ''; 
    $errstr = ''; 

    $fp = fsockopen($url['host'], 80, $errno, $errstr, 30); 

    $out = "POST ".$url['path']." HTTP/1.1\r\n"; 
    $out.= "Host: ".$url['host']."\r\n"; 
    $out.= "Content-Type: text/json\r\n"; 
    $out.= "Content-Length: ".strlen($post_string)."\r\n"; 
    $out.= "Connection: Close\r\n"; 
    $out.= $post_string; 

    fwrite($fp, $out); 
    fclose($fp);
 
    return 0; 
}

function swAsyncCron()
{
	global $swBaseHrefFolder;
	$addr = $swBaseHrefFolder.'cron.php';
	if (isset($swCronToken)) $addr .= '?token='.$swCronToken;
	$data = '';
	
	swAsyncCurl($addr,$data);
}