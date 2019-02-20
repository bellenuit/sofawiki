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
		$swError = swSystemMessage('new-user-missing-fields-error',$lang);
	
	// verify that no field has a tag
	$s = $email;
		if (stristr($s,'<') || stristr($s,'>')) 
		$swError = swSystemMessage('new-user-invalid-characters-error',$lang);
	
	// verify the email looks possible
	if (!(stristr($email,'@')) || stristr($email,' '))
		$swError = swSystemMessage('email-looks-not-valid-error',$lang);
	
	
	// verify that the signature exists and the email is valid	
	$lostuser= new swUser;
	$lostuser->name = ('User:'.swNameURL($email));
	$lostuser->lookup();
	if (!$lostuser->visible()) $swError = swSystemMessage('lost-password-wrong-user-error',$lang);
		
	if (!$swError)
	{
		 
		
		if (function_exists('swGeneratePasswordHook'))
			$lostuser->pass = swGeneratePasswordHook();
		else
			$lostuser->pass = rand(111111,999999);
		
		$p = '[[_newpass::'.$lostuser->encryptpassword().']]';
		$s = $lostuser->content;
		// $s = preg_replace("/\[\[_pass::(.*)\]\]/",$p,$s);
		// must be added.
		$s .= $p;
		$lostuser->comment = 'lost password';
		$lostuser->content = $s;
		$lostuser->user = '';
		$lostuser->insert();
		
		$label = $swMainName.':'.swSystemMessage('your-password-title',$lang);
		$msg = swSystemMessage('your-password-message',$lang)."\n
User = $email\n
Password = $lostuser->pass\n";
		
		swNotify('lostpasswordsubmit',$swError,$label,$msg,$email);
		
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

$swParsedName = swSystemMessage('lost-password',$lang);

if ($submitted)
{
	$swParsedContent .= swSystemMessage('email',$lang) . ': '.$email.'<br/><br/>
	<div id="help">'.swSystemMessage('lost-password-submit-help',$lang).'</div>';
	
}
else
{
$swParsedContent = $err.'<div id="editzone">
		<form method="post" action="index.php">
		<table class="blanktable" ><tr><td>'.swSystemMessage('email',$lang).'</td><td>
		<input type="text" name="email" value=""/>
		<input type="hidden" name="action" value="lostpasswordsubmit" /></td></tr><tr><td></td><td>
		<input type="submit" name="submitlostpassword" value="'.swSystemMessage("lost-password-submit",$lang).'" /></td></tr></table>
	</form>
	
	<div id="help">'.swSystemMessage("lost-password-help",$lang).'</div>
	</div>
	';
}



?>
