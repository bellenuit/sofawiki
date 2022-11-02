<?php

if (!defined("SOFAWIKI")) die('invalid acces');

/** 
 * If no site/configuration.php is present, this configuration file is used. 
 * It creates the default power user default with the password 123. 
 * The $action however is always install, so it requests you mainly to install a configuration file.
 */ 

$swMainName = 'SofaWiki';

$poweruser = new swUser;
$poweruser->username = 'default';
$poweruser->ppass = '123';
$poweruser->content = '';

$swEncryptionSalt = "";

$swSkins['default'] = $swRoot.'/inc/skins/default.php';

$_REQUEST['action'] = 'install'; // must be set that it cannot be overriden


?>