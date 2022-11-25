<?php
	
/** 
 * Shows a list of main namespace pages without categories
 *
 * special:uncategorized pages
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Uncategorized Pages";
$swParsedContent = "";


$q = '

filter _namespace "main", _name
project _name
filter _namespace "main", _name, _category "*"
​project _name
difference
​order _name a
update _name = link(_name)
​label _name ""


print grid 50


';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>