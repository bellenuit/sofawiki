<?php

if (!defined("SOFAWIKI")) die("invalid acces");



$swParsedName = "Special:Indexes";

if (!isset($_REQUEST['index'])) $_REQUEST['index'] = '';
$l0 = '';


if ($_REQUEST['index'] == 'indexbloom') {$l0 = swIndexBloom(1000, true); $_REQUEST['index'] = 'bloom';}
if ($_REQUEST['index'] == 'rebuildindex') {$l0 = $db->indexedbitmap->countbits(); $db->init(true); /*$db->RebuildIndexes($l0);*/}





$swParsedContent = '
<p><a href="index.php?name=special:indexes&index=indexedbitmap">indexedbitmap</a>
<a href="index.php?name=special:indexes&index=currentbitmap">currentbitmap</a>
<a href="index.php?name=special:indexes&index=deletedbitmap">deletedbitmap</a>
<a href="index.php?name=special:indexes&index=protectedbitmap">protectedbitmap</a>
<a href="index.php?name=special:indexes&index=urls">urls</a>
';
if (isset($swRamdiskPath) && $swRamdiskPath=='db')
$swParsedContent .= ' <a href="index.php?name=special:indexes&index=db">db</a>';
$swParsedContent .=  ' <a href="index.php?name=special:indexes&index=bloom">bloom</a>
<a href="index.php?name=special:indexes&index=queries">queries</a>
<br>GetLastRevisionFolderItem = '.$db->GetLastRevisionFolderItem().'
<br>lastindex = '.$db->lastrevision .' ('.$db->indexedbitmap->countbits().')
<br>current = '. $db->currentbitmap->countbits().'
<br>bloom = '. $db->bloombitmap->countbits().'
<br><a href="index.php?name=special:indexes&index=rebuildindex">Rebuild Index</a>';
if (isset($swRamdiskPath) && $swRamdiskPath=='db')
$swParsedContent .= ' <a href="index.php?name=special:indexes&index=indexdb">Index DB</a>';
$swParsedContent .=  ' <a href="index.php?name=special:indexes&index=indexbloom">Index Bloom</a> 
<a href="index.php?name=special:indexes&index=indexnames">Index Names</a> 
<a href="index.php?name=special:indexes&index=docron">Do Cron</a>';

$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:indexes'>";
$swParsedContent .= "\n<input type='submit' name='submitresetbitmaps' value='Reset Bitmaps' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitreseturls' value='Reset URLs' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresetbloom' value='Reset Bloom' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresetcurrent' value='Reset Current' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresetqueries' value='Reset Queries' style='color:red'/>";

$swParsedContent .= "\n<input type='submit' name='submitreset' value='Reset ALL' style='color:red'/>";

$swParsedContent .= "\n</p></form>";
$swParsedContent .= "\n<p><i>To reliabily reset indexes: Reset All, Rebuild Indexes, Index DB, Index Bloom, Index Names, Reset Bitmaps, Rebuild Index</i>";


