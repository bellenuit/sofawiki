<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Images";

$extension = "*";
if (isset($_REQUEST['extension'])) $extension = $_REQUEST['extension'];
else $extension = ".jpg";
$extension0 = $extension;
if ($extension == "*") $extension = "";

$link = "\"<nowiki><a href='index.php?name=special:images&extension=\".extension.\"'>\".extension.\"</a></nowiki>\"";

$q = '

filter _namespace "image", _name
extend extension = regexreplace(_name,"(.*)(\..*?)","$2")
select substr(extension,0,1) == "."
project extension
insert "*"
order extension a
update extension = '.$link.' 
project extension concat
update extension_concat = replace(extension_concat,"::"," ")
rename extension_concat Extensions
print raw

echo " "
echo "Extension '.$extension0.'"

filter _namespace "image", _name
select _name regex "'.$extension.'$"
order _name
extend image = "[["._name."|160]]" 
update _name = "[[:"._name."]]"
project _name, image
label _name "", image ""
print grid 100';

$lh = new swRelationLineHandler;
$swParsedContent = $lh->run($q);
$swParseSpecial = true;


?>