<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Users";

$swParsedContent = 'Add new user: <a href="index.php?name=special:passwords">Special:Passwords</a>';

$q = '
filter _namespace "user", _name
order _name a
update _name = "[["._name."]]"
project _name
label _name "" 
print grid 100';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>