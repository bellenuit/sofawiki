<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Logs";

$root = "$swRoot/site/logs/";

$files = glob($root."*.txt");
arsort($files);

$swParsedContent .= "\n<form method='get' action='index.php'><p>";
// $swParsedContent .= "\n<select name='file'>";

$query = swGetArrayValue($_REQUEST,'query');
$query = str_replace("\\\\","\\",$query);
$regex = swGetArrayValue($_REQUEST,'regex');
$regex =  str_replace("\\\\","\\",$regex);
if ($regex)
	$checked = "checked='checked'";
else
	$checked = "";
$stats = swGetArrayValue($_REQUEST,'stats');
$stats =  str_replace("\\\\","\\",$stats);
if ($stats)
	$statschecked = "checked='checked'";
else
	$statschecked = "";
$unique = swGetArrayValue($_REQUEST,'unique');
$unique =  str_replace("\\\\","\\",$unique);
if ($unique)
	$uniquechecked = "checked='checked'";
else
	$uniquechecked = "";

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
if (!$datestart) $datestart = max($minfile, date("Y-m-d",time()-29*86400));
if (!$dateend) $dateend = $maxfile;

// $swParsedContent .= "\n</select>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:logs'><p>";
$swParsedContent .= "\nstart <input type='text' name='datestart' value='$datestart' /> ";
$swParsedContent .= "\nend<input type='text' name='dateend' value='$dateend' />";
$swParsedContent .= "\nfilter <input type='text' name='query' value='$query' />";
$swParsedContent .= "\n<p><input type='checkbox' name='regex' value='1' $checked /> Regex";
$swParsedContent .= "\n<input type='checkbox' name='unique' value='1' $uniquechecked /> Unique";
$swParsedContent .= "\n<input type='checkbox' name='stats' value='1' $statschecked /> Statistics";
$swParsedContent .= "\n<input type='checkbox' name='table' value='1' $tablechecked /> Table";
$swParsedContent .= "\n<p><input type='submit' name='submit' value='Query' />";
$swParsedContent .= "\n<input type='submit' name='submitsave' value='Query and save to page:' />";
$swParsedContent .= "\n<input type='text' name='savename' value='$savename' />";
$swParsedContent .= "\n</p><form>";

