<?php

if (!defined('SOFAWIKI')) die('invalid acces');


if (array_key_exists('email', $_REQUEST)) 
	$email = $_POST['email'];
else
	$email = '';

$submitted = false;

$swError ='';

if (isset($_REQUEST['submitemailaccess']))
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
		 
		
			$token = rand(111111111,999999999);

			$p = '[[_token::'.$token.']]';
			$s = $lostuser->content;
			// $s = preg_replace("/\[\[_pass::(.*)\]\]/",$p,$s);
			// must be added.
			$s .= $p;
			$lostuser->comment = 'emailaccess';
			$lostuser->content = $s;
			$lostuser->user = '';
			$lostuser->insert();
			
			$label = $swMainName.': '.swSystemMessage('your-email-access',$lang);
			$msg = swSystemMessage('your-email-access-message',$lang)."\n".'
			'.$swBaseHref .'?action=emailaccess&email='.$email.'&token='.$token."\n";
			
				
		
		
		swNotify('email-access-submit',$swError,$label,$msg,$email);
		
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

$swParsedName = swSystemMessage('email-access',$lang);

if ($submitted)
{

	$swParsedContent .= swSystemMessage('email',$lang) . ': '.$email.'<br/><br/>
	<div id="help">'.swSystemMessage('email-access-submit-help',$lang).' <b>'.$swMainName.': '.swSystemMessage('your-email-access',$lang).'</b></div>';

	
}
else
{

	$swParsedContent = $err.'<div id="editzone">
		<form method="post" action="index.php">
		<table class="blanktable" ><tr><td>'.swSystemMessage('email',$lang).'</td><td>
		<input type="text" name="email" value=""/>
		<input type="hidden" name="action" value="askemailaccess" /></td></tr><tr><td></td><td>
		<input type="submit" name="submitemailaccess" value="'.swSystemMessage("email-access-submit",$lang).'" /></td></tr></table>
	</form>.';
	

	$swParsedContent .=	'<div id="help">'.swSystemMessage("email-access-help",$lang).'</div>';
	$swParsedContent .='
	</div>
	';
}



?>
