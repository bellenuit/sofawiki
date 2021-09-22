<?php

if (!defined("SOFAWIKI")) die("invalid acces");


$swParsedName = "Special:Templates";

$q = '
filter _namespace "template", _name
update _name = "[["._name."]]"
project _name
order _name a
label _name "" 
print space';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>
