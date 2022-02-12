<?php

if (!defined('SOFAWIKI')) die('invalid access');

if (swGetArrayValue($_REQUEST,'submitdelete',false) || swGetArrayValue($_REQUEST,'submitdeletewithfile',false))
{
	if (!swGetArrayValue($_POST,'submitdelete',false) && !swGetArrayValue($_POST,'submitdeletewithfile',false))
	{
		$swError = swSystemMessage('not-delete-without-post',$lang);
		$wiki->parsers = $swParsers;
		$swParsedContent = $wiki->parse();		
	}
	else
	{
	
		$wiki->name = $name;
		$wiki->name = $wiki->namewithoutlanguage();
		$wiki->user = $user->name;
		$wiki->lookup();
		
		$swParsedName = $name2;
		
		$wiki->parsers = $swParsers;
		$swParsedContent = $wiki->parse();
		
		$name = $wiki->namewithoutlanguage();
		$wiki->name = $name;
		$wiki->user = $user->name;
		$wiki->lookup();
		if ($wiki->status == 'ok')
		{
			$swStatus = 'Deleted: '.$wiki->name;
			
			if (swGetArrayValue($_POST,'submitdeletewithfile',false))
			{
				$path = $swRoot.'/site/files/'.str_replace('Image:','',$wiki->name);
				@unlink($path);
				$swStatus = 'Deleted with file: '.$wiki->name;
			}

			$wiki->delete();
			$swParsedName = '';

		}	
		
		// do it again for all subpages
		
		foreach($swLanguages as $ln)
		{
			
			$wiki2 = new swWiki;
			$wiki2->name = $name.'/'.$ln;
			$wiki->user = $user->name;
			$wiki2->lookup();
			if ($wiki2->revision > 0 && $wiki->status !== 'deleted')
			{
				$wiki2->delete();
				$swStatus .=' /'.$ln;
			}
		}
	}
	
	$swParsedName = '';
	$swParsedContent = '';
}


elseif ($name)
{
		
	$swParsedName = $wiki->name;
	
	$swParsedContent .= PHP_EOL.'<div id="editzone" class="editzone">';
	$swParsedContent .= PHP_EOL.'<div class="editheader">'.swSystemMessage("delete",$lang).'</div>';

	
	$swParsedContent .= PHP_EOL.'<form method="post" action="index.php">';
	$swParsedContent .= PHP_EOL.'<input type="hidden" name="name" value="'.$wiki->name.'" />';
	$swParsedContent .= PHP_EOL.'<input type="hidden" name="action" value="delete" />';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="submitcancel" value="'.swSystemMessage('cancel',$lang).'" />';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="submitdelete" value="'.swSystemMessage('delete',$lang).'" style="color:red" />';
	
	if ($wiki->wikinamespace() == 'Image') $swParsedContent .= PHP_EOL.'<input type="submit" name="submitdeletewithfile" value="'.swSystemMessage('delete-with-file',$lang).'" style="color:red" />';

	
	$swParsedContent .= PHP_EOL.'<p></p>';
	$swParsedContent .= PHP_EOL.'<p>';
	
	if ($wiki->status == 'ok')
	{
		$swParsedContent .= '<span style="white-space:nowrap;"><input type="checkbox" name="subpage[--]" checked />'.swSystemMessage('main-page',$lang).'</span>';
	}
	foreach($swLanguages as $ln)
	{
		$wiki2 = new swWiki;
		$wiki2->name = $name.'/'.$ln;
		$wiki2->lookup();
		if ($wiki2->status == 'ok')
		{
			$swParsedContent .= '<span style="white-space:nowrap;"><input type="checkbox" name="subpage['.$ln.']" checked /> '.$ln.'</span>';
		}
	}

	$swParsedContent .= '</p>';
	
	$swParsedContent .= PHP_EOL.'</form>';
	
	$swParsedContent .= PHP_EOL.'<div id="help">'.swSystemMessage("delete-help",$lang).'</div><!-- help -->';	
	$swParsedContent .= PHP_EOL.'</div><!-- editzone -->';
	
	
	$wiki->parsers = $swParsers;
	$swParsedContent .= $wiki->parse();							
			
	$swFooter = $wiki->name.', '.swSystemMessage('revision',$lang).': '.$wiki->revision.', '.$wiki->user.', '.swSystemMessage('date',$lang).':'.$wiki->timestamp.', '.swSystemMessage('status',$lang).':'.$wiki->status;
	
	switch($wiki->integrity())
	{
		case 0: $swFooter .= ' error checksum not ok'; break;
		case 1: $swFooter .= ' checksum ok'; break;
	}
	if(!$name) $swFooter = '';
	if (!$wiki->revision) $swFooter='';
	
	
}


?>