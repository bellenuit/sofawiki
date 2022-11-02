<?php
	
/** 
 * Shows a list of all users
 *
 * special:users
 * Does not show superusers in configuration.php
 */


if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Users";

$swParsedContent = 'Add new user: [[Special:Passwords]]';




$q = '

filter _namespace "user", _name
order _name a


update _name = "[["._name."]]"
project _name



label _name "" 


print grid 50


';

$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;


?>