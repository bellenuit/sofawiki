<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$l=$r=$f=$s=0;

if (isset($_REQUEST['logs'])) $l = 1;
if (isset($_REQUEST['revisions'])) $r = 1;
if (isset($_REQUEST['files'])) $f = 1;
if (!$l && !$r && !$f)	$s = 1;

$swParsedName = "Backup";
$swParsedContent = swBackup($s, $l, $r, $f);



?>