<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Mail";


if (array_key_exists("subject", $_POST)) 
	$subject = $_POST['subject'];
else
	$subject="";
if (array_key_exists("adresses", $_POST)) 
	$adresses = $_POST['adresses'];
else
	$adresses="";
if (array_key_exists("body", $_POST)) 
	$body = $_POST['body'];
else
	$body="";
if (array_key_exists("submitsendmail", $_POST)) 
	$submitsendmail = $_POST['submitsendmail'];
else
	$submitsendmail=false;
if (array_key_exists("preview", $_POST)) 
	$preview = $_POST['preview'];
else
	$preview=false;
	
	
if ($submitsendmail)
{
	$swParsedContent = "<p>Send Mail started";
	
	// create a page in the mail namespace
	
	$wiki = new swWiki();
	// $wiki->init($db);
	$wiki->name = "Mail:".$subject." ".date("Y-m-d",time());
	$wiki->user = $user->name;
	$wiki->content = "$body\n\n[[_outbox::$adresses]]";
	$wiki->insert();	
	
	// leave a pseudo cron job
	
	$wiki2 = new swWiki();
	// $wiki2->init($db);
	$wiki2->name = "Cron:Cron";
	$wiki2->lookup();
	
	$wiki2->content .= "[[_todo::$wiki->name]]";
	$wiki2->insert();	
	
	
	// notify
	
	swNotify("sendmail",$subject,"");
	
	
	
	
	
}
else
{
	$swParsedContent = "";
	if ($preview)
	$swParsedContent = "\n<pre>From: $swNotifyMail
To: $adresses
Subject: $subject
Body: $body

</pre>";
	
	
	
	$swParsedContent .= "<p>".swSystemMessage("Send Mail",$lang)."
	
	<div id='editzone'>
			<form method='post' action='index.php?name=special:mail'>
			<p>
			<input type='text' name='subject' value=\"$subject\" size=60 />
			<input type='hidden' name='action' value='sendmail' size=60 />
			<input type='submit' name='preview' value='".swSystemMessage("Preview",$lang)."' />
			<input type='submit' name='submitsendmail' value='".swSystemMessage("Send",$lang)."' />
	
			<br/>".swSystemMessage("Text",$lang)."<br/><textarea name='body' rows='40' cols='80'>$body</textarea>
			<br/>".swSystemMessage("Adresses separated by comma",$lang)."<br/><textarea name='adresses' rows='40' cols='80'>$adresses</textarea></p>
	</form>
	</div>";
}

$swParsedContent .= "\n\n<ul>";

$names = $db->GetAllNames();


foreach ($names as $n=>$s)
{
	if (substr($n,0,5)=="Mail:" && ($s == "ok" || $s == "proteted"))
	{
		
		$first = strtolower(substr($n,0,1));
		
		$swParsedContent .= "<li><a href='index.php?name=$n'>$n</a></li>\n";
		$oldfirst = $first;
 	}
}
$swParsedContent .= "\n\n</ul>";


$swParseSpecial = false;





?>