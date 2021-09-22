<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Unused Files";
$swParsedContent = "Files with no image and no media link.<br><br>";

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

filter _namespace "image", _name
project _name
extend _link = urltext(_name)
filter _link
update _link = replace(urltext(_link),"media:","image:")
join leftanti
update _name = "[[:"._name."]]"
project _name
order _name a
label _name ""

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

echo ncs
print space
echo _newline
echo ncs
';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>