$rawlines = array();
$hits = 0;
$totaltime = 0;
$uniquevisitors = array();
$uniquepageviews = array();
if (swGetArrayValue($_REQUEST,'submit',false) || swGetArrayValue($_REQUEST,'submitsave',false))
{
	echotime('read logs');
	foreach($files as $file)
	{
		$file = str_replace($root,"",$file);
		if (stristr($f,'deny-')) continue;
		$file = str_replace(".txt","",$file);

		if ($file>=$datestart && $file<=$dateend)
		{
			//$t = file_get_contents($root.$file.".txt");
			//$lines = explode("\n",$t);
			
			//$lines = file($root.$file.".txt",FILE_IGNORE_NEW_LINES || FILE_SKIP_EMPTY_LINES);
			
			
			
			$handle = @fopen($root.$file.'.txt', 'r');
			if ($handle)
			 	while (($line = fgets($handle, 4096)) !== false)
			{
				
				
				$found=false;
				if ($regex)
				{
					if (preg_match($query,$line))
					{	$found = true; }
				}
				else
				{
					if ($query == "" || stristr($line,$query))
					{	$found = true; }
				}
			
				if ($found)
				{
					$hits++;
					$hitdate = substr(swGetValue($line,'timestamp'),0,10); //yyyy-mm-dd
					$hituser = swGetValue($line,'user');
					$hitname = swGetValue($line,'name');
					$hitname = swNameURL($hitname); 
					$i=strpos($hitname,"/");
					if ($i>-1) $hitname= substr($hitname,0,$i);
					$hitaction = swGetValue($line,'action');
					$hittime = swGetValue($line,'time');
					$totaltime += $hittime;
					if (!isset($uniquevisitors[$hituser]))	$uniquevisitors[$hituser] = 0;
					if (!isset($uniquevisitortime[$hituser]))	$uniquevisitortime[$hituser] = 0;
					$uniquevisitors[$hituser] += 1;
					$uniquevisitortime[$hituser] += $hittime;
					$uniquepageviews[$hituser.'::'.$hitname.'::'.$hitdate] = 1;
					
					
					if ($table)
					{
						$values = swGetAllFields($line);
						$newvalues = array();
						foreach($values as $v)
						{
							$newvalues[] = '"'.join('::',$v).'"';
						}
						$line = join(', ',$newvalues);
						
					}
					
					
					if ($unique)
					{
						$key = $hituser.'::'.$hitname.'::'.$hitdate;
						$rawlines[$key] = $line;
					}
					else
					{
						$rawlines[] = $line;
					}
				}
				
				
			}
		}
	} 
	echotime('analyse logs');
	arsort($rawlines); 
	if ($hits>0) 
		$averagetime = sprintf("%04d",$totaltime/$hits);
	else
		$averagetime = '0000';
		
		
	if ($stats)
	{
		$statlines = array();
		$statlines[]= "[[datestart::$datestart]][[dateend::$dateend]][[query::$query]][[regex::$regex]]";
		$statlines[]= "[[hits::$hits]]";
		$statlines[]= "[[averagetime::$averagetime]]"; 
		$statlines[]= "[[uniquepageviews::".count($uniquepageviews)."]]";
		$statlines[]= "[[uniquevisitors::".count($uniquevisitors)."]]";
		
		$statlines[]= "[[title::Most viewed pages]]"; 
		$uniquepages = array();
		foreach($uniquepageviews as $k=>$v)
		{
			$fields = explode('::',$k);
			$pagename = $fields[1];
			if (!isset($uniquepages[$pagename])) $uniquepages[$pagename] = 0;
			$uniquepages[$pagename] += 1;
		}
		arsort($uniquepages); $i=0;
		foreach($uniquepages as $hitpage=>$views)
		{
			$i++;
			$viewpercentage = sprintf("%0.1f",100*$views/count($uniquepageviews)).'%';
			$statlines[]= "$i. [[name::$hitpage]][[uniqueviews::$views]][[viewpercentage::$viewpercentage]]";
			//if($i>=100) break; 
		}

		$statlines[]= "[[title::Most active users]]"; 
		
		
		arsort($uniquevisitors); $i=0;
		foreach($uniquevisitors as $hituser=>$hits)
		{
			$i++;
			$averagetime = sprintf("%04d",$uniquevisitortime[$hituser]/$hits);
			$statlines[]= "$i. [[user::$hituser]][[hits::$hits]][[totaltime::$uniquevisitortime[$hituser]]][[averagetime::$averagetime]]";
			//if($i>=100) break;
		}
		
		if ($table)
		{
			foreach ($statlines as $line)
			{
				$values = swGetAllFields($line);
				$newvalues = array();
				foreach($values as $v)
				{
					$newvalues[] = '"'.join('::',$v).'"';
				}
				$line = join(', ',$newvalues);
				$newstatlines[] = $line;		
			}
			$statlines = $newstatlines;
		}
		$swParsedContent .= "\n\n<pre>".join("\n",$statlines)."\n</pre>\n";	
		
		
		if (swGetArrayValue($_REQUEST,'submitsave',false))
		{
			if ($savename)
			{
				$w = new swRecord;
				$w->name = $savename;
				$w->content = join("\n",$statlines);
				$w->insert();
			}
			else
			{
				$swError = 'no name for saved log';
			}
		}
	}
	else
	{
		$swParsedContent .= "\n\n<pre>\n\n</pre>\n";
		$swParsedContent .= "\n\n<pre>".join("\n",$rawlines)."\n</pre>\n";	
	}

} 
 
 



$swParseSpecial = false;

// print_r($_ENV);

?>