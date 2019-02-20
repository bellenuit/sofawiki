<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swFieldsFunction extends swFunction
{

	function info()
	{
	 	return "() shows fields in table when not in fields mode";
	}

	
	function dowork($args)
	{
		global $wiki;
		global $action;
		if ($action == 'fields') return '';
		
			//count columns
	$maxcols = 0;
	foreach($wiki->internalfields as $key=>$valuelist)
	{
		if ($key == '_link') continue;
		if ($key == '_template') continue;
		if ($key == '_category') continue;
		$maxcols = max($maxcols,count($valuelist));
	}

		
		
		$s = '';
		$s .= '<table class="fieldseditor blanktable">';
		foreach($wiki->internalfields as $key=>$valuelist)
		{
			if ($key == '_link') continue;
			if ($key == '_template') continue;
			if ($key == '_category') continue;
			
			$s .= '<th>'.$key.'</th>';
			$maxcols = max($maxcols,count($valuelist));
		}
		$s .= '</tr>';
		
		
		for($i=0;$i<$maxcols;$i++)
		{
			$j=0;
			$s .= '<tr>';
			foreach($wiki->internalfields as $key=>$valuelist)
			{
				if ($key == '_link') continue;
				if ($key == '_template') continue;
			    if ($key == '_category') continue;

				$j++;
				if (isset($_POST['fieldeditheader']))
					$key = '_newvalue'.$key;
				
				
				$value = @$valuelist[$i];
				$s .= '<td class="value">'.$value.'</td>';
			}
	
			$s .= '</tr>';
		}
	
			
	
	
	$s .= PHP_EOL.'</table>';
	return $s;

		
	}

}

$swFunctions["fields"] = new swFieldsFunction;


?>