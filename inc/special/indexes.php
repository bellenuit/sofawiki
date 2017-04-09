<?php

if (!defined("SOFAWIKI")) die("invalid acces");



$swParsedName = "Special:Indexes";

if (!isset($_REQUEST['index'])) $_REQUEST['index'] = '';
$l0 = '';


if ($_REQUEST['index'] == 'indextrigram') {$t0 = $db->trigrambitmap->countbits(); swIndexTrigram(2500); }
if ($_REQUEST['index'] == 'indexbloom') {$l0 = swIndexBloom(1000); $_REQUEST['index'] = 'bloom';}
if ($_REQUEST['index'] == 'rebuildindex') {$l0 = $db->indexedbitmap->countbits(); $db->init(true); /*$db->RebuildIndexes($l0);*/}





$swParsedContent = '
<p><a href="index.php?name=special:indexes&index=indexedbitmap">indexedbitmap</a>
<a href="index.php?name=special:indexes&index=currentbitmap">currentbitmap</a>
<a href="index.php?name=special:indexes&index=deletedbitmap">deletedbitmap</a>
<a href="index.php?name=special:indexes&index=protectedbitmap">protectedbitmap</a>
<a href="index.php?name=special:indexes&index=urls">urls</a>
<a href="index.php?name=special:indexes&index=short">short</a>
<a href="index.php?name=special:indexes&index=trigram">trigram</a>
<a href="index.php?name=special:indexes&index=bloom">bloom</a>
<a href="index.php?name=special:indexes&index=queries">queries</a>
<br>GetLastRevisionFolderItem = '.$db->GetLastRevisionFolderItem().'
<br>lastindex = '.$db->lastrevision .' ('.$db->indexedbitmap->countbits().')
<br>current = '. $db->currentbitmap->countbits().'
<br>trigram = '.$db->trigrambitmap->countbits().'
<br><a href="index.php?name=special:indexes&index=rebuildindex">Rebuild Index</a>
<a href="index.php?name=special:indexes&index=indexnames">Index Names</a>
<a href="index.php?name=special:indexes&index=indextrigram">Index Trigram</a>
<a href="index.php?name=special:indexes&index=indexbloom">Index Bloom</a>
<a href="index.php?name=special:indexes&index=docron">Do Cron</a>

';

$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:indexes'>";
$swParsedContent .= "\n<input type='submit' name='submitresetcurrent' value='Reset Current' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresetqueries' value='Reset Queries' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresettrigram' value='Reset Trigram' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresetbloom' value='Reset Bloom' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitresetbitmaps' value='Reset Bitmaps' style='color:red'/>";
$swParsedContent .= "\n<input type='submit' name='submitreseturls' value='Reset URLs' style='color:red'/>";

$swParsedContent .= "\n<input type='submit' name='submitreset' value='Reset ALL' style='color:red'/>";

$swParsedContent .= "\n</p></form>";
$swParsedContent .= "\n<p><i>To reliabily reset indexes: Reset All, Rebuild Indexes, Reset Bitmaps, Index Names, Index Trigram, Reset Bitmaps</i>";


$done = '';
	if (isset($_REQUEST['submitreset']) && $swRamdiskPath != '')
	{
		// delete all in ramdisk
		$files = glob($swRamdiskPath.'*.txt');
		if (is_array($files))
			foreach($files as $file) { unlink($file); }
		$done .= '<p>Deleted ramdisk';
		
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
		swUnlink($swRoot.'/site/indexes/shortbitmap.txt');
		swUnlink($swRoot.'/site/indexes/short.txt');
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
	
	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitresettrigram']))
	{

		clearTrigrams();
		
		$file = $swRoot.'/site/indexes/trigrambitmap.txt';
		swUnlink($file); 	
	
		$done .= '<p>Deleted trigram';
	}
	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitresetbloom']))
	{

		
		swClearBloom();	
	
		$done .= '<p>Deleted bloom';
	}
	
	
	if (isset($_REQUEST['submitreset'])||isset($_REQUEST['submitresetbitmaps']))
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
		swUnlink($swRoot.'/site/indexes/short.txt');
		swUnlink($swRoot.'/site/indexes/shortbitmap.txt');
	
		$done .= '<p>Deleted urls, short and bitmaps';
	}


