<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Dead End Pages";
$swParsedContent = "Links to non existing pages (except media and special namespace).<br><br>";

$start = @$_REQUEST['start'];
$limit = 500;
		

$revisions = swQuery(array('SELECT _revision, _name, _link','CALC n _link URLIFY %(.*)/.*% $1 REGEX %media:% image: REGEX','WHERE n !=* special:','PROJECT _revision, _name, _link, n','SELECT _name','CALC n _name URLIFY %(.*)/.*% $1 REGEX','PROJECT n','EXCEPT n'));

$lines = array();
foreach ($revisions as $row)
{
	
	
	$origin = $row['_name'];
	$name = $row['_link'];
	$revision = $row['_revision'];
	$url = swNameURL($origin);
	
	// remove false positives
	if (stristr($name,'{{') && stristr($name,'}}')) continue;
	if (trim($name)=='') continue;
	
	$w = new swWiki;
	$w->name = $url;
	$w->lookup();
	
	if ($w->revision > 0 && $w->status != 'deleted')
	
	$lines[$url] = '<li>'.$name.' in <a href="index.php?name='.$url.'">'.$origin.'</a> (<a href="index.php?revision='.$revision.'&action=edit">'.$revision.'</a>)</li> ';

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