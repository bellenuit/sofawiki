<?php
	
/** 
 * Shows a list of links to non existing pages in main namespace
 *
 * special:wanted-pages
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Wanted Pages";
$swParsedContent = "Links to non existing pages in main namespace.<br><br>";



$q = '
filter _namespace "", _name
extend _link = urltext(_name)
project _link
filter _namespace "main", _name, _link "*"
update _link = urltext(_link)
project _name, _link
join rightanti
select _link not ( regex "media|special|system" ) and _link !== "" and _link !== "-" and not (_link regex ":")
update _link = link(_link)." in ".link(_name)
project _link
rename _link _name
order _name a
label _name ""
print grid 50


';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

?>