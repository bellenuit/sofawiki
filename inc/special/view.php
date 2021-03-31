<?php

//echo " viewstart ".microtime(); 

if (!defined("SOFAWIKI")) die("invalid acces");


if ($user->hasright("view", $wiki->name))
{
			
	$wiki->lookupLocalName();
	$wiki->lookup(); 
	if ($wiki->error)
	{
		$swError = swSystemMessage($wiki->error,$lang);
	}
	else
		$swError = '';
	if ($wiki->status == "deleted" || $wiki->status == "delete")
	{
		header('HTTP/1.0 404 Not Found');
		$swParsedContent = 'HTTP/1.0 404 Not Found';
		$swParsedContent .= '<br><a href="index.php?action=search&query='.$name.'">'.swSystemMessage('search',$lang).' '.$name.'</a>';

	}
	else
	{
		$name = $wiki->namewithoutlanguage();
		$wiki->parsers = $swParsers;
		$swParsedContent = $wiki->parse();
	}
	if ($wiki->error == 'No record with this name')
	{
			// we check again directly
			$revisions = swGetAllRevisionsFromName($name);
			if (count($revisions)>0)
			{
				$swError = '';
				$wiki = new swWiki;
				$wiki->revision = array_pop($revisions);
				$wiki->lookup();
				$wiki->parsers = $swParsers;
				$swParsedContent = $wiki->parse();
				
			}
			else
			{
				header('HTTP/1.0 404 Not Found');
				$swParsedContent = 'HTTP/1.0 404 Not Found';
				$swParsedContent .= '<br><a href="index.php?action=search&query='.$name.'">'.swSystemMessage('search',$lang).' '.$name.'</a>';
			}
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
		$swError = swSystemMessage("no-access-error",$lang);
		$swFooter = "";
	}
}
if (isset($wiki->displayname))
	$swParsedName = $wiki->displayname;  // must be here, because the wiki can be redirected
						

?>