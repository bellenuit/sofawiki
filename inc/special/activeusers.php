<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Active Users";

$root = "$swRoot/site/logs/";

$files = glob($root."*.txt");
arsort($files);



$table = swGetArrayValue($_REQUEST,'table');
$table =  str_replace("\\\\","\\",$table);
if ($table)
	$tablechecked = "checked='checked'";
else
	$tablechecked = "";
$savename = swGetArrayValue($_REQUEST,'savename');
$savename =  str_replace("\\\\","\\",$savename);
$file = swGetArrayValue($_REQUEST,'file');

$minfile = null;
$maxfile = null;
foreach ($files as $f)
{
	$f = str_replace($root,"",$f);
	if (stristr($f,'deny-')) continue;
	$f = str_replace(".txt","",$f);
	if ($f ==$file)
		$selected = "selected='selected'";
	else	
		$selected = "";
	if (!$minfile) $minfile = $f; else $minfile = min($minfile,$f);
	if (!$maxfile) $maxfile = $f; else $maxfile = max($maxfile,$f);
	// $swParsedContent .= " \n<option value='$f' $selected>$f</option>";
}
$datestart = swGetArrayValue($_REQUEST,'datestart');
$dateend = swGetArrayValue($_REQUEST,'dateend');
if (!$datestart) $datestart = max($minfile, date("Y-m-d",time()));
if (!$dateend) $dateend = $maxfile;

$_REQUEST['submit'] = 1;




$rawlines = array();
$hits = 0;
$totaltime = 0;
$uniquevisitors = array();
$uniquepageviews = array();
$queries = array();
$queriesgoodresults = array();
$actions = array();
$errors = array();


if ($table) 
{
	$tablefields = array('timestamp', 'user', 'name', 'action', 'query', 'lang', 'referer', 'time', 'memory', 'error', 'label', 'receiver');
	$rawlines[] = join(', ',$tablefields);
}

if (!isset($swMemoryLimit)) $swMemoryLimit = 100000000;




if (swGetArrayValue($_REQUEST,'submit',false) || swGetArrayValue($_REQUEST,'submitsave',false))
{
	echotime('read logs');
	foreach($files as $file)
	{
		if (memory_get_usage()>$swMemoryLimit) break;
		
		$file = str_replace($root,"",$file);
		if (stristr($f,'deny-')) continue;
		$file = str_replace(".txt","",$file);

		if ($file>=$datestart && $file<=$dateend)
		{			
			$handle = @fopen($root.$file.'.txt', 'r');
			if ($handle)
			 	while (($line = fgets($handle, 4096)) !== false)
			{
				
				$hittime = substr(swGetValue($line,'timestamp'),11); 
				$hituser = swGetValue($line,'user');
				$hitname = swGetValue($line,'name');
				$hitaction = swGetValue($line,'action');
				
				if (!isset($usertime[$hituser]))
				{
					$usertime[$hituser] = $hittime;
					$usertimes[$hituser] = array($hittime);
					$usernames[$hituser] = array($hitname);
					$useractions[$hituser] = array($hitaction);
				}
				else
				{
					$usertime[$hituser] = max($hittime,$usertime[$hituser]);
					$usertimes[$hituser][] = $hittime;
					$usernames[$hituser][] = $hitname;
					$useractions[$hituser][] = $hitaction;
				}
				
				
			
				$rawlines[] = $line;
				
			}
		}
	} 
	echotime('analyse logs');
	$usertime = array_flip($usertime);
	
	krsort($usertime);
	
	$usertime = array_flip($usertime);
	
	$swParsedContent = '<p>Users today. Click on name to get detail.';
	$swParsedContent .= '<ul>';
	foreach($usertime as $k=>$v)
	{
		$hits = count($usertimes[$k]);
		if ($hits>1) $hits .= ' hits'; else $hits .= ' hit';
		$swParsedContent .= '<li onclick="if(this.firstElementChild.style.display==\'none\') this.firstElementChild.style.display=\'block\'; else this.firstElementChild.style.display=\'none\' ;">'.$v.' '.$k.' ('.$hits.')';
		$swParsedContent .= '<ul style="display:none">';
		rsort($usertimes[$k]);
		rsort($usernames[$k]);
		rsort($useractions[$k]);
		for($i=0;$i<count($usertimes[$k]);$i++)
		{
			$swParsedContent .= '<li>'.$usertimes[$k][$i].' <a href="index.php?name='.$usernames[$k][$i].'" target="_blank">'.$usernames[$k][$i].'</a> '.$useractions[$k][$i].'</li>';
		}
		$swParsedContent .= '</ul></li>';
	}
	$swParsedContent .= '</ul>';
	
	

} 
 
 
if (swGetArrayValue($_REQUEST,'submitdeleteold',false))
{

$files = glob($root."*.txt");
	
foreach ($files as $f)
{
	$fdate = str_replace($root,"",$f);
	if (stristr($f,'deny-')) continue;
	$fdate = str_replace(".txt","",$fdate);
	$datelimit = date("Y-m-d",time()-366*86400);
	if ($fdate<$datelimit)
	{
		$swParsedContent .= '<p><i>Delete '.$f.'</i></p>';
		// unlink($f);
	}
}
}
 



$swParseSpecial = false;

// print_r($_ENV);

?>