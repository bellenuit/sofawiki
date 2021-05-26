<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Categories";

$q = '
filter _namespace "category", _name
order _name a
update _name = "[[:"._name."]]"
project _name
label _name "" 
print grid 100';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>