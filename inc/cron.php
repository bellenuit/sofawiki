<?php
	
/**
 *	The poor man's cron job. Or, on the bright side: a cron job without configuration needed.
 *
 *  The functions are used randomly by index.php and cron.php
 */	
	
if (!defined("SOFAWIKI")) die("invalid acces");


/**
 * Chooses randomly a maintenance function to be executed.
 *
 * Default function used at the end of index.php and directly by cron.php
 * You can provide a swInternalCronHook() function in site/configuration.php to override this.
 * The cron function is not running when there is an index error or when a filter functions runs out of time.
 * The cron function runs only if $action is view or empty.
 * Further, the cron function is only used 1 time out of 5.
 * It then either builds some indexes, saves logs or works on relation filters that are not completely up to date.
 * 
 * The function returns the activity it has done.
 */


function swCron()
{
	global $db; 
	global $action; 
	global $swIndexError;
	global $swOvertime;
	global $swRoot;
	
	if ($action != 'view' && $action != '') return;
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
			case 0: 	echotime('cron index'); 	
						$db->init(true); 
						return 'cron index'; 
						
			case 1: 	echotime('cron bitmap'); 
						$db->rebuildBitmaps(); 
						return 'cron bitmap';  
						
			case 2: 	echotime('cron sitemap');
						swSitemap();  
						return 'cron sitemap';  

			case 3: 
			case 4: 	echotime('cron bloom'); 
						swIndexBloom(50);
						return 'cron bloom';  
			
			case 5:
			case 6: 	echotime('cron monogram'); 
						swIndexMonogram(50);
						return 'cron monogram';  
							
			case 7: 	echotime('cron metrics'); 
			  		  	if (!defined('CRON')) define('CRON',true); // defining CRON modifies the behaviour of logs.php
			  		  	include_once $swRoot.'/inc/special/metrics.php';
			  		  	return "cron metrics"; 
			  		  	break; 
			  		  	
			  		  
			default:  	echotime( 'cron overtime');
						swRelationFilterOvertimeRetake();
						echotime('cron end');
		}
	}
	
	
}


/**
 * Works on recent relation filters that could not been completed because of overtime.
 */


function swRelationFilterOvertimeRetake()
{
	global $swRoot;
	global $swMaxSearchTime;
	
	$files = glob($swRoot.'/site/queries/*.db');
	
	if (count($files)>0)
	{
	
		// builds an array to choose the most recent files 
		$files = array_combine(array_map('filemtime', $files),$files);
		arsort($files);
		
		$i = 0;
		
		foreach($files as $mtime=>$file)
		{
			$i++;
			
			if ($i>250) return; // only checks the 250 most recent
			
			$bdb = new swDba($file,'wdt');	; // force write. don't open if used and therefore blocked
			if (!$bdb) continue;
			
			$s = $bdb->fetch('_overtime');
			$overtime = false;					
			if ($s == 'b:1;' ) $overtime  = true; // unserialize does not work on a simple boolean, we hack	
			
			if (!$overtime)
			{ 
				$bdb->close();
				continue;
			}
			
			$filter = $bdb->fetch('_filter');
			$bdb->close(); 
			if (!$filter) continue;
			
			echotime( 'cron overtime filter '.$filter );
			
			if (!defined('CRON')) $swMaxSearchTime = 1000; // limit for normal uses with index.phps
			
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





?>