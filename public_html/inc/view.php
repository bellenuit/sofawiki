<?php

//echo " viewstart ".microtime(); 

if (!defined("SOFAWIKI")) die("invalid acces");

echotime('view');

if ($user->hasright("view", $wiki->name))
{
	echotime('hasright');		
	$wiki->lookupLocalName();
	$wiki->lookup(); 
	echotime('lookup');		
	if ($wiki->error)
	{
		$swError = swSystemMessage($wiki->error,$lang);
	}
	else
		$swError = '';
	if ($wiki->status == "deleted" || $wiki->status == "delete")
	{
		header('HTTP/1.0 404 Not Found');
		$swError = swSystemMessage("ThisPageHasBeenDeletedError",$lang);
	}
	else
	{
		$wiki->parsers = $swParsers;
		echotime('parse');
		$swParsedContent = $wiki->parse();
		echotime('parseend');
	}
	if ($wiki->error == 'No record with this name')
	{
			header('HTTP/1.0 404 Not Found');
			$swParsedContent = 'HTTP/1.0 404 Not Found';
	}
	
	$swFooter = "Revision:$wiki->revision, $wiki->user, Date:$wiki->timestamp, Status:$wiki->status";
	$swFooter = "";  // do not show

}
else
{
	$hookresult = '';
	if (function_exists('swInternalNoAccessHook')) 
	{
		
		
		$hookresult = swInternalNoAccessHook($name);
		if ($hookresult != '')
		{
			
			$wiki->content = $hookresult;
			$wiki->parsers = $swParsers;
			$swParsedContent = $wiki->parse();
			
		}
		
		//print_r($hookresult);
	}
	
	if (!$hookresult)
	{
		$swError = swSystemMessage("NoAccessError",$lang);
		$swFooter = "";
	}
}
if (isset($wiki->displayname))
	$swParsedName = $wiki->displayname;  // must be here, because the wiki can be redirected
if ($name != $wiki->name and $user->hasright("modify", $wiki->name))
{
	$swEditMenus[] = "<a href='".$wiki->link("edit")."'>".swSystemMessage("Edit",$lang)." $wiki->name</a>";
}

echotime('viewend');						

?>