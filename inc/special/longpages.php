<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Long Pages";
$swParsedContent = "Note: This page is slow, because _content is not cached.<br><br>";


$start = @$_REQUEST['start'];
$limit = 500;
		

$revisions = swQuery(array('SELECT _name, _content',
'CALC len _content STRLEN',
'PROJECT _name, len','ORDER len NUMERIC DESC'));

$lines = array();
foreach ($revisions as $row)
{
	
	
	$name = $row['_name'];
	$len = $row['len'];
//	if (stristr($name,'/')) $name = substr($name,0,strpos($name,'/'));
	$url = swNameURL($name);
	$lines[$url] = '<li><a href="index.php?name='.$url.'">'.$name.'</a> '.$len.'</li> ';

}
$count = count($lines);

$lines2 = array();
$i =0;
foreach($lines as $line)
{
	if ($i < $start) { $i++; continue;}
	$i++;
	if ($i > $start + $limit) continue;
	$lines2[] = $line;
}


$navigation = '<nowiki><div class="categorynavigation">';
if ($start>0)
	$navigation .= '<a href="index.php?name=special:long-pages&start='.sprintf("%0d",$start-$limit).'"> '.swSystemMessage('back',$lang).'</a> ';
		
$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
if ($start<$count-$limit)
	$navigation .= ' <a href="index.php?name=special:long-pagess&start='.sprintf("%0d",$start+$limit).'">'.swSystemMessage('forward',$lang).'</a>';
	$navigation .= '</div></nowiki>';

$swParsedContent .= $navigation.join(' ',$lines2).$navigation;

$swParseSpecial = false;


?>