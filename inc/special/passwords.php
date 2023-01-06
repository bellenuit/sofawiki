<?php
	
/** 
 * Allows the admin to create passwords for users and to create new user pages.
 *
 * Used by the $action password.
 * In the first step you enter the email, the password.
 * You can optionally enter the email of another user to use as template for the rights.
 * This creates a preview of the user page with the hash of the password.
 * You can add content to the page.
 * In a second step, you save the user page.
 * A 6 digit password is proposed. You can define your own swGeneratePasswordHook() function in site/configuration.php.
 */

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Passwords';

$mypass='';
if (array_key_exists('mypass', $_POST)) $mypass = $_POST['mypass'];
$myname='';
if (array_key_exists('myname', $_POST)) $myname = $_POST['myname'];
$defaultpassword = rand(111111,999999);
if (function_exists('swGeneratePasswordHook')) $defaultpassword = swGeneratePasswordHook();
$mypass0 = $mypass;
if (!$mypass) $mypass = $defaultpassword;

$myusertemplate='';
if (array_key_exists('myusertemplate', $_POST)) $myusertemplate = $_POST['myusertemplate'];

$but = '';
$act = '';
$lin = '';
$tea = '';


if (isset($_REQUEST['submitmodify']) && isset($_REQUEST['content']))
{
	$userwiki = new swWiki;
	$userwiki->name = "User:$myname";
	$userwiki->content = $_REQUEST['content'];
	$userwiki->user = $username;
	$userwiki->insert();
	$userwiki->parsers = $swParsers;
	$swParsedName = $userwiki->name;
	$swParsedContent = $userwiki->parse();
	
	
}
else
{

	if ($mypass0 && $myname)
	{
		$myuser = new swUser;
		$myuser->pass=$mypass;
		$myuser->username=swNameURL($myname);
		$passencrypted = $myuser->encryptpassword();
		
		$userwiki = new swWiki;
		$userwiki->name = "User:$myname";
		$userwiki->lookup();
		
		$otherusercontent = '';
		if ($myusertemplate)
		{
			$otheruser = new swWiki;
			$otheruser->name = "User:$myusertemplate";
			$otheruser->lookup();
		
			$otherusercontent = $otheruser->content;
			$otherusercontent = preg_replace("/\[\[\_pass\:\:(.*)\]\]/","",$otherusercontent);
			$otherusercontent = preg_replace("/\[\[email\:\:(.*)\]\]/","",$otherusercontent);
		}
		
		switch ($userwiki->status)
		{
			case 'ok':
			case 'protected':	$s = $userwiki->content;
								$s = preg_replace('/\[\[_pass::[a-f0-9]*]]/', '[[_pass::'.$passencrypted.']]', $s);
								
								$but = '<input type="Submit" name="submitmodify" value="Save new password for this user" />';
								$act = '<input type ="hidden" name="action" value="preview">'; // preview=??
								$lin = '<p>User exists already. <a href="index.php?name=User:'.$myname.'">User:'.$myname.'</a></p><pre>'.$userwiki->content.'</pre>';
								$tea = '<textarea name="content" rows="5">'.$s.'</textarea>';
								$swParsedContent .= '
	<p>User exists already.</p>
	<a href="index.php?name=User:'.$myname.'">User:'.$myname.'</a></p>
	<pre>'.$userwiki->content.'</pre>
	<form method="post" action="index.php">
		<input type ="hidden" name="action" value="preview">
		<input type="hidden" name="name" value="'.$userwiki->name.'" />
		<input type="hidden" name="revision" value="'.$userwiki->revision.'">
		<br><textarea name="content" rows="20" cols="80" style="width:95%">'.$s.'</textarea>
		<input type="Submit" name="submitmodify" value="Save new password for this user" /></p>
	</form>';
								break;
				
			default : 			
								$but = '<input type="Submit" name="submitmodify" value="Create" />';
								$act = '<input type ="hidden" name="action" value="preview">'; // preview=??
								$lin = '<p>Create user '.$myname.' with pass '.$mypass.'</p>';
								$tea = '<textarea name="content" rows="5">[[_pass::'.$passencrypted.']]
	'.$otherusercontent.'</textarea>';
			
			$swParsedContent .=  '
	<p>Create user '.$myname.' with pass '.$mypass.'
	<form method="post" action="index.php">
		<input type ="hidden" name="action" value="preview">
		<input type="text" name="name" value="'.$userwiki->name.'" />
		<br><textarea name="content" rows="20" cols="80" style="width:95%">[[_pass::'.$passencrypted.']]
	'.$otherusercontent.'</textarea>
		<input type="Submit" name="submitmodify" value="Create" /></p>
	</form>';
	
		}
	}
	
	
	$swParsedContent  = PHP_EOL.'<div id="editzone" class="editzone specialpassword">';
	$swParsedContent .= PHP_EOL.'<div class="editheader">Password Encryptor</div>';
	$swParsedContent .= PHP_EOL.'<form method="post" action="index.php?name=special:passwords">';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="submit" value="Encrypt" />';
	$swParsedContent .= PHP_EOL.$but;
	$swParsedContent .= PHP_EOL.$act;
	$swParsedContent .= PHP_EOL.'<table><tr><td><p>Email</td><td><p>Password</td><td><p>User template</td></tr>';
	$swParsedContent .= PHP_EOL.'<tr><td><input type="text" name="myname" value="'.$myname.'" autocomplete="off" /></td>';
	$swParsedContent .= PHP_EOL.'<td><input type="text" name="mypass" value="'.$mypass.'" autocomplete="off" /></td>';
	$swParsedContent .= PHP_EOL.'<td><input type="text" name="myusertemplate" value="'.$myusertemplate.'" /></td></tr>';
	$swParsedContent .= PHP_EOL.'</table>';
	$swParsedContent .= PHP_EOL.$lin;
	$swParsedContent .= PHP_EOL.$tea;
	$swParsedContent .= PHP_EOL.'</form>';
	
	$swParsedContent .= PHP_EOL.'<div class="editfooter help">
	<p>Typical user rights for editing users
	
	<p>[[_view::Main]]
	<br/>[[_talk::Main]] [[_modify::Main]] [[_create::Main]]
	<br/>[[_view::Category]] [[_modify::Category]] [[_create::Category]]
	<br/>[[_view::Image]] [[_talk::Image]] [[_modify::Image]]
	<br/>[[_upload::*]]
	
	<p>Typical user rights for editing users for admins
	
	<p>[[_view::*]]
	<br/>[[_modify::*]] [[_create::*]] [[_protect::*]] [[_delete::*]] [[_rename::*]] [[_fields::*]]
	<br/>[[_upload::*]]
	<br/>[[_special::special]]
	</div><!-- editfooter -->';
	
	
	
	$swParsedContent .= PHP_EOL.'</div><!-- editzone -->';
	
	
	
	
	
	$swParseSpecial = false;
}



?>