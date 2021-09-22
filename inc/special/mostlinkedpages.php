<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Most Linked Pages";
$swParsedContent = "";



$q = '
filter _name, _link "*"
extend cat = urltext(_link)
project cat, _link first, _name count
order _name_count 9
update _link_first = "<nowiki><a href=\'index.php?name="._link_first."\'>"._link_first."</a></nowiki>"
project _link_first, _name_count 
​label _link_first "Page", _name_count "Link count"
​print 100
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;




?>