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
write "images"
extend extension = regexreplace(_name,"(.*)(\..*?)","$2")
select substr(extension,0,1) == "."
project extension
insert "*"
order extension a
update extension = lower(extension)
extend extension2 = extension
update extension2 = "<b>".extension."</b>" where extension == ext
update extension = "<nowiki><a href=\'index.php?name=special:images&extension=".extension."\'>".extension2."</a></nowiki> "
project extension concat
update extension_concat = replace(extension_concat,"::"," ")
rename extension_concat Extensions
print raw
echo " "

read "images"
set namespacelength = length(namespace)+1
if namespace == "main" or namespace == ""
set namespacelength = 0
end if
update _name = lower(substr(_name,namespacelength,1))
order _name a
project _name
extend _name2 = _name
update _name = "<b>"._name."</b>" where _name == alph
update _name = "<nowiki><a href=\'index.php?name=special:images&extension=".extenstion."&alpha="._name."\'</a>"._name2."</a> </nowiki>"
project _name concat
rename _name_concat Alpha
update Alpha = replace(Alpha,"::","")
label Alpha "&nbsp;"
print raw
echo " "
pop

read "images"
select _name regexi ext."$"

if alph !== ""
select lower(substr(_name,namespacelength,1)) == alph
end if


order _name
update _name = "<div style=\'width:200px; height:200px; float:left\'>[["._name."|160|160|auto]]<br>[[:"._name."|".substr(_name,6,99)."]]</div>"
project _name


label _name ""

echo ncs
print space
echo "<div style=\'float:none; clear:both\'></div>"
echo ncs
';




$lh = new swRelationLineHandler;
$swParsedContent = $lh->run($q);
$swParseSpecial = true;


?>