if (isset($_REQUEST['submitreset']) || isset($_REQUEST['submitresetcurrent']) || isset($_REQUEST['submitresetqueries']) || isset($_REQUEST['submitresettrigram'])
|| isset($_REQUEST['submitresetbitmaps']) || isset($_REQUEST['submitreseturls']) || isset($_REQUEST['submitresetbloom']) )
	{	
		$swIndexError = true;
		$swParsedContent = $done;
		$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
		$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:indexes'>";
		$swParsedContent .= "\n<input type='submit' name='submit' value='Reopen' style='color:red'/>";
		$swParsedContent .= "\n</p></form>";
		echo $swParsedContent;
		exit;
	}



switch($_REQUEST['index'])
{
	case 'fillgaps'		: $bm = $db->indexedbitmap;
						  $path = $db->pathbase.'indexes/urls.txt';
						  $fpt = fopen($path,'c');
						  
						  for ($i=0;$i<$bm->length-32;$i++)
						  {
						  	if (! $bm->getbit($i))
						  	{
						  		$bm->setbit($i);
						  		$line = str_pad($i,9,' ')."\t";
								$line .= "-\t";
								$line .= str_repeat(' ',32); // 32
								$line .= substr('    '.PHP_EOL,-4);
		
								@fseek($fpt, 48*($i-1));
								@fwrite($fpt, $line);
							}
	
						  }
						  @fclose($fpt);
	
	case 'indexedbitmap': $swParsedContent .= '<h3>indexedbitmap</h3>';
						  $bm = $db->indexedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  $swParsedContent .= '<p><a href="index.php?name=special:indexes&index=fillgaps">Fill Gaps</a> (use with caution)';
						  break;
	case 'currentbitmap': $swParsedContent .= '<h3>currrentbitmap</h3>';
						  $bm = $db->currentbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  break;
	case 'deletedbitmap': $swParsedContent .= '<h3>deletedbitmap</h3>';
						  $bm = $db->deletedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  break;
	case 'protectedbitmap': $swParsedContent .= '<h3>protectedbitmap</h3>';
						  $bm = $db->protectedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  break;

	case 'urls': 		$swParsedContent .= '<h3>urls</h3>';
	
						 $path = $swRoot.'/site/indexes/urls.txt';
						 $s = file_get_contents($path);
						 $lines = array();
						 for($i=0;$i<strlen($s);$i+=48) $lines[]=substr($s,$i,48);
						// $lines = file($path,FILE_IGNORE_NEW_LINES || FILE_SKIP_EMPTY_LINES);
						 $bm = new swBitmap;
						 foreach($lines as $line)
						 {
						 	$fields = explode("\t",$line);
							$r = $fields[0];
						 	$bm->setbit($r);
						 }
	
							
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  break;

	case 'short': 		$swParsedContent .= '<h3>short</h3>';
	
						  $bm = $db->shortbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						  break;

	
	case 'trigram':			$swParsedContent .= '<h3>trigram</h3><p>';
							$swParsedContent .= '<i>Trigram is depreciated and replaced by Bloom filter</i><p>';

							
							if (isset($_REQUEST['t']))
							{
								$bm = getTrigram($_REQUEST['t']);
								$swParsedContent .= $_REQUEST['t'];
								
						 	    $swParsedContent .= '<p>length: '.$bm->length;
						        $swParsedContent .= '<br>countbits: '.$bm->countbits();
								$swParsedContent .= '<p>'.bitmap2canvas($bm);
								
							}
							else
							{
								$swParsedContent .= '<h3>trigrambitmap</h3>';
						  		$bm = $db->trigrambitmap;
						  		$swParsedContent .= '<p>length: '.$bm->length;
						 		$swParsedContent .= '<br>countbits: '.$bm->countbits();
						  		$swParsedContent .= '<p>'.bitmap2canvas($bm,0);
						 		$swParsedContent .= '<p>';
								$list = trigramlist();
								foreach($list as $k=>$v)
								{
							 		$swParsedContent .= '<a href="index.php?name=special:indexes&index=trigram&t='.$v.'">'.$v.'</a> ';
								}
							}
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
						 			//$bm2 = $bm2->andop($db->currentbitmap);
						 			
						 			$n = $db->currentbitmap->countbits();
						 			$c = $bm2->countbits();
						 		
									$swParsedContent .= $c .' / '.$n.' ' .sprintf("%0d", $c/$n*100).'%<p>'.bitmap2canvas($bm2,0,2);

						 		}
						 		//$swParsedContent .= '<p>'.swGetBloomDensity();
							break;


	case "docron": 		   $swParsedContent .= '<h3>Cron</h3><p>'.swCron() ; break;
	
							
	
	case 'queries':			$swParsedContent .= '<h3>queries</h3><p>';
	
							$querypath = $swRoot.'/site/queries/';
						
							if (isset($_REQUEST['q']) && isset($_REQUEST['reset']) )
							{
								$path = $querypath.$_REQUEST['q'].'.txt';
								swUnlink($path);
								
								$swParsedContent .= 'reset '.$_REQUEST['q'].'.txt';
								
								break;
								
							}
							
							if (isset($_REQUEST['q']))
							{
								$path = $querypath.$_REQUEST['q'].'.txt';
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
								
								
								
								
								$swParsedContent .= '<p>filter: '.$results['filter'];
								$swParsedContent .= '<br>mode: '.$results['mode'];
								$swParsedContent .= '<br>namespace: '.$results['namespace'];
								$swParsedContent .= '<br>goodrevisions: '.count($results['goodrevisions']);
								$bm = $results['bitmap'];
								$bm2 = $results['checkedbitmap'];
						        $swParsedContent .= '<br>overtime: '.$results['overtime'];
						        $t = filemtime($path);
							 	$d = date('Y-m-d',$t);
						        $swParsedContent .= '<br>modification: '.$d;
								$swParsedContent .= '<p>Good: '.$bm->countbits().'/'.$bm->length.' <br>'.bitmap2canvas($bm, false);
								$swParsedContent .= '<p>Checked: '.$bm2->countbits().'/'.$bm2->length.'<br>'.bitmap2canvas($bm2, false,2);
								$swParsedContent .= '<p><a href="index.php?name=special:indexes&index=queries&q='.$_REQUEST['q'].'&reset=1">reset '.$_REQUEST['q'].'.txt</a> ';
								
								
								
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
							else
							{
								$list = querylist();
								$swParsedContent .= 'count = '.count($list);
								
								$i = 0;
								foreach($list as $k=>$v)
								{
							 		//biggest 25 and last 
							 		$t = filemtime($querypath.$v.'.txt');
							 		$d = date('Y-m-d',$t);
							 		if ($i<25 || time() - $t < 60*60)
							 		{
							 				$swParsedContent .= '<p><a href="index.php?name=special:indexes&index=queries&q='.$v.'">'.$v.'.txt</a> ';
											$results = array();
											if ($handle = fopen($querypath.$v.'.txt', 'r'))
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
											}
											$swParsedContent .= '<br>' .$d.' '.count($results['chunks']).' ch '.@$results['mode'].' '.@$results['namespace']. '<br>'.@$results['filter'];
							 		}
							 		$i++;
							 		
								}
								
							}
							
							
							break;
						

	case "indextrigram": $swParsedContent .= '<h3>Index Trigram</h3><p>'.sprintf('%0d',$db->trigrambitmap->countbits() - $t0 ). ' revisions';
							$swParsedContent .= '<p><i>Trigram is depreciated and replaced by Bloom filter</i><p>';break;
	case "indexnames": $result = swFilter('SELECT _revision, _name WHERE _name *','*'); 
						$swParsedContent .= '<h3>Index Names</h3><p>'.sprintf('%0d',count($result) ). ' names'; break;
	
	case "rebuildindex": $swParsedContent .= '<h3>Index Rebuild Index</h3><p>'.sprintf('%0d',$db->indexedbitmap->countbits()-$l0).' revisions'; break;
	
	case "indexfields" : 	$swParsedContent .= '<h3>Index Fields</h3>';
							$revisions = swFilter('SELECT _field','*','query');
							foreach($revisions as $row)
							{
									$fields[] =  $row['_field'];
							}
							sort($fields);
							foreach($fields as $field)
							{
								if (!$field) continue;
								if (isset($swOvertime)) continue;
								$result2 = swFilter('SELECT _revision, '.$field.' WHERE '.$field.' * ','*'); 
								$c = count($result2);
								
								$swParsedContent .= $c. ' '.$field.'<br>';
							}
							if (isset($result2)) unset($result2);
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
	   	$fn = substr($fn,0,-4);
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


// print_r($_ENV);

?>