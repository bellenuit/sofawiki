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
	
	if (!$swFulltextIndex->exec('CREATE VIRTUAL TABLE IF NOT EXISTS pages USING fts3(url, lang, revision, title, body, soundex)'))
	{
		throw new swDbaError('swFulltextIndex create table error '.$swFulltextIndex->lastErrorMsg());
	}
		
	$swFulltextIndex->createFunction('score', 'swFulltextScore');	
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
	
	// check if revision is already present and current
	global $db;
	if ($db->fulltextbitmap->getbit($revision)) return;
	if (!$db->currentbitmap->getbit($revision)) return;
	
	// clean HTML
	$hasfulltext = false;
	$html = preg_replace('/<script.*?\>.*?<\/script>/si', ' ', $html); 
    $html = preg_replace('/<style.*?\>.*?<\/style>/si', ' ', $html); 
    $html = preg_replace('/<.*?\>/si', ' ', $html); 
    $html = preg_replace('!\s+!', ' ', $html);
    $html = htmlentities($html,ENT_SUBSTITUTE|ENT_HTML5,'UTF-8',FALSE);  // now we are single byte
    $html = $swFulltextIndex->escapeString($html);
    if (substr($name,-3,1)=='/') $name = substr($name,0,-3);
    $name = htmlentities($name,ENT_SUBSTITUTE|ENT_HTML5,'UTF-8',FALSE);  // now we are single byte
    $name = $swFulltextIndex->escapeString($name);
    if (substr($url,-3,1)=='/') $url = substr($url,0,-3);
    
    $soundex = $name.' '.$html;
    $fn = new xpSoundexLong;
    $soundex = $fn->run(array($soundex)); 
    
    $q = "REPLACE INTO pages(url,lang,revision,title, body, soundex) VALUES('$url','$lang',$revision,'$name','$html','.$soundex.')";
    if (!$swFulltextIndex->exec($q))
	{
		throw new swDbaError('swFulltextIndex create table error '.$swFulltextIndex->lastErrorMsg());
	}
	$db->fulltextbitmap->setbit($revision);
	$db->touched = true;
}

function swQueryFulltext($query, $limit=1000, $star = true)
{
	$query = htmlentities($query,ENT_SUBSTITUTE|ENT_HTML5,'UTF-8',FALSE); 
	$query0 = $query;
	if ($star) $query = swQueryStar($query);
	echotime('queryfulltext "'.$query.'"');
	swOpenFulltext();
	global $swFulltextIndex;
	if (!$swFulltextIndex) return;
	global $db;
	$useSoundex = false;
	
	$r = new swRelation('found, score, url, lang, revision, title, body');
	
	// escape quote
	$query = str_replace("'","''",$query);
	
	if (!trim($query)) return $r; // empty
	
	$q = "SELECT '1' as found, score(offsets(pages)) as score, url, lang, revision, title, snippet(pages) as body FROM pages 
  WHERE pages MATCH '$query'
  ORDER BY score DESC
  LIMIT $limit";
	
	//echo '<p>'.$q;
	$result = $swFulltextIndex->query($q);
	
	if (NULL == $result->fetchArray(SQLITE3_ASSOC)) 
	{
		$useSoundex = true;
		$query = swQuerySoundex($query0);
		
		echotime('soundex '.$query );

		
		$q = "SELECT '0' as found, score(offsets(pages)) as score, url, lang, revision, title, body FROM pages 
  WHERE soundex MATCH '$query'
  ORDER BY score DESC
  LIMIT $limit";
  		$result = $swFulltextIndex->query($q);
	}
	else
	{
		$result->reset();
	}
	  	
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
    		if (!$d['title']) $d['title'] ='...';
    		
    		if ($useSoundex)
    		{
	    		$qlist = explode(' ',$query);
	    		$body = $d['body'];
	    		$a = preg_replace('/[^a-zA-Z&;0-9 ]/','',$body);
				foreach(explode(' ',$a) as $w)
				{
					if (in_array(soundex($w),$qlist))
						$body = str_replace($w,'<b>'.$w.'</b>',$body);
						
					// but not duplicate
					$body = str_replace('<b><b>'.$w.'</b></b>','<b>'.$w.'</b>',$body);
		 	    }
		 	    $p = strpos($body,'<b>');
	    		if ($p === NULL) $p = 0;
	    		$p = strpos($body,' ',$p-40);
	    		if ($p<0) $p = 0;
	    		$p2 = strpos($body,' ',min($p+100,strlen($body)));
	    		if ($p2 === NULL) $p2 = $p+64;
	    		$body = substr($body,$p,max($p2-$p,32));
	    		
	    		// check open <b>
	    		$br1 = explode('<br>',$body);
	    		$br2 = explode('</br>',$body);
	    		if (count($br1)>count($br2)) $body .= '</b>';
	    		
	    		if (strpos($body,'<b>'))
	    			$d['body'] = '...'.$body.'...';
	    		else
	    			$d['body'] = '';
    		}
    		

    		
    		$tp = new swTuple($d);
			$r->tuples[$tp->hash()] = $tp;
			$foundnames[$d['url']] = 1;
		}
		elseif ($db->indexedbitmap->getbit($rev))
		{
			$u = $d['url'];
			$journal []= "DELETE FROM pages WHERE revision = $rev; ";
		}
	}
	
	$journal []= 'COMMIT; ';
	
	if (count($journal)>2) @$swFulltextIndex->exec(join(' ',$journal));
	
	echotime('queryfulltext end '.(count($r->tuples)));
	return $r;
}

