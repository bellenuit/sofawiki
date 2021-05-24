<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = 'Special:All Pages';

$namespace = '';
if (isset($_REQUEST['namespace'])) $namespace = $_REQUEST['namespace'];
else $namespace = "main";

$singlequote = "'";

$q = '
filter _namespace
insert "*"
order _namespace
update _namespace = "<nowiki><a href='.$singlequote.'index.php?name=special:all-pages&namespace="._namespace."'.$singlequote.'>"._namespace."</a> </nowiki>"
project _namespace concat
update _namespace_concat = replace(_namespace_concat,"::","")
rename _namespace_concat Namespaces
print raw

echo " "

echo "Namespace '.$namespace.'"

filter _namespace "'.$namespace.'", _name
order _name
extend _nameurl = "[["._name."]]"
update _nameurl = "[[:"._name."]]" where _namespace regex "category|image"
project _nameurl
label _nameurl "" 
print grid 100';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;





?>