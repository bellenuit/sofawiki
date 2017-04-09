<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:System Messages";

$revisions = swFilter('SELECT _name FROM system: WHERE _name *','*','query');
$names = array();
foreach($revisions as $r=>$row)
{
	if (isset($row['_name']))
	{
		$names[$row['_name']] = $r;
	}
}

uksort($names, 'strnatcasecmp'); 
//ksort($names);
$swParsedContent = "";
$oldfirst = "";
$oldshort = "";

$foundmessages=array();
$foundshorts=array();

$missing = "";
$pages = "";
$defaults = "";

foreach ($names as $n=>$s)
{
	
		$first = strtolower(substr($n,7,1));
		if ($oldfirst && $oldfirst != $first) $pages .= "\n\n";
		$shorts = explode("/",$n);
		$short = $shorts[0];
		if ($short != $oldshort) $pages .= "\n"; else $pages .= " ";
		$shortname = substr($n,7);
		
		$pages .= "[[$n|$shortname]]";
		$foundmessages[swNameURL($n)] = true;
		$foundshorts[swNameURL($short)] = true;
		$oldfirst = $first;
		$oldshort = $short;
 	
}

uksort($swSystemDefaults ,'strnatcasecmp');

$oldfirst = "";
$oldshort = "";

foreach ($swSystemDefaults as $k=>$v)
{
		if (!array_key_exists("system:$k",$foundmessages))
		{
			$first = strtolower(substr($k,0,1));
			if ($oldfirst && $oldfirst != $first) $defaults .= "\n\n";
			$shorts = explode("/",$k);
			$short = $shorts[0];
			if ($short != $oldshort) $defaults .= "\n\n"; else $defaults .= " ";
			$defaults .= "[[System:$k|$k]] $v ";
			$oldfirst = $first;
			$oldshort = $short;
		
		}
}
$oldfirst = "";
uksort($foundshorts,'strnatcasecmp');

foreach($foundshorts as $s=>$v)
{
	foreach ($swLanguages as $l)
	{
		if (!array_key_exists("$s/$l",$foundmessages))
		{
			
			$first = strtolower(substr($s,7,1));
			if ($oldfirst != "" && $oldfirst != $first) $missing .= "\n\n";
			$missing .="[[$s/$l]] ";
			$oldfirst = $first;
		}
	}
}


$swParsedContent = "====Missing translations====\n$missing";


$swParsedContent .= "\n====Pages====\n$pages";

$swParsedContent .= "\n====Defaults====\n$defaults";


$swParseSpecial = true;



?>