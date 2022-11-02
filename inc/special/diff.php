<?php
	
	
/**
 *	Renders difference between the revision of the wiki and the present revision of the wiki.
 */


if (!defined('SOFAWIKI')) die('invalid acces');


if ($user->hasright('modify', $wiki->wikinamespace())  ||
	$user->hasright('protect', $wiki->wikinamespace()) || 
	$user->hasright('delete', $wiki->wikinamespace()))	

{
	$wiki2 = new swWiki;
	$wiki2->name = $wiki->name;
	$wiki2->lookup();
	$diff = swHtmlDiff($wiki->content,$wiki2->content);
		
	$swParsedName = 'Diff: '.$wiki->name.' (revision '.$wiki2->revision.' against '.$wiki->revision.')';
	$swParsedContent = '<pre>'.$diff.'</pre>';
	
	if ($user->hasright('modify', $wiki->wikinamespace()))
	{
		$swParsedContent .= '<p><a href="index.php?revision='.$wiki->revision.'&action=revert">Revert to revision '.
		$wiki->revision.'</a></p>';
	}
	$swParsedContent .= '<p><a href="index.php?revision='.$wiki->revision.'&action=edit">Edit revision '.$wiki->revision.'</a></p>';

	$swParsedContent .= PHP_EOL.PHP_EOL.'<div class="preview">'.$wiki->parse().PHP_EOL.'</div>';
}
else
{
	$swError = 'No access';
}

?>