<?php
	

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedContent = '';

$wiki->name = $name;
if (trim($name)=='') $swError = swSystemMessage('empty-name',$lang);
if (!swValidate($name2,"\"\\<>[]{}*")) $swError = swSystemMessage('invalid-characters',$lang).' (name2)';
if (!swValidate($name,"\"\\<>[]{}*")) $swError = swSystemMessage('invalid-characters',$lang).' (name)';

if ($wiki->status == '' && ! $user->hasright('create', $wiki->name))
{
	$swError = swSystemMessage('no-access-error',$lang);
}
elseif ($user->hasright('modify', $wiki->name))
{
	// validate globals
	
	if (!swGetArrayValue($_POST,'submitmodify',false)&&!swGetArrayValue($_POST,'submitmodifymulti',false)
		&&!swGetArrayValue($_POST,'submiteditor',false))
	{
		$swError = swSystemMessage('not-modify-without-post',$lang);
	}
	
	if (!isset($_POST['currentrevision']) || !is_array($_POST['currentrevision']) || !is_array($content))
	{
		print_r($_POST);
		$swError = swSystemMessage('revision-or-content-not-array',$lang);
	}
	else
	{
		foreach($_POST['currentrevision'] as $l=>$rev)
		{
			$subname = $wiki->namewithoutlanguage();
			if ($l != '--') $subname .= '/'.$l;
			else {
				if (strstr($name,'/') && count($l)==1 ) $subname = $wiki->name; // submitted single revision with language.
			}
			
			
			// check for editing conflict
			if ($rev > 0 || true)
			{
				$w2 = new swWiki();
				$w2->name = $subname;
				$w2->lookup();
				$r = $w2->revision;
				if ($r<>0 && $r > $rev && $w2->status != 'deleted')
				{
					if ($rev > 0)
						$swError .= swSystemMessage('editing-conflict',$lang).' name: '.$subname;
					else
						$swError .= swSystemMessage('editing-new-conflict',$lang).' name: '.$subname;
					// set the old current content to the wiki
					$currentwiki =  new swWiki;
					$currentwiki->revision = $r;
					$currentwiki->lookup();
					
					
					$conflictwiki = new swWiki;
					$conflictwiki->content = $content[$l];
					
					$revision0 = $rev;
					$rev = $r;
					
					
					include_once 'inc/special/editconflict.php';
				}
			}
			
			
			if (!$swError)
			{
				$w2 = new swWiki();
				$w2->name = $subname;
				$w2->lookup();

				$w2->user = $user->name;
				
				if ($action=='modifyeditor')
				{		
					swInsertFromEditorTemplate($_POST,$w2);
					
				}
				else
				{
					$oldcontent = $w2->content;
					
					$w2->content = str_replace("\\",'',$content[$l]);
					$w2->comment = str_replace("\\",'',$comment);
					
					if (!$w2->revision || $w2->content !== $oldcontent)
					{
						$w2->insert();
						$swStatus .=  'Saved: '.$w2->name.'. ';
						$wiki = $w2;
					}
					else
					{
						$swStatus .=  'No changes: '.$w2->name.'. ';
					}
				}	
			}			
		
		}
		
		$swParsedName = $wiki->name;
			
		$wiki->lookup();
		$wiki->parsers = $swParsers;
		$swParsedContent .= $wiki->parse();
			

	}
}
else
{
	$swError = swSystemMessage('no-access-error',$lang);
}


?>