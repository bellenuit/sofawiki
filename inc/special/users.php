<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Users";

$swParsedContent = 'Add new user: [[Special:Passwords]]';

$alpha = '';
if (isset($_REQUEST['alpha'])) $alpha = $_REQUEST['alpha'];

$start = @$_REQUEST['start'];
if (!$start) $start = 1;
$limit = 100;

$previous = ' <nowiki><a href=\'index.php?name='.swNameURL($name).'&start='.($start-$limit).'\'>&lt--</a></nowiki>';
$next = ' <nowiki><a href=\'index.php?name='.swNameURL($name).'&start='.($start+$limit).'\'>--&gt;</a></nowiki>';	


$q = '
set start = '.$start.'
set limit1 = '.$limit.'
set previous = "'.$previous.'"
set next = "'.$next.'"
set namespace = "user"
set alph = "'.$alpha.'"
if alph == "alpha"
set alph = "a"
end if

filter _namespace "user", _name
order _name a

dup
set namespacelength = length(namespace)+1
if namespace == "main" or namespace == ""
set namespacelength = 0
end if
update _name = lower(substr(_name,namespacelength,1))
order _name a
project _name
extend _name2 = _name
update _name2 = "<b>"._name."</b>" where _name == alph
update _name = "<nowiki><a href=\'index.php?name=special:users&alpha="._name."\'</a>"._name2."</a> </nowiki>"
project _name concat
rename _name_concat Alpha
update Alpha = replace(Alpha,"::","")
label Alpha "&nbsp;"
print raw
echo " "
pop

if alph !== ""
select lower(substr(_name,namespacelength,1)) == alph
end if

update _name = "[["._name."]]"
project _name


// add counter
dup
project _name count
set nc = _name_count
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

label _name "" 

echo ncs
print space
echo _newline
echo ncs

';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>