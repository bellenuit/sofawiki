<?php

if (!defined("SOFAWIKI")) die("invalid acces");


if ($revision0 > 0)
	$helptext = swSystemMessage("editing-conflict-help",$lang);
	
else
	$helptext = swSystemMessage("editing-new-conflict-help",$lang);
	
$swParsedContent .= '<div id="editzone">';
$swParsedContent .= '<div class="editheader">'.swSystemMessage('editing-conflict',$lang).'</div>';

$swParsedContent .= '<table class="blanktable"><tr>';

$swParsedContent .= "<td valign=top>";
$lines = explode("\n",$wiki->content);
$rows = max(count($lines) + 2, 3);
$cols = 80 ;

$wikicomment="";

$submitbutton = "";
if ($user->hasright("modify", $wiki->name))
	$submitbutton .= "<input type='submit' name='submitmodify' value='".swSystemMessage("modify",$lang)."' />";


$diff = swHtmlDiff($currentwiki->content,$conflictwiki->content);



$swParsedContent .= PHP_EOL.'<form method="post" action="'.$currentwiki->link('modify').'">';
$swParsedContent .= PHP_EOL.'<input type="submit" name="submitcancel" value="'.swSystemMessage('cancel',$lang).'" />';
$swParsedContent .= PHP_EOL.'<input type="submit" name="submitmodify" value="'.swSystemMessage('save',$lang).'" />';

$swParsedContent .= PHP_EOL.'<input type="hidden" name="currentrevision['.$currentwiki->language().']" value="'.$currentwiki->revision.'">';
$swParsedContent .= PHP_EOL.'<p>'.$currentwiki->name.'</p>';
$swParsedContent .= PHP_EOL.'<p>'.swSystemMessage('editing-conflict-current-page',$lang).
					' ('.$currentwiki->revision.', '.$currentwiki->user.', '.$currentwiki->timestamp.')</p>';
$swParsedContent .= PHP_EOL.'<textarea name="content['.$currentwiki->language().']" rows="'.$rows.'" cols="'.$cols.'">'.$currentwiki->contentclean().'</textarea>';
$swParsedContent .= PHP_EOL.'<p>'.swSystemMessage('editing-conflict-your-change',$lang).'</p>';
$swParsedContent .= PHP_EOL.'<textarea name="content2" rows="'.$rows.'" cols="'.$cols.'" style="background-color:lightgray" readonly>'.$conflictwiki->contentclean().'</textarea>';
$swParsedContent .=	 PHP_EOL.'<p>'.swSystemMessage('editing-conflict-differences',$lang).'</p>';
$swParsedContent .=	 PHP_EOL.'<p><pre>'.$diff.'</pre>';
$swParsedContent .= PHP_EOL.'</td>';
$swParsedContent .= PHP_EOL.'</tr></table>';

if ($helptext) $swParsedContent .= PHP_EOL.'<div class="editfooter help">'.$helptext.'</div><!-- editfooter -->';

$swParsedContent .= "\n</div>";




?>