<?php
	
/** 
 * Shows a list of all templates that are not used directly
 *
 * special:unused-templates
 * The script does not see templates in relation code
 */


if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Unused Templates";
$swParsedContent = "";



$q = '
filter _namespace "template", _name
project _name
filter _template
extend _name = "Template:"._template
project _name
difference
order _name a
update _name = link(_name)
​label _name ""
print grid 50
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

?>