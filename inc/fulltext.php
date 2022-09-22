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
	$q = "SELECT revision FROM pages WHERE revision = $revision";
	$result = $swFulltextIndex->querySingle($q);
	if ($result) return;
	
	// clean HTML
	$html = preg_replace('/<script.*?\>.*?<\/script>/si', ' ', $html); 
    $html = preg_replace('/<style.*?\>.*?<\/style>/si', ' ', $html); 
    $html = preg_replace('/<.*?\>/si', ' ', $html); 
    $html = preg_replace('!\s+!', ' ', $html);
    $html = html_entity_decode($html); 
    
    $html = swTrigramize($html);
    $html = $swFulltextIndex->escapeString($html);
    if (substr($name,-3,1)=='/') $name = substr($name,0,-3);
    $name = swTrigramize($name);
    $name = $swFulltextIndex->escapeString($name);
    if (substr($url,-3,1)=='/') $url = substr($url,0,-3);
    
    $q = "INSERT INTO pages(url,lang,revision,title, body) VALUES('$url','$lang',$revision,'$name','$html')";
    if (!$swFulltextIndex->exec($q))
	{
		throw new swDbaError('swFulltextIndex create table error '.$swFulltextIndex->lastErrorMsg());
	}
}

function swQueryFulltext($query)
{
	echotime('queryfulltext');
	swOpenFulltext();
	global $swFulltextIndex;
	if (!$swFulltextIndex) return;
	global $db;
	$querylines = swTrigramizeQuery($query);
	$query =  join(' ',$querylines);
	
	$q = "SELECT score(offsets(pages)) as score, url, lang, revision, title, body, byteoffsets(offsets(pages)) as byteoffsets FROM pages 
  WHERE pages MATCH '$query'
  ORDER BY score DESC";
	
	// echo '<p>'.$q;
	$result = $swFulltextIndex->query($q);
	$r = new swRelation('score, url, lang, revision, title, body');
	
	while ($d = $result->fetchArray(SQLITE3_ASSOC)) 
	{
    	$rev = $d['revision'];
    	if ($db->currentbitmap->getbit($rev))
    	{
 
//     		$d['title']=detrigramize($d['title']);
    		$d['title']=swFulltextSnippet($d['title'],'',$querylines);
    		if (!$d['title']) $d['title'] ='...';
    		$d['body']=swFulltextSnippet($d['body'],$d['byteoffsets'],$querylines);
    		$tp = new swTuple($d);
			$r->tuples[$tp->hash()] = $tp;
		}
		else
		{
			$q = "DELETE FROM pages WHERE revision = $rev";
			if (!$swFulltextIndex->exec($q))
			{
				throw new swDbaError('swFulltextIndex delete entry error '.$swFulltextIndex->lastErrorMsg());
			}
		}
	}
	echotime('queryfulltext end');
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
	$l = grapheme_strlen($s);
	for($i=0;$i<$l-2;$i++)
	{
		$lines[] = grapheme_substr($s,$i,3);
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
	
	$lines = array();
	$l = grapheme_strlen($s); 

	for($i=0;$i<$l;$i+=4)
	{
		$lines[] = grapheme_substr($s,$i,1);
	}
// 	$lines[] = ':';
	$lines[] = grapheme_substr($s,-2,2);
// 	$lines[] = ' - '.$s;
	return join('',$lines);
}

function swFulltextSnippet($s, $os,$querylines)
{
	$l = grapheme_strlen($s);
	$half =360;
	$offsets = explode(' ',$os);
	if (!trim($os)) 
	{
		$start = 0;
	}
	else
	{
		$t = substr($s,0,$offsets[0]);
		$start0 = grapheme_strlen($t);
		if ($start0 < $half )
		{
			$start = 0;
		}
		else
		{
			for($i = $start0-$half; $i<$start0; $i+=4)
			{
				if (grapheme_substr($s,$i,1)== ' ')
				{
					$start = $i;
					break;
				}
				
			}
			
		} 		
		if (!isset($start)) $start = $start0;
	}
	
	$ende = $start+$half*2;
	$s = grapheme_substr($s,$start,$ende-$start);
	$s = swDetrigramize($s);
	rsort($querylines); // longer first
	foreach($querylines as $q)
	{
		switch ($q)
		{
			case 'AND':
			case 'OR': break;
			default:
			$v = str_replace('"','',$q);
			$v=swDetrigramize($v);
			$s = str_ireplace($v,'<b>'.$v.'</b>', $s);

		}
	}
	if ($start>0) $s = '...'.$s;
	if ($ende<$l) $s .='...';
	
	return $s;
	
}
