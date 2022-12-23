<?php
	
/** 
 * Shows a list of all pages, optionally by namespace.
 *
 * Used by the $action allpages.
 * Optional parameter $namespace (default main = main namespay, * = all pages)
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = 'Special:All Pages';

echotime('all pages start');



$namespace = 'main';
if (isset($_REQUEST['namespace'])) $namespace = $_REQUEST['namespace'];
if ($namespace == '*') $namespace = '';

$singlequote = "'";

$q = '
set namespace = "'.$namespace.'"
filter _namespace
insert "*"
order _namespace
extend _namespace2 = _namespace
update _namespace2 = tag("b",_namespace) where _namespace == namespace
update _namespace = tag("nowiki",tag("a","href="._singlequote."index.php?name=special:all-pages&namespace="._namespace._singlequote,_namespace2)." ")
project _namespace concat
update _namespace_concat = replace(_namespace_concat,"::","")
rename _namespace_concat Namespaces
print raw

echo " "

filter _namespace namespace, _name
// order _name

order _name a
// limit 1 10000
extend mainname = regexreplace(_name,"/\w\w","")
extend _nameurl = tag("nowiki","<a href="._singlequote."index.php?name=" ._name._singlequote.">" . _name . "</a> ")
project mainname, _nameurl concat
rename _nameurl_concat _nameurl
project _nameurl

label _nameurl "" 

print grid 50

';


$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;

echotime('all pages end');



?>