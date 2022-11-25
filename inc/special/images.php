<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Images";
echotime("special:images");

$extension = "*";
if (isset($_REQUEST['extension'])) $extension = $_REQUEST['extension'];
else $extension = ".jpg";
$extension0 = $extension;
if ($extension == "*") $extension = "";

$alpha = '';
if (isset($_REQUEST['alpha'])) $alpha = $_REQUEST['alpha'];

$q = '

set ext = "'.$extension.'"
set alph = "'.$alpha.'"
set namespace = "image"

filter _namespace "image", _name
order _name
update _name = link(replace(_name,"Image:","Imagelazy:"),160,160,"auto").tag("br").link(":"._name,substr(_name,6,99)) where _name regexi "jpg$|jpeg$|gif$|png$"
update _name = link(":"._name) where not ( _name regexi "]]" ) // has set link on line before
project _name

label _name ""

print spacegrid 30

echo tag("div","style=\'float:none; clear:both\"," ")

echo " "
';




$lh = new swRelationLineHandler;
$swParsedContent = $lh->run($q);
$swParseSpecial = true;


?>