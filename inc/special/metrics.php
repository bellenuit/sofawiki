<?php
	
/** 
 * Shows metrics of website accesses using logs
 *
 * special:metrics
 * names and user are limit to 1000 each per month
 * metrics for one month saves 3 files to cache: logs-YYYY-MM.csv with the numbers, logs-names-YYYY-MM.csv with 1000 pages, logs-users-YYYY-MM.csv with 1000 users
 * metrics for one year use the cache files. 
 * cron updates any arbitrary month cache of the last 10 years. 
 */


if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Metrics";

$y = '';
if (isset($_REQUEST['year'])) $y = $_REQUEST['year'];
if ($y == '*') $y = '';

$m =  '';
if (isset($_REQUEST['month'])) $m = $_REQUEST['month'];

if (!isset($_REQUEST['year']) && !isset($_REQUEST['month'])) $m = date('Y-m');

if (defined('CRON'))
{
	// do any month last 10 years
	$d = rand(1,120);
    $m = date('Y-m', strtotime('-'.$d.' months'));
}

if ($m)
{
	$q = 
	"
	
	set m = ".'"'. $m .'"'."
	
	logs file
	update file = substr(file,0,4)
	order file z
	update file = \"<nowiki><a href='index.php?name=special:metrics&year=\".file.\"'>\".file.\"</a></nowiki>\"
	project file concat
	update file_concat = replace(file_concat,\"::\",\" \")
	rename file_concat Year
	
	print raw
	
	
	
	set y = substr(m,0,4)
	
	logs file
	update file = substr(file,0,7)
	select file regex y
	order file z
	update file = \"<nowiki><a href='index.php?name=special:metrics&month=\".file.\"'>\".file.\"</a></nowiki>\"
	project file concat
	update file_concat = replace(file_concat,\"::\",\" \")
	rename file_concat Month
	echo _space
	print raw
	
	
	
	
	echo \" \"
	echo \"'''\".m.\"'''\"

	logs stats m
	// print
	dup
	select category == \"stat\" and file == m
	project file, key, value
	deserialize
	// print
	project hits, totaltime, visited_pages, unique_users
	print
	pop
	dup
	select category == \"stat\"
	project file, key, value
	deserialize
	project day, hits, totaltime, visited_pages, unique_users
	// print
	write \"logs-\".m.\".csv\"
	select day !== m
	project day, hits, visited_pages, unique_users
	order day a
	delegate \"barchart -tensions 0.3\"
	// print
	
	pop
	dup
	select category == \"name\"
	rename key name, value count
	project name, count
	order count 9
	limit 1 1000
	print grid 20
	write \"logs-names-\".m.\".csv\"
	pop
	select category == \"user\"
	rename key user, value count
	project user, count
	order count 9
	limit 1 1000
	print grid 20
	write \"logs-users-\".m.\".csv\"
	
	";
}
elseif ($y)
{
	$q = 
	"
	set y =  \"".$y."\"
	
	logs file
	update file = substr(file,0,4)
	order file z
	update file = \"<nowiki><a href='index.php?name=special:metrics&year=\".file.\"'>\".file.\"</a></nowiki>\"
	project file concat
	update file_concat = replace(file_concat,\"::\",\" \")
	rename file_concat Year
	
	print raw
	
	logs file
	select file regex y
	update file = substr(file,0,7)
	order file z
	update file = \"<nowiki><a href='index.php?name=special:metrics&month=\".file.\"'>\".file.\"</a></nowiki>\"
	project file concat
	update file_concat = replace(file_concat,\"::\",\" \")
	rename file_concat Month
	
	echo \" \"
	print raw
	
	
	echo \" \"
	echo \"'''\".y.\"'''\"
	
	
	relation day,hits,totaltime,visited_pages,unique_users
	set i = 1
	while i < 13
	set file = \"logs-\".y.\"-\".substr(\"0\".i,-2,2).\".csv\" 
	if fileexists(file)
	read file
	union
	end if
	set i = i + 1
	end while
	extend month = substr(day,0,7)
	select day == month
	// print
	project month, hits sum, totaltime sum, visited_pages sum, unique_users sum
	rename hits_sum hits, totaltime_sum totaltime, visited_pages_sum visited_pages, unique_users_sum unique_users
	dup
	
	project hits sum, totaltime sum, visited_pages sum, unique_users sum
	
	rename hits_sum hits, totaltime_sum totaltime, visited_pages_sum visited_pages, unique_users_sum unique_users
	update totaltime = round(totaltime)
	print
	pop	
	
	project drop totaltime
	delegate \"barchart -tension 0.3\"
	pop
	update totaltime = round(totaltime)
	dup

	// print
	
	relation name, count, month
	set i = 1
	while i < 13
	set file = \"logs-names-\".y.\"-\".substr(\"0\".i,-2,2).\".csv\" 
	if fileexists(file)
	read file
	extend month = file
	union
	end if
	set i = i + 1
	end while
	project name, count sum
	rename count_sum count
	order count 9
	limit 1 1000
	print grid 20
	
	relation user, count, month
	set i = 1
	while i < 13
	set file = \"logs-users-\".y.\"-\".substr(\"0\".i,-2,2).\".csv\" 
	if fileexists(file)
	read file
	extend month = file
	union
	end if
	set i = i + 1
	end while
	project user, count sum
	rename count_sum count
	order count 9
	limit 1 1000
	print grid 20
	
	";
}
else
{
	$q = 
	"
	
	logs file
	update file = substr(file,0,4)
	order file z
	update file = \"<nowiki><a href='index.php?name=special:metrics&year=\".file.\"'>\".file.\"</a></nowiki>\"
	project file concat
	update file_concat = replace(file_concat,\"::\",\" \")
	rename file_concat Year
	
	print raw
		
	relation day,hits,totaltime,user_count,name_count
	set k = 0
	while k < 10
	set year = ".date("Y",time())." - k
	set i = 1
	while i < 13
	set file = \"logs-\".year.\"-\".substr(\"0\".i,-2,2).\".csv\" 
	if fileexists(file)
	read file
	union
	end if
	set i = i + 1
	end while
	set k = k + 1
	end while
	extend year = substr(day,0,4)
	project year, hits sum, totaltime sum, user_count sum, name_count sum
	order year 1
	rename hits_sum hits, totaltime_sum totaltime, name_count_sum visited_pages, user_count_sum unique_users
	dup
	project drop totaltime
	delegate \"barchart -tension 0.3\"
	pop
	update totaltime = format(totaltime,\"%0d\")
	dup
	project hits sum, totaltime sum, visited_pages sum, unique_users sum
	
	rename hits_sum hits, totaltime_sum totaltime, visited_pages_sum visited_pages, unique_users_sum unique_users
	update totaltime = format(totaltime,\"%0d\")
	print
	pop
	print
	
	relation name, name_count, month
	set k = 0
	while k < 10
	set year = ".date("Y",time())." - k
	
	set i = 1
	while i < 13
	set file = \"logs-pages-\".year.\"-\".substr(\"0\".i,-2,2).\".csv\" 
	if fileexists(file)
	read file
	extend month = file
	union
	end if
	set i = i + 1
	end while
	set k = k + 1
	end while
	project name, name_count sum
	rename name_count_sum name_count
	order name_count 9
	limit 1 1000
	print grid 20
	
	relation user, user_count, month
	
	set k = 0
	while k < 10
	set year = ".date("Y",time())." - k
	set i = 1
	while i < 13
	set file = \"logs-users-\".year.\"-\".substr(\"0\".i,-2,2).\".csv\" 
	if fileexists(file)
	read file
	extend month = file
	union
	end if
	set i = i + 1
	end while
	set k = k + 1
	end while
	project user, user_count sum
	rename user_count_sum user_count
	order user_count 9
	limit 1 1000
	print grid 20
	
	";
}


$lh = new swRelationLineHandler;
$swParsedContent = '<nowiki><p></nowiki>'.$lh->run($q);
$swParseSpecial = true;
	

?>