<?php
	
/** 
 * Shows a list of all protected pages.
 *
 * special:protected-pages
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Protected Pages";

$q = '

filter _name, _status "protected"
extend mainname = regexreplace(_name,"/\w\w","")
order _name a
update _name = "[["._name."]]"
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




// $swParsedContent .= join(' ',$list);

// $swParsedContent .= '</ul>';


// $swParseSpecial = false;


?>