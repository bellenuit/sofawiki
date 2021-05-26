<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:System Messages";

$swParsedContent .= "===Translations===";
$swParsedContent .= "\nMissing translations are marked _MISSING.";


$lines= array();


foreach ($swSystemDefaults as $k=>$v)
{
	if (substr($k,-3,1)=='/')
	{
		$key  = substr($k,0,-3);
		$la = substr($k,-2);
		
		$v = str_replace('"','&quote;',$v);
		$v = str_replace(PHP_EOL,'<br>',$v);
		
		$lines[] = '"'.$key.'","'.$la.'", "'.str_replace('"','\"',$v).'"';		
	}	
}

$q = 'relation key, lang, data
data
'.join(PHP_EOL,$lines).'
end data
select lang regex "'.join('|',$swLanguages).'" 
dup
dup
project key
swap
project lang
join cross
swap
join left
update data = "<nowiki>_MISSING</nowiki>" where data == ""
'."extend link = \"<nowiki><a href='index.php?action=edit&name=system:\".key.\"/\".lang.\"'>\".key.\"/\".lang.\"</a></nowiki>\"".'
order key, lang
print grid';

//echo $q;

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;






?>