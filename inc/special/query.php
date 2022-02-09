<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Query';

$swMaxOverallSearchTime *=3;  

$q = swGetArrayValue($_REQUEST,'q');
$submitrefresh = swGetArrayValue($_REQUEST,'submitrefresh');
$_REQUEST['verbose'] = swGetArrayValue($_REQUEST,'submitverbose');
if ($submitrefresh)
	$swDebugRefresh = true;


$swParsedContent = '<div id="editzone" class="editzone">
<div class="editheader">Query</div>
<form method="post" action="index.php">
<input type="hidden" name="name" value="special:query" />
<input type="submit" name="submit" value="Search" />
<input type="submit" name="submitrefresh" value="Search Refresh" />
<input type="submit" name="submitverbose" value="Verbose" />
<textarea name="q" rows=8>'.$q.'</textarea>
		</form>
</div><!-- editzone -->';




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

$swMaxOverallSearchTime /=3;  


$swParseSpecial = false;





?>