<?php

if (!defined("SOFAWIKI")) die("invalid acces");

if (isset($_REQUEST['submitfolderrights']))
{
	@chmod($swRoot.'/site/', 0777);
	@chmod($swRoot.'/site/cache/', 0777);
	@chmod($swRoot.'/site/current/', 0777);
	@chmod($swRoot.'/site/files/', 0777);
	@chmod($swRoot.'/site/functions/', 0777);
	@chmod($swRoot.'/site/indexes/', 0777);
	@chmod($swRoot.'/site/logs/', 0777);
	@chmod($swRoot.'/site/queries/', 0777);
	@chmod($swRoot.'/site/revisions/', 0777);
	@chmod($swRoot.'/site/skins/', 0777);
	@chmod($swRoot.'/site/trigram/', 0777);
	@chmod($swRoot.'/site/upload/', 0777);
}

if (isset($_REQUEST['submitconfiguration']))
{
	$s = file_get_contents($swRoot.'/inc/configuration.php');
	$s = str_replace('{{{swmainname}}}',$_REQUEST['swmainname'],$s);
	$s = str_replace('{{{swbasehrefolder}}}',$_REQUEST['swbasehrefolder'],$s);
	$s = str_replace('{{{powerusername}}}',$_REQUEST['powerusername'],$s);
	$s = str_replace('{{{poweruserpass}}}',$_REQUEST['poweruserpass'],$s);
	$s = str_replace('{{{encryptionsalt}}}',$_REQUEST['encryptionsalt'],$s);
	$s = str_replace('{{{swlang}}}',$_REQUEST['swlang'],$s);
	$s = str_replace('{{{swskin}}}',$_REQUEST['swskin'],$s);
	file_put_contents($swRoot.'/site/configuration.php', $s);
	
	// reload
	header("Location: index.php?action=login");
	exit;
	
}



$swParsedName = 'SofaWiki Installation';
$swParsedContent = 'Welcome to Sofawiki.
Please complete your installation now.';

$langlist = array('en','de','fr','it','es','dk');
foreach ($langlist as $f)
{	
	if ($f !=  "")
	$langoptions .= "<option value='$f'>$f</option>";
}

$langlist = array('en','de','fr','it','es','dk');
foreach ($swSkins as $f=>$v)
{	
	if ($f !=  "")
	$skinoptions .= "<option value='$f'>$f</option>";
}


$sitefolderrights =substr(sprintf('%o',fileperms($swRoot.'/site/')),-3);
$sitefolderrightslogs =substr(sprintf('%o',fileperms($swRoot.'/site/logs')),-3);
if ($sitefolderrights != 777 || $sitefolderrightslogs != 777)


$swParsedContent .= '
* Set the folder rights for the site folder and its subfolders (now '.$sitefolderrights.'; should be 777)<nowiki><br><form method="post" action="index.php"><input type="submit" name="submitfolderrights" value="Try to fix" /><br>If Try to fix does not work, do it manually with the FTP tools</nowiki>
* Create the basic configuration.
* Login
* Write main page';

else
$swParsedContent .= '
* Folder rights seem ok (777).
* Create the basic configuration
Here are the basic settings to get you running. You can change these and other settings later manually the site/configuration.php file.
<nowiki><form method="post" action="index.php"></nowiki>
{|
| swMainName || <nowiki><input type="text" name="swmainname" size=40 value="Sofawiki"></nowiki> 
|-
| swBaseHrefFolder || <nowiki><input type="text" name="swbasehrefolder" size=40 value="http://www.example.com/"></nowiki>
|-
| poweruser name || <nowiki><input type="text" name="powerusername" size=40 value="admin"></nowiki>
|-
| poweruser pass || <nowiki><input type="text" name="poweruserpass" size=40 value="1234"></nowiki>
|- 
| encryption salt || <nowiki><input type="text" name="encryptionsalt" size=40 value="'.rand(1000,9999).'"></nowiki>
|- 
| default lang || <nowiki><select name="swlang">'.$langoptions.'</select></nowiki>
|- 
| default skin || <nowiki><select name="swskin">'.$skinoptions.'</select></nowiki>
|- 
| <nowiki><input type="submit" name="submitconfiguration" value="Install" /><br></nowiki> 
|}
<nowiki></form></nowiki>
* Login
* Write main page
SofaWiki documentation https://www.sofawiki.com/';


$wiki->content = $swParsedContent;
$swParsedContent = $wiki->parse();

?>