<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$formatttribute = '';
$hiddenfield = '';
$passwordtype = 'password';

if (isset($swBlockLoginAutocomplete) && $swBlockLoginAutocomplete)
{
	$formatttribute = 'autocomplete="off"';
	$hiddenfield = '<input autocomplete="false" name="hidden" type="text" style="display:none;">';
	$passwordtype = 'text';
}


$swParsedName = swSystemMessage("login",$lang);
$swParsedContent = '
<div id="editzone" class="editzone actionlogin">
	<div class="editheader">'.swSystemMessage("login",$lang).'</div>
	<form method="post" action="index.php" '.$formatttribute.'>
	<input type="submit" name="submitlogin" value="'.swSystemMessage('login',$lang).'" />
		'.$hiddenfield.'
	<p>'.swSystemMessage('email',$lang).'</p>
	<input type="text" name="username" value="'.$username.'" /0>
	<p>'.swSystemMessage('password',$lang).'</p>
	<input type="'.$passwordtype.'" name="pass" value="" />
	<input type="hidden" name="name" value="'.$name.'" />
	<input type="hidden" name="action" value="login" />';

	
	if ($swNewUserEnable)
	$swParsedContent .= '
	<p><a href="index.php?action=newuser">'.swSystemMessage('new-user',$lang).'</a></p>';
	
	if (@$swEmailAccess)
	
		$swParsedContent .= '
	<p><a href="index.php?action=askemailaccess">'.swSystemMessage('email-access',$lang).'</a></p>';
	
	else
	
		$swParsedContent .= '
	<p><a href="index.php?action=lostpassword">'.swSystemMessage('lost-password',$lang).'</a></p>';
	
	$swParsedContent .=	'</form>';
	
	$swParsedContent .= '
	<div id="help">
		'.swSystemMessage('login-help',$lang).'
	</div>
</div>
	';
	
?>