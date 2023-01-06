<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swError = '';

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
	@chmod($swRoot.'/site/', 0755);
	@chmod($swRoot.'/site/cache/', 0755);
	@chmod($swRoot.'/site/current/', 0755);
	@chmod($swRoot.'/site/files/', 0755);
	@chmod($swRoot.'/site/functions/', 0755);
	@chmod($swRoot.'/site/indexes/', 0755);
	@chmod($swRoot.'/site/logs/', 0755);
	@chmod($swRoot.'/site/queries/', 0755);
	@chmod($swRoot.'/site/revisions/', 0755);
	@chmod($swRoot.'/site/skins/', 0755);
	@chmod($swRoot.'/site/trigram/', 0755);
	@chmod($swRoot.'/site/upload/', 0755);
}

if (isset($_REQUEST['submitconfiguration']) || isset($_REQUEST['submitconfigurationanddelete']))
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
	chmod($swRoot.'/site/configuration.php', 0755);
	
	$wiki = new swWiki;
	$wiki->name = $_REQUEST['swmainname'];
	$wiki->content = 'Hello world';
	$wiki->insert();
	
	if (isset($_REQUEST['submitconfigurationanddelete']))
	{
		$file = 'install.php';
		if (file_exists($file)) 
			unlink($file);
	}
	
	// reload
	header("Location: index.php");
	exit;
	
}



$swParsedName = 'SofaWiki Installation';
$swParsedContent = 'Welcome to SofaWiki.
Please complete your installation now.';

$langlist = array('en','de','fr','it','es','dk');
$langoptions = '';
foreach ($langlist as $f)
{	
	if ($f !=  '')
		if ($f ==  'en')
			$langoptions .= "<option value='$f' selected>$f</option>";
		else
			$langoptions .= "<option value='$f'>$f</option>";
}

$skinoptions = '';
foreach ($swSkins as $f=>$v)
{	
	if ($f !=  '')
		if ($f ==  'wiki')
			$skinoptions .= "<option value='$f' selected>$f</option>";
		else
			$skinoptions .= "<option value='$f'>$f</option>";
}


$sitefolderrights =substr(sprintf('%o',@fileperms($swRoot.'/site/')),-3);
$sitefolderrightslogs =substr(sprintf('%o',@fileperms($swRoot.'/site/logs/')),-3);


if ($sitefolderrights < 0755 || $sitefolderrightslogs < 0755)
{

	$swParsedContent .= PHP_EOL.'<ul>';
	$swParsedContent .= PHP_EOL.'<li>Folder rights</li>';

	$swParsedContent .= PHP_EOL.'</ul>';

	$swParsedContent .= PHP_EOL.'<div id="editzone" class="editzone specialinstall">';
	$swParsedContent .= PHP_EOL.'<div class="editheader">Set folder rights</div>';
	$swParsedContent .= PHP_EOL.'<form method="post" action="index.php">';
	$swParsedContent .= PHP_EOL.'<p>Set the folder rights to 755 for the site folder and its subfolders</p>';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="submitfolderrights" value="Try to fix" />';
	$swParsedContent .= PHP_EOL.'</form>';
	
	$swParsedContent .= PHP_EOL.'<div id="help"><p>The rights are now '.$sitefolderrights.' for the site folder and '.$sitefolderrightslogs.' for the site/logs folder. They should be 755 everywhere. If Try to fix does not work, do it manually with the FTP tools</p></div><!-- help -->';	
	$swParsedContent .= PHP_EOL.'</div><!-- editzone -->';

	
	$swParsedContent .= PHP_EOL.'<ul>';
	$swParsedContent .= PHP_EOL.'<li>Create the basic configuration</li>';
	$swParsedContent .= PHP_EOL.'<li>Login</li>';
	$swParsedContent .= PHP_EOL.'<li>Write main page</li>';
	$swParsedContent .= PHP_EOL.'</ul>';


}
else
{
	$swStatus = 'Folder rights seem ok (755)';
	$swParsedContent .= PHP_EOL.'<ul>';
	$swParsedContent .= PHP_EOL.'<li>Folder rights seem ok (755)</li>';
	$swParsedContent .= PHP_EOL.'<li>Create the basic configuration</li>';
	$swParsedContent .= PHP_EOL.'</ul>';
	$swParsedContent .= PHP_EOL.'<p>Here are the basic settings to get you running. You can change these and other settings later manually the site/configuration.php file.';
	$swParsedContent .= PHP_EOL.'<div id="editzone" class="editzone specialinstall">';
	$swParsedContent .= PHP_EOL.'<div class="editheader">Create the basic configuration</div>';
	$swParsedContent .= PHP_EOL.'<form method="post" action="index.php">';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="submitconfiguration" value="Install" />';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="submitconfigurationanddelete" value="Install and delete install.php" />';
	$swParsedContent .= PHP_EOL.'<p>$swMainName (the name of the main page)</p>';
	$swParsedContent .= PHP_EOL.'<input type="text" name="swmainname" size=40 value="My first wiki">';
	$swParsedContent .= PHP_EOL.'<p>$swBaseHrefFolder (the URL of the site)</p>';
	$swParsedContent .= PHP_EOL.'<input type="text" name="swbasehrefolder" size=40 value="'.urlbase().'">';
	$swParsedContent .= PHP_EOL.'<p>poweruser name</p>';
	$swParsedContent .= PHP_EOL.'<input type="text" name="powerusername" size=40 value="admin">';
	$swParsedContent .= PHP_EOL.'<p>poweruser pass <b>(write it down!)</b></p>';
	$swParsedContent .= PHP_EOL.'<input type="text" name="poweruserpass" size=40 value="'.rand(100000,999999).'">';
	$swParsedContent .= PHP_EOL.'<input type="hidden" name="encryptionsalt" size=40 value="'.rand(100000,999999).'">';
	$swParsedContent .= PHP_EOL.'<p>default lang</p>';
	$swParsedContent .= PHP_EOL.'<select name="swlang">'.$langoptions.'</select>';
	$swParsedContent .= PHP_EOL.'<p>default skin</p>';
	$swParsedContent .= PHP_EOL.'<nowiki><select name="swskin">'.$skinoptions.'</select>';
	
	$swParsedContent .= PHP_EOL.'<p><b>Did you write down the password?</b></p>';
	
	$swParsedContent .= PHP_EOL.'</form>';
	
	$swParsedContent .= PHP_EOL.'<div id="help"><p>It is recommended to choose the option delete install.php.</p></div><!-- help -->';	
	$swParsedContent .= PHP_EOL.'</div><!-- editzone -->';

	$swParsedContent .= PHP_EOL.'<ul>';
	$swParsedContent .= PHP_EOL.'<li>Login</li>';
	$swParsedContent .= PHP_EOL.'<li>Write main page</li>';
	$swParsedContent .= PHP_EOL.'</ul>';
	$swParsedContent .= PHP_EOL.'<p>SofaWiki documentation <a href="https://www.sofawiki.com/" target="_blank">https://www.sofawiki.com</a>';
}	

?>