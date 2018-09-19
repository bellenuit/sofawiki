<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Wanted Pages";
$swParsedContent = "Links to non existing pages (except media and special namespace).<br><br>";

$start = @$_REQUEST['start'];
$limit = 500;
		

$revisions = swQuery(array('SELECT _name, _link','WHERE _link !*=* {','CALC _link _link URLIFY' ,'WHERE _link !=* media:','WHERE _link !=* special:','SELECT _name','CALC _link _name URLIFY','EXCEPT _link'));

$lines = array();
foreach ($revisions as $row)
{
	$origin = $row['_name'];
	$name = $row['_link'];

	if (trim($name)=='') continue;
	
	
	
	$url = swNameURL($name);
	$originurl= swNameURL($origin); 
	
	$lines[$url] = '<li><a href="index.php?name='.$url.'" style="color:red;">'.$name.'</a> in <a href="index.php?name='.$originurl.'">'.$origin.'</a></li> ';

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
	$navigation .= '<a href="index.php?name=special:dead-end-pages&start='.sprintf("%0d",$start-$limit).'"> '.swSystemMessage('back',$lang).'</a> ';
		
$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
if ($start<$count-$limit)
	$navigation .= ' <a href="index.php?name=special:dead-end-pages&start='.sprintf("%0d",$start+$limit).'">'.swSystemMessage('forward',$lang).'</a>';
	$navigation .= '</div></nowiki>';

$swParsedContent .= $navigation.join(' ',$lines2).$navigation;

$swParseSpecial = false;


?>