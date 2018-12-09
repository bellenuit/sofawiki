<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = swSystemMessage('search',$lang).':"'.$query.'"';

$ns = join('|',$swSearchNamespaces); 
if (stristr($ns,'*')) $ns = '*';

$urlquery = swNameURL($query);



$found = false;

if (isset($swQuickSearchRedirect) && $swQuickSearchRedirect && !isset($_REQUEST['allresults']))
{
	$wiki = new swWiki; //must be global wiki to be parsed by functions
	$wiki->name = $urlquery;
	$nsw = $wiki->wikinamespace();
	if ($nsw == '' || stristr($ns.' ',$nsw.' ') || $ns=='*')
	{
		$wiki->lookupLocalName();
		$wiki->lookup();
		if ($wiki->revision)
		{
			$name = $query;
			$swParsedContent = $wiki->content;
			$swParsedContent .= '<nowiki><br><br><i><a href="index.php?action=search&allresults=1&query='.$query.'">'.swSystemMessage('all-results',$lang).' "'.$query.'"</a></i></nowiki>';

			$swParsedName = $wiki->namewithoutlanguage();
			$swParseSpecial = true;
			$found = true;
			if ($user->hasright('modify', $wiki->namewithoutlanguage()))
				array_unshift($swEditMenus,'<a href="'.$wiki->link('edit','--').'" rel="nofollow">'.swSystemMessage('edit',$lang).'</a>');
			array_unshift($swEditMenus, '<a href="'.$wiki->link('view','--').'" rel="nofollow">'.swSystemMessage('view',$lang).'</a>');

		}
	}
	$action='view';
	
}
$names = array();
if (!$found)
{
	$urlquerylist = explode('-',$urlquery);
	foreach($urlquerylist as $k=>$word)
	{
		if(strlen($word)<3)
			unset($urlquerylist[$k]);
	}
	usort($urlquerylist,'swLengthSort');
	if (stristr($urlquery,'-')) $urlquerylist[] = $urlquery;
	
	//print_r($urlquerylist);
	
	$first = true;
	
	$hint = NULL;
	
	foreach($urlquerylist as $word)
	{
		if (!$first && !count($names)) continue;
		
		
		
		$revisions = swFilter('SELECT _name, _rating, _revision WHERE _* *~* '.$word,$ns,'query','',$hint);
		
		// remove revisions of excluded name spaces
		
		
		if (count($swSearchExcludeNamespaces)>0)
		{

			
			foreach($swSearchExcludeNamespaces as $excl)
			{
				
				$excl .= ':';
				$excl = swNameURL($excl);
				
				echotime($excl);
				
				$l = strlen($excl);
				foreach($revisions as $k=>$v)
				{
					$n = $v['_name'];
					if (swNameURL(substr($n,0,$l)) == $excl) unset($revisions[$k]);
				}
			}
		}
		
		
		
		if (!isset($revisions) || !is_array($revisions)) continue;
		
		$hint = new swBitmap();
		foreach($revisions as $rows)
		{
			$hint->setbit($rows['_revision']);
		}
		
		//print_r($revisions);
		
		if ($first)
		{
			foreach($revisions as $k=>$v)
			{
				$n = swNameURL($v['_name']);
				$r = $v['_rating'];
				if (isset($names[$n]))
					$names[$n] += $r;
				else
					$names[$n] = $r;
			}
			$first = false;	
		}
		else
		{
			$thisrevision = array();
			foreach($revisions as $k=>$v)
			{
				$n = swNameURL($v['_name']);
				$r = $v['_rating'];
				$thisrevision[$n] = $r;
			}
			
			foreach($names as $n=>$r)
			{
				if (stristr($word,'-'))
				{
					if (isset($thisrevision[$n]))
					{
						$names[$n] *= 2;
					}//!! last should not exclude, but premium
				}
				else
				{
					if (isset($thisrevision[$n]))
					{
						$names[$n] = $r*$thisrevision[$n];
					}
					else
					{
						unset($names[$n]);
					}
				}
			}
		
		
		}
		
	}
	
	$goodresults = swFilter('SELECT querygoodresults, querygoodhits FROM logs: WHERE querygoodresults == '.$urlquery,'*','query','','');
	
	//print_r($names);
	
	foreach($goodresults as $row)
	{
		$goodhits = explode('+',$row['querygoodhits']);
		
		$c = count($goodhits);
		
		foreach ($goodhits as $g)
		{
			echotime('good '.$g); 
			if (isset($names[$g])) $names[$g] += (5/$c);
			
		}
	}

	//print_r($names);

	if (count($names))
		arsort($names);




	$swParseSpecial = false; 
	$separator = "\n";
	$gprefix = '<ul>';
	$gpostfix = '</ul>';
	$limit = '';
	
	
	// function can reorder list and apply custom templates for each name
	if (function_exists('swInternalSearchHook')) 
	{
		$hookresult = swInternalSearchHook($names,$query);
		if ($hookresult)
		{
			$gprefix = ''; if (isset($hookresult['gprefix'])) $gprefix =  $hookresult['gprefix'];
			$gpostfix = ''; if (isset($hookresult['gpostfix'])) $gpostfix =  $hookresult['gpostfix'];
			$names = ''; if (isset($hookresult['names'])) $names =  $hookresult['names'];
			$separator = ''; if (isset($hookresult['separator'])) $separator =  $hookresult['separator'];
			if (isset($hookresult['limit'])) $limit =  $hookresult['limit'];
			
			$swParseSpecial = true; 
		}
	}
	
	
	foreach($names as $k=>$v)
	{	
		if (!$user->hasright('view', $k))
			unset($names[$k]);
	}
	
	
	if ($limit==0) $limit = 50;
	
	$start = 0; if (isset($_REQUEST['start'])) $start = $_REQUEST['start'];
	$count = count($names);
	global $lang;
	global $name;
	
	$navigation = '<nowiki><div class="categorynavigation">';
	if ($start>0 && $count)
		$navigation .= '<a href="index.php?action=search&allresults=1&query='.$query.'&start='.sprintf("%0d",$start-$limit).'"> '.swSystemMessage('back',$lang).'</a> ';
		
	if ($count)
		$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
	else
		$navigation .= swSystemMessage('no-result',$lang);
	if ($start<$count-$limit && $count)
		$navigation .= ' <a href="index.php?action=search&allresults=1&query='.$query.'&start='.sprintf("%0d",$start+$limit).'">'.swSystemMessage('forward',$lang).'</a>';
	$navigation .= '</div></nowiki>';
	
	
	$searchtexts = array();
	
	
	
	$i=0;
	if (is_array($names) && count($names)>0)
	{
	foreach ($names as $k=>$v)
	{
	
		
		$i++;
		if ($i<=$start) continue; 
		if ($i>$start+$limit) continue; 
			$record = new swWiki;
			$record->name = $k;
			$record->lookup();
			
			if ($record->status == 'deleted') continue; // should not happen
			
			if (substr($record->content,0,9) == '#REDIRECT') continue;
			
			$link = $record->link("");
			
			$link .= '&query='.urlencode(swNameURL($query));
			
			$dplen = strlen('#DISPLAYNAME');
			if (substr($record->content,0,$dplen)=='#DISPLAYNAME')
				{
					$pos = strpos($record->content,"\n");
					$record->name = substr($record->content,$dplen+1,$pos+1-$dplen-2);
				}
			
			
			
			if (true)
			{
				
					if (isset($hookresult) && $hookresult )
					{
						$searchtexts[] = $v;	
						
					}
					else
					{
	
						$t = "";
						$pref="";
						$qs = swQuerySplit($query);
						$s0 = $qs[0];
						if (stristr($record->content,$s0))
						{
							
							$pos = stripos($record->content,$s0);
							if ($pos > 40)
							{
								$pos -= 40;
								$possp = stripos($record->content,' ',$pos);
								if ($possp >= $pos && $possp < $pos + 40)
									$pos = $possp;
								$pref = '...';
							}
							else
							{
								$pos = 0;
								$pref = '';
							}
							
							$pos2 = min($pos + 200,strlen($record->content));
							
							$possp = stripos($record->content,' ',$pos2);
							if ($possp >= $pos2 && $possp < $pos2+40)
									$pos2 = $possp;
							

							$record->content = substr($record->content,$pos,$pos2-$pos);  
							$record->content = str_replace(PHP_EOL,' ',$record->content);

							$record->parsers = array();
							$record->parsers['nowiki'] = new swNoWikiParser;
							switch($record->wikinamespace())
							{
								case 'User': case 'Template': $t = ''; break;
								default: $t = $record->parse(); 
							}
							
							
							foreach ($qs as $q)
								$t = swStrReplace($q,'<span class="found">'.$q.'</span>',$t);
						}
						
						if (trim($t)) $t = $pref.trim($t). '...'; else $t = '';
						
						if (trim($record->name))
						
							$searchtexts[] = '<li><a href="'.$link.'">'.$record->name.'</a>
						<br/>'.$t.'</li>';
						
						unset($swParseSpecial);
				
					}
			}
	}
	} // count >0
	

	$swParsedContent = $navigation.$gprefix.join($separator,$searchtexts).$gpostfix;
	if ($count)
		$swParsedContent .=$navigation;
	

}

if ($query == '') $swParsedContent = swSystemMessage('query-is-empty-error',$lang);

if (isset($swParseSpecial))
{
	
	$wiki->content = $swParsedContent;
	$wiki->parsers = $swParsers;
	$swParsedContent = $wiki->parse();
}


if (isset($swOvertime) && $swOvertime)
	$swParsedContent .= '<div id="searchovertime">'.swSystemMessage('search-limited-by-timeout.',$lang).' <a href="index.php?action=search&query='.$query.'">'.swSystemMessage('search-again',$lang).'</a></div>';


?>