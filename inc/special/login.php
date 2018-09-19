<?php

if (!defined("SOFAWIKI")) die("invalid acces");



$swParsedName = swSystemMessage("login",$lang);
$swParsedContent = "<div id='editzone'>
		<form method='post' action='index.php'>
		<table class='blanktable' ><tr><td>
		".swSystemMessage("email",$lang)."</td><td>
		<input type='text' name='username' value='".$username."' /></td></tr><tr><td>
		".swSystemMessage("password",$lang)."</td><td>
		<input type='password' name='pass' value='' />
		<input type='hidden' name='name' value='$name' />
		<input type='hidden' name='action' value='login' />
		</td></tr><tr><td></td><td>
		<input type='submit' name='submitlogin' value='".swSystemMessage("login",$lang)."' /></td></tr></table>
	</form>";
	
	if ($swNewUserEnable)
	$swParsedContent .= 
	"<p><a href='index.php?action=newuser'>".swSystemMessage("new-user",$lang)."</a></p>";
	
	$swParsedContent .= 
	"<p><a href='index.php?action=lostpassword'>".swSystemMessage("lost-password",$lang)."</a></p>

	<div id='help'>".swSystemMessage("login-help",$lang)."</div>
	</div>
	";

?>