<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "SofaWiki Installation";
$swParsedContent = "Welcome to Sofawiki.
Please complete your installation now.
* Copy the configuration.php file from the inc folder to the site folder and set your configuration.
* Set the folder rights
SofaWiki documentation http://www.belle-nuit.com/sofawiki/
";

$wiki->content = $swParsedContent;
$swParsedContent = $wiki->parse();

?>