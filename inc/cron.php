<?php

if (!defined("SOFAWIKI")) die("invalid acces");


function swCron()
{
	global $db; 
	global $action; 
	global $swIndexError;
	global $swRoot;
	
	$db->init(true);
	if ($action == 'indexerror' || $swIndexError) return;
	
	if (function_exists('swInternalCronHook') && swInternalCronHook()) 
	{
		// everything is handled by configuration.php
	}
	else
	{
		switch(rand(0,6))
		{
			case 0: echotime('cron index'); $db->init(true); return 'cron index'; // rebuild indexes 
						
			case 2: echotime('cron bitmap'); $db->rebuildBitmaps(); return 'cron bitmap';  
						
			case 3: echotime('cron sitemap'); swSitemap();  return 'cron sitemap';  

			case 4: echotime('cron bloom'); swIndexBloom(100); return 'cron bloom';  
			
			case 5: 
	
				// check if there is a recent search that has overtime set
				
				$files = glob($swRoot.'/site/queries/*.txt');
				if (count($files)>0)
				{
					$files = array_combine($files, array_map("filemtime", $files));
					arsort($files);
					
					$i=0;
					$filterlist='';
					foreach($files as $k=>$m)
					{
						
						$fn = str_replace($swRoot.'/site/queries/*.txt','',$k);
						if (stristr($fn,'-')) continue; // check only indexfile
						$s = file_get_contents($k); // slow on big files
						$overtime = swGetValue($s,'overtime');
						$filter = swGetValue($s,'filter');
						$namespace = swGetValue($s,'namespace');
						$mode = swGetValue($s,'mode');
						if ($overtime)
						{
							swFilter($filter,$namespace,$mode);
							$filterlist .= '<br>'.$filter;
							$i+=9;
						}
						$i++;
						if ($i>200) break;
					}
					return 'cron overtime filter '.$filterlist;
				}

			
			default: return "cron pause";
		
		}
	}
}



?>