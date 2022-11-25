<?php
	
/** 
 * Shows a list of all files im image namespace that are not via an image or media link
 *
 * special:categories
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Unused Files";
$swParsedContent = "Files with no image and no media link.<br><br>";


$q = '

filter _namespace "image", _name
project _name
extend _link = urltext(_name)
filter _link
update _link = replace(urltext(_link),"media:","image:")
join leftanti
update _name = link(":"._name)
project _name
order _name a
label _name ""

print grid 50

';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>