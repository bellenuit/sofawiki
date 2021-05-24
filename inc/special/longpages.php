<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Long Pages";
$swParsedContent = "The 100 longest pages in main namespace. Note: This page is slow, because _content is not cached.<br><br>";


$q = '
filter _namespace "main", _name, _content "*"
extend size = length(_content)
project _name, size
update _name = "[["._name."]]"
order size 9
limit 1 100
label _name "", size ""
print grid
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>
