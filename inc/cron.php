<?php

if (!defined("SOFAWIKI")) die("invalid acces");


function swRelationFilterOvertimeRetake()
{
	// check if there is a recent filter that has overtime set
	
	

	global $swRoot;
	
	$files = glob($swRoot.'/site/queries/*.db');
	if (count($files)>0)
	{
	
		$files = array_combine(array_map("filemtime", $files),$files);
		arsort($files);
		
		$i = 0;

		foreach($files as $mtime=>$file)
		{
			$i++;
			if ($i>250) return;
			
			$bdb = swDBA_open($file, 'wdt', 'db4'); // force write. don't open if used
			if (!$bdb) continue;
			
			$s = swDBA_fetch('_overtime',$bdb);
			$overtime = false;			
			// unserialize does not work on a simple boolean, we hack			
			if ($s == 'b:1;' ) $overtime  = true;
			
			if (!$overtime) { swDBA_close($bdb); continue; }
			
			$filter = swDBA_fetch('_filter',$bdb);
						
			swDBA_close($bdb); 
			
			if (!$filter) continue;
			
			echotime( 'cron overtime filter '.$filter );
			
			if (!defined("CRON")) $swMaxSearchTime = 1000;
			
			try
			{
			swRelationfilter($filter);
			}
			catch (swExpressionError $err)
			{
				// do nothing;
			}
			catch (swRelationError $err)
			{
				// do nothing;
			}
			echotime('max searchtime '.$swMaxSearchTime);
			
			break;
		}
		
	}

}


function swCron()
{
	global $db; 
	global $action; 
	global $swIndexError;
	global $swOvertime;
	global $swRoot;
	
	
	
	//$db->init(true);
	// if ($action == 'indexerror' || $swIndexError) return;
	if ($swIndexError) return;
	if ($swOvertime) return;
	
	if (function_exists('swInternalCronHook') && swInternalCronHook()) 
	{

		// everything is handled by configuration.php
	}
	else
	{
		
		$r = rand(0,100);
		
		if ($r > 20) return;
		
		switch($r)
		{
			case 0: echotime('cron index'); $db->init(true); return 'cron index'; // rebuild indexes 
						
			case 1: echotime('cron bitmap'); $db->rebuildBitmaps(); return 'cron bitmap';  
						
			case 2: echotime('cron sitemap'); swSitemap();  return 'cron sitemap';  

			case 3: echotime('cron bloom'); swIndexBloom(10); return 'cron bloom';  
			
				
			case 4: echotime('cron logs'); 
			  		  
			  		  if (!defined("CRON")) define('CRON',true);
			  		  global $swParsedContent;
			  		  $swParsedContent .= '';
			  		  include_once $swRoot.'/inc/special/logs.php';
			  		  
			  		  
			  
			  		  return "cron logs"; 
			  		  break; 
			case 5: echotime('cron IndexRamDiskDB'); 
			  		  swIndexRamDiskDB();	
			  		  break;
			  		  

			
			default:  echotime( 'cron overtime');
			
					  swRelationFilterOvertimeRetake();
					  
					  echotime('cron end');
		
		}
	}
	
	
}



?>