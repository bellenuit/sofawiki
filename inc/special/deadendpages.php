<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Dead End Pages";
$swParsedContent = "Pages in main namespace with no internal links (links via Tenplates ignored here).<br><br>";

$q = '
filter _namespace "main", _name
filter _namespace "main", _name, _link "*"
project _namespace, _name
difference
project _name
update _name = "[["._name."]]"
order _name
​label _name ""
​print grid 100
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>