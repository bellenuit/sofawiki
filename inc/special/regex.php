 <?php

if (!defined('SOFAWIKI')) die('invalid acces');

$replace = swGetArrayValue($_REQUEST,'replace');
$hint = swGetArrayValue($_REQUEST,'hint');
$submitreplace = swGetArrayValue($_REQUEST,'submitreplace');
$submitreplacepreview = swGetArrayValue($_REQUEST,'submitreplacepreview');
if (isset($_REQUEST['searchnames'])) { $searchnames = "1"; $searchnameschecked = ' CHECKED';}
else 
{ $searchnames = 0;  $searchnameschecked = '';}
if (isset($_REQUEST['searchliteral'])) { $searchliteral = "1"; $searchliteralchecked = ' CHECKED';}
else 
{ $searchliteral = 0;  $searchliteralchecked = '';}

$swMaxRelaxedSearchTime *=5;   
$swMaxOverallSearchTime *=5;  


if ($query)
{
	//preserve backspace
	$query = str_replace("\\\\","\\",$query);
	$replace = str_replace("\\\\","\\",$replace);
}

$swParsedName = swSystemMessage('Regex',$lang);
if ($query) $swParsedName .= ': '.$query;

$regexerror = false;

if ($query)
{

	$ch0 = substr($query,0,1);
	$ch1 = substr($query,-1,1);
	
	
	if ($ch0 == $ch1 || $searchliteral)
	{
		
		
		if (isset($_REQUEST['searchnames']))
			$q = 'SELECT _revision WHERE _name r= '.$query;
		else
			$q = 'SELECT _revision WHERE _content r= '.$query;
		
		
		if (isset($hint) && $hint != '')
		{
			if (isset($_REQUEST['searchnames']))
			{
				if ($searchliteral)
				{
					$qhint = 'SELECT _revision, _name WHERE _name *=* '.$hint;
					$q = '';
				}
				else
				{
					$qhint = 'SELECT _revision, _name WHERE _name *~* '.$hint;
					$q = 'WHERE _name r= '.$query;
				}
				
			}
			else
			{
				if ($searchliteral)
				{
					$qhint = 'SELECT _revision, _content WHERE _content *=* '.$hint;
					$q = '';	
				}
				else
				{
					$qhint = 'SELECT _revision, _content WHERE _content *~* '.$hint;
					$q = 'WHERE _content r= '.$query;	
				}
			}
			
			$old_error = error_reporting(0); // Turn off error reporting

			$revisions = swQuery(array($qhint, $q));
			
			error_reporting($old_error);
			
		}
		else
			$revisions = swFilter($q,'*');
	}
	else
	{
		$regexerror = '<br><b>Error: Starting and ending delimiter do not match</b>';
		$revisions = array();
	}
}
else
	$revisions = array();

ksort($revisions,SORT_NUMERIC);

if (count($revisions) > 0 && isset($_REQUEST['submitexport'])) //immediate before we limit
{
	$revs = array();
	foreach ($revisions as $k=>$v)
	{
		$revs[] = $v['_revision'];
	}
	
	$swParsedContent .= swExport($revs);
}


$c=count($revisions);
if ($c>50)
{
	while(count($revisions)>50) array_pop($revisions);
	$arraylimited = "<p>Search had $c results. Limited to 50";
}
else
	$arraylimited = '<p>';

if (isset($swOvertime))
	$arraylimited .= "<br>Search limited by time out. Search again.";

$searchtexts = array();

$record = new swWiki;

