<?php
	
/**
 *	Handles the swFulltext index  
 */


if (!defined("SOFAWIKI")) die("invalid acces");

 

 
function swOpenFulltext()
{
	global $swUseFulltext; // can only be used if sqlite3 is present and fts3 enabled
	if (!isset($swUseFulltext) || ! $swUseFulltext) return;
	
	global $swRoot;
	$path = $swRoot.'/site/indexes/fulltext.db';
	
	global $swFulltextIndex;
	if ($swFulltextIndex) return;
	
	try
	{
		$swFulltextIndex= new SQLite3($path);
	}
	catch (Exception $err)
	{
		echo 'swFulltextIndex open errror '.$err->getMessage().' '.$path; return;
	}
	
	if (!$swFulltextIndex)
	{
			throw new swDbaError('swFulltextIndex construct db not exist '.$swFulltextIndex->lastErrorMsg().' path'.$path);
	}
		
	if (!$swFulltextIndex->busyTimeout(5000))  // sql tries to connect during 5000ms
	{
		throw new swDbaError('swFulltextIndex is busy');
	}
	
	if (!$swFulltextIndex->exec('CREATE VIRTUAL TABLE IF NOT EXISTS pages USING fts3(url, lang, revision, title, body)'))
	{
		throw new swDbaError('swFulltextIndex create table error '.$swFulltextIndex->lastErrorMsg());
	}
	
	if (!$swFulltextIndex->exec('CREATE TABLE IF NOT EXISTS snippets (revision, query, snippet); CREATE INDEX IF NOT EXISTS snippetindex ON snippets (query);'))
	{
		throw new swDbaError('swFulltextIndex create table snippet error '.$swFulltextIndex->lastErrorMsg());
	}

	
	$swFulltextIndex->createFunction('score', 'swFulltextScore');	
	$swFulltextIndex->createFunction('byteoffsets', 'swFulltextByteOffsets');
}

function swClearFulltext()
{
	global $swRoot;
	$path = $swRoot.'/site/indexes/fulltext.db';
	unlink($path);
}

function swIndexFulltext($url,$lang,$revision,$name,$html)
{
	swOpenFulltext();
	global $swFulltextIndex;
	if (!$swFulltextIndex) return;
	global $action;
	if ($action != 'view') return;
	global $swSearchNamespaces;
	// use only search domain
	$ns = '';
	if ($p = strpos($url,':')) $ns = substr($url,0,$p);
	$found = true;
	if ($ns) $found = false; 
	foreach($swSearchNamespaces as $sp)
	{
		if (strtolower($ns) == strtolower($sp)) $found = true;
	}
	if (!$found) return;
	if (strpos($html,'<!-- nofulltext -->')) return;
	if (!$revision) return;
	
	// check if revision is already present
	global $db;
	if ($db->fulltextbitmap->getbit($revision)) return;
	/*
	$q = "SELECT revision FROM pages WHERE revision = $revision";
	$result = $swFulltextIndex->querySingle($q);
	if ($result) return;
	*/
	
	// clean HTML
	$hasfulltext = false;
	$html = preg_replace('/<script.*?\>.*?<\/script>/si', ' ', $html); 
    $html = preg_replace('/<style.*?\>.*?<\/style>/si', ' ', $html); 
    $html = preg_replace('/<.*?\>/si', ' ', $html); 
    $html = preg_replace('!\s+!', ' ', $html);
    $html = htmlentities($html,ENT_SUBSTITUTE|ENT_HTML5,'UTF-8',FALSE);  // now we are single byte
    $html = swTrigramize($html);
    $html = $swFulltextIndex->escapeString($html);
    if (substr($name,-3,1)=='/') $name = substr($name,0,-3);
    $name = swTrigramize($name);
    $name = $swFulltextIndex->escapeString($name);
    if (substr($url,-3,1)=='/') $url = substr($url,0,-3);
    
    $q = "REPLACE INTO pages(url,lang,revision,title, body) VALUES('$url','$lang',$revision,'$name','$html')";
    if (!$swFulltextIndex->exec($q))
	{
		throw new swDbaError('swFulltextIndex create table error '.$swFulltextIndex->lastErrorMsg());
	}
	$db->fulltextbitmap->setbit($revision);
}

