<?php

if (!defined('SOFAWIKI')) die('invalid acces');


if (array_key_exists('email', $_REQUEST)) 
	$email = $_POST['email'];
else
	$email = '';

$submitted = false;

$swError ='';

if ($action == 'lostpasswordsubmit')
{
	// verify that all arguments are there
	if ( !$email )
		$swError = swSystemMessage('NewUserMissingFieldsError',$lang);
	
	// verify that no field has a tag
	$s = $email;
		if (stristr($s,'<') || stristr($s,'>')) 
		$swError = swSystemMessage('NewUserInvalidCharactersError',$lang);
	
	// verify the email looks possible
	if (!(stristr($email,'@')) || stristr($email,' '))
		$swError = swSystemMessage('EmailLooksNotValidError',$lang);
	
	
	// verify that the signature exists and the email is valid	
	$lostuser= new swUser;
	$lostuser->name = ('User:'.swNameURL($email));
	$lostuser->lookup();
	if (!$lostuser->visible()) $swError = swSystemMessage('LostPasswordWrongUserError',$lang);
		
	if (!$swError)
	{
		 
		$lostuser->pass = rand(111111,99999);
		$p = '[[_newpass::'.$lostuser->encryptpassword().']]';
		$s = $lostuser->content;
		// $s = preg_replace("/\[\[_pass::(.*)\]\]/",$p,$s);
		// must be added.
		$s .= $p;
		$lostuser->comment = 'lost password';
		$lostuser->content = $s;
		$lostuser->insert();
		
		$label = $swMainName.':'.swSystemMessage('YourPasswordTitle',$lang);
		$message = swSystemMessage('YourPasswordMessage',$lang)."\n
User = $email\n
Password = $lostuser->pass\n";
		
		swNotify('lostpasswordsubmit',$swError,$label,$message,$email);
		
		$submitted = true;
	
	}
	
}


if ($swError)
{
	$err = '<p class="error">'.$swError;
	$swError = '';
}
else
{
	$err = '';
}

$swParsedName = swSystemMessage('Lost Password',$lang);

if ($submitted)
{
	$swParsedContent .= swSystemMessage('Email',$lang) . ': '.$email.'<br/><br/>
	<div id="help">'.swSystemMessage('LostPasswordSubmitHelp',$lang).'</div>';
	
}
else
{
$swParsedContent = $err.'<div id="editzone">
		<form method="post" action="index.php">
		<table><tr><td>'.swSystemMessage('Email',$lang).'</td><td>
		<input type="text" name="email" value="email"/>
		<input type="hidden" name="action" value="lostpasswordsubmit" /></td></tr><tr><td></td><td>
		<input type="submit" name="submitlostpassword" value="'.swSystemMessage("Lost Password Submit",$lang).'" /></td></tr></table>
	</form>
	
	<div id="help">'.swSystemMessage("LostPasswordHelp",$lang).'</div>
	</div>
	';
}



?>