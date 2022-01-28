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
$query = trim($query);
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
if (!$datestart) $datestart = max($minfile, date("Y-m-d",time()));
if (!$dateend) $dateend = $maxfile;


if (swGetArrayValue($_REQUEST,'submitconsolidate',false) || defined("CRON") )
{
	

	
	$datestart = date("Y-m-d",time());
	$dateend = date("Y-m-d",time()); //no at default
	$stats = 1;
	foreach($files as $f)
	{
		$f = str_replace($root,"",$f);
		if (stristr($f,'deny-')) continue;
		$f = str_replace(".txt","",$f);
		if ($dateend <= $f) continue;
		$linkwiki= new swWiki;
		$linkwiki->name = 'Logs:'.$f;
		$linkwiki->lookup();
		
		// if timestamp is same day, it doesn't count as recorded
		$ts = substr($linkwiki->timestamp,0,10);
		
		if (!$linkwiki->visible() || $ts == $f)
		{
			$datestart = $dateend = $f;
			$savename = 'Logs:'.$f;
			$_REQUEST['submitsave'] = 1;
			
			break;

		}
	}
		  
}
if (!defined("CRON"))
{
// $swParsedContent .= "\n</select>";
$swParsedContent .= "\n<input type='hidden' name='name' value='special:logs'><p>";
$swParsedContent .= "\nStart <input type='text' name='datestart' value='$datestart' /> ";
$swParsedContent .= "\nEnd <input type='text' name='dateend' value='$dateend' />";
$swParsedContent .= "\nFilter <input type='text' name='query' value='$query' />";
$swParsedContent .= "\n<p><input type='checkbox' name='regex' value='1' $checked /> Regex";
$swParsedContent .= "\n<input type='checkbox' name='unique' value='1' $uniquechecked /> Unique";
$swParsedContent .= "\n<input type='checkbox' name='stats' value='1' $statschecked /> Statistics";
$swParsedContent .= "\n<input type='checkbox' name='table' value='1' $tablechecked /> Table";
$swParsedContent .= "\n<p><input type='submit' name='submit' value='Query' />";
$swParsedContent .= "\n<input type='submit' name='submitsave' value='Query and save to page:' />";
$swParsedContent .= "\n<input type='text' name='savename' value='$savename' />";
//$swParsedContent .= "\n<p><input type='submit' name='submitconsolidate' value='Consolidate Logs (1 day at a click)' />";
//$swParsedContent .= "\n<input type='submit' name='submitdeleteold' style='color:red' value='Delete logs older than one year' />";
$swParsedContent .= "\n</p><form>";
}

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
					$hittime = intval(swGetValue($line,'time'));
					$referer = swGetValue($line,'referer');
					$q = swGetValue($line,'query');
					$a = swGetValue($line,'action');
					$e = swGetValue($line,'error');
					$totaltime += $hittime;
					if (!isset($uniquevisitors[$hituser]))	$uniquevisitors[$hituser] = 0;
					if (!isset($uniquevisitortime[$hituser]))	$uniquevisitortime[$hituser] = 0;
					$uniquevisitors[$hituser] += 1;
					$uniquevisitortime[$hituser] += $hittime;
					$uniquepageviews[$hituser.'::'.$hitname.'::'.$hitdate] = 1;
					$uniquevisitorexitpage[$hituser] = $hitname;
					if (!isset($uniquevisitorentrypage[$hituser]))
					{
						$uniquevisitorentrypage[$hituser] = $hitname;
						$uniquevisitorreferer[$hituser] = $referer;
					}
					if ($q != '')
					{
						if ($a == 'search')
							if (isset($queries[$q])) $queries[$q]++; else $queries[$q]=1;
						if ($a == 'view' && $hitname != swNameURL(@$swMainName))
						{
							$queriesgoodresults[$q][] = $hitname;
						}
					}
					if ($a != '')
					{
						if (isset($actions[$a])) $actions[$a]++; else $actions[$a]=1;
					}

					if ($e != '')
					{
						if (isset($errors[$e])) $errors[$e]++; else $errors[$e]=1;
					}

					
					if ($table)
					{
						$values = swGetAllFields($line);
						$newvalues = array();
						foreach($tablefields as $f)
						{
							if (isset($values[$f]))
							{
								$newvalues[] = '"'.str_replace('"','""',join('::',$values[$f])).'"';

							}
							else
							{
								$newvalues[] = '""';
							}
						}
						
						
						/*
						foreach($values as $v)
						{
							$newvalues[] = '"'.join('::',$v).'"';
						}
						*/
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
		$b = 0;
		$maxaction = 0;
		foreach($uniquevisitors as $hituser=>$h)
		{
			if ($h == 1) $b++;
			$maxaction=max($maxaction,$h);
		}
		$bouncingrate = floor(100*$b/max(1,count($uniquevisitors)));
		$averageaction = floor(10* count($uniquepageviews) / max(1,count($uniquevisitors)))/10;
		$statlines = array();
		$statlines[]= "[[datestart::$datestart]][[dateend::$dateend]][[query::$query]][[regex::$regex]]";
		$statlines[]= "[[hits::$hits]]";
		$statlines[]= "[[totaltime::$totaltime]]";
		$statlines[]= "[[averagetime::$averagetime]]"; 
		$statlines[]= "[[uniquepageviews::".count($uniquepageviews)."]]";
		$statlines[]= "[[uniquevisitors::".count($uniquevisitors)."]]";
		$statlines[]= "[[bouncingrate::".$bouncingrate."%]]";
		$statlines[]= "[[averageaction::".$averageaction."]]";
		$statlines[]= "[[maxaction::".$maxaction."]]";
		
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
		$swLogNameSpaceList = explode('::',@$swLogNameSpace); 
		foreach($uniquepages as $hitpage=>$views)
		{
			$i++;
			if (isset($swLogCount) && $swLogCount && $i>=$swLogCount)
			{

				$found = false;
				foreach($swLogNameSpaceList as $elem)
				{
					if (strpos($hitpage,$elem) === 0) $found = true;
				}
				if (!$found) continue;
			}
			$viewpercentage = sprintf("%0.1f",100*$views/count($uniquepageviews)).'%';
			$statlines[]= "$i. [[name::$hitpage]][[uniqueviews::$views]][[viewpercentage::$viewpercentage]]";
			
			
			
		}
		
		$statlines[]= "[[title::Search keywords]]";
		arsort($queries); $i=0;
		foreach($queries as $k=>$v)
		{
			$i++;
			$statlines[]= "$i. [[query::$k]][[queryhits::$v]]";
			if($i>=50) break; 
		}
		
		$statlines[]= "[[title::Search good results]]";
		asort($queriesgoodresults); $i=0;
		foreach($queriesgoodresults as $k=>$v)
		{
			$i++;
			$statlines[]= "$i. [[querygoodresults::$k]][[querygoodhits::".join('+',$v)."]]";
			if($i>=25) break; 
		}
		$statlines[]= "[[title::Actions]]";
		arsort($actions); $i=0;
		foreach($actions as $k=>$v)
		{
			$i++;
			$statlines[]= "$i. [[action::$k]][[actionhits::$v]]";
			if($i>=25) break; 
		}

		$statlines[]= "[[title::Errors]]";
		arsort($errors); $i=0;
		foreach($errors as $k=>$v)
		{
			$i++;
			$statlines[]= "$i. [[error::$k]][[errorhits::$v]]";
			if($i>=25) break; 
		}
		
		$statlines[]= "[[title::Entry pages]]";
		
		$entrypages = array();
		foreach(@$uniquevisitorentrypage as $u=>$v)
		{
			if (isset($entrypages[$v])) $entrypages[$v]++; else $entrypages[$v]=1;
		}
		arsort($entrypages); $i=0;
		foreach($entrypages as $k=>$v){
			$i++;
			$statlines[]= "$i. [[entrypage::$k]] [[entrypagehits::$v]]";
			if($i>=25) break; 
		}

		$statlines[]= "[[title::Exit pages]]";
		
		$exitpages = array();
		foreach(@$uniquevisitorexitpage as $u=>$v)
		{
			if (isset($exitpages[$v])) $exitpages[$v]++; else $exitpages[$v]=1;
		}
		arsort($exitpages); $i=0;
		foreach($exitpages as $k=>$v){
			$i++;
			$statlines[]= "$i. [[exitpages::$k]] [[exitpageshits::$v]]";
			if($i>=25) break; 
		}
		
		$statlines[]= "[[title::Referer]]";
		
		$referers = array();
		foreach(@$uniquevisitorreferer as $u=>$v)
		{
			if (isset($referers[$v])) $referers[$v]++; else $referers[$v]=1;
		}
		arsort($referers); $i=0;
		foreach($referers as $k=>$v){
			$i++;
			$statlines[]= "$i. [[referers::$k]] [[refererhits::$v]]";
			if($i>=25) break; 
		}

		
		
		$statlines[]= "[[title::Most active users]]"; 
		
		
		arsort($uniquevisitors); $i=0;
		foreach($uniquevisitors as $hituser=>$hits)
		{
			$i++;
			$averagetime = sprintf("%04d",$uniquevisitortime[$hituser]/$hits);
			$statlines[]= "$i. [[user::$hituser]][[userhits::$hits]][[totalusertime::$uniquevisitortime[$hituser]]][[averagetime::$averagetime]] [[referer::$uniquevisitorreferer[$hituser]]] [[entrypage::$uniquevisitorentrypage[$hituser]]] [[exitpage::$uniquevisitorexitpage[$hituser]]] ";
			if($i>=50) break; 
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
		if (!defined("CRON")) $swParsedContent .= "\n\n<pre>".join("\n",$statlines)."\n</pre>\n";	
		
		
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
		if (!defined("CRON")) $swParsedContent .= "\n\n<pre>\n\n</pre>\n";
		if (!defined("CRON")) $swParsedContent .= "\n\n<pre>".join("\n",$rawlines)."\n</pre>\n";
		
		if (swGetArrayValue($_REQUEST,'submitsave',false))
		{
			if ($savename)
			{
				$w = new swRecord;
				$w->name = $savename;
				$w->content = join("\n",$rawlines);
				$w->insert();
			}
			else
			{
				$swError = 'no name for saved log';
			}
		}
	}

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