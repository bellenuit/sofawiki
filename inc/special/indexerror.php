<?php

if (!$swParsedName) $swParsedName = "Site maintenance";
$swParsedContent = '<b>Site indexing</b>
<p>The site is reindexing and currently not available. Please come back in a few minutes. ('
						.sprintf('%0d',100*$db->indexedbitmap->countbits()/$db->GetLastRevisionFolderItem()).'%)';
echotime('error '.$swError);
$swError = '';
$swFooter = '';
$swEditMenus = array();
$swOvertime = true;
						
?>
