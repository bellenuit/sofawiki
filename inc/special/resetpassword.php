<?php

if (!defined("SOFAWIKI")) die("invalid acces");


if (array_key_exists("email", $_REQUEST)) 
	$email = $_REQUEST['email'];
else
	$email = "";
	
if (array_key_exists("token", $_REQUEST)) 
	$token = $_REQUEST['token'];
else
	$$token = "";
	
if (array_key_exists("newpassword", $_POST)) 
	$newpassword = $_POST['newpassword'];
else
	$newpassword = "";
	
$submitted = false;

// verify that no field has a tag
$s = $email;
if (!swValidate($s,"<'[]{}*\""))
	$swError = swSystemMessage("email-invalid-characters-error",$lang);
	
// verify that no field has a tag
$s = $newpassword;
if (!swValidate($s,"<'[]{}*\""))
	$swError = swSystemMessage("password-invalid-characters-error",$lang);

	
// verify the email looks possible
if (!(stristr($email,"@")) || stristr($email," ") || !(stristr($email,".")))
	$swError = swSystemMessage("email-looks-not-valid-error",$lang);

$s = $token;
if (!swValidate($s,"<'[]{}*\""))
	$swError = swSystemMessage("token-invalid-characters-error",$lang);
	
$resetuser = new swUser;
$resetuser->username = $email;
$resetuser->name = 'User:'.$email;
$resetuser->lookup();
if (!$resetuser->visible())
{
	$swError = swSystemMessage("user-does-not-exist-error",$lang);
	$token = '';
}
if (!stristr($resetuser->content,'[[_token::'.$token.']]'))
{
	$swError = swSystemMessage("invalid-token-error",$lang);
	$token = '';
}

if ($newpassword != '')
{
	if (strlen($newpassword)<8)
		$swError = swSystemMessage("password-too-short",$lang);
}

if ($newpassword != '' && $swError == '')
{		
	
	$resetuser->pass = $newpassword;
	$resetuser->content = preg_replace("/\[\[\_token\:\:([^\]]*)\]\]/","",$resetuser->content);
	$resetuser->content = preg_replace("/\[\[\_pass\:\:([^\]]*)\]\]/","",$resetuser->content);
	$resetuser->content .= '[[_pass::'.$resetuser->encryptpassword().']]';
	$resetuser->insert();
	$submitted = true;
}

if ($swError)
{
	$err = '<p class="error">'.$swError;
	$swError = "";
}
else
{
	$err = '';
}

$swParsedName = swSystemMessage('password-reset',$lang);

if ($submitted)
{
	$swParsedContent = swSystemMessage('password-has-been-set',$lang).'<p>'.$swLoginMenus['login'];
	
}
elseif($token != '')
{
	$swParsedContent = $err.'
	<div id="editzone">
	<form method="post" action="index.php">
		<input type="hidden" name="action" value="resetpassword" />
		<input type="hidden" name="email" value="'.$email.'" />
		<input type="hidden" name="token" value="'.$token.'" />
		<table class="blanktable">
		<tr><td>'.swSystemMessage('new-password',$lang).'</td>
		<td><input type="text" name="newpassword" value="" /></td>
		</tr>
		<tr><td></td>
		<td><input type="submit" name="submitresetpassword" value="'.swSystemMessage('set-password',$lang).'" /></td></tr></table>
	</form>
	<div id="help">'.swSystemMessage("reset-password-help",$lang).'
	</div>
	</div>';
}
else
{
	$swParsedContent = $err;
}



?>
