<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Passwords';


if (array_key_exists('mypass', $_POST)) 
	$mypass = $_POST['mypass'];
else
	$mypass='';
if (array_key_exists('myname', $_POST)) 
	$myname = $_POST['myname'];
else
	$myname='';
if (array_key_exists('myusertemplate', $_POST)) 
	$myusertemplate = $_POST['myusertemplate'];
else
	$myusertemplate='';
	
	

$swParsedContent = '<p>Password Encryptor
<div id="editzone">';
		


	$swParsedContent .= '<form method="post" action="index.php?name=special:passwords">
		<table class="blanktable"><tr><td>Email</td><td>Password</td><td>User template</td></tr>
		<tr><td><input type="text" name="myname" value="'.$myname.'" autocomplete="off" /></td>
		<td><input type="text" name="mypass" value="'.$mypass.'" autocomplete="off" /></td>
		<td><input type="text" name="myusertemplate" value="'.$myusertemplate.'" /></td></tr>
		</table>

		<input type="submit" name="submit" value="Encrypt" />
</form>';


if ($mypass != "")
{
	$myuser = new swUser;
	$myuser->pass=$mypass;
	$myuser->username=swNameURL($myname);
	$passencrypted = $myuser->encryptpassword();
	
	$userwiki = new swWiki;
	$userwiki->name = "User:$myname";
	$userwiki->lookup();
	
	if ($myusertemplate != "")
	{
		$otheruser = new swWiki;
		$otheruser->name = "User:$myusertemplate";
		$otheruser->lookup();
	
		$otherusercontent = $otheruser->content;
		$otherusercontent = preg_replace("/\[\[\_pass\:\:(.*)\]\]/","",$otherusercontent);
		$otherusercontent = preg_replace("/\[\[email\:\:(.*)\]\]/","",$otherusercontent);
	}
	else
		$otherusercontent = "";
	
	
	switch ($userwiki->status)
	
	{
		case "ok":
		case "protected":
			
			$swParsedContent .= '<p><a href="index.php?name=User:'.$myname.'">User:'.$myname.'</a></p><p>'.$mypass.'</p><p>[[_pass::'.$passencrypted.']]</p>';
			break;
			
		default : $swParsedContent .=  'Create user '.$myname.' with pass '.$mypass.'<form method="post" action="index.php">
		<input type ="hidden" name="action" value="preview">
		<input type="text" name="name" value="'.$userwiki->name.'" />
		<br><textarea name="content" rows="20" cols="80" style="width:95%">[[_pass::'.$passencrypted.']]
'.$otherusercontent.'</textarea>
		<input type="Submit" name="submitmodify" value="Create" /></p>
</form>';
	}
	
}

$swParsedContent .= '</div>
<p>Typical user rights for editing users

<p>[[_view::Main]]
<br/>[[_talk::Main]] [[_modify::Main]] [[_create::Main]]
<br/>[[_view::Category]] [[_modify::Category]] [[_create::Category]]
<br/>[[_view::Image]] [[_talk::Image]] [[_modify::Image]]
<br/>[[_upload::*]]

<p>Typical user rights for editing users for admins

<p>[[_view::*]]
<br/>[[_modify::*]] [[_create::*]] [[_protect::*]] [[_delete::*]] [[_fields::*]]
<br/>[[_upload::*]]
<br/>[[_special::special]]';

$swParseSpecial = false;



?>