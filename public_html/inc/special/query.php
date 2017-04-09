<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Query';

$swMaxRelaxedSearchTime *=3;   
$swMaxOverallSearchTime *=3;  

$q = swGetArrayValue($_REQUEST,'q');
$submitrefresh = swGetArrayValue($_REQUEST,'submitrefresh');
$_REQUEST['verbose'] = swGetArrayValue($_REQUEST,'submitverbose');
if ($submitrefresh)
	$swDebugRefresh = true;


$swParsedContent = '<div id="editzone"><form method="post" action="index.php">
		<p>
		<input type="hidden" name="name" value="special:query" />
		<textarea name="q" rows=8 cols=180 style="width:100%">'.$q.'</textarea>
		<input type="submit" name="submit" value="'.swSystemMessage('Search',$lang).'" />
		<input type="submit" name="submitrefresh" value="'.swSystemMessage('SearchRefresh',$lang).'" />
		<input type="submit" name="submitverbose" value="'.swSystemMessage('Search Verbose',$lang).'" /></form>';




	$alines = explode("\n",$q);
	
	$lines = array();
	foreach($alines as $line)
	{
		if (substr($line,0,1) == '|')
		$line = substr($line,1);
		$line = ltrim($line);
		$line = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u',' ',$line);

		$lines[] = $line; 
	}
	
	$dtb = new swQueryFunction;
	$dtb->searcheverywhere = true;
		
	$swParsedContent .= $dtb->dowork($lines);
	
	// remove nowiki
	$swParsedContent = str_replace('<nowiki>','',$swParsedContent);
	$swParsedContent = str_replace('</nowiki>','',$swParsedContent);
	$swParsedContent = swUnescape($swParsedContent);

$query = str_replace("\n","<br>",$query);

$swMaxRelaxedSearchTime /=3;   
$swMaxOverallSearchTime /=3;  


$swParseSpecial = false;





?>