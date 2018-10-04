<?php

if (!defined("SOFAWIKI")) die("invalid acces");

function url(){
    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function urlbase()
{
	$url = url();
	$list = explode('/',$url);
	array_pop($list);
	return join('/',$list).'/';
}

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
	$s = str_replace('{{{encryptionsalt}}}',$_REQUEST['encryptionsalt'],$s);

	
	$u = new swUser;
	$u->name = 'User:'.$_REQUEST['powerusername'];
	$u->pass = $_REQUEST['poweruserpass'];
	$db->salt = $_REQUEST['encryptionsalt'];
	$pp = $u->encryptpassword();
	//$pp = $_REQUEST['poweruserpass'];
	
	
	$s = str_replace('{{{powerusername}}}',$_REQUEST['powerusername'],$s);
	$s = str_replace('{{{poweruserpass}}}',$pp,$s);
	
	
	
	
	$s = str_replace('{{{swlang}}}',$_REQUEST['swlang'],$s);
	$s = str_replace('{{{swskin}}}',$_REQUEST['swskin'],$s);
	file_put_contents($swRoot.'/site/configuration.php', $s);
	chmod($swRoot.'/site/configuration.php', 0777);
	
	// reload
	header("Location: index.php?action=login");
	exit;
	
}



$swParsedName = 'SofaWiki Installation';
$swParsedContent = 'Welcome to SofaWiki.
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
{| class="blanktable"
| swMainName || <nowiki><input type="text" name="swmainname" size=40 value="SofaWiki"></nowiki> 
|-
| swBaseHrefFolder || <nowiki><input type="text" name="swbasehrefolder" size=40 value="'.urlbase().'"></nowiki>
|-
| poweruser name || <nowiki><input type="text" name="powerusername" size=40 value="admin"></nowiki>
|-
| poweruser pass || <nowiki><input type="text" name="poweruserpass" size=40 value="1234"></nowiki><nowiki><input type="hidden" name="encryptionsalt" size=40 value="'.rand(1000,9999).'"></nowiki>
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