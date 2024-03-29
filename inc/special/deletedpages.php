<?php
	
/** 
 * Shows a list of all deleted pages.
 *
 * Used by the $action allpages.
 * The link will go to the history of the page
 */

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Deleted Pages";



$currentbitmap = $db->currentbitmap->duplicate();
$deleted = $db->deletedbitmap->toarray();

$urldbpath = $db->pathbase.'indexes/urls.db';
if (file_exists($urldbpath))
		$urldb = swDbaOpen($urldbpath, 'rdt');
if (!@$urldb)
{
	echotime('urldb failed');
}
else
{
	$rel = new swRelation('_name');
	$key = swDbaFirstKey($urldb);
	$dw = new swWiki;
	do 
	{
	  
	  if (substr($key,0,1) != ' ')
	  {
		  $value = swDbaFetch($key,$urldb);
		  if (!$value) continue;
		  if (substr($value,0,1) == 'd')
		  {
			 
			 $rel->insert('"'.swEscape($key).'"');
		  }
	  }
		
	} while ($key = swDbaNextKey($urldb));
	
	$rel->label('_name ""');
	$rel->update('_name = "<nowiki><a href="._quote."index.php?name="._name."&action=history"._quote.">"._name."</a></nowiki>" ');

	$swParsedContent = '<p>'.$rel->toHtml('grid 50');

}



$swParseSpecial = true;


?>