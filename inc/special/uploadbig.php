<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Upload Big';
$wiki->content = '{{uploadzone}}';
$wiki->parsers = $swParsers;
$swParsedContent = $wiki->parse();

