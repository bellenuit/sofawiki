<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Unused Categories";
$swParsedContent = "Category Pages not used.<br><br>";


$q = '
filter _namespace "category", _name
select not (_name regexi "/")
project _name
filter _category
extend _name = "Category:"._category
project _name
difference
order _name a
update _name = "[[:"._name."]]"
​label _name ""
print
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>