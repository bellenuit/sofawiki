<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Orphaned Pages";
$swParsedContent = "Pages that are not linked in namespace (except subpages).<br><br>";


$q = '
filter _namespace "main", _name
select _name not (regex "\/")
extend _link = urltext(_name)
filter _link "*"
update _link = urltext(_link)
join leftanti 
project _name
update _name = "[["._name."]]"
order _name a
label _name ""
print grid 100
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;



?>