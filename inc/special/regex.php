 <?php

if (!defined('SOFAWIKI')) die('invalid acces');

$replace = swGetArrayValue($_REQUEST,'replace');
$hint = swNameURL(swGetArrayValue($_REQUEST,'hint'));
$namespace = swGetArrayValue($_REQUEST,'namespace');
$submitreplace = swGetArrayValue($_REQUEST,'submitreplace');
$submitreplacepreview = swGetArrayValue($_REQUEST,'submitreplacepreview');
if (isset($_REQUEST['searchnames'])) { $searchnames = "1"; $searchnameschecked = ' CHECKED';}
else 
{ $searchnames = 0;  $searchnameschecked = '';}
if (isset($_REQUEST['ignorecase'])) { $ignorecase = "1"; $ignorecasechecked = ' CHECKED';}
else 
{ $ignorecase = 0;  $ignorecasechecked = '';}

$swMaxOverallSearchTime *=5;  


if ($query)
{
	//preserve backspace
	$query = str_replace("\\\\","\\",$query);
	$replace = str_replace("\\\\","\\",$replace);
}

$swParsedName = 'Special:Regex';
if ($query) $swParsedName .= ': '.$query;

$regexerror = false;

if ($query)
{

	$ch0 = substr($query,0,1);
	$ch1 = substr($query,-1,1);
	
	
	$regex = 'regex'; if ($ignorecase) $regex = 'regexi';
	$domain = '_content'; if ($searchnames) $domain = '_name';
	$hint2 = ''; if (isset($hint) && $hint != '') $hint2 = '"'.$hint.'"';
	
	$q = 'filter _namespace "'.$namespace.'", _revision, '.$domain.' '.$hint2.'
select '.$domain.' '.$regex.' "'.$query.'"
project _revision';
	
		
	$old_error = error_reporting(0); // Turn off error reporting
	$swParsedContent .= '<pre>'.$q.'</pre>';
	$revisions = swRelationToTable($q);
	error_reporting($old_error);


}
else
	$revisions = array();

// ksort($revisions,SORT_NUMERIC);

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
	if ($ignorecase) $case ='i'; else $case='';
	
	if (($submitreplacepreview || $submitreplace) && $replace)
	{
		$t = preg_replace('/'.$query.'/'.$case,'<ins>'.$replace.'</ins><del>$0</del>',$record->content);
	}
	else
		$t = preg_replace('/'.$query.'/'.$case,'<ins>$0</ins>',$record->content);
		
	$ts = explode("\n",$t);
	
	$tlines = array();
	foreach($ts as $tline)
	{
		if (stristr($tline,'<ins>') || stristr($tline,'<del>'))
		$tlines[] = $tline;
	}
	
	$t = join("\n",$tlines);
	
	
	
	if ($submitreplace && @$_REQUEST['revision'.$record->revision])
	{
		if ($record->status == 'protected')
			$replaced = 'protected';
		elseif ($record->status == 'ok' && $user->hasright('modify', $record->name))
		{
			$record->content = preg_replace('/'.$query.'/'.$case,$replace,$record->content);
			$record->comment = 'regex find /'.$query.'/'.$case.' replace '.$replace;
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

if(!$query) $query="";
if(!$namespace) $namespace="*";

$swParsedContent .= '<div id="editzone" class="editzone specialregex"><form method="post" action="index.php">
		<p>
		Regex <input type="hidden" name="name" value="special:regex" />
		<input type="text" size=40 name="query" value="'.$query.'" />
		<br>Hint <input type="text" size=40 name="hint" value="'.$hint.'" />
		<br>Namespace <input type="text" size=40 name="namespace" value="'.$namespace.'" />
		
		<br><input type="checkbox" name="searchnames" value="'.$searchnames.'" / '. $searchnameschecked.'> Search names
		<input type="checkbox" name="ignorecase" value="'.$ignorecase.'" / '. $ignorecasechecked.'> Ignore case
		<br><input type="submit" name="submit" value="'.swSystemMessage('search',$lang).'" />';
		
if (!isset($_REQUEST['searchnames']) && ($query != '//'))
$swParsedContent .= '
		<br><br>Replace <input type="text" size=40 name="replace" value="'.$replace.'" />
		<br><input type="submit" name="submitreplacepreview" value="'.swSystemMessage('replace-preview',$lang).'" />';
		
if (!isset($_REQUEST['searchnames']) && $submitreplacepreview &&!isset($swOvertime) && !$regexerror && count($revisions)>0)

$swParsedContent .= ' <input type="submit" name="submitreplace" value="'.swSystemMessage("replace",$lang).'" />';


if (count($revisions)>0)
	$swParsedContent .= ' <input type="submit" name="submitexport" value="'.swSystemMessage("export",$lang).'" />';
	
$swParsedContent .= '</p>This is a very powerful tool that can change many wiki pages at once. Use it carefully.
	<br>Matches: . any \\w azAZ09_ \\W not w \\s whitespace \\S not s \\d 09 \\D non d
	<br>\\t tab \\n newline \\r return
\\021  octal char \\xf0  hex char [ab] a ot b [^ab] neither a nor b
<br>* 0+ + 1 ? 0-1 {n} n times {n,m} n to m times *? mutliple not greedy
<br>/exp/i case insensitive /^exp/ beginning of string /exp$/ end of string
<br>The Hint field allows you to enter a string that must be present as url form in the page. This can improve the performance of the regex.
<br>NB: you cannot replace when you search for names only.
	</i>'.$regexerror.$arraylimited;

$swParsedContent .= '<ul>'.join("\n",$searchtexts).'</ul>';


$swParsedContent .= '</p></form>';
?>