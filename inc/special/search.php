<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = swSystemMessage('search',$lang).':"'.trim($query).'"';

$ns = join('|',$swSearchNamespaces); 
if (stristr($ns,'*')) $ns = '*';

$urlquery = swNameURL($query);


$swError = '';
$found = false;
$name = 'special:search';

if (@$swOldSearch)
{
	include "inc/special/oldsearch.php";
	$wiki->content = $swParsedContent;
}
elseif (@$swCustomSearch)
{
	$query = @$_REQUEST['query'];
	$start = @$_REQUEST['start'];
	if (!$start) $start = 1;
	$limit = 500;
	
	$previous = ' <nowiki><a href=\'index.php?action=search&start='.($start-$limit).'&query='.$query.'\'>&lt--</a></nowiki>';
	$next = ' <nowiki><a href=\'index.php?action=search&start='.($start+$limit).'&query='.$query.'\'>--&gt;</a></nowiki>';	

	$wiki = new swWiki;
	$wiki->name = 'Template:'.$swCustomSearch;
	$wiki->lookup();
	$wiki->name = swSystemMessage('search',$lang).':"'.trim($query).'"'; // force parse template
	$wiki->content = str_replace("{{query}}",$query,$wiki->content);
	$wiki->content = str_replace("{{start}}",$start,$wiki->content);
	$wiki->content = str_replace("{{limit}}",$limit,$wiki->content);
	$wiki->content = str_replace("{{previous}}",$previous,$wiki->content);
	$wiki->content = str_replace("{{next}}",$next,$wiki->content);

	
	
	

	$swParseSpecial = true;
	
}
else
{
	$start = 1; if (isset($_REQUEST['start'])) $start = $_REQUEST['start'];
	$swParsedContent = swRelationSearch($query,$start,500, @$swSearchTemplate, @$swRelationFilterHook); $swParseSpecial = true;
	$wiki->content = $swParsedContent;
	
}

if (@$swParseSpecial)
{
	$swParsedContent = $wiki->parse();
}


if (isset($swOvertime) && $swOvertime)
	$swParsedContent .= '<div id="searchovertime">'.swSystemMessage('search-limited-by-timeout.',$lang).' <a href="index.php?action=search&query='.$query.'">'.swSystemMessage('search-again',$lang).'</a></div>';


?>