function swQueryFulltextURL($query, $limit=500, $star = true)
{
	$query = htmlentities($query,ENT_SUBSTITUTE|ENT_HTML5,'UTF-8',FALSE); 
	$query0 = $query;
	if ($star) $query = swQueryStar($query);
	echotime('queryfulltexturl "'.$query.'"');
	swOpenFulltext();
	global $swFulltextIndex;
	if (!$swFulltextIndex) return;
	global $db;
	
	$r = new swRelation('found, score, url');
	
	// escape quote
	$query = str_replace("'","''",$query);
	if (!trim($query)) return $r; // empty
	
	
	
	
	$q = "SELECT '1' as found, score(offsets(pages)) as score, url FROM pages 
  WHERE pages MATCH '$query'
  ORDER BY score DESC
  LIMIT $limit";
	
	
	
	$result = $swFulltextIndex->query($q);
	
	if (NULL == $result->fetchArray(SQLITE3_ASSOC)) 
	{
		

		$query = swQuerySoundex($query0);
		
		echotime('soundex '.$query );
		
		$q = "SELECT '0' as found, score(offsets(pages)) as score, url FROM pages 
  WHERE soundex MATCH '$query'
  ORDER BY score DESC
  LIMIT $limit";
  		$result = $swFulltextIndex->query($q);
	}
	else
	{
		$result->reset();
	}
	
	  	
 	echotime('loop');
	
	$foundnames = array();
	
	$qescape = $swFulltextIndex->escapeString($query);
	
	$journal = array();
	
	
	while ($d = $result->fetchArray(SQLITE3_ASSOC)) 
	{ 		
    	
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

function swQueryStar($q)
{
	$list = array();
	$q = str_replace('(',' ( ',$q);
	$q = str_replace(')',' ) ',$q);
	foreach(explode(' ',$q) as $w)
	{
		switch(strtolower($w))
		{
			case 'and':
			case 'or':
			case 'not':
			case 'near':
			case '(';
			case ')': $list[] = $w; break;
			case '': break;
			default: if (substr($w,-1) == '*') $list[] = $w; else $list[] = $w.'*';
		}
	}
	return join(' ',$list);
}

function swQuerySoundex($q)
{
	$list = array();
	$q = str_replace('(',' ( ',$q);
	$q = str_replace(')',' ) ',$q);
	foreach(explode(' ',$q) as $w)
	{
		switch(strtolower($w))
		{
			case 'and':
			case 'or':
			case 'not':
			case 'near':
			case '(';
			case ')': $list[] = $w; break;
			case '': break;
			default: $list[] = soundex(str_replace('*','',$w));
		}
	}
	return join(' ',$list);
}




