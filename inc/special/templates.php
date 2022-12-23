<?php

if (!defined("SOFAWIKI")) die("invalid acces");


$swParsedName = "Special:Templates";

$q = '
filter _namespace "template", _name
update _name = link(_name)
project _name
order _name a
label _name "" 
print grid 50';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>
