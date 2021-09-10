<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Deny";

$root = "$swRoot/site/logs/";

$files = glob($root."*.txt");
arsort($files);

// $swParsedContent .= "\n<select name='file'>";

$minfile = null;
$maxfile = null;
foreach ($files as $f)
{
	$f = str_replace($root,"",$f);
	if (!stristr($f,'deny-')) continue;
	$f = str_replace(".txt","",$f);
	if (!$minfile) $minfile = $f; else $minfile = min($minfile,$f);
	if (!$maxfile) $maxfile = $f; else $maxfile = max($maxfile,$f);
}
$datestart = swGetArrayValue($_POST,'datestart');
$dateend = swGetArrayValue($_POST,'dateend');
$denyend = swGetArrayValue($_POST,'denyend');
if (trim($denyend) == '') $denyend = date("Y-m-d",time());
$ip = swGetArrayValue($_POST,'ip');
if (!$datestart) $datestart = max($minfile, date("Y-m-d",time()-29*86400));
if (!$dateend) $dateend = $maxfile;

// $swParsedContent .= "\n</select>";
$swParsedContent .= "\n<p>Deny Manager:<br>Search for unsuccessful logins. After $swDenyCount attempts, IP will be blocked for the day";
$swParsedContent .= "\n<form method='post' action='index.php'><p>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:deny'><p>";
$swParsedContent .= "\nstart <input type='text' name='datestart' value='$datestart' /> ";
$swParsedContent .= "\nend<input type='text' name='dateend' value='$dateend' />";
$swParsedContent .= "\n<input type='submit' name='submit' value='Query' />";
$swParsedContent .= "\n</p></form>";


$swParsedContent .= "\n<p>Deny blocks manually an IP until the specified date (SQL format YYYY-MM-DD)";
$swParsedContent .= "\n<form method='post' action='index.php'><p>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:deny'><p>";
$swParsedContent .= "\nIP <input type='text' name='ip' value='$ip' /> ";
$swParsedContent .= "\nend<input type='text' name='denyend' value='$denyend' />";
$swParsedContent .= "\n<input type='submit' name='submitdeny' value='Deny' />";
$swParsedContent .= "\n</p></form>";

$swParsedContent .= "\n<p>Allow removes IP from deny list";
$swParsedContent .= "\n<form method='post' action='index.php'><p>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:deny'><p>";
$swParsedContent .= "\nIP <input type='text' name='ip' value='$ip' /> ";
$swParsedContent .= "\n<input type='submit' name='submitallow' value='Allow' />";
$swParsedContent .= "\n</p></form>";

$swParsedContent .= '<p>Configuration<br>$swDenyCount ='.@$swDenyCount. ' (threshold unsuccessful logins to block for the day)';
$swParsedContent .= '<br>$swStrongDeny ='.@$swStrongDeny. ' (probability 0-100% that empty login action triggers unsuccessful login)';


$rawlines = array();

if (swGetArrayValue($_REQUEST,'submitallow',false) && trim($ip) != '')
{
	swLogAllow($ip);
}

if (swGetArrayValue($_REQUEST,'submitdeny',false) && trim($ip) != '')
{
	
	swLogDeny($ip,$denyend);
}


if (swGetArrayValue($_REQUEST,'submit',false) || swGetArrayValue($_REQUEST,'submitallow',false)
|| swGetArrayValue($_REQUEST,'submitdeny',false))
{
	
	$file = $swRoot."/site/indexes/deny.txt";
	if (file_exists($file)) 
	{
		$t = file_get_contents($file);
		$ts = explode(']]',$t);
		natsort($ts);
		$ts = array_filter($ts); // remove empry
		$swParsedContent .= "<p>Denied: <br><b>".join(']]<br>',$ts)."]]</b>";
	}
	
	
	echotime('read deny logs');
	foreach($files as $file)
	{
		$file = str_replace($root,"",$file);
		if (!stristr($file,'deny-')) continue;
		$file = str_replace(".txt","",$file);
		
		if ($file>=$datestart && $file<=$dateend)
		{
			$handle = @fopen($root.$file.'.txt', 'r');
			if ($handle)
			 	while (($line = fgets($handle, 4096)) !== false)
				{
					$rawlines[] = $line;
				}
		}
	} 
	arsort($rawlines); 
	$swParsedContent .= "<p>".join("<br>",$rawlines);	

} 
 
 



$swParseSpecial = false;

// print_r($_ENV);

?>