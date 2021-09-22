<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Uncategorized Pages";
$swParsedContent = "";

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
filter _namespace "main", _name
project _name
filter _namespace "main", _name, _category
​project _name
difference
​order _name a
update _name = "[["._name."]]"
​label _name ""


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