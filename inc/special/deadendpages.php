<?php
	
	/** 
 * Shows a list of all pages with no interrnal links.
 *
 * Used by the $action deadendpages.
  */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Dead End Pages";
$swParsedContent = "Pages in main namespace with no internal links (links via Templates ignored here).<br><br>";


$q = '
filter _namespace "main", _name
filter _namespace "main", _name, _link "*"
project _namespace, _name
difference
project _name
update _name = link(_name)
order _name
​label _name ""

print grid 50
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>