<?php

if (!defined('SOFAWIKI')) die('invalid acces');

//print_r($_POST);

$wiki->lookup(); 
$swParsedName = $wiki->name; 

$addrows = 0; if (isset($_POST['addrows'])) $addrows = $_POST['addrows'];
$addcolumns = 0; if (isset($_POST['addcolumns'])) $addcolumns = $_POST['addcolumns'];


if (isset($_POST['fieldaddrow'])) $addrows++; 
if (isset($_POST['fieldaddcolumn'])) $addcolumns++; 



if (isset($_POST['fieldsubmitmodify']) && $_POST['fieldsubmitmodify'])
{
	$fields0 = $_POST;
	unset($fields0['fieldsubmitmodify']);
	unset($fields0['revision']);
	unset($fields0['addrows']);
	unset($fields0['addcolumns']);
	
	
	$newkey = array();
	$fields = array();
	$newfields = array();
	foreach($fields0 as $key=>$value) // find all new keys
	{
		if (stristr($key,'_newkey'))
		{
			$nk = str_replace('_newkey','',$key);
			$newkey[$nk] = $value;
			$newfields[$newkey[$nk]] = array();
			unset($fields0[$key]);
		}
		
	}
	
	
	foreach($fields0 as $key=>$value) // find all new values
	{
		if (stristr($key,'_newvalue'))
		{
			$keylist = explode(':',$key);
			$key2 = $keylist[0];
			$nk = str_replace('_newvalue','',$key2);
			$newfields[$newkey[$nk]][] = $value;
			unset($fields0[$key]);
		}
		
	}

	
	foreach($fields0 as $key=>$value)
	{
		$keylist = explode(':',$key);
		if ($value != '')
				$fields[$keylist[0]][] = $value;
	}
	foreach($newfields as $key=>$value) // add new fields at end of list
		$fields[$key] = $value;
		
	$s = $wiki->content;
	$s = swReplaceFields($s,$fields,'REMOVE_OLD');
	$wiki->content = $s;
	$wiki->insert();
	
	$addrows = $addcolumns = 0;
	
	$swStatus = swSystemMessage('fields-saved',$lang).' '.$wiki->name; 

	
}





if ($wiki->error)
{
	$swError = swSystemMessage($wiki->error,$lang);
}
else
$swError = '';
if ($wiki->status == 'deleted' || $wiki->status == 'delete')
{
	header('HTTP/1.0 404 Not Found');
	$swError = swSystemMessage('this-page-has-been-deleted-error',$lang);
}
else
{
	$wiki->parsers = $swParsers;
	$wiki->internalfields = swGetAllFields($wiki->content);
	
	//count columns
	$maxcols = 0;
	foreach($wiki->internalfields as $key=>$valuelist)
	{
		if (substr($key,0,1) == '_') continue;
		$maxcols = max($maxcols,count($valuelist));
	}
	
	$minwidth = ($maxcols+$addcolumns) * 100;
	
	$swParsedContent .= PHP_EOL.'<div id="editzone" class="editzone actionfields" style="min-width: '.$minwidth.'px">';
	$swParsedContent .= PHP_EOL.'<div class="editheader">'.swSystemMessage("fields",$lang).'</div>';
	
	$swParsedContent .= PHP_EOL.'<form method="post" action="index.php?action=fields">';
	$swParsedContent .= PHP_EOL.'<input type="hidden" name="revision" value="'.$wiki->revision.'">';
	$swParsedContent .= PHP_EOL.'<input type="hidden" name="addrows" value="'.$addrows.'">';
	$swParsedContent .= PHP_EOL.'<input type="hidden" name="addcolumns" value="'.$addcolumns.'">';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="fieldaddcolumn" value="'.swSystemMessage('add-column',$lang).'">';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="fieldaddrow" value="'.swSystemMessage('add-row',$lang).'">';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="fieldeditheader" value="'.swSystemMessage('edit-header',$lang).'">';
	$swParsedContent .= PHP_EOL.'<input type="submit" name="fieldsubmitmodify" value="'.swSystemMessage('save',$lang).'">';
	$swParsedContent .= PHP_EOL.'<p><table class="fieldseditor">';
		
		$swParsedContent .= PHP_EOL.' <tr>';
		foreach($wiki->internalfields as $key=>$valuelist)
		{
			if (substr($key,0,1) == '_') continue;
			
			if (isset($_POST['fieldeditheader']))
				$swParsedContent .= PHP_EOL.'   <th class="key"><input type="text" name="_newkey'.$key.'" value="'.$key.'"></th>';
			else
				$swParsedContent .= PHP_EOL.'  <th class="key"><input type="text" name="_readonly'.$key.'" value="'.$key.'" readonly></th>';
			$maxcols = max($maxcols,count($valuelist));
		}
		for ($j=1;$j<=$addcolumns;$j++)
			$swParsedContent .= PHP_EOL.'   <th class="key"><input type="text" name="_newkey'.$j.'" value=""></th>';
		$swParsedContent .= PHP_EOL.' </tr>';
		
		
		for($i=0;$i<$maxcols+$addrows;$i++)
		{
			$j=0;
			$swParsedContent .= PHP_EOL.' <tr>';
			foreach($wiki->internalfields as $key=>$valuelist)
			{
				if (substr($key,0,1) == '_') continue;

				$j++;
				if (isset($_POST['fieldeditheader']))
					$key = '_newvalue'.$key;
				
				
				$value = @$valuelist[$i];
				if (!stristr($value,'\n') && !stristr($value,'\r'))
					$t = '<input type="text" name="'.$key.':'.$i.'" value="'.htmlspecialchars($value).'">';
				else
					$t = '<textarea name="'.$key.':'.$i.'">'.htmlspecialchars($value).'</textarea>';
				$swParsedContent .= PHP_EOL.'  <td class="value">'.$t.'</td>';
			}
			for ($j=1;$j<=$addcolumns;$j++)
				$swParsedContent .= PHP_EOL.'   <td class="value"><input type="text" name="_newvalue'.$j.':'.$i.'" value=""></td>';
	
			$swParsedContent .= PHP_EOL.' </tr>';
		}
	
			
	
	
	$swParsedContent .= PHP_EOL.'</table>';
	$swParsedContent .= PHP_EOL.'</form>';
	
	$swParsedContent .= PHP_EOL.'<div id="help">'.swSystemMessage("fields-help",$lang).'</div><!-- help -->';	
	$swParsedContent .= PHP_EOL.'</div><!-- editzone -->';


}				

?>