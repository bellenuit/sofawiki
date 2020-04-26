<?php

if (!defined("SOFAWIKI")) die("invalid acces");

// required variables

$swMainName = "SofaWiki";

$poweruser = new swUser;
$poweruser->username = "default";
$poweruser->ppass = "123";
$poweruser->content = "";

$swEncryptionSalt = "";

$swSkins["default"] = "$swRoot/inc/skins/default.php";

$_REQUEST["action"] = "install"; // must be set that it cannot be overriden




?>