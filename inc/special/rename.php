<?php

if (!defined('SOFAWIKI')) die('invalid access');

if (swGetArrayValue($_REQUEST,'submitrename',false) &&  swGetArrayValue($_REQUEST,'name2',''))
{
	
	$name2 = swGetArrayValue($_REQUEST,'name2',$name);
	$name2 = swSimpleSanitize($name2);
	$name2 = str_replace("\\",'',$name2); // still needed?
	
	$wiki->name = $name;
	$wiki->name = $wiki->namewithoutlanguage();
	$wiki->user = $user->name;
	$wiki->lookup();
	
	if (count($_REQUEST['subpage'])) $swStatus = 'Renamed: ';
	
	if ($wiki->status == 'ok')
	{
		
		
		if (isset($_REQUEST['subpage']['--']))
		{
			if (swNameURL($name2) != swNameURL($wiki->name))
			{
				$wiki2 = new swWiki;
				$wiki2->name = $wiki->name;
				$wiki2->user = $user->name;
				$wiki2->content = '#REDIRECT [['.$name2.']]';
				$wiki2->insert();		
			}		
			$wiki->name = $name2;
			$wiki->insert();
			
			$swStatus .= ' '.$name.' to '.$name2;	
		}
	}
	
	foreach($swLanguages as $ln)
	{
		if (isset($_REQUEST['subpage'][$ln]))
		{
		
			$wiki2 = new swWiki;
			$wiki2->name = $name.'/'.$ln;
			$wiki2->lookup();
			if ($wiki2->status == 'ok')
			{
				$wiki2->name = $name2.'/'.$ln;
				$wiki2->user = $user->name;
				$wiki2->insert();
				$swStatus .= ' /'.$ln;	
			}
		
		}
	}

		
	$swParsedName = $name2;
	
	$wiki->parsers = $swParsers;
	$swParsedContent = $wiki->parse();
	

}
else
{

	if ($name)
	{
			
		$swParsedName = $wiki->name;
		
		$swParsedContent .= PHP_EOL.'<div id="editzone" class="editzone">';
		$swParsedContent .= PHP_EOL.'<div class="editheader">'.swSystemMessage("rename",$lang).'</div>';
		
		$swParsedContent .= PHP_EOL.'<form method="post" action="'.$wiki->link('rename').'">';
		$swParsedContent .= PHP_EOL.'<input type="submit" name="submitcancel" value="'.swSystemMessage('cancel',$lang).'" />';
		$swParsedContent .= ' <input type="submit" name="submitrename" value="'.swSystemMessage('rename',$lang).'" />';
		$swParsedContent .= PHP_EOL.'<p>'.swSystemMessage('new-name',$lang).'</p>';
		$swParsedContent .= PHP_EOL.'<input type="text" class="editzonetext" name="name2" value="'.$wiki->name.'" />';
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
		$swParsedContent .= PHP_EOL.'</p>';
		
		
		$swParsedContent .= PHP_EOL.'</form>';
				
		$swParsedContent .= PHP_EOL.'<div id="help">'.swSystemMessage("rename-help",$lang).'</div><!-- help -->';	
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
}

?>