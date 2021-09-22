<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Metrics";

$y = date('Y', time());
if (isset($_REQUEST['year'])) $y = $_REQUEST['year'];

$q = 
"filter datestart, _namespace \"logs\"
extend Year = substr(datestart,0,4)
project Year
​order Year
update Year = \"<nowiki><a href='index.php?name=special:metrics&year=\".Year.\"'>\".Year.\"</a></nowiki>\"
project Year concat
update Year_concat = replace(Year_concat,\"::\",\" \")
rename Year_concat Year
print raw



filter datestart \"".$y."\", uniquevisitors, uniquepageviews, hits, totaltime, _namespace \"logs\"
extend datemonth = substr(datestart,0,7)
update totaltime = totaltime / 1000
project datemonth, uniquevisitors sum, uniquepageviews sum, hits sum, totaltime sum
order datemonth z
label datemonth \"Month\", uniquevisitors_sum \"Unique visitors\", uniquepageviews_sum \"Unique pageviews\", hits_sum \"Hits\", totaltime_sum \"Total time\" 
format uniquevisitors_sum \"%1.0n\", uniquepageviews_sum \"%1.0n\", hits_sum \"%1.0n\", totaltime_sum \"%1.3n\"
print



format uniquevisitors_sum \"%1.0f\", uniquepageviews_sum \"%1.0f\", hits_sum \"%1.0f\", totaltime_sum \"%1.3f\"
order datemonth a
project datemonth, uniquevisitors_sum, uniquepageviews_sum, hits_sum
delegate \"linechart -tensions 0.3\"



echo \"====Most viewed pages ".$y."====\"

filter datestart \"".$y."\", _namespace \"logs\", name, uniqueviews

project name, uniqueviews sum
order uniqueviews_sum 9
limit 1 100
label name \"Name\", uniqueviews_sum \"Unique views\"
format uniqueviews_sum \"%1.0n\"
print grid 25



echo \"====Popular search keywords ".$y."====\"

filter datestart \"".$y."\", query, queryhits
select trim(query) !== \"\"
project query, queryhits sum
order queryhits_sum 9
limit 1 100
label query \"Query\", queryhits_sum \"Query hits\"
format queryhits_sum \"%1.0n\"
print grid 25

";


$lh = new swRelationLineHandler;
$swParsedContent .= $lh->run($q);
$swParseSpecial = true;
	

?>