<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Unused Templates";
$swParsedContent = "";



$q = '
filter _namespace "template", _name
project _name
filter _template
extend _name = "Template:"._template
project _name
difference
order _name a
update _name = "[["._name."]]"
​label _name ""
print 
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

?>