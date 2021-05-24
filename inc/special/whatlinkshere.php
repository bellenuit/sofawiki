<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$url =  swNameURL($name);

$swParsedName = 'Special:What links here';
$swParsedContent = 'Pages that link to <b>'.$url.'</b>.<br><br>';
		

$q = '
filter _name, _link "'.$name.'"
select urltext(_link) ==  "'.$name.'"
project _name
order _name a
update _name = "[["._name."]]"
label _name ""
print grid
';



$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

$wiki->content = $swParsedContent;
$wiki->parsers = $swParsers;
$swParsedContent = $wiki->parse();


?>