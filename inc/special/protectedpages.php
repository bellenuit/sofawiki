<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$swParsedName = "Special:Protected Pages";

$swParsedContent = "";

// deletedbitmap && currentbitmap use url

$currentbitmap = $db->currentbitmap->duplicate();
$deleted = $db->protectedbitmap->toarray();

$urldbpath = $db->pathbase.'indexes/urls.db';
if (file_exists($urldbpath))
		$urldb = @dba_open($urldbpath, 'rdt', 'db4');
if (!@$urldb)
{
	echotime('urldb failed');
}

$listmenu = array();
$list = array();

$alpha = @$_REQUEST['alpha'];
if (!$alpha)$alpha = 'a';

$list = array();
$i = 0;
foreach($deleted as $rev)
{
	if ($currentbitmap->getbit($rev))
	{
		// $i++; if ($i>10) continue;
		if ($urldb && dba_exists(' '.$rev,$urldb))
		{
			$s = dba_fetch(' '.$rev,$urldb);
			$url = swNameURL(substr($s,2));
			if (!$url) continue;
			
			$u = substr($url,0,1);
			if ($u == $alpha)	
				$list[$url] = '<li><a href="index.php?action=history&revision='.$rev.'">'.$url.'</a></li>' ;
			
			$listmenu[$u] = '<a href="index.php?name=special:protected-pages&alpha='.$u.'">'.$u.'</a> ' ;
			if ($u == $alpha)
				$listmenu[$u] = '<a href="index.php?name=special:protected-pages&alpha='.$u.'"><b>'.$u.'</b></a> ' ;
		}
	}

}
dba_close($urldb);


ksort($list);
ksort($listmenu);

$swParsedContent .= '<p>'.join(' ',$listmenu);

$swParsedContent .= '<ul>'.join('',$list).'</ul>';


$swParseSpecial = false;


// $swParsedContent .= join(' ',$list);

// $swParsedContent .= '</ul>';


// $swParseSpecial = false;


?>