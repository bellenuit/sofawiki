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
			$list[$url] = '"'."&lt;nowiki>&lt;a class='invalid' href='index.php?action=history&revision=".$rev."'>".$w->name."&lt;/a>&lt;/nowiki>".'"' ;
	}

}
ksort($list);

$data = join(PHP_EOL,$list);



$q = '
relation deleted
data
'.$data.'
end data
label deleted ""
print grid
';



$lh = new swRelationLineHandler;
$s = str_replace("&lt;","<",$lh->run($q));
$swParsedContent .= $s;
$swParseSpecial = true;


// $swParsedContent .= join(' ',$list);

// $swParsedContent .= '</ul>';


// $swParseSpecial = false;


?>