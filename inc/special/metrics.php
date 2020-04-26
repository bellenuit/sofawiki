<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Metrics";



$revisions = swQuery(array('SELECT datestart FROM logs',
'CALC year datestart 0 4 SUBSTR',
'PROJECT year',
'WHERE year !0',
'ORDER year'));

$swParsedContent .= '<nowiki><p></nowiki>';
foreach($revisions as $rev)
{
	$swParsedContent .= '<nowiki><a href="index.php?name=special:metrics&year='.$rev['year'].'">'.$rev['year'].'</a> </nowiki>';
}
$swParsedContent .= '<nowiki></p></nowiki>';
	
	
$year = date('Y', time());
if (isset($_REQUEST['year'])) $year = $_REQUEST['year'];

	
$swParsedContent .= '{{query
| SELECT datestart, uniquevisitors, uniquepageviews, hits, totaltime FROM logs WHERE datestart =* '.$year.'
| CALC datemonth datestart 0 7 SUBSTR
| CALC totaltime totaltime 1000 /
| GROUP datemonth, uniquevisitors SUM, uniquepageviews SUM, hits SUM, totaltime SUM BY datemonth
| ORDER datemonth DESC
}}

====Most viewed pages '.$year.'====

{{query
| SELECT datestart, name, uniqueviews FROM logs WHERE datestart =* '.$year.'
| GROUP name, uniqueviews SUM BY name
| ORDER uniqueviews-sum NUMERIC DESC
| LIMIT 0 50
}}

====Search keywords '.$year.'====

{{query
| SELECT datestart, query, queryhits FROM logs WHERE datestart =* '.$year.'
| WHERE query !0
| GROUP query, queryhits SUM BY query
| ORDER queryhits-sum NUMERIC DESC
| LIMIT 0 50
}}



';



$swParseSpecial = true;

// print_r($_ENV);

?>