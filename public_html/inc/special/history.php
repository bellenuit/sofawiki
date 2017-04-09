<?php

if (!defined("SOFAWIKI")) die("invalid acces");

if ($user->hasright("modify", $wiki->name) || $user->hasright("propose", $wiki->name) |
	$user->hasright("protect", $wiki->name) || $user->hasright("delete", $wiki->name) )

 {

	$swParsedName = swSystemMessage("History",$lang)." ".$wiki->localname($lang); ;
		
	$list= $wiki->history();
	$historytexts = array();
	foreach($list as $item)
	{
		
	
		
		$item->lookup(true);
		
		
		$historytexts["$item->timestamp $item->revision"] = "<li><a href='index.php?revision=$item->revision&action=diff'>$item->revision</a> $item->timestamp  $item->status $item->user <i>$item->comment</i></li>";
	}
	krsort($historytexts);
	$swParsedContent = "<ul>".join("\n",$historytexts)."</ul>";
 }
else
{
	$swError = swSystemMessage("No access",$lang);
}


?>