<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:All Pages";

$start = @$_REQUEST['start'];
$limit = 500;
if (isset($_REQUEST['ns']))
{
	$ns = @$_REQUEST['ns'];
	$start = 0; $limit = 999999999;
}

$revisions = swFilter('SELECT _name WHERE _name *','*','query');
$lines = array();
$namespaces = array();

echotime('allpages revisions '.count($revisions));
foreach ($revisions as $row)
{
	
	$name = $row['_name'];
	if (stristr($name,'/')) $name = substr($name,0,strpos($name,'/'));
	if (stristr($name,':')) { $namespace = strtolower(substr($name,0,strpos($name,':'))); $namespaces[$namespace] = $namespace; }
	else $namespace = '';
	$url = swNameURL($name);
	if (!$url) continue;
	
	
	if (!isset($ns) || $namespace == $ns)
	$lines[$url] = '<li><a href="index.php?name='.$url.'">'.$name.'</a></li> ';

}
sort($lines);
$count = count($lines);
echotime('allpages lines '.count($lines));

$lines2 = array();
$i =0;
foreach($lines as $line)
{
	if ($i < $start) { $i++; continue;}
	$i++;
	if ($i > $start + $limit) continue;
	$lines2[] = $line;
}
ksort($namespaces);

$navigation = '<nowiki><div class="categorynavigation">';
$navigation .= '<a href="index.php?name=special:all-pages&start=0">all</a> ';
foreach($namespaces as $namespace)
	$navigation .= '<a href="index.php?name=special:all-pages&ns='.$namespace.'"> '.$namespace.'</a> ';
$navigation .= '<br>';
if ($start>0)
	$navigation .= '<a href="index.php?name=special:all-pages&start='.sprintf("%0d",$start-$limit).'"> '.swSystemMessage('back',$lang).'</a> ';
		
$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
if ($start<$count-$limit)
	$navigation .= ' <a href="index.php?name=special:all-pages&start='.sprintf("%0d",$start+$limit).'">'.swSystemMessage('forward',$lang).'</a>';
	$navigation .= '</div></nowiki>';

$swParsedContent .= $navigation.join(' ',$lines2).$navigation;

$swParseSpecial = false;


?>