function swQueryFulltext($query, $limit=1000)
{
	echotime('queryfulltext "'.$query.'"');
	swOpenFulltext();
	global $swFulltextIndex;
	if (!$swFulltextIndex) return;
	global $db;
	$querylines = swTrigramizeQuery($query);
	$query =  join(' ',$querylines);
	
	$r = new swRelation('score, url, lang, revision, title, body');
	
	if (!trim($query)) return $r; // empty
	
	$q = "SELECT score(offsets(pages)) as score, url, lang, revision, title, body, byteoffsets(offsets(pages)) as byteoffsets FROM pages 
  WHERE pages MATCH '$query'
  ORDER BY score DESC
  LIMIT $limit";
	
	//echo '<p>'.$q;
	$result = $swFulltextIndex->query($q);
	  	
 	echotime('loop');
	
	$foundnames = array();
	
	$qescape = $swFulltextIndex->escapeString($query);
	
	$journal = array();
	$journal []= 'BEGIN TRANSACTION; ';
	
	$counter = 0;
	
	global $swMaxSearchTime;
	global $swMaxOverallSearchTime;
	global $swStartTime;
	global $swOvertime;
	global $swMemoryLimit;

	
	while ($d = $result->fetchArray(SQLITE3_ASSOC)) 
	{
    	if (memory_get_usage()>$swMemoryLimit)
		{
			echotime('overmemory '.memory_get_usage());
			$overtime = true;
			$swOvertime = true;
			break;
		}
    	 
    	if (isset($foundnames[$d['url']])) continue;
    	$rev = $d['revision'];
    	
    	if ($db->currentbitmap->getbit($rev))
    	{
    		$d['title']=swDetrigramize($d['title']);
    		if (!$d['title']) $d['title'] ='...';
    		$d['body']=swFulltextSnippet($d['body'],$d['byteoffsets'],$querylines);
    		$url = $d['url'];
    		
    		$tp = new swTuple($d);
			$r->tuples[$tp->hash()] = $tp;
			$foundnames[$d['url']] = 1;
		}
		elseif ($db->indexedbitmap->getbit($rev))
		{
			$u = $d['url'];
			$journal []= "DELETE FROM pages WHERE revision = $rev; DELETE FROM snippets WHERE revision = $rev; ";
		}
	}
	
	$journal []= 'COMMIT; ';
	
	if (count($journal)>2) @$swFulltextIndex->exec(join(' ',$journal));
	
	echotime('queryfulltext end '.(count($r->tuples)));
	return $r;
}

function swQueryFulltextURL($query, $limit=500)
{
	echotime('queryfulltexturl "'.$query.'"');
	swOpenFulltext();
	global $swFulltextIndex;
	if (!$swFulltextIndex) return;
	global $db;
	$querylines = swTrigramizeQuery($query);
	$query =  join(' ',$querylines);
	
	$r = new swRelation('url');
	
	if (!trim($query)) return $r; // empty
	
	$q = "SELECT score(offsets(pages)) as score, url FROM pages 
  WHERE pages MATCH '$query'
  ORDER BY score DESC
  LIMIT $limit";
	
	//echo '<p>'.$q;
	$result = $swFulltextIndex->query($q);
	  	
 	echotime('loop');
	
	$foundnames = array();
	
	$qescape = $swFulltextIndex->escapeString($query);
	
	$journal = array();
	
	
	while ($d = $result->fetchArray(SQLITE3_ASSOC)) 
	{ 		
    	unset($d['score']);
    	$tp = new swTuple($d);
		$r->tuples[$tp->hash()] = $tp;
		
	}	
	echotime('queryfulltexturl end');
	return $r;
}

function swFulltextScore($s) {
   $offsets = explode(' ',$s);
   $r = 0;
   for ($i=0;$i<count($offsets);$i+=4)
   {
   	 $o = $offsets[$i+2];
   	 $c = $offsets[$i];
   	 if ($c == 3)
   	 	$r += 10/(1+log($o+1)); // title, avoid div/0
   	 else
   	 	$r += 1/(1+log($o+1));
   }
   $r = floor($r*1000);
   return $r;
}

function swFulltextByteOffsets($s) {
   $offsets = explode(' ',$s);
   $lines = array();
   $r = 0;
   $minoffset = -1;
   for ($i=0;$i<count($offsets);$i+=4)
   {
   	 $o = $offsets[$i+2];
   	 $l = $offsets[$i+3];
   	 $c = $offsets[$i];
   	 if ($c < 4) continue;
   	 if ($minoffset < 0) $minoffset = $o;
   	 if ($o - $minoffset > 255) break;
   	 $lines[] = $o;
   	 $lines[] = $l;
   }
   return join(' ',$lines);
}

function swTrigramize($s)
{
	$lines = array();
	$l = strlen($s);
	for($i=0;$i<$l-2;$i++)
	{
		$lines[] = substr($s,$i,3);
	}
	return join(' ',$lines);
}

function swTrigramizeQuery($s)
{
	$s = trim($s);
	$tokens = preg_split('/([ |])/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
	$lines = array();
	
	// NB: does not handle mutiple operators following (like multiple spaces)

	foreach($tokens as $t)
	{
		if (strlen($t)<3) continue;
		
		switch ($t)
		{
			case ' ': $lines[]='AND'; break;
			case '|': $lines[]='OR'; break;
			default: $lines[] = '"'.swTrigramize($t).'"';
		}
	}
	return $lines;
}

function swDetrigramize($s)
{
	if (!$s) return '';
	
	return preg_replace('/(.).{3}/','$1',$s);
	
}

function swFulltextSnippet($s, $os,$querylines)
{
	$half =400;
	$p = max(0,(intval($os)-$half));
	$s = substr($s,$p,2*$half);
	$s = swDetrigramize($s);
	
	if ($p)
	{
		$space = strpos($s,' ');
		if ($space>0)
		{
			$s = substr($s,$space+1);
		}	
	}
	if (substr($s,-1) != ' ')
	{
		$space = strrpos($s, ' ');
		if ($space>0)
		{
			$s = substr($s,0,$space);
		}
	}
	if (substr($s,-1) != '.')
	{
		$s .= '...';
	}
	return $s;
	
}
