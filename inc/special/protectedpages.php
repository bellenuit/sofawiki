<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Proteced Pages";

$q = '
filter _name, _status "protected"
project _name
order _name a
update _name = "[["._name."]]"
​label _name "Name"
print grid 100
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>