$done = '';
	if (isset($_REQUEST['submitreset']) && $swRamdiskPath != '')
	{
		// delete all in ramdisk
		$files = glob($swRamdiskPath.'*.txt');
		if (is_array($files))
			foreach($files as $file) { unlink($file); }
		$done .= '<p>Deleted ramdisk';
		swUnlink($swRoot.'/site/indexes/records.db');
		$swOvertime = true;
		
	}


	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitresetcurrent']))
	{
		// delete all current
		$path =$swRoot.'/site/current';
		$dir = opendir($path);
		$i=0;
		while($file = readdir($dir))
    	{
			if($file != '..' && $file != '.')
			{
				swUnlink($swRoot.'/site/current/'.$file);
				$i++;
			}
		}
		//swUnlink($swRoot.'/site/indexes/shortbitmap.txt');
		//swUnlink($swRoot.'/site/indexes/short.txt');
		$done .= '<p>Deleted current '.$i; 
		
	}
		
	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitresetqueries']))
	{
		// delete all queries
		$path =$swRoot.'/site/queries/';
		$dir = opendir($path);
		while($file = readdir($dir))
    	{
			if($file != '..' && $file != '.')
			{
				swUnlink($swRoot.'/site/queries/'.$file);
			}
		}
		$done .= '<p>Deleted queries';
				
	}
	
	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitresetbloom']))
	{
	 	
	 	swUnlink($swRoot.'/site/indexes/bloom.raw');
	 	swUnlink($swRoot.'/site/indexes/bloombitmap.txt');
		
		swClearBloom();	
		
		swUnlink($swRoot.'/site/indexes/bloom.raw');
	 	swUnlink($swRoot.'/site/indexes/bloombitmap.txt');
	
		$done .= '<p>Deleted bloom';
	}
	
	
	if (isset($_REQUEST['submitresetbitmaps']))
	{

		// delete index folder
		swUnlink($swRoot.'/site/indexes/indexedbitmap.txt');
		swUnlink($swRoot.'/site/indexes/currentbitmap.txt');
		swUnlink($swRoot.'/site/indexes/deletedbitmap.txt');
		swUnlink($swRoot.'/site/indexes/protectedbitmap.txt');

		$db->indexedbitmap = new swBitmap;
		$db->currentbitmap = new swBitmap;
		$db->deletedbitmap = new swBitmap;
		$db->protectedbitmap = new swBitmap;

	
		$done .= '<p>Deleted bitmaps';


	}

	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitreseturls']))
	{

		// delete index folder
		swUnlink($swRoot.'/site/indexes/indexedbitmap.txt');
		swUnlink($swRoot.'/site/indexes/currentbitmap.txt');
		swUnlink($swRoot.'/site/indexes/deletedbitmap.txt');
		swUnlink($swRoot.'/site/indexes/protectedbitmap.txt');

		$db->indexedbitmap = new swBitmap;
		$db->currentbitmap = new swBitmap;
		$db->deletedbitmap = new swBitmap;
		$db->protectedbitmap = new swBitmap;
		swUnlink($swRoot.'/site/indexes/urls.txt');
		//swUnlink($swRoot.'/site/indexes/short.txt');
		//swUnlink($swRoot.'/site/indexes/shortbitmap.txt');
		
		swUnlink($swRoot.'/site/indexes/urls.db');
	
		//$done .= '<p>Deleted urls, short and bitmaps';
		$done .= '<p>Deleted urls and bitmaps';
	}


if (isset($_REQUEST['submitreset']) || isset($_REQUEST['submitresetcurrent']) || isset($_REQUEST['submitresetqueries']) 
|| isset($_REQUEST['submitresetbitmaps']) || isset($_REQUEST['submitreseturls']) || isset($_REQUEST['submitresetbloom']) )
	{	
		$swIndexError = true;
		$swParsedContent = $done;
		$swParsedContent .= "\n<form method='get' action='index.php?'><p><pre>";
		$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:indexes'>";
		$swParsedContent .= "\n</pre><input type='hidden' name='action' value='rebuildindex'>";
		$swParsedContent .= "\n<input type='submit' name='submit' value='Reopen' style='color:red'/>";
		$swParsedContent .= "\n</p></form>";
		echo $swParsedContent;
		exit;
	}



