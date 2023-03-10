<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Redirects";
$swParsedContent = "";

$q = '
filter _name, _short "#REDIRECT"
select _short regex "^#REDIRECT"
order _name a
update _name = "<nowiki><a href=""index.php?name="._name."&action=edit"">"._name."</a></nowiki>"
update _short = substr(_short,10,999)
​label _name "Name", _short "Redirect to"
print 100
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

?>