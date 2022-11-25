<?php
	
/** 
 * Shows a list of all category pages.
 *
 * special:categories
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Categories";


$q = '

filter _namespace "category", _name
extend mainname = regexreplace(_name,"/\w\w","")
order _name a
update _name = link(":"._name)
project mainname, _name concat
update _name_concat = replace(_name_concat,"::",", ")
project _name_concat
rename _name_concat _name
label _name "" 

print grid 50



';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>