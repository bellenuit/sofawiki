<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Indexes";
echotime('special:indexes');


if (!isset($_REQUEST['index'])) $_REQUEST['index'] = '';

if ($_REQUEST['index'] == 'indextrigram') {$t0 = GetLastTrigram(); swIndexTrigram(300);}
if ($_REQUEST['index'] == 'rebuildindex') {$l0 = $db->lastrevision;   $db->init(true);}

$swParsedContent = '
<p><a href="index.php?name=special:indexes&index=currentbitmap">currentbitmap</a>
<br><a href="index.php?name=special:indexes&index=deletedbitmap">deletedbitmap</a>
<br><a href="index.php?name=special:indexes&index=protectedbitmap">protectedbitmap</a>
<br><a href="index.php?name=special:indexes&index=urls">urls</a>
<br><a href="index.php?name=special:indexes&index=trigram">trigram</a>
<br><a href="index.php?name=special:indexes&index=queries">queries</a>
<br>GetLastRevisionFolderItem = '.$db->GetLastRevisionFolderItem().'
<br>lastindex = '.$db->lastrevision .'
<br>lasttrigram = '.GetLastTrigram().'
<br><a href="index.php?name=special:indexes&index=rebuildindex">Rebuild Index</a>
<br><a href="index.php?name=special:indexes&index=indextrigram">Index Trigram</a>';

$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:indexes'>";
$swParsedContent .= "\n<input type='submit' name='submitreset' value='Reset All Indexes' style='color:red'/>";
$swParsedContent .= "\n</p></form>";

if (isset($_REQUEST['submitreset']) && $_REQUEST['submitreset'])
{
	// delete all current
	$files = glob($swRoot.'/site/current/*.txt');
	foreach($files as $file) { unlink($file); }
	
	$swParsedContent = '<p>Deleted current';
		
	// delete all queries
	$files = glob($swRoot.'/site/queries/*.txt');
	foreach($files as $file) { unlink($file); }
	
	$swParsedContent .= '<p>Deleted queries';
	
	// delete all trigram
	$files = glob($swRoot.'/site/trigram/*.txt');
	foreach($files as $file) { unlink($file); }
	
	$swParsedContent .= '<p>Deleted trigram';
	
	// delete index folder
	$files = glob($swRoot.'/site/indexes/*.txt');
	foreach($files as $file) { unlink($file); }
	
	$db->lastrevision = 0;
	$db->currentbitmap = new swBitmap;
	$db->deletedbitmap = new swBitmap;
	$db->protectedbitam = new swBitmap;
	
	$swParsedContent .= '<p>Deleted indexes';
	
$swParsedContent .= "\n<form method='get' action='index.php'><p><pre>";
$swParsedContent .= "\n</pre><input type='hidden' name='name' value='special:indexes'>";
$swParsedContent .= "\n<input type='submit' name='submit' value='Reopen' style='color:red'/>";
$swParsedContent .= "\n</p></form>";

echo $swParsedContent;
exit;
	

}



