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
	
	$lastrevisiontime = $item->timestamp;
	$i++;
	
	if ($item->wikinamespace()=="Image" || $item->wikinamespace()=="Category")
	{
		$swParsedContent .= "$item->timestamp <nowiki><a href='index.php?action=edit&revision=$r'>$r</a></nowiki> [[:$item->name]] $item->status $item->user <i><nowiki>$item->comment</nowiki></i>\n";
	}
	else
	{
		if ($item->status != '')
			$swParsedContent .= "$item->timestamp <nowiki><a href='index.php?action=edit&revision=$r'>$r</a></nowiki> [[$item->name]] $item->status $item->user <i><nowiki>$item->comment</nowiki></i>\n";
		else
			$swParsedContent .= "Missing $r\n";
	}
	
	

}
unset($now);

$swParseSpecial = true;


?>