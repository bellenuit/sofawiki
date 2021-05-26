<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = swSystemMessage('search',$lang).':"'.$query.'"';

$ns = join('|',$swSearchNamespaces); 
if (stristr($ns,'*')) $ns = '*';

$urlquery = swNameURL($query);



$found = false;

if ($swOldSearch) {

include "inc/special/oldsearch.php";

}
else
{
	$start = 1; if (isset($_REQUEST['start'])) $start = $_REQUEST['start'];
	$swParsedContent = swRelationSearch($query,$start,500, @$swSearchTemplate); $swParseSpecial = true;
	
}

if (isset($swParseSpecial))
{
	
	$wiki->content = $swParsedContent;
	$wiki->parsers = $swParsers;
	$swParsedContent = $wiki->parse();
}


if (isset($swOvertime) && $swOvertime)
	$swParsedContent .= '<div id="searchovertime">'.swSystemMessage('search-limited-by-timeout.',$lang).' <a href="index.php?action=search&query='.$query.'">'.swSystemMessage('search-again',$lang).'</a></div>';


?>