foreach ($revisions as $k=>$v)
{
	$record = new swWiki;
	$record->revision = $v['_revision'];
	$record->lookup();
	
	$t = '';
	if (($submitreplacepreview || $submitreplace) && $replace)
			if ($searchliteral)
				$t = str_replace($hint,'<del>'.$hint.'</del><ins>'.$replace.'</ins>',$record->content);
			else
				$t = preg_replace($query,'<del>$0</del><ins>'.$replace.'</ins>',$record->content);
	else
			if ($searchliteral)
				$t = str_replace($hint,'<ins>'.$hint.'</ins>',$record->content);
			else
				$t = preg_replace($query,'<ins>$0</ins>',$record->content);
		
	$ts = explode("\n",$t);
	
	$tlines = array();
	foreach($ts as $tline)
	{
		if (stristr($tline,'<ins>'))
		$tlines[] = $tline;
	}
	
	$t = join("\n",$tlines);
	
	
	if ($submitreplace && @$_REQUEST['revision'.$record->revision])
	{
		if ($record->status == 'protected')
			$replaced = 'protected';
		elseif ($record->status == 'ok' && $user->hasright('modify', $record->name))
		{
			if ($searchliteral)
				$record->content = str_replace($hint,$replace,$record->content);
			else
				$record->content = preg_replace($query,$replace,$record->content);
			$record->comment = 'regex find '.$query.' replace '.$replace;
			$record->insert();
			$replaced = 'replaced';
		}
	}
	else
		$replaced = '';
		
	if ($submitreplacepreview && $record->status == 'ok' && $user->hasright('modify', $record->name))
		$check = '<input type="checkbox" name="revision'.$record->revision.'" value="1" CHECKED>';
	else
		$check = '';
	
	$searchtexts[] = '<li>'.$check.' '.$record->revision.' <a href="'.$record->link('').'">'.$record->name.'</a> '.$replaced.'
	<br/><pre>'.$t.'</pre></li>';
	
}

if(!$query) $query="//";

$swParsedContent .= '<div id="editzone"><form method="post" action="index.php">
		<p>
		<input type="hidden" name="name" value="special:regex" />
		<input type="text" size=40 name="query" value="'.$query.'" />
		Hint <input type="text" size=40 name="hint" value="'.$hint.'" />
		<input type="submit" name="submit" value="'.swSystemMessage('Search',$lang).'" />
		<input type="checkbox" name="searchnames" value="'.$searchnames.'" / '. $searchnameschecked.'>'.swSystemMessage('Search Names',$lang).
		'<input type="checkbox" name="searchliteral" value="'.$searchliteral.'" / '. $searchliteralchecked.'>'.swSystemMessage('Search Literal',$lang);
		
if (!isset($_REQUEST['searchnames']) && ($query != '//' || $searchliteral))
$swParsedContent .= '
		<input type="text" size=40 name="replace" value="'.$replace.'" />
		<input type="submit" name="submitreplacepreview" value="'.swSystemMessage('Replace Preview',$lang).'" />';
		
if (!isset($_REQUEST['searchnames']) && $submitreplacepreview &&!isset($swOvertime) && !$regexerror && count($revisions)>0)

$swParsedContent .= ' <input type="submit" name="submitreplace" value="'.swSystemMessage("Replace",$lang).'" />';


if (count($revisions)>0)
	$swParsedContent .= ' <input type="submit" name="submitexport" value="'.swSystemMessage("Export",$lang).'" />';
	
$swParsedContent .= '</p><p><i>Note: You need to use Perl style delimiters at the start and at the end like /searchterm/.
	<br/>This is a very powerful tool that can change many wiki pages at once. Use it carefully.
	<br>Matches: . any \\w azAZ09_ \\W not w \\s whitespace \\S not s \\d 09 \\D non d
	<br>\\t tab \\n newline \\r return
\\021  octal char \\xf0  hex char [ab] a ot b [^ab] neither a nor b
<br>* 0+ + 1 ? 0-1 {n} n times {n,m} n to m times *? mutliple not greedy
<br>/exp/i case insensitive /^exp/ beginning of string /exp$/ end of string
<br>The Hint field allows you to enter a string that must be present as url form in the page. This can improve the performance of the regex.
<br>Search literal translates a literal query in a regex.
	</i>'.$regexerror.$arraylimited;

$swParsedContent .= '<ul>'.join("\n",$searchtexts).'</ul>';


$swParsedContent .= '</p></form>';
?>