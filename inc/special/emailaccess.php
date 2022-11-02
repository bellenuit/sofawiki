<?php

if (!defined("SOFAWIKI")) die("invalid acces");


if (array_key_exists("email", $_REQUEST)) 
	$email = $_REQUEST['email'];
else
	$email = "";
	
if (array_key_exists("token", $_REQUEST)) 
	$token = $_REQUEST['token'];
else
	$token = "";
		
$submitted = false;

// verify that no field has a tag
$s = $email;
if (!swValidate($s,"<'[]{}*\""))
	$swError = swSystemMessage("email-invalid-characters-error",$lang);
		
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

$swParsedName = swSystemMessage('email-access',$lang);

if (!$swError)
{
	$resetuser->content = preg_replace("/\[\[\_token\:\:([^\]]*)\]\]/","",$resetuser->content);
	$resetuser->content .= '[[_connect::'. date("Y-m-d H:i:s",time()).']]';
	$resetuser->insert();

	$username = $email;
	
	$swParsedContent = swSystemMessage('email-access-ok',$lang).'<p><a href="index.php">Homepage</a></p>'.
	'<p>'.$username;
	
	$swUserCookieExpiration = 4*60*60;
	swSetCookie('username',$username,$swUserCookieExpiration);
	$_SESSION[$swMainName.'-username'] = $username;
	$passwordtoken = md5(swNameURL($username).date('Ymd',time()).$swEncryptionSalt);
	swSetCookie('passwordtoken',$passwordtoken,$swUserCookieExpiration);
	$user->username = $username = $user->nameshort();
	$swLoginMenus['user'] = $username;
	unset($swLoginMenus['login']);

}
else
{
	$err = '<p class="error">'.$swError;
	$swParsedContent = $err;
}




?>
