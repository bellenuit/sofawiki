<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swHTMLtablefunction extends swFunction
{

	function info()
	{
	 	return "(url,n) Creates fields from HMTL table.";
	}

	
	function dowork($args)
	{
		$url = $args[1];	
		$n=0; $n = @$args[2];
		
		$html = swFileGetContents($url);
		
		// set error level
		$internalErrors = libxml_use_internal_errors(true);
		
		$doc = new DOMDocument;
		$doc->loadHTML($html);
		
		// Restore error level
libxml_use_internal_errors($internalErrors);

		
		$tables = $doc->getElementsByTagName('table');
		$result = '';
		foreach($tables as $t)
		{
			if ($n>0) { $n--; continue;}
					
			$rows = $t->getElementsByTagName('tr');
			$fields = array();
			
			foreach($rows as $r)
			{
				$cells = $r->getElementsByTagName('th');
				foreach($cells as $c)
				{
					$fields[] = trim($c->nodeValue);
				}
				
				$cells = $r->getElementsByTagName('td');
				$i = 0;
				$result .= '<nowiki><p><pre>';
				foreach($cells as $c)
				{
						if (!isset($fields[$i])) $fields[$i] = trim($c->nodeValue);
					else
						$result .= '[['.$fields[$i].'::'.trim($c->nodeValue).']] ';
					$i++;
				}
				$result .= '</pre></p></nowiki>';
			}
			
			
			return $result;
		}
	}
}

$swFunctions["htmltable"] = new swHTMLtablefunction;


?>