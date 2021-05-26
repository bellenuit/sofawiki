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
<div id="editzone"">
	<form method="post" action="index.php" '.$formatttribute.'>
		'.$hiddenfield.'
		<table class="blanktable" >
		<tr>
			<td>'.swSystemMessage('email',$lang).'</td>
			<td><input type="text" name="username" value="'.$username.'" / size=30></td>
		</tr>
		<tr>
			<td>'.swSystemMessage('password',$lang).'</td>
			<td><input type="'.$passwordtype.'" name="pass" value="" size=30 />
				<input type="hidden" name="name" value="'.$name.'" />
				<input type="hidden" name="action" value="login" />
				</td>
			</tr>
		<tr>
			<td></td>
			<td><input type="submit" name="submitlogin" value="'.swSystemMessage('login',$lang).'" /></td>
		</tr>
		</table>
	</form>';
	
	if ($swNewUserEnable)
	$swParsedContent .= '
	<p><a href="index.php?action=newuser">'.swSystemMessage('new-user',$lang).'</a></p>';
	
	if (@$swEmailAccess)
	
		$swParsedContent .= '
	<p><a href="index.php?action=askemailaccess">'.swSystemMessage('email-access',$lang).'</a></p>';
	
	else
	
		$swParsedContent .= '
	<p><a href="index.php?action=lostpassword">'.swSystemMessage('lost-password',$lang).'</a></p>';
	
	$swParsedContent .= '
	<div id="help">
		'.swSystemMessage('login-help',$lang).'
	</div>
</div>
	';
	
?>