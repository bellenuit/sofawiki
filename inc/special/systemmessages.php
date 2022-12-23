<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:System Messages";

$swParsedContent .= "===Translations===";

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

//print_r($lines);

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

filter _namespace "system", _name, _content
rename _content data2
extend test = lower(replace(_name,"/","::"))
select test regex "::"
extend lang = regexreplace(test,"system:(.+)::(.+)","$2")
extend key = urltext(regexreplace(test,"system:(.+)::(.+)","$1"))
project key, lang, data2

join left
update data = data2 where data2 !== ""

update data = "-" where data == ""
'."extend link = \"<nowiki><a href='index.php?action=edit&name=system:\".key.\"/\".lang.\"'>\".key.\"/\".lang.\"</a></nowiki>\"".'
project link, data
order link
print grid 50';

//echo $q;

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;






?>