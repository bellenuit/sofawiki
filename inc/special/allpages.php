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
update _namespace2 = "<b>"._namespace."</b>" where _namespace == namespace
update _namespace = "<nowiki><a href=\'index.php?name=special:all-pages&namespace="._namespace."\'>"._namespace2."</a> </nowiki>"
project _namespace concat
update _namespace_concat = replace(_namespace_concat,"::","")
rename _namespace_concat Namespaces
print raw

echo " "

filter _namespace namespace, _name
// order _name

order _name a
extend mainname = regexreplace(_name,"/\w\w","")
extend _nameurl = "[["._name."]]"
update _nameurl = "[[:"._name."]]" where _namespace regex "category|image"
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