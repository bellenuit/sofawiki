<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Long Pages";
$swParsedContent = "The 100 longest pages in main namespace. Note: This page is slow, because _content is not cached.<br><br>";


$q = '
filter _namespace "main", _name, _length
project _name, _length
update _name = "[["._name."]]"
order _length 9
limit 1 100
label _name "", _length ""
print
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>
