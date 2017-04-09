<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Special Pages";

$swParsedContent = ""; 

ksort($swSpecials);

foreach ($swSpecials as $k=>$v)
{
	$label = str_replace("Special:","",$k);
	$swParsedContent .= "*[[Special:$k|$label]]\n";
}

$swParseSpecial = true;


?>