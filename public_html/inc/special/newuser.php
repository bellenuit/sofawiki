<?php

if (!defined("SOFAWIKI")) die("invalid acces");

if (!$swNewUserEnable) return;


if (array_key_exists("email", $_REQUEST)) 
	$email = $_POST['email'];
else
	$email = "";
	
$submitted = false;

if ($action == "newusersubmit")
{
	// verify if new user can add themselves
	if (!$swNewUserEnable)
		$swError = swSystemMessage("NoAccessError",$lang);

	
	// verify that no field has a tag
	$s = $email;
		if (!swValidate($s,"<'[]{}*\""))
		$swError = swSystemMessage("NewUserInvalidCharactersError",$lang);
	
	// verify the email looks possible
	if (!(stristr($email,"@")) || stristr($email," ") || !(stristr($email,".")))
		$swError = swSystemMessage("EmailLooksNotValidError",$lang);
	
	// verify that the email is not already taken
	$newuser = new swRecord;
	$newuser->username = $email;
	$newuser->name = 'User:'.$email;
	$newuser->lookup();
	if ($newuser->visible())
	{
		$swError = swSystemMessage("UserExistsAlreadyError",$lang);
		$email = '';
	}
	if ($email == $poweruser->nameshort())
		$swError = swSystemMessage("UserExistsAlreadyError",$lang);
	
	
	
	if (!$swError)
	{
		
		$w = new swUser;
		$w->name = "User:$email";
		$w->pass = rand(111111,99999);
		$w->content = "[[_pass::".$w->encryptpassword()."]]
$swNewUserRights";
		$w->insert();
		
		$label = $swMainName.":".swSystemMessage("YourPasswordTitle",$lang);
		$message = swSystemMessage("YourPasswordMessage",$lang)."\n
		User=$email\n
		Password=$w->pass\n";
		
		swNotify("newusersubmit",$swError,$label,$message,$email);
		$submitted = true;
	
	}
	
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

$swParsedName = swSystemMessage("New User",$lang);

if ($submitted)
{
	$swParsedContent = swSystemMessage("Email",$lang) . ": $email<br/><br/>
	<div id='help'>".swSystemMessage("NewUserSubmitHelp",$lang)."</div>";
	
}
else
{
$swParsedContent = "$err<div id='editzone' >
		<form method='post' action='index.php'>
		<table><tr><td>
		".swSystemMessage("Email",$lang)."</td><td>
		<input type='text' name='email' value='$email' />
		</td></tr>";
		
 	$swParsedContent .=
		
		"<input type='hidden' name='action' value='newusersubmit' /></td></tr><tr><td></td><td>
		<input type='submit' name='submitprotect' value='".swSystemMessage("New User",$lang)."' /></td></tr></table>
	</form>
	
	<div id='help'>".swSystemMessage("NewUserHelp",$lang)."</div>
	</div>
	";
}



?>