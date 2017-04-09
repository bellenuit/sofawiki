<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$wiki = $currentwiki; // override

$wikis = array($wiki);
$modifymode = "modify";


if (!$wiki->status)
	$wiki->name = str_replace("_"," ",$wiki->name);

$swParsedName = swSystemMessage("Edit",$lang)." ".$wiki->name; 
$namefieldtype = "hidden";
$namespacelist  = "";
$defaultnamespace = "";

if ($revision0 > 0)
	$swParsedContent = "<div id='help'>".swSystemMessage("EditingConflictHelp",$lang). "</div>
<div id='editzone'>";
else
	$swParsedContent = "<div id='help'>".swSystemMessage("EditingNewConflictHelp",$lang). "</div>
<div id='editzone'>";

$swParsedContent .= "<table><tr>";

$swParsedContent .= "<td valign=top>";
if (count($wikis)>1) $swParsedContent .= "<h4>$wiki->name</h4>";
$lines = explode("\n",$wiki->content);
$rows = max(count($lines) + 2, 3);
if (strlen($wiki->content)<2) $rows = 16;
$cols = 80 / count($wikis);

if ($action=="preview") $wikicomment = $wiki->comment;
	else	$wikicomment="";

$submitbutton = "";
if ($user->hasright("modify", $wiki->name))
	$submitbutton .= "<input type='submit' name='submitmodify' value='".swSystemMessage("Modify",$lang)."' />";


$diff = htmldiff($wiki->content,$conflictwiki->content);



$swParsedContent .= "
		<div id='editzone'>
		<form method='post' action='".$wiki->link($modifymode)."'>
		<p>
			<input type='$namefieldtype' name='name' value=\"$wiki->name\" style='width:60%' />
			<input type='submit' name='submitpreview' value='".swSystemMessage("Preview",$lang)."' />
			$submitbutton
		</p>
		<p>".swSystemMessage("EditConflictCurrentPage",$lang)." ($wiki->revision, $wiki->user, $wiki->timestamp):</p>
		<p>
			<textarea name='content' rows='$rows' cols='$cols' style='width:95%'>".$currentwiki->contentclean()."</textarea>
		</p>
		<input type='hidden' name='revision' value='$wiki->revision'>
			<p>
			".swSystemMessage("Comment",$lang).": <input type='text' name='comment' value=\"$wikicomment\" style='width:95%' />
			</p>
		</form>

		<p>".swSystemMessage("EditConflictYourChange",$lang).":</p>
		<p>	<textarea name='content' rows='$rows' cols='$cols' style='width:95%; background-color:lightgray' readonly>".$conflictwiki->contentclean()."</textarea>
		</p>
		<p>".swSystemMessage("EditConflictDifferences",$lang).":</p>
		<p><pre>$diff</pre>
		</div>";

$swParsedContent .= "</td>";
$swParsedContent .= "</tr></table>";

$swParsedContent .= "\n</div>";


$swFooter = "".swSystemMessage("Revision",$lang).":$wiki->revision, $wiki->user, ".swSystemMessage("Date",$lang).":$wiki->timestamp, ".swSystemMessage("Status",$lang).":$wiki->status";
if(!$name) $swFooter = "";
if (!$wiki->revision) $swFooter="";
if (count($wikis)>1) $swFooter="";


?>