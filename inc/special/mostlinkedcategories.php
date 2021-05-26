<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Most Linked Categories";
$swParsedContent = "";

$q = '
filter _name, _category "*"
extend cat = urltext(_category)
project cat, _category first, _name count
order _name_count 9
update _category_first = "[[:Category:"._category_first."]]"
project _category_first, _name_count 
​label _category_first "Category", _name_count "Count"
​print grid 25
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;



		




?>