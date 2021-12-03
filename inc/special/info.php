<?php

function foldersize($path)
{
	return sprintf('%0d MB',foldersizer($path)/1024/1024);
}

function foldercount($path)
{
	if(!file_exists($path)) return 0;
	if(is_file($path)) return filesize($path);
	$result = 0;
	$list = glob($path."/*");
	$c = count($list);
	return $c;
}

function foldersizer($path)
{
	if(!file_exists($path)) return 0;
	if(is_file($path)) return filesize($path);
	$result = 0;
	$list = glob($path."/*");
	$c = count($list);
	if (!$c) return 0;
	$n = min($c,1000);
	for($i = 0; $i < $n; $i++)
    	$result += foldersizer($list[rand(0,$c-1)]);
  	return $result*$c/$n;
}


if (!defined("SOFAWIKI")) die("invalid acces");



if (isset($_REQUEST['phpinfo']))
{
	phpinfo();
	return;
}

$swParseSpecial = true;

$swParsedName = "Special:Info";

// suppress notices
if (!isset($_ENV["REMOTE_ADDR"])) $_ENV["REMOTE_ADDR"] = '?';
if (!isset($_ENV["HTTP_USER_AGENT"])) $_ENV["HTTP_USER_AGENT"] = '?';
if (!isset($_ENV["REQUEST_URI"])) $_ENV["REQUEST_URI"] = '?';
if (!isset($_ENV["HTTP_REFERER"])) $_ENV["HTTP_REFERER"] = '?';
if (!isset($_ENV["SERVER_ADDR"])) $_ENV["SERVER_ADDR"] = '?';
if (!isset($_ENV["HTTP_HOST"])) $_ENV["HTTP_HOST"] = '?';
if (!isset($_ENV["SERVER_SIGNATURE"])) $_ENV["SERVER_SIGNATURE"] = '?';


$swParsedContent = "'''Version''' {{version}}
'''Encoding''' UTF-8
'''Date''' {{currentdate}}
'''User''' {{currentuser}}
'''Name''' {{currentname}}
'''Skin''' {{currentskin}}
'''Language''' {{currentlanguage}} (".join(', ',$swLanguages).")
'''Pages''' {{countpages}}
'''Revisions''' {{countrevisions}}
'''Base path''' ".$swRoot."
'''RAM disk''' ".$swRamdiskPath;

if ($deepl = swTranslateUsage())
$swParsedContent .=  "
'''DeepL''' ".swTranslateUsage();

$swParsedContent .= "
====Installed Functions====
{{functions}}
====Installed Parsers====
{{parsers}}
====Installed Skins====
{{skins}}
====Templates====
{{templates}}
====Environment====
Remote Address " .getenv('REMOTE_ADDR')."
User Agent " .getenv('HTTP_USER_AGENT')."
Request URI " .getenv('REQUEST_URI')."
Referer " .getenv('HTTP_REFERER')."
Server Address " .getenv('SERVER_ADDR')."
Host " .getenv('HTTP_HOST')."
PHP Version ".phpversion()." <nowiki><a href='index.php?name=special:info&phpinfo=1'>PHP Info</a></nowiki>
Memory Limit ".ini_get("memory_limit")."
Memory Usage ".sprintf("%0.1f",memory_get_usage()/1024/1024)."M
Upload max filesize ".ini_get('upload_max_filesize')."
Post max size ".ini_get('post_max_size')."
Server Signature " .getenv('SERVER_SIGNATURE')."


";

if ($swRamdiskPath == 'memcache')
{
	$swParsedContent .= print_r($swMemcache->getStats(), true);
}


// print_r($_ENV);

?>