switch($_REQUEST['index'])
{
	
	
	case 'indexedbitmap': $swParsedContent .= '<h3>indexedbitmap</h3>';
						  $bm = $db->indexedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  $missing = $bm->notop();
						  $swParsedContent .= '<p>Missing<p>'.join(' ',$missing->toarray());
						  
						  break;
	case 'currentbitmap': $swParsedContent .= '<h3>currrentbitmap</h3>';
						  $bm = $db->currentbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  $swParsedContent .= '<p>'.join(' ',$bm->toarray());
						  break;
	case 'deletedbitmap': $swParsedContent .= '<h3>deletedbitmap</h3>';
						  $bm = $db->deletedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  $swParsedContent .= '<p>'.join(' ',$bm->toarray());
						  break;
	case 'protectedbitmap': $swParsedContent .= '<h3>protectedbitmap</h3>';
						  $bm = $db->protectedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  $swParsedContent .= '<p>'.join(' ',$bm->toarray());
						  break;

	case 'urls': 		$swParsedContent .= '<h3>urls</h3>';
	
						$swParsedContent .= '<p>'.swDBA_count($db->urldb).' urls';

						

	
						  break;

	/*
		case 'short': 		$swParsedContent .= '<h3>short</h3>';
	
						  $bm = $db->shortbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  break;

							break;*/
	case 'indexdb' :  
							swIndexRamDiskDB();	
							
								
							// no break;	
											

	case 'db': 				$swParsedContent .= '<h3>DB</h3>';
							
						 // swInitRamdisk();
						  //swDBA_close($swRamDiskDB);
						  //$swRamDiskDB = swDBA_open($swRamDiskDBpath, 'rdt', 'db4');
						 // $swRamDiskDB->readIndex();
							// echotime(print_r($swRamDiskDB,true));
						  if (isset($swRamDiskDB) && $swRamDiskDB)
						  {
							  
							  $counter = 0;
							  $bm = new swBitmap($db->lastrevision);
							  $k = swDBA_firstkey($swRamDiskDB);
							  if ($k === FALSE)
							  	echotime($k);
							  else
							  	$counter = 1;
							  	
							  do
							  {
								  $k2 = substr($k,0,-4);
								  $bm->setbit(intval($k2));
								  $counter++;
								  
								  $k = swDBA_nextkey($swRamDiskDB);
								  
								 // echotime('k' .$k);
								  
							  } while ($k !== FALSE);

							  $swParsedContent .= '<p>length: '.$counter;
							  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
							  $swParsedContent .= '<p>'.join(' ',$bm->toarray());
							  
							 
							  
						  }
						  else
						  	$swParsedContent .= '<p>not available';
						  break;
	


	case 'bloom':			 

								
								
								
								$swParsedContent .= '<h3>bloombitmap</h3>';
								
								swOpenBloom();

						  		$bm = $db->bloombitmap;
						  		$swParsedContent .= '<p>length: '.$bm->length;
						 		$swParsedContent .= '<br>countbits: '.$bm->countbits();
						  		$swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						 		$swParsedContent .= '<p>';
						 		
						 		if ($l0)
						 		$swParsedContent .= '<p>indexed: '.$l0;
						 		
						 		$swParsedContent .= "<form method='get' action='index.php'><p>";
								$swParsedContent .= "<input type='hidden' name='name' value='special:indexes'>";
								$swParsedContent .= "</pre><input type='hidden' name='index' value='bloom'>";
								$swParsedContent .= "</pre><input type='text' name='term' value='".@$_REQUEST['term']."'>";
								$swParsedContent .= "<input type='submit' name='submitterm' value='Test Term' />";
								$swParsedContent .= "</form>";
						 		
						 		if (isset($_REQUEST['term']))
						 		{
						 			$swParsedContent .= '<p>Possible current revisions for '.$_REQUEST['term'].':<br>';
						 			
						 			$bm2 = swGetBloomBitmapFromTerm($_REQUEST['term']);
						 			$c0 = $bm2->countbits();
						 			$n = $db->currentbitmap->countbits();
						 			
						 			
						 			$bm2 = $bm2->andop($db->currentbitmap);
						 			//$bm2 = $bm2->andop($test);
						 			
						 			
						 			$c = $bm2->countbits();
						 		
									$swParsedContent .= '<p>'.$c .' / '.$n.' ' .sprintf("%0d", $c/$n*100).'%<p>'.bitmap2canvas($bm2,0,2);
									
									$swParsedContent .= '<p>Term : '.join(' ',swGetHashesFromTerm($_REQUEST['term']));
									
									
									// if ($c0 < 2000)
									$arr = $bm->toarray();
									
									$swParsedContent .=  "<p>Revisions:<br>".join(' ',$arr); 
																	
									
									
						 		}
						 		//$swParsedContent .= '<p>'.swGetBloomDensity();
							break;


	case "docron": 		   $swParsedContent .= '<h3>Cron</h3><p>'.swCron() ; break;
	
							
	
	case 'queries':			$swParsedContent .= '<h3>queries</h3><p>';
	
							$querypath = $swRoot.'/site/queries/';
						
							if (isset($_REQUEST['q']) && isset($_REQUEST['reset']) )
							{
								$path = $_REQUEST['q'];
								if (!stristr($path,'.db'))
									$path = $path.'.txt';
								swUnlink($querypath.$path);
								$swParsedContent .= 'reset '.$path;
								
								break;
								
							}
							if (isset($_REQUEST['q']) && isset($_REQUEST['check']) )
							{
								$path = $querypath.$_REQUEST['q'];
								$results = array();
								if ($bdb = swDBA_open($path, 'wdt', 'db4'))
								{
									$filter = swDBA_fetch('_filter',$bdb);
									swRelationFilter($filter);
								}
								
								
							}
							
							if (isset($_REQUEST['q']))
							{
								if (stristr($_REQUEST['q'],'.db'))
								{
									$path = $querypath.$_REQUEST['q'];
									$results = array();
									if ($bdb = swDBA_open($path, 'wdt', 'db4'))
									{
										$results['filter'] = swDBA_fetch('_filter',$bdb);
										$results['bitmapcount'] = swDBA_fetch('_bitmapcount',$bdb);
										$results['checkedbitmapcount'] = swDBA_fetch('_checkedbitmapcount',$bdb);
										$results['overtime'] = unserialize(swDBA_fetch('_overtime',$bdb));
										$results['mode'] = 'relation';
										$results['namespace'] = '-';
										$results['bitmap'] = unserialize(swDBA_fetch('_bitmap',$bdb));
										$results['checkedbitmap'] = unserialize(swDBA_fetch('_checkedbitmap',$bdb));
										
									}
								}
								
								else
								{
									$path = $querypath.$_REQUEST['q'];
									if (substr($path,-4)!='.txt') $path.= '.txt';
								if ($handle = fopen($path, 'r'))
								{
									$results['goodrevisions'] = array();
									while ($arr = swReadField($handle))
									{
										if (count($arr) == 0) break;
										if (@$arr['_primary'] == '_header')
										{
											$results['filter'] = @$arr['filter'];
											$results['mode'] = @$arr['mode'];
											$results['chunks'] = unserialize(@$arr['chunks']);
											$results['namespace'] = @$arr['namespace'];
											$results['overtime'] = @$arr['overtime'];
											$results['bitmap'] = unserialize(@$arr['bitmap']);
											$results['checkedbitmap'] = unserialize(@$arr['checkedbitmap']);
										}
										else
										{
											$primary = @$arr['_primary'];
											unset($arr['_primary']);
											$kr = substr($primary,0,strpos($primary,'-'));
											$results['goodrevisions'][$kr][$primary] = $arr;
										}
									}
								}
								
								
								}
								
								$swParsedContent .= '<p>filter: '.@$results['filter'];
								$swParsedContent .= '<br>mode: '.@$results['mode'];
								$swParsedContent .= '<br>namespace: '.@$results['namespace'];
								$bm = @$results['bitmap'];
								$bm2 = @$results['checkedbitmap'];
						        $swParsedContent .= '<br>overtime: '.@$results['overtime'];
						        $t = filemtime($path);
							 	$d = date('Y-m-d',$t);
						        $swParsedContent .= '<br>modification: '.$d;
						        if ($bm)
								$swParsedContent .= '<p>Good: '.$bm->countbits().'/'.$bm->length.' <br>'.bitmap2canvas($bm, false);
								if ($bm2)
								$swParsedContent .= '<p>Checked: '.$bm2->countbits().'/'.$bm2->length.'<br>'.bitmap2canvas($bm2, false,2);
								
								if (substr($_REQUEST['q'],-3) !== '.db')
									$swParsedContent .= '<p><a href="index.php?name=special:indexes&index=queries&q='.$_REQUEST['q'].'&reset=1">reset '.$_REQUEST['q'].'</a> ';
								else
									$swParsedContent .= '<p><a href="index.php?name=special:indexes&index=queries&q='.$_REQUEST['q'].'&reset=1">reset '.$_REQUEST['q'].'</a> ';
									
								if (substr($_REQUEST['q'],-3) !== '.db')
								{}
								else
									$swParsedContent .= '<p><a href="index.php?name=special:indexes&index=queries&q='.$_REQUEST['q'].'&check=1">check '.$_REQUEST['q'].'</a> ';


								
								if (stristr($_REQUEST['q'],'.db'))
								{
									if ($bm && $bm->countbits())
									{
									
										$key = swDBA_firstkey($bdb);
										$first = true;
										$lines = array();
										while($key)
										{
											if (substr($key,0,1)=='_') { $key = swDBA_nextkey($bdb); continue;}
											
											$fields = @unserialize(swDBA_fetch($key,$bdb));
											
											if ($first && is_array($fields))
											{
												$keys = array_keys($fields);
												$swParsedContent .= '<p>relation '.join(', ',$keys).'<br>data';
												$first = false;
											}
											
											
											$values = unserialize(swDBA_fetch($key,$bdb));
											if (is_array($values))
												$fields = array_values($values);
											
											$fields2 = array();
											if (is_array($fields))
											{
												foreach($fields as $f)
													$fields2[] = swUnescape($f);
											}
											
											
											$lines[] = '<br>"'.join('", "',$fields2).'"';
													
											$key = swDBA_nextkey($bdb);
										}
										//echo (join('',$lines));
										
										$swParsedContent .= join('',$lines);
										$swParsedContent .= '<br>end data';
										
										//echo 'end data '.$swParsedContent;
									}

									
								}
								else
								{
								
								$swParsedContent .='<p><pre>'.file_get_contents($path);
								
								
								foreach($results['chunks'] as $chunk)
								{
									$chunkpath = substr($path,0,-4).'-'.$chunk.'.txt';
									$swParsedContent .= PHP_EOL.PHP_EOL.'chunk '.$chunk.PHP_EOL.print_r(unserialize(file_get_contents($chunkpath)),true);
								}
								$swParsedContent .='</pre>';
								
								if (isset($_REQUEST['debug']))	
								{
									$swParsedContent .= print_r($results['goodrevisions'],true);
								}
								
								}
							}
							else
							{
								$list = querylist();
								
								$swParsedContent .= 'count = '.count($list);
								// $swParsedContent .= print_r($list, true);
								
								$i = 0;
								$lines = array();
								foreach($list as $k=>$v)
								{
							 		//biggest 25 and last 
							 		
							 		$t = filemtime($querypath.$v);
							 		$filesize = floor(filesize($querypath.$v)/1024);
							 		$d = date('Y-m-d H:i',$t);
							 		if ($i<25 || time() - $t < 60*60)
							 		{
							 				
											$results = array();
											if (substr($v,-4)=='.txt' && $handle = fopen($querypath.$v, 'r'))
											{
												
												while ($arr = swReadField($handle))
												{
													if (count($arr) == 0) break;
													if (@$arr['_primary'] == '_header')
													{
														$results['filter'] = @$arr['filter'];
														$results['chunks'] = unserialize(@$arr['chunks']);
														$results['mode'] = @$arr['mode'];
														$results['namespace'] = @$arr['namespace'];
													}
													
												}
												
												$lines[$d] = '<p><a href="index.php?name=special:indexes&index=queries&q='.$v.'">'.$v.'</a><br>' .$d.' '.@count(@$results['chunks']).' chunks '.@$results['mode'].' '.@$results['namespace']. '<br>'.@$results['filter'];
											}
											else
											{
												$bdb = swDBA_open($querypath.$v,'r','db4');
												if ($bdb)
													$results['filter'] = swDBA_fetch('_filter',$bdb);
												
												$lines[$d] = '<p><a href="index.php?name=special:indexes&index=queries&q='.$v.'">'.$v.'</a><br>' .$d.' '.$filesize.' kB <br>filter '.@$results['filter'];
												
											}
											
											
											
											
							 		}
							 		$i++;
							 		
								}
								
								krsort($lines);
								$swParsedContent .= join('',$lines);
								
								
							}
							
							
							break;
						

	case "indexnames": $result = swRelationToTable('filter _name, _revision'); 
						$swParsedContent .= '<h3>Index Names</h3><p>'.sprintf('%0d',count($result) ). ' names'; break;
	
	case "rebuildindex": $swParsedContent .= '<h3>Index Rebuild Index</h3><p>'.sprintf('%0d',$db->indexedbitmap->countbits()-$l0).' revisions'; break;
	
	
}

