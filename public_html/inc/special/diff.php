<?php

if (!defined("SOFAWIKI")) die("invalid acces");


if ($user->hasright("modify", $wiki->wikinamespace())  ||
$user->hasright("protect", $wiki->wikinamespace()) || $user->hasright("delete", $wiki->wikinamespace()))	

{

	$wiki2 = new swWiki;
	$wiki2->name = $wiki->name;
	$wiki2->lookup();
	
	
	//$wiki->writecurrent();	// we need to force read again because lookup writes the current version.
	
	
		$swParsedName = "Diff: $wiki->name (revision $wiki2->revision against $wiki->revision)";
		$swParsedContent = "<pre>".htmldiff($wiki->content,$wiki2->content)."</pre>";
		if ($user->hasright("modify", $wiki->wikinamespace()))
		$swParsedContent .= "<p><a href='index.php?revision=$wiki->revision&action=revert'>Revert to revision $wiki->revision</a></p>";
		$swParsedContent .= "<p><a href='index.php?revision=$wiki->revision&action=edit'>Edit revision $wiki->revision</a></p>";
	
	
		
		$swParsedContent .= "\n\n<div class='preview'>".$wiki->parse()."\n</div>";
		
	
	}
	else
	{
		$swError = "No access";
	}

?>