switch($_REQUEST['index'])
{
	case 'currentbitmap': $swParsedContent .= '<h3>currrentbitmap</h3>';
						  $bm = $db->currentbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm);
						  break;
	case 'deletedbitmap': $swParsedContent .= '<h3>deletedbitmap</h3>';
						  $bm = $db->deletedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm);
						  break;
	case 'protectedbitmap': $swParsedContent .= '<h3>protectedbitmap</h3>';
						  $bm = $db->protectedbitmap;
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm);
						  break;

	case 'urls': $swParsedContent .= '<h3>urls</h3>';
	
						 $path = $swRoot.'/site/indexes/urls.txt';
						 $lines = file($path,FILE_IGNORE_NEW_LINES || FILE_SKIP_EMPTY_LINES);
						 $bm = new swBitmap;
						 foreach($lines as $line)
						 {
						 	$fields = explode("\t",$line);
							$r = $fields[0];
						 	$bm->setbit($r);
						 }
	
							
						  $swParsedContent .= '<p>length: '.$bm->length;
						  $swParsedContent .= '<br>countbits: '.$bm->countbits();
						  $swParsedContent .= '<p>'.bitmap2canvas($bm);
						  break;


	
	case 'trigram':			$swParsedContent .= '<h3>trigram</h3><p>';
							
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
								$list = trigramlist();
								foreach($list as $k=>$v)
								{
							 		$swParsedContent .= '<a href="index.php?name=special:indexes&index=trigram&t='.$v.'">'.$v.'</a> ';
								}
							}
							break;
							
	case 'queries':			$swParsedContent .= '<h3>queries</h3><p>';
							
							if (isset($_REQUEST['q']))
							{
								$s = file_get_contents($swRoot.'/site/queries/'.$_REQUEST['q'].'.txt');
								$results = unserialize($s);
								
								//$swParsedContent .= $s;
								//$swParsedContent .= $_REQUEST['q'];
								//$swParsedContent .= print_r($results,true);
								$swParsedContent .= '<p>filter: '.$results['filter'];
								$swParsedContent .= '<br>namespace: '.$results['namespace'];
								$swParsedContent .= '<br>lastfoundrevision: '.$results['lastfoundrevision'];
								$swParsedContent .= '<br>goodrevisions: '.count($results['goodrevisions']);
								$bm = $results['bitmap'];
						 	    $swParsedContent .= '<br>length: '.$bm->length;
						        $swParsedContent .= '<br>countbits: '.$bm->countbits();
								$swParsedContent .= '<br>'.bitmap2canvas($bm);
							}
							else
							{
								$list = querylist();
								$i = 0;
								foreach($list as $k=>$v)
								{
							 		$swParsedContent .= '<p><a href="index.php?name=special:indexes&index=queries&q='.$v.'">'.$v.'.txt</a> ';
							 		//biggest 25 and last 
							 		$t = filemtime($swRoot.'/site/queries/'.$v.'.txt');
							 		$d = date('Y-m-d',$t);
							 		if ($i<25 || time() - $t < 60*60*24*7)
							 		{
											$s = file_get_contents($swRoot.'/site/queries/'.$v.'.txt');
											$results = unserialize($s);
											$swParsedContent .= ' ' .$d.'<br>'.$results['namespace']. ' '.$results['filter'];
							 		}
							 		$i++;
							 		
								}
							}
							break;
						

	case "indextrigram": $swParsedContent .= '<h3>Index Trigram</h3><p>'.sprintf('%0d',GetLastTrigram() - $t0 ). ' revisions'; break;
	case "rebuildindex": $swParsedContent .= '<h3>Index Rebuild Index</h3><p>'.sprintf('%0d', $db->lastrevision-$l0).' revisions'; break;
}

function querylist()
{
global $swRoot;
	 $files = glob($swRoot.'/site/queries/*.txt');
	  
	 $list = array();
	 foreach($files as $file)
	 {
	   $key = sprintf('%05d',filesize($file));
	   	$fn = str_replace($swRoot.'/site/queries/','',$file);
	   	$fn = substr($fn,0,-4);
	   	$list[$key.' '.$fn] = $fn;
	 }
	 krsort($list);
	 return $list;
}
function bitmap2canvas($bm)
{
	$h = ceil($bm->length /512);
	$result = '<canvas id="myCanvas" width="512" height="'.$h.'"></canvas>
	<script type="text/javascript">
	var canvas=document.getElementById("myCanvas");
	var ctx=canvas.getContext("2d");
	ctx.fillStyle="#dddddd";
	ctx.fillRect(0,0,512,'.$h.');
	ctx.fillStyle="#000000";';
	
	$l = $bm->length;
	for($i=0;$i<$l;$i++)
	{
		if ($bm->getbit($i))
		{
			$r = floor($i / 512);
			$c = $i % 512;
			$result .= 'ctx.fillRect('.$c.','.$r.',1,1);';
		}
	}
	
	$result .= '</script>';
	
	return $result;
	
}

$swParseSpecial = false;


// print_r($_ENV);

?>