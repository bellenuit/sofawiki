<?php
	
/** 
 * Allows search of field values and the relating pages
 *
 * Used by the $action allpages.
 * Optional parameter $namespace (default main = main namespay, * = all pages)
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = 'Special:Field Search';

echotime('fieldsearch start');

$field = '';
if (isset($_REQUEST['field'])) $field = $_REQUEST['field'];




$list = array_keys(swMonogramFields());
asort($list);
$blist = array();
foreach($list as $elem)
{
	$blist[] = '"'.$elem.'"';
}
$keys = 'data'.PHP_EOL.join(PHP_EOL,$blist).PHP_EOL.'end data'.PHP_EOL;


$singlequote = "'";

$q = '
set field = "'.$field.'"

relation key
'.$keys.'

order key
extend key2 = key
update key2 = "<b>".key."</b>" where key == field
update key = "<nowiki><a href=\'index.php?name=special:field-search&field=".key."\'>".key2."</a> </nowiki>"
project key concat
update key_concat = replace(key_concat,"::","")
rename key_concat Fields
print raw

echo " "

if field == "" 
echo "Select a field."
echo "NB: Only the first 255 characters of each field are shown."
stop
end if

filter '.$field.' "*", _name

select trim('.$field.') !== ""
update '.$field.' = substr(field,0,255)."..." where length('.$field.')>255

extend _nameurl = "[["._name."]]"
update _nameurl = "[[:"._name."]]" where _name regexi "^category:|^image:"
project '.$field.', _nameurl
order '.$field.', _nameurl
label _nameurl "Page"

print grid 50

';


$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

echotime('all pages end');



?>