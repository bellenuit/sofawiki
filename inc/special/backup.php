<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$l=$r=$f=$s=$trigram=$tree=0;

if (isset($_REQUEST['logs'])) $l = 1;
if (isset($_REQUEST['revisions'])) $r = 1;
if (isset($_REQUEST['files'])) $f = 1;
if (isset($_REQUEST['trigram'])) $trigram = 1;
if (isset($_REQUEST['tree'])) $tree = 1;
if (!$l && !$r && !$f && !$trigram  && !$tree)	$s = 1;

$swParsedName = "Backup";
$swParsedContent = swBackup($s, $l, $r, $f, $trigram, $tree);



?>