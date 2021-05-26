<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Short Pages";
$swParsedContent = "The 100 shortest pages in main namespace, without redirects but with subpages. Note: This page is slow, because _content is not cached.<br><br>";


$q = '
filter _namespace "main", _name, _content "*"
select _content not (regex "^#REDIRECT")
extend size = length(_content)
project _name, size
update _name = "[["._name."]]"
order size 1
limit 1 100
label _name "", size ""
print grid
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>



