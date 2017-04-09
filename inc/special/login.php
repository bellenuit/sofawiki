<?php

if (!defined("SOFAWIKI")) die("invalid acces");



$swParsedName = swSystemMessage("Login",$lang);
$swParsedContent = "<div id='editzone'>
		<form method='post' action='index.php'>
		<table><tr><td>
		".swSystemMessage("Email",$lang)."</td><td>
		<input type='text' name='username' value='".$username."' /></td></tr><tr><td>
		".swSystemMessage("Password",$lang)."</td><td>
		<input type='password' name='pass' value='' />
		<input type='hidden' name='name' value='$name' />
		<input type='hidden' name='action' value='login' />
		</td></tr><tr><td></td><td>
		<input type='submit' name='submitprotect' value='".swSystemMessage("Login",$lang)."' /></td></tr></table>
	</form>";
	
	if ($swNewUserEnable)
	$swParsedContent .= 
	"<p><a href='index.php?action=newuser'>".swSystemMessage("New User",$lang)."</a></p>";
	
	$swParsedContent .= 
	"<p><a href='index.php?action=lostpassword'>".swSystemMessage("Lost Password",$lang)."</a></p>

	<div id='help'>".swSystemMessage("LoginHelp",$lang)."</div>
	</div>
	";

?>