<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$wikis = array();
if ($action == "editmulti" || $action == "modifymulti" || swGetArrayValue($_REQUEST,'submitdeepl',false)) 
{
	
	$deeplsources = array();
	$targetlang='';
	
	$n = $wiki->namewithoutlanguage();
			
	foreach($swLanguages as $l)
	{
		$w = new swWiki();
		$w->name = $n.'/'.$l;
		$w->lookup();
		$wikis[$l]=$w;
		
		if (trim($w->content)!='') $deeplsources[$l]= $w->content;
	}
	
	if (swGetArrayValue($_REQUEST,'deepltarget',false))	
	{		
		$sourcelang = $_REQUEST['deeplsource'];
		$targetlang = $_REQUEST['deepltarget'];
					
		$w = $wikis[$sourcelang];
		$sourcetext = $w->content;
		
		$translated[$targetlang] = swTranslate($sourcetext,$sourcelang,$targetlang);			
		
		$action == "editmulti";
	}	
	
	$headertext = swSystemMessage('edit-multi',$lang);
	$modifymode = "modifymulti";
}
else
{
	$l = $wiki->language();	
	$wikis[$l] = $wiki;
	$headertext = swSystemMessage('edit',$lang);
	$modifymode = "modify";
}
	


$swParsedName = $wiki->name; 

$namefieldtype = 'hidden';
$helptext = swSystemMessage('modify-help',$lang);




switch ($wiki->status)
{
	case 'protected':   $swError = 'Protected';
						$wiki->parsers = $swParsers;
						$swParsedContent .= $wiki->parse();
						break;
	
	case 'deleted':     $swError = 'Deleted';
						$swParsedContent .= "<a href='".$wiki->link("history")."'>".swSystemMessage("history",$lang)."</a>";
						break;

	case '':			if ($modifymode != 'modifymulti')
						{						
							$s = $wiki->name;
		
							//clean up url
							if ($s)	
							{
								$s = str_replace("-"," ",$s);
								$s = strtoupper(substr($s,0,1)).substr($s,1);
							}
							$wiki->name=$s;
							
							$swParsedName = ''; 
							$headertext = swSystemMessage('new',$lang);
							$namefieldtype = 'text';
							$helptext = swSystemMessage('new-help',$lang);
						}
						
						// no break
	
	
	default:			if ($action=='new')
							$swParsedContent = '<div id="editzone" class="editzone actionnew">';
						else
							$swParsedContent = '<div id="editzone" class="editzone actionedit">';
						$swParsedContent .= '<div class="editheader">'.$headertext.'</div>';

						
						if (isset($wiki->internalfields['editortemplate']) && $action != 'editmulti')
						{
							$v = $wiki->internalfields['editortemplate'];
							if (is_array($v)) $v = array_shift($v);
							$_REQUEST['editor'] = $v;
						}
						
						
						if (isset($_REQUEST['editor']) && $action != 'editsource')
						{
							$swParsedContent .= swParseEditorTemplate($_REQUEST['editor'],$wiki);	
							break;						
						}
						
						
						
						
						
												
						
						//$swParsedContent .= "\n<table class='blanktable'>\n";
						//$swParsedContent .= '<tbody><tr>'; 
						
						$cw = max(count($wikis),1);
						$cw = 100/$cw;
						
						// $wikiwidth = ' style=" width:'.$cw.'%"';
						//$swParsedContent .= "\n<td valign=top>";
						$swParsedContent .= PHP_EOL.'<form method="post" action="index.php?action=modifymulti">';
						
						$swParsedContent .= PHP_EOL.'<input type="submit" name="submitcancel" value="'.swSystemMessage('cancel',$lang).'" />';
						$submitbutton = "<input type='submit' name='submit$modifymode' value='".swSystemMessage("save",$lang)."' />";
						$swParsedContent .= PHP_EOL.$submitbutton;
						
						if ($namefieldtype=='text') $swParsedContent .= PHP_EOL.'<p>'.swSystemMessage('name',$lang).'</p>';
						
						if ($action=='new')
						{
							if (isset($_REQUEST['name'])) $wiki->name = $_REQUEST['name'];
							
						}
						
						$swParsedContent .= PHP_EOL.'<input type="'.$namefieldtype.'" id="name" name="name" value="'.$wiki->name.'" />';
						
						foreach ($wikis as $wikilang=>$wikisub)
						{
							
														
							$wikisub->persistance = false;
							if ($action != 'new')
								$wikisub->lookup(); // by revision again.
							$wikisub->comment = '';
							
							if ($action=='new')
							{
								if (isset($_REQUEST['content'])) $wikisub->content = $_REQUEST['content'];
							}
							
							
							if (count($wikis)>1) $swParsedContent .= '<p>'.$wikisub->name.'</p>';
							if ($wikisub->content)
							{   
								$lines = explode("\n",$wikisub->content); 
								$rows = max(count($lines) + 2, strlen($wikisub->content)*count($wikis)/70, 3);
								$rows = max(count($lines) + 2, strlen($wikisub->content)*1/70, 3);
								$rows = min($rows,50);
								if (strlen($wikisub->content)<2) $rows = 5;
							}
							else
							{
								$rows = 5;
							}
							$cols = 100 / count($wikis);
							$codecols = $cols;
							if ($codecols==80) $codecols=100;
																					
							$deeplcontent = '';
							if ($action == 'editmulti' && (isset($swDeeplKey) && count($deeplsources)>0 && trim($wiki->content) == '' 
							&& in_array($wikilang,$swTranslateLanguages)))
							{
								$deeplcontent = '';
								foreach($deeplsources as $k=>$v)
								{
								
									
									
									$deepllink = 'index.php?action=editmulti';
									$deepllink .= '&name='.$wiki->namewithoutlanguage();
									$deepllink .= '&deeplsource='.$k;
									$deepllink .= '&deepltarget='.$wikilang;
									
									if ($k !== $wikilang) $deeplcontent .= '<a href="'.$deepllink.'">Deepl '.$k.'</a> ';
								
								}
								// override wiki content
								if ($wikilang==$targetlang)
									$wikisub->content = $translated[$targetlang];
								$swError = ''; // This page does not exist. 
							}
							

							
							
							
							if ($namefieldtype=='text') $swParsedContent .= '</p>';
							$swParsedContent .= PHP_EOL.'<textarea name="content['.$wikilang.']" rows='.$rows.' cols='.$cols.' >'.$wikisub->contentclean().'</textarea>';
							$swParsedContent .= PHP_EOL.'<input type="hidden" name="currentrevision['.$wikilang.']" value="'.$wikisub->revision.'">';
														
							$swParsedContent .= PHP_EOL.$deeplcontent;
							
						}
						
						$swParsedContent .= PHP_EOL.'<p>'.swSystemMessage('comment',$lang).'</p>';
						$swParsedContent .= PHP_EOL.'<input type="text" name="comment" value="'.$wiki->comment.'"  />';
						
						$swParsedContent .= PHP_EOL.'</form>';
						
						if ($helptext) $swParsedContent .= PHP_EOL.'<div class="editfooter help">'.$helptext.'</div><!-- editfooter -->';
						
						
						

						$swParsedContent .= PHP_EOL.'</div><!-- editzone -->';

}





$swFooter = "$wiki->name, ".swSystemMessage('revision',$lang).": $wiki->revision, $wiki->user, ".swSystemMessage("date",$lang).":$wiki->timestamp, ".swSystemMessage("status",$lang).":$wiki->status";
switch($wiki->integrity())
{
	case 0: $swFooter .= ' error checksum not ok'; break;
	case 1: $swFooter .= ' checksum ok'; break;
}

if(!$name) $swFooter = "";
if (!$wiki->revision) $swFooter="";
if (count($wikis)>1) $swFooter="";

//$wiki->parsers = $swParsers;
//$swParsedContent .= $wiki->parse();

?>