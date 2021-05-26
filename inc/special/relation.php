<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Relation';

$swMaxOverallSearchTime *=3;  

$q = swGetArrayValue($_REQUEST,'q');
$submitrefresh = swGetArrayValue($_REQUEST,'submitrefresh');
$submkitwikitext = swGetArrayValue($_REQUEST,'submitwikitext');
if ($submitrefresh)
	$swDebugRefresh = true;

$swParsedContent = '<nowiki><div id="editzone"><form method="post" action="index.php"><p><input type="hidden" name="name" value="special:relation" /><code id="editor" style="width:100%; display:none">'.$q.'</code><textarea id="shadoweditor" name="q" rows=8 cols=180 style="width:100%">'.$q.'</textarea><p><input type="submit" name="submit" value="Run" /><input type="submit" name="submitrefresh" value="Run Refresh" /><input type="submit" name="submitwikitext" value="Run Wikitext" /></form></nowiki>';

	$alines = array('relation',$q);
	$dtb = new swRelationfunction;
	
	/* echo "<pre>";
	print_r($alines);
	echo "</pre>"; */

		
	$s = $dtb->dowork($alines);
	
	// remove nowiki
	//$swParsedContent = str_replace('<nowiki>','',$swParsedContent);
	//$swParsedContent = str_replace('</nowiki>','',$swParsedContent);
	//$swParsedContent = swUnescape($swParsedContent);

//$query = str_replace("\n","<br>",$query);

$swMaxOverallSearchTime /=3;  

if (trim($s)=="" && !stristr($q,'print') && (stristr($q,'relation') || stristr($q,'read') || stristr($q,'filter') || stristr($q,'import') || stristr($q,'virtual')))
$s = "''Did you forget '''''print'''''?''";


if ($submkitwikitext)
{
	$swParseSpecial = false;
	$swParsedContent .= '<textarea rows=16 cols=180 style="width:100%">'.$s.'</textarea>';
}
else
{
	$swParseSpecial = true;
	$swParsedContent .= $s;

}





?>