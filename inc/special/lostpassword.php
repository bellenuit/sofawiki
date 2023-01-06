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
		 
		
			
			
		if (isset($swOldPasswordReset) && $swOldPasswordReset ) // depreciated
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

		}
		else
		{
			
			
			
			
			$token = rand(111111111,999999999);

			$p = '[[_token::'.$token.']]';
			$s = $lostuser->content;
			// $s = preg_replace("/\[\[_pass::(.*)\]\]/",$p,$s);
			// must be added.
			$s .= $p;
			$lostuser->comment = 'lost password';
			$lostuser->content = $s;
			$lostuser->user = '';
			$lostuser->insert();
			
			
			$label = $swMainName.': '.swSystemMessage('your-password-reset',$lang);
				$msg = swSystemMessage('your-password-reset-message',$lang)."\n".'
				'.$swBaseHref .'?action=resetpassword&email='.$email.'&token='.$token."\n";

				
		}
		
		swNotify('lostpasswordsubmit',$swError,$label,$msg,$email);
		
		$submitted = true;
	
	}
	
}

$swParsedName = swSystemMessage('lost-password',$lang);

if ($submitted)
{
	if (isset($swOldPasswordReset) && $swOldPasswordReset ) //depreciated
		$swParsedContent .= swSystemMessage('email',$lang) . ': '.$email.'<br/><br/>
	<div id="help">'.swSystemMessage('lost-password-submit-help',$lang).'</div>';
	else
		$swParsedContent .= swSystemMessage('email',$lang) . ': '.$email.'<br/><br/>
	<div id="help">'.swSystemMessage('reset-password-submit-help',$lang).' <b>'.$swMainName.': '.swSystemMessage('your-password-reset',$lang).'</b></div>';

	
}
else
{

	$swParsedContent = '<div id="editzone" class="editzone actionlostpassword">
		<div class="editheader">'.swSystemMessage("lost-password-header",$lang).'</div>
		<form method="post" action="index.php">
		<input type="submit" name="submitlostpassword" value="'.swSystemMessage("lost-password-submit",$lang).'" />
		<p>'.swSystemMessage('email',$lang).'</p>
		<input type="text" name="email" value=""/>
		<input type="hidden" name="action" value="lostpasswordsubmit" />
		';
	
	$swParsedContent .='</form>';
	
	if (isset($swOldPasswordReset) && $swOldPasswordReset ) //depreciated
		$swParsedContent .=	'<div id="help" class="editfooter">'.swSystemMessage("lost-password-help",$lang).'</div>';
	else
		$swParsedContent .=	'<div id="help" class="editfooter">'.swSystemMessage("lost-reset-password-help",$lang).'</div>';
	
	
	$swParsedContent .='</div>';
}



?>
