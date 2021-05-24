<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swCreateSitemap = false;

function swSitemap()

{
		global $swMaxOverallSearchTime;
		global $swCreateSitemap ; 
		global $db;
		global $swBaseHrefFolder;
		
		echo "sitemap1";
		
		if (!$swCreateSitemap) return;
		
		echo "sitemap2";
			
		$swMaxOverallSearchTime /= 40;
		
		$table = swRelationToTable('filter _namespace "main", _name _short
select _short not (regex "^#REDIRECT")
project _name
order _name a
');
		
	
		// $filter = 'SELECT _name WHERE _content !=* #REDIRECT';
		// $swMaxOverallSearchTime *= 40;
		// $revisions = swFilter($filter,'','query');
		
		$resultlist []= '<?xml version="1.0" encoding = "UTF-8"?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">';
		
		foreach ($table as $row)
		{
			$url = swNameURL($row['_name']);
			
			
			
				$resultlist[] =
' <url>
   <loc>'.$swBaseHrefFolder.$url.'</loc>
   <changefreq>weekly</changefreq>
   <priority>1.0</priority>
 </url>';
			
		}
		
		$resultlist []= 
		'</urlset>';
	
	$result = join(PHP_EOL,$resultlist);
	
	global $swRoot;
	$file = $swRoot.'/sitemap.xml';
	unlink($file);
	if ($handle = fopen($file, 'c')) { fwrite($handle, $result); fclose($handle); }
	else { echo swException('Write error sitemap'); $error = 'Write error sitemap';  return; }

}

?>
