<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Orphaned Pages";
$swParsedContent = "Pages that are not linked (except category, image logs, template and user namespace).<br><br>";


$start = @$_REQUEST['start'];
$limit = 500;
		

$revisions = swQuery(array('SELECT _name','CALC n _name URLIFY %(.*)/.*% $1 REGEX','WHERE n !=* template:','WHERE n !=* image:',
'WHERE n !=* category:','WHERE n !=* user:','WHERE n !=* logs:', 'PROJECT n','SELECT _link','CALC n _link URLIFY %(.*)/.*% $1 REGEX','PROJECT n','EXCEPT n'));

$lines = array();
foreach ($revisions as $row)
{
	
	
	$name = $row['n'];
	if (stristr($name,'/')) $name = substr($name,0,strpos($name,'/'));
	$url = swNameURL($name);
	$lines[$url] = '<li><a href="index.php?name='.$url.'">'.$name.'</a></li> ';

}
sort($lines);
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
	$navigation .= '<a href="index.php?name=special:orphaned-pages&start='.sprintf("%0d",$start-$limit).'"> '.swSystemMessage('back',$lang).'</a> ';
		
$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
if ($start<$count-$limit)
	$navigation .= ' <a href="index.php?name=special:orphaned-pages&start='.sprintf("%0d",$start+$limit).'">'.swSystemMessage('forward',$lang).'</a>';
	$navigation .= '</div></nowiki>';

$swParsedContent .= $navigation.join(' ',$lines2).$navigation;

$swParseSpecial = false;


?>