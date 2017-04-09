<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = swSystemMessage('Search',$lang).': '.$query;

$ns = join(' ',$swSearchNamespaces);

echotime('query');
$names = array();

$foundname = false;
$found = false;


$urlquery = swNameURL($query);
$urlquerylist = explode('-',$urlquery);

//remove all small words if there are words longer than 3 chars
foreach($urlquerylist as $k=>$word)
{
	if(strlen($word)<3)
		unset($urlquerylist[$k]);
}
if (stristr($urlquery,'-')) array_push($urlquerylist,$urlquery);


if  ($query != '' && isset($swQuickSearchinTitle) && $swQuickSearchinTitle && !isset($_REQUEST['allresults']))
{
	
	
	$record = new swWiki;
	$record->name = $urlquery;
	$c = $record->CurrentPath();
	
	if (isset($swQuickSearchRedirect) && $swQuickSearchRedirect && file_exists($c) )
	{
		$nsw = $record->wikinamespace();
		if ($nsw == '' || stristr($ns.' ',$nsw.' ') || $ns=='*')
		{
			
			$swParsedContent = '#REDIRECT [['.$urlquery.']]';
			$name = $urlquery;
			$swParseSpecial = true;
			$found = true;
			$foundname = true;
		}
	}
	else
	{
		echotime ('qsit all');
		
		
		$first = true;
		$names = array();

		foreach($urlquerylist as $word)
		{
			
			$revisions = swFilter('SELECT _name, _rating WHERE _name *~* '.$word,'*','query');
				
			$thisresult = array();
			foreach($revisions as $k=>$v)
			{
				$n = $v['_name'];
				$r = $v['_rating'];
				if ($n)
					$thisresult[$n] = $r; 
			}
		
			if ($first)
			{
				$names = $thisresult;
				$first = false;
			}
			elseif (stristr($word,'-')) //make sure this is last
			{
				foreach($thisresult as $tn=>$tr)
				{
					if (isset($names[$tn]))
						$names[$tn] += $thisresult[$tn];
					else
						$names[$tn] = $thisresult[$tn];
				}
			}
			else
			{
				foreach($thisresult as $tn=>$tr)
				{
					
					if (!isset($names[$tn])) unset ($thisresult[$tn]);
				}
				foreach($names as $tn=>$tr)
				{
					
					if (!isset($thisresult[$tn]))
						unset ($names[$tn]);
					else
						$names[$tn] *= $thisresult[$tn];
				}
			}
			
		}

		
		if (count($names)>0) $found = true;
		
	}
}


if (!count($names))
{

	
	
	$first = true;
	$names = array();

	foreach($urlquerylist as $word)
	{
		$revisions = swFilter('SELECT _name, _rating WHERE _content *~* '.$word,'*','query');
			
		$thisresult = array();
		foreach($revisions as $k=>$v)
		{
			$n = $v['_name'];
			$r = $v['_rating'];
			if ($n)
				$thisresult[$n] = $r;
		}
		
		if (count($thisresult) == 0) continue;
	
	
		if ($first)
		{
			$names = $thisresult;
			$first = false;
		}
		elseif (stristr($word,'-')) //make sure this is last
		{
				foreach($thisresult as $tn=>$tr)
				{
					if (isset($names[$tn]))
						$names[$tn] += $thisresult[$tn];
					else
						$names[$tn] = $thisresult[$tn];
				}
		}
		else
		{
		
			foreach($thisresult as $tn=>$tr)
			{
				if (!isset($names[$tn])) unset ($thisresult[$tn]);
			}
			foreach($names as $tn=>$tr)
			{
				if (!isset($thisresult[$tn]))
					unset ($names[$tn]);
				else
					$names[$tn] *= $thisresult[$tn];
			}
		}
	}
		
}

arsort($names);

/*
echo "<pre>";
print_r($names);
echo "</pre>";
*/

if (!$foundname && $query != '' )
{

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
	if ($start>0)
		$navigation .= '<a href="index.php?action=search&query='.$query.'&start='.sprintf("%0d",$start-$limit).'"> '.swSystemMessage('back',$lang).'</a> ';
		
	$navigation .= " ".sprintf("%0d",min($start+1,$count))." - ".sprintf("%0d",min($start+$limit,$count))." / ".$count;
	if ($start<$count-$limit)
		$navigation .= ' <a href="index.php?action=search&query='.$query.'&start='.sprintf("%0d",$start+$limit).'">'.swSystemMessage('forward',$lang).'</a>';
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
	

	$swParsedContent = $navigation.$gprefix.join($separator,$searchtexts).$gpostfix.$navigation;
	

}

if ($query == '') $swParsedContent = swSystemMessage('Error: query is empty',$lang);

if (isset($swParseSpecial))
{
	
	$wiki->content = $swParsedContent;
	
	$wiki->parsers = $swParsers;
	$swParsedContent = $wiki->parse();
	
	//echo $swParsedContent;
}

if ($found)  $swParsedContent.="\n".'<a href="index.php?action=search&query='.$query.'&allresults=1">'.swSystemMessage('all results',$lang).'</a>';
elseif (isset($swOvertime) && $swOvertime)  $swParsedContent.="\n".'<a href="index.php?action=search&query='.$query.'&allresults=1&moreresults=1">'.swSystemMessage('more results',$lang).'</a>';

if (isset($swOvertime))
	$swParsedContent .= '<br>'.swSystemMessage('Search limited by time out.',$lang).' <a href="index.php?action=search&query='.$query.'">'.swSystemMessage('Search again',$lang).'</a>';


?>