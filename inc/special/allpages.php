<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = 'Special:All Pages';

echotime('all pages start');


$namespace = 'main';
if (isset($_REQUEST['namespace'])) $namespace = $_REQUEST['namespace'];
if ($namespace == '*') $namespace = '';

$alpha = '';
if (isset($_REQUEST['alpha'])) $alpha = $_REQUEST['alpha'];


$start = @$_REQUEST['start'];

if (!$start) $start = 1;
$limit = 100;

$previous = ' <nowiki><a href=\'index.php?name='.swNameURL($name).'&start='.($start-$limit).'&namespace='.$namespace.'&alpha='.$alpha.'\'>&lt--</a></nowiki>';
$next = ' <nowiki><a href=\'index.php?name='.swNameURL($name).'&start='.($start+$limit).'&namespace='.$namespace.'&alpha='.$alpha.'\'>--&gt;</a></nowiki>';	


$singlequote = "'";

$q = '
set start = '.$start.'
set limit1 = '.$limit.'
set previous = "'.$previous.'"
set next = "'.$next.'"
set alph = "'.$alpha.'"
set namespace = "'.$namespace.'"
if alph == ""
set alph = ""
end if

filter _namespace
insert "*"
order _namespace
extend _namespace2 = _namespace
update _namespace2 = "<b>"._namespace."</b>" where _namespace == namespace
update _namespace = "<nowiki><a href=\'index.php?name=special:all-pages&namespace="._namespace."\'>"._namespace2."</a> </nowiki>"
project _namespace concat
update _namespace_concat = replace(_namespace_concat,"::","")
rename _namespace_concat Namespaces
print raw

echo " "

filter _namespace namespace, _name
// order _name

dup
set namespacelength = length(namespace)+1
if namespace == "main" or namespace == ""
set namespacelength = 0
end if
update _name = lower(substr(_name,namespacelength,1))
order _name a
project _name
extend _name2 = _name
update _name = "<b>"._name."</b>" where _name == alph
update _name = "<nowiki><a href=\'index.php?name=special:all-pages&amespace="._namespace."&alpha="._name."\'</a>"._name2."</a> </nowiki>"
project _name concat
rename _name_concat Alpha
update Alpha = replace(Alpha,"::","")
label Alpha "&nbsp;"
print raw
echo " "
pop

if alph !== ""
select lower(substr(_name,0,1)) == alph
end if

order _name a
extend mainname = regexreplace(_name,"/\w\w","")
extend _nameurl = "[["._name."]]"
update _nameurl = "[[:"._name."]]" where _namespace regex "category|image"
project mainname, _nameurl concat
rename _nameurl_concat _nameurl
project _nameurl

// add counter
dup
project _nameurl count
set nc = _nameurl_count
set ende = min(start+limit1-1,nc)
set ncs =  start. " - " . ende . " / ". nc

if start > 1 
set ncs = ncs . previous
end if

if start + limit1 -1 < nc 
set ncs = ncs . next
end if

pop

limit start limit1

label _nameurl "" 

echo ncs
print space
echo _newline
echo ncs
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

echotime('all pages end');



?>