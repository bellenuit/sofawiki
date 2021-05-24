<?php

if (!defined("SOFAWIKI")) die("invalid acces");

function swRSS($query,$rssname)

{
		global $swMainName;
		global $swBaseHrefFolder;
				
		//$lines = explode("\n",$query);
		//$list0 = swQuery($lines);
		
		$list0 = swRelationToTable($query);
		
		$list = array(); 
		foreach($list0 as $row)
		{
			if (is_array($row) && count($row)>0)
				$list[] = array_shift($row);
		}
		
		
		$resultlist []= '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
	<title>'.swSystemMessage("sitename$rssname","").'</title>
	<description>'.swSystemMessage("sitedescription$rssname","").'</description>
    <lastBuildDate>'.date("Y-m-d H:i:s").'</lastBuildDate>
    <link>'.$swBaseHrefFolder.'</link>';
		
		foreach ($list as $k=>$v)
		{
			$w = new swWiki;
			$w->name = $v;
			$w->lookup();
			
			$f = new swResumeFunction;
			$t = $f->dowork(array('',$v,140));
			$title = $w->name;
			$title = str_replace("& ","&amp;",$title);
									
			$resultlist[] ='
			<item>
            	<title>'.$title.'</title>
            	<description>'.$t.'</description>
            	<pubDate>'.$w->timestamp.'</pubDate>
            	<link>'.$swBaseHrefFolder.swNameURL($v).'</link>
        	</item>';
			
		}
		
		$resultlist []= '
	 </channel>
</rss>';
	
	$result = join(' ',$resultlist);
	
	//$result.=print_r($list,true);
	
	global $swRoot;
	$file = $swRoot.'/feed'.$rssname.'.rss';
	if ($handle = fopen($file, 'w')) { fwrite($handle, $result); fclose($handle); }
	else { echo swException('Write error RSS'); $error = 'Write error RSS';  return; }

		
}



?>