<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Deleted Pages";

$swParsedContent = "";

// deletedbitmap && currentbitmap use url

$currentbitmap = $db->currentbitmap->duplicate();
$deleted = $db->deletedbitmap->toarray();

$swParsedContent .= '<ul>';

$list = array();
foreach($deleted as $rev)
{
	if ($currentbitmap->getbit($rev))
	{
		$w = new swRecord;
		$w->revision = $rev;
		$w->lookup();
		$url = swNameURL($w->name);
		if ($w->name != '')
			$list[$url] = '<li><a class="invalid" href="index.php?action=edit&revision='.$rev.'">'.$w->name.'</a></li>' ;
	}

}
ksort($list);
$swParsedContent .= join(' ',$list);

$swParsedContent .= '</ul>';


$swParseSpecial = false;


?>