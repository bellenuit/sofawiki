<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Short Pages";
$swParsedContent = "The 100 shortest pages in main namespace, without redirects.<br><br>";


$q = '
filter _namespace "main", _name, _length
project _name, _length
filter _namespace "main", _name, _length, _content "-redirect"
select _content regex "#REDIRECT"
project _name, _length
difference
update _name = link(_name)
order _length 1
limit 1 100
label _name "", _length ""
print
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>