function querylist()
{
	 global $swRoot;
	 
	 $querypath = $swRoot.'/site/queries/';
	 
	 $files = glob($querypath.'*.txt');
	  
	 $list = array();
	 foreach($files as $file)
	 {
	  	$key = sprintf('%05d',filesize($file));
	   	$fn = str_replace($querypath,'',$file);
		if (stristr($fn,'-')) continue;
	   	// $fn = substr($fn,0,-4);
	   	$list[$key.' '.$fn] = $fn;
	 }
	 
	 $files = glob($querypath.'*.db');
	  
	 foreach($files as $file)
	 {
	  	$key = sprintf('%05d',filesize($file));
	   	$fn = str_replace($querypath,'',$file);
		if (stristr($fn,'-')) continue;
	   	// $fn = substr($fn,0,-4);
	   	$list[$key.' '.$fn] = $fn;
	 }
	 krsort($list);
	 return $list;
}
function bitmap2canvas($bm,$listrevisions=1,$id='1')
{
	$h = ceil($bm->length /512);
	$result = '<canvas id="myCanvas'.$id.'" width="512" height="'.$h.'"></canvas>
	<script type="text/javascript">
	var canvas=document.getElementById("myCanvas'.$id.'");
	var ctx=canvas.getContext("2d");
	ctx.fillStyle="#dddddd";
	ctx.fillRect(0,0,512,'.$h.');
	ctx.fillStyle="#000000";';
	
	$l = $bm->length;
	$list = '';
	$listnot = '';
	$listsimple = '';
	for($i=0;$i<$l;$i++)
	{
		if ($bm->getbit($i))
		{
			$r = floor($i / 512);
			$c = $i % 512;
			$result .= 'ctx.fillRect('.$c.','.$r.',1,1);';
			$list .= '<a href="index.php?action=edit&revision='.$i.'" target="_blank">'.$i.'</a> ';
			$listsimple .= $i.' ';
		}
		else
		{
			$listnot .= '<a href="index.php?action=edit&revision='.$i.'" target="_blank">'.$i.'</a> ';
		}
	}
	
	$result .= '</script>';
	$listnot = '<i>'.$listnot.'</i>';
	
	if ($listrevisions==2)
		return $result.'<p>'.$listnot;
	elseif ($listrevisions==3)
		return $result.'<p>'.$listsimple;
	elseif ($listrevisions)
		return $result.'<p>'.$list;
	else
		return $result;
	
}



$swParseSpecial = false;

if ($swIndexError) include 'inc/special/indexerror.php';


// print_r($_ENV);

?>