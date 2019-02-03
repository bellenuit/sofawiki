<?php

if (!defined("SOFAWIKI")) die("invalid acces");

$wikis = array();
if ($action == "editmulti" || $action == "modifymulti"  ) 
{
				
		foreach($swLanguages as $l)
		{
			//echotime($l);
			$w = new swWiki();
			//$w->parsers = $swParsers;
			
			// remove subpages
			$ns = explode("/",$wiki->name);
			$w->name = $ns[0]."/$l";
			
			$w->lookup();
			
			//echotime($w->name);
			array_push($wikis,$w);
		}
		$modifymode = "modifymulti";
}
else
{
	$wikis = array($wiki);
	$modifymode = "modify";
}

if (!$wiki->status)
	$wiki->name = str_replace("_"," ",$wiki->name);

if ($name && $wiki->status != "" || $modifymode == "modifymulti")
{
	$swParsedName = swSystemMessage("edit",$lang)." ".$wiki->name; 
	$namefieldtype = "hidden";
	$namespacelist  = "";
	$defaultnamespace = "";
	$helptext = swSystemMessage('modify-help',$lang);
}
else
{
	$s = $wiki->name;
	
	//clean up
	$s = str_replace("-"," ",$s);
	$s = $s = strtoupper(substr($s,0,1)).substr($s,1);
	$wiki->name=$s;
	
	
	$swParsedName = swSystemMessage("new",$lang)." ".$wiki->name ;
	$namefieldtype = "text";
	$helptext = swSystemMessage("new-help",$lang);
	
	
}
$swParsedContent = "<div id='editzone'>";

switch ($wiki->status)
{
	case "protected":
	
						if ($user->hasright("protect", $wiki->name))
						{
							$swParsedContent .= "
 <form method='post' action='".$wiki->link("unprotect")."'><p>
 <input type='Hidden' name='name' value='$name' />
 <input type='Submit' name='submitprotect' value='".swSystemMessage("unprotect",$lang)."' />
 </p></form>";
							$swParsedContent .= "\n<div class='preview'>".$wiki->parse()."\n</div>";
						}
						else
						{
							$swParsedContent .= "Protected";
							
							
						}
						
						break;
	
	case "deleted":
						if ($user->hasright("delete", $wiki->name))
						{
							$swEditMenus[] = "Deleted <a href='".$wiki->link("history")."'>".swSystemMessage("history",$lang)."</a>";
							$helptext = "";
						}
						else
						{
							$swParsedContent .= "Deleted";
							$helptext = "";
						}

						break;

	default:
						
						
						
						
						$swParsedContent .= "\n<table class='blanktable' style='width:100%'>\n<tr>";
						$cw = max(count($wikis),1);
						$cw = 100/$cw;
						$wikiwidth = ' style=" width:'.$cw.'%"';
						
						foreach ($wikis as $wiki)
						{
							
							//print_r($wiki);
							$wiki->persistance = false;
							if ($action != 'new')
								$wiki->lookup(); // by revision again.
							$wiki->comment = '';
							
							
							$swParsedContent .= "\n<td valign=top $wikiwidth>";
							if (count($wikis)>1) $swParsedContent .= "<h4>$wiki->name</h4>";
							$lines = explode("\n",$wiki->content);
							$rows = max(count($lines) + 2, strlen($wiki->content)*count($wikis)/70, 3);
							$rows = min($rows,50);
							if (strlen($wiki->content)<2) $rows = 16;
							$cols = 100 / count($wikis);
							$codecols = $cols;
							if ($codecols==80) $codecols=100;
														
							$submitbutton = "";
							if ($user->hasright("modify", $wiki->name))
							{
								$submitbutton .= "<input type='submit' name='submit$modifymode' value='".swSystemMessage("save",$lang)."' />";
							}
							
							if ($modifymode == "modify")
							{
								$cid = "id='editzonecolor'"; 
								if (!$swEditZoneColor) $cid .= "style='visibility:hidden'";
								$sid = "id='editzonesource'";
								// use this only in skin (opt-in)
								/*
								$swParsedContent .= '
<script>' . file_get_contents('inc/skins/editzone.js').'</script>';  
 								$swParsedCSS .= file_get_contents('inc/skins/editzone.css'); 
 								*/
							}
							else 
							{
								$cid = $sid = 0;
								$rows = 20;
							}
							
							
							
							
							$swParsedContent .= "
 <form method='post' action='index.php?action=modify'>
 <p id='editzonesubmit' style='width:100%; position:relative'>
 <input type='$namefieldtype' name='name' value=\"$wiki->name\" style='width:60%' />
 <input type='submit' name='submitcancel' value='".swSystemMessage("cancel",$lang)."' />
 $submitbutton
 </p>
 <div id='editzonewrapper' style='width: 100%; height: "  . floor($rows*20)  . "px; position: relative; background-color: transparent;' >
 <div $cid class='editzonecommon'></div>
 <textarea $sid class='editzonecommon' name='content' style = 'width:100%; height:100%'
 >".$wiki->contentclean()."</textarea>
 </div>

 <input type='hidden' name='revision' value='$wiki->revision'>
 <p>".swSystemMessage("comment",$lang).":
 <input type='text' name='comment' value=\"$wiki->comment\" style='width:90%' />
 </p></form>
";
						
						
							if ($modifymode == "modify")
							{
								if ($user->hasright("protect", $wiki->name) && $name && $wiki->status != "")
								{
									$swParsedContent .= "
 <p>
 <form method='post' action='".$wiki->link("protect")."'><p>
 <input type='submit' name='submitprotect' value='".swSystemMessage("protect",$lang)."' />
 </p></form>
";
								}
								if ($user->hasright("rename", $wiki->name) && $name 
								&& $wiki->status != "")
								{
									$swParsedContent .= "
 <form method='post' action='".$wiki->link("rename")."'><p>
 <input type='submit' name='submitdelete' value='".swSystemMessage("rename",$lang)."' />
 <input type='text' name='name2' value=\"$wiki->name\" style='width:60%;' />";
 
 if (!stristr($name,'/'))
 $swParsedContent .="<input type='checkbox' name='renamesubpages' value='1'/>".swSystemMessage("rename-subpages",$lang);
 $swParsedContent .=" </p></form>";
								}
								if ($user->hasright("delete", $wiki->name) && $name && $wiki->status != "")
								{
									$swParsedContent .= "
 <form method='post' action='".$wiki->link("delete")."'><p>
 <input type='submit' name='submitdelete' value='".swSystemMessage("delete",$lang)."' />
 </p></form>
";
								}
								
								
	
	
								
							}
							
							$swParsedContent .= "</td>";
						}
						$swParsedContent .= "</tr></table>";
}




$swParsedContent .= "\n</div><!-- editzone -->";
if ($helptext != "")
$swParsedContent .= "
<br/>
<div id='help'>
".swSystemMessage("modify-help",$lang). "
</div><!-- help -->";


$swFooter = "$wiki->name, ".swSystemMessage("revision",$lang).": $wiki->revision, $wiki->user, ".swSystemMessage("date",$lang).":$wiki->timestamp, ".swSystemMessage("status",$lang).":$wiki->status";
switch($wiki->integrity())
{
	case 0: $swFooter .= ' error checksum not ok'; break;
	case 1: $swFooter .= ' checksum ok'; break;
}
if(!$name) $swFooter = "";
if (!$wiki->revision) $swFooter="";
if (count($wikis)>1) $swFooter="";

?>