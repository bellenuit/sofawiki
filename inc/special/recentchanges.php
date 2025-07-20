<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Recent Changes";
$db->init();
$last = $db->lastrevision;


$swParsedContent = "";
$i = 0;

$item = new swWiki;
$now = date("Y-m-d H:i:s",time()-60*60*24*30);
$lastrevisiontime = date("Y-m-d H:i:s",time());

for ($r = $last; $r > $last - 200 && $r>0; $r--)
{
	if ($i>100 && $lastrevisiontime<$now) continue;
	
	$item = new swWiki;
	$item->revision = $r;
	$item->lookup(true);
	$n = $item->name;
	$urlname = swNameURL($n);
	
	$lastrevisiontime = $item->timestamp;
	$i++;
	
	if ($item->status != '')
			$swParsedContent .= $item->timestamp. ' <a href="index.php?action=edit&revision='.$r.'">'.$r.'</a> <a href="index.php?action=view&name='.$urlname.'"">'.$n.'</a> </nowiki> '.$item->status.' '.$item->user.'<i>'.$item->comment.'</i><br>';
		else
			$swParsedContent .= 'Missing '.$r.'<br>';
	
	

}
unset($now);

$swParseSpecial = false;


?>