<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swStyleParser extends swParser
{

	var $domakepretty = true;
	
	function info()
	{
	 	return "Applies wikitext styles";
	}


	function dowork(&$wiki)
	{
		
		global $swOldStyle;
		if ($swOldStyle)
		{
			$this->doworkOld($wiki);
			return;
		}
		
		$s = $wiki->parsedContent; //echo $s;
		
		echotime('parse style');
		
		// keep only \n
		$s = str_replace("\r\n","\n",$s);
		$s = str_replace("\n\r","\n",$s);
		$s = str_replace("\r","\n",$s);
		$s = str_replace("\t","",$s);
		
		$s = "\n".$s."\n";
		
		$s = preg_replace('/^====(.*)====/Um',"<h4><a id='$1'>$1</a></h4>",$s);
		$s = preg_replace('/^===(.*)===/Um',"<h3><a id='$1'>$1</a></h3>",$s);
		$s = preg_replace('/^==(.*)==/Um',"<h2><a id='$1'>$1</a></h2>",$s);
		
		// lists
		$s = preg_replace('/^\*\*\*(.*)(\n)/Um', "<ul><ul><ul><li>$1</li></ul></ul></ul>$2", $s);
        $s = preg_replace('/^\*\*(.*)(\n)/Um', "<ul><ul><li>$1</li></ul></ul>$2", $s);
        $s = preg_replace('/^\*(.*)(\n)/Um', "<ul><li>$1</li></ul>$2", $s);
        
        while (stristr($s,"</ul>\n<ul>")) $s = str_replace("</ul>\n<ul>",'',$s);
        while (stristr($s,"</ul><ul>")) $s = str_replace("</ul><ul>",'',$s);

 		// numbered lists
		$s = preg_replace('/^\#\#\#(.*)(\n)/Um', "<ol><ol><ol><li>$1</li></ol></ol></ol>$2", $s);
        $s = preg_replace('/^\#\#(.*)(\n)/Um', "<ol><ol><li>$1</li></ol></ol>$2", $s);
        $s = preg_replace('/^\#(.*)(\n)/Um', "<ol><li>$1</li></ol>$2", $s);

        while (stristr($s,"</ol>\n<ol>")) $s = str_replace("</ol>\n<ol>",'',$s);
		while (stristr($s,"</ol><ol>")) $s = str_replace("</ol><ol>",'',$s);


		// blockquote
		$s = preg_replace('/^\:\:\:(.*)(\n)/Um', "<blockquote><blockquote><blockquote>$1</blockquote></blockquote></blockquote>$2", $s);
        $s = preg_replace('/^\:\:(.*)(\n)/Um', "<blockquote><blockquote>$1</blockquote></blockquote>$2", $s);
        $s = preg_replace('/^\:(.*)(\n)/Um', "<blockquote>$1</blockquote>$2", $s);
		
		$s = str_replace('<blockquote> ','<blockquote>',$s);
		
		while (stristr($s,"</blockquote>\n<blockquote><blockquote>")) $s = str_replace("</blockquote>\n<blockquote><blockquote>",'<blockquote>',$s);
		while (stristr($s,"</blockquote><blockquote><blockquote>")) $s = str_replace("</blockquote><blockquote><blockquote>",'<blockquote>',$s);
		while (stristr($s,"</blockquote></blockquote>\n<blockquote>")) $s = str_replace("</blockquote></blockquote>\n<blockquote>",'</blockquote>',$s);
		while (stristr($s,"</blockquote></blockquote><blockquote>")) $s = str_replace("</blockquote></blockquote><blockquote>",'</blockquote>',$s);
		while (stristr($s,"</blockquote>\n<blockquote>")) $s = str_replace("</blockquote>\n<blockquote>",'<br>',$s);
		while (stristr($s,"</blockquote><blockquote>")) $s = str_replace("</blockquote><blockquote>",'<br>',$s);
		
		// hr
		$s = str_replace("\n----\n","\n<hr>\n",$s);	
		
		
		
		// preserve div
		$s = str_replace('<div',"\n<div",$s);
		$s = str_replace('</div>', "\n</div>\n", $s); // if div multiline

		// preserve <:s>
		$s = str_replace('<nop>',"\n<nop>",$s);		

		$lines = explode("\n",$s);
		$s = '';
		$state = '';
		$tablerow = '';
		//echotime('parse lines '.count($lines));
		
		//print_r($lines);
		
		global $swWikiTextPre;
		// $swWikiTextPre = true;
		
		foreach ($lines as $line)
		{
						
			if (trim($line) == '') 
			{  
				if ($state == 'p')
				{
					$s .= '</p>';
					$state = 'waitp';
				}
				else
				{
					$state = 'waitp';
				}
				continue;
			}
			
			
					
			switch (substr($line,0,3))
			{
				case '<h2': 
				case '<h3': 
				case '<h4':
				case '<ul': 
				case '<ol': 
				case '<bl':
				case '<hr': 
				case '<di': 
				case '<pr':
				case '<ta':
				case '<tr':
				case '<th':
				case '<td': 
							  if ($state == 'p') $s .= '</p>';
							  if ($tablerow)
							  {
								  $tablerow = substr($tablerow,0,-5).$line.substr($tablerow,-5); // </td>
							  }
							  else
							  {
							 	 $s .= $line;
							  }
							  $state = '';
							  break;
				
				case '<no':   $s .= '<p>'.$line;
							  $state = 'p';	
							  break;
				
				
				
				default: 	  switch (substr($line,0,2))
							  {
				
									case '{|':   $s.= '<table '.substr($line,2).'>'; $tablerow = ''; 
												  break;
									case '|+':   $s.= '<caption>'.substr($line,2).'</caption>'; 
												  break;
									case '|}':   if ($tablerow) $s.= $tablerow .'</tr>';
												  $s.= '</table>';
												  if (substr($line,2))
												  {
													  $s.='<p>'.substr($line,2);
													  $state = 'p';
												  }
												  $tablerow = '';
												  break;
									case '|-':   if ($tablerow) $s.= $tablerow .'</tr>'; 
												  $tablerow = '<tr '.substr($line,2).'>';
												  break;
													
									default: 	switch (substr($line,0,1))
												{
												
												case '!' :
									
															if (!$tablerow) $tablerow = '<tr>';
															$cells = explode(' !! ', substr($line,2));
															foreach($cells as $cell)
															{
																$t = strpos($cell,' | ');
																if ($t>0) 
																{
																	$c = substr($cell,$t+3);
																	if (!$c) $c = '&nbsp;';
																	$tablerow .= '<th '.substr($cell,0,$t).'>'.$c.' </th>';
																}
																else
																{
																	if (!$cell) $cell = '&nbsp;';
																	$tablerow .= '<th>'.$cell.' </th>';
																}
															}
															break;					
												
												case '|' :
												
															if (!$tablerow) $tablerow = '<tr>';
															$cells = explode(' || ', substr($line,2));
															foreach($cells as $cell)
															{
																$t = strpos($cell,' | ');
																if ($t>0) 
																{
																	$c = substr($cell,$t+3);
																	if (!$c) $c = '&nbsp;';
																	$tablerow .= '<td '.substr($cell,0,$t).'>'.$c.' </td>';
																}
																else
																{
																	$tablerow .= '<td>'.$cell.'</td>';
																}
															}
															break;
															
												default: 	
												
															if ($tablerow)
															{
																// continue last cell
																//echo 'tablerow '.$line;
																if (substr($line,0,1) == '<')
																{										
																	$tablerow = substr($tablerow,0,-5).$line.substr($tablerow,-5); // </td>
								
																}
																else
																{
																	$tablerow = substr($tablerow,0,-5).'<br>'.$line.substr($tablerow,-5); // </td>
																}
															}
															elseif (substr($line,0,1)==' ' && $swWikiTextPre)
															{
																if ($state == 'p')
																{
																	$s .= '</p>';
																	$state = '';
																} 
																
																$s .= '<pre>'.substr($line,1).'</pre>';
															}
															elseif ($state == 'p')
															{
																$s .= '<br>'.$line;
															}
															elseif (substr($line,0,1) == '<') 
															{
																if ($state == 'p')
																{
																	$s .= '</p><p>'.$line;
																	$state = '';
																}
																elseif(trim(preg_replace('/<.*?>/', '', $line)) =='') // only single tag on line
																{
																	$s .= $line;
																	$state = '';
																}
																else
																{
																	$s .= '<p>'.$line;
																	$state = 'p';
																}	
															}
															elseif (trim($line) != '') 
															{
																	if ($state == 'waitp')
																	{
																		$s .= '<p>'.$line;
																		$state = 'p';
																	}
																	else
																	{
																		$s .= '<br>'.$line;
																		$state = '';
																	}
															}	
															
										}					
								}
			}
			//$s.="(STATE $state)";
			
		}	
		
		//echo $s;
		
		// bugs
		$s = str_replace("<p><div","<div",$s);
		$s = str_replace("</div></p>","</div>",$s);
		$s = str_replace('<td></td>','<td>&nbsp;</td>',$s);
		$s = str_replace('<br></div>',"</div>",$s);
		$s = str_replace('</div><br>',"</div>",$s);
		$s = str_replace('<br><div>',"<div>",$s);
		$s = str_replace('<div><br>',"<div>",$s);

				
		// template block preserve
		$s = str_replace('<nop>','',$s);
		$s = str_replace('</nop>','',$s);

		
		// bold and italics
		$s = str_replace("''''''","",$s);
        $s = preg_replace("/'''''(.*)'''''/Um", '<b><i>$1</i></b>', $s);
		$s = str_replace("''''","",$s);
        $s = preg_replace("/'''(.*)'''/Um", '<b>$1</b>', $s);
        $s = preg_replace("/''(.*)''/Um", '<i>$1</i>', $s);
        
        // detect pre
        //$s = preg_replace("/<p> (.*)<\/p>/Um", '<pre>$1</pre>', $s);
		// br inside pre
		
        
		$s = swUnescape($s);  		
		
		if ($this->domakepretty)
		{
		
       		 // make pretty
	        $s = str_replace("<ol>","\n<ol>",$s);
	        $s = str_replace("</ul>","</ul>\n",$s);
	        $s = str_replace("</ol>","</ol>\n",$s);
	       // $s = str_replace("<br>","<br>\n",$s);
	        $s = str_replace("<hr>","\n<hr>\n",$s);
	        $s = str_replace("<h1>","\n<h1>",$s);
	        $s = str_replace("<h2>","\n<h2>",$s);
	        $s = str_replace("<h3>","\n<h3>",$s);
	        $s = str_replace("<h4>","\n<h4>",$s);
	        $s = str_replace("</h1>","</h1>\n",$s);
	        $s = str_replace("</h2>","</h2>\n",$s);
	        $s = str_replace("</h3>","</h3>\n",$s);
	        $s = str_replace("</h4>","</h4>\n",$s);
	        
	        
			$s = str_replace("<br>\n</pre>","</pre>\n",$s); 
			$s = str_replace("</pre>\n<pre>","\n",$s);
			$s = str_replace("</pre><pre>","\n",$s);
			
			$s = str_replace("</pre>","</pre>\n",$s);
						
	        $s = str_replace("</p>","</p>\n",$s);
	        
	
			$s = str_replace("<table","\n\n<table",$s);
			$s = str_replace("</table>","</table>\n",$s);
		
			$s = str_replace("</ul>","\n</ul>",$s);
	        $s = str_replace("</ol>","\n</ol>",$s);
	        $s = str_replace("</li><ul>","</li>\n<ul>",$s);
			$s = str_replace("</li><ol>","</li>\n<ol>",$s);
	        $s = str_replace("<li>","\n <li>",$s);
			$s = str_replace("<caption>","\n <caption>",$s);
			
			$s = str_replace('<blockquote>',"\n<blockquote>",$s);
		
		
			$s = str_replace("<tr","\n  <tr",$s);
			$s = str_replace("</tr>","\n  </tr>",$s);
			$s = str_replace("<td","\n   <td",$s); // can be styled
			$s = str_replace("<th","\n   <th",$s); // can be styled
			$s = str_replace("<tbody","\n <tbody",$s); // can be styled
	    	$s = str_replace("</tbody>","\n </tbody>",$s); 
	    	$s = str_replace("</table>","\n</table>",$s);
	    	
	    	// auto close divs
			$divopen = substr_count($s,'<div');
			$divclose = substr_count($s,'</div>');
			while ($divopen > $divclose)
			{
				$s .= '</div>'; $divclose++;
			}

    	
    	}
		
		$wiki->parsedContent = trim($s);
		
	}
	
	
	function doworkOld(&$wiki)
	{

		$s = $wiki->parsedContent;

		// keep only \n
		$s = str_replace("\r\n","\n",$s);
		$s = str_replace("\n\r","\n",$s);
		$s = str_replace("\r","\n",$s);
		$s = str_replace("\t","",$s);
		
		$s = "\n".$s."\n";
		
		// table parser
		
		preg_match_all('/\n{\|(.*)\n\|}/Us', $s, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $match)
		{
			$lines = explode("\n",$match[0]);
			// discount first
			unset($lines[0]);
			
			$rowcount = 0;
			$tabletext= "";
			$captiontext= "";
			$tablestyle = "";
			$currentrow = "";
			$currentrowstyle = "";
			$currentcell = "";
			$currentcelltag = "";
			$currentcellstyle = "";
						
			foreach ($lines as $line)
			{
				$first = substr($line,0,1);
				$second = substr($line,0,2);
				
				switch ($second)
				{
					case "{|":  $tablestyle = substr($line,2); 
								break;
					case "|+":  $captiontext = "<caption>".substr($line,3)."</caption>"; 
								break;
					case "|-": 	if ($currentcell !="")
									$currentrow .= "<$currentcelltag$currentcellstyle>$currentcell</$currentcelltag>";
								if ($currentrow !="")
									$tabletext .= "<tr$currentrowstyle>$currentrow</tr>";
								$currentrow = "";
								$currentcell = "";
								$currentrowstyle = substr($line,2);
								break;
					
					default:
							switch	($first)
							{
								case "|":	
											$cells = explode(" || ", substr($line,2));
											foreach ($cells as $cell)
											{
												if ($currentcell !="")
													$currentrow .= "<$currentcelltag$currentcellstyle>$currentcell</$currentcelltag>";
												
												$currentcelltag = "td";
												$t = strpos($cell," | ");
												if ($t>0)
												{
													$currentcellstyle = " ".substr($cell,0,$t);
													$currentcell = substr($cell,$t+3);
												}
												else
												{
													$currentcell = $cell;
													$currentcellstyle = "";
												}	
											
											}
											
											break;
								case "!":	if ($currentcell != "") 
												$currentrow .= "<$currentcelltag$currentcellstyle>$currentcell</$currentcelltag>";
											$currentcell = str_replace(" !! ","</th><th>",substr($line,2));
											$currentcelltag = "th";
											break;
							 
								default :  $currentcell .= "\n$line";
							
							}
				}
			}
			if ($currentcell !="")
				$currentrow .= "<$currentcelltag$currentcellstyle>$currentcell</$currentcelltag>";
			if ($currentrow != "")
				$tabletext .= "<tr$currentrowstyle>$currentrow</tr>";
			$tabletext = "<table$tablestyle>$captiontext$tabletext</table>";			
			
			$s = str_replace($match,$tabletext,$s);
		}
			
		$s = str_replace("<td>","<td>\n",$s);	
		$s = str_replace("<th>","<th>\n",$s);	
		
			
		
	
		// headers     	
		// id must use single token
		while (preg_match('/\n====(.*)====/U', $s, $matches))
		{
			$s = str_replace($matches[0],"\n<h4><a id='".$matches[1]."'>".$matches[1]."</a></h4>",$s);
		}
		while (preg_match('/\n===(.*)===/U', $s, $matches))
		{
			$s = str_replace($matches[0],"\n<h3><a id='".str_replace(" ","_",$matches[1])."'>".$matches[1]."</a></h3>",$s);
		}
		while (preg_match('/\n==(.*)==/U', $s, $matches))
		{
			$s = str_replace($matches[0],"\n<h2><a id='".str_replace(" ","_",$matches[1])."'>".$matches[1]."</a></h2>",$s);
		}
		
		
		
		// lists
		$s = preg_replace('/^\*\*\*(.*)(\n)/Um', "<ul><ul><ul><li>$1</li></ul></ul></ul>$2", $s);
        $s = preg_replace('/^\*\*(.*)(\n)/Um', "<ul><ul><li>$1</li></ul></ul>$2", $s);
        $s = preg_replace('/^\*(.*)(\n)/Um', "<ul><li>$1</li></ul>$2", $s);

		// numbered lists
		$s = preg_replace('/^\#\#\#(.*)(\n)/Um', "<ol><ol><ol><li>$1</li></ol></ol></ol>$2", $s);
        $s = preg_replace('/^\#\#(.*)(\n)/Um', "<ol><ol><li>$1</li></ol></ol>$2", $s);
        $s = preg_replace('/^\#(.*)(\n)/Um', "<ol><li>$1</li></ol>$2", $s);


		$s = str_replace("<li> ","<li>",$s);

		// blockquote
		$s = preg_replace('/^\:\:\:(.*)(\n)/Um', "<blockquote><blockquote><blockquote>$1</blockquote></blockquote></blockquote>$2", $s);
        $s = preg_replace('/^\:\:(.*)(\n)/Um', "<blockquote><blockquote>$1</blockquote></blockquote>$2", $s);
        $s = preg_replace('/^\:(.*)(\n)/Um', "<blockquote>$1</blockquote>$2", $s);


		// hr
		$s = str_replace("\n----\n","<hr/>",$s);

		// paragraphs
		$s = str_replace("\n","<br/>",$s);
					

		

		// correct parser conflicts
		$s = str_replace("</h2><br/><br/>","</h2>",$s);
		$s = str_replace("</h3><br/><br/>","</h3>",$s);
		$s = str_replace("</h4><br/><br/>","</h4>",$s);
		$s = str_replace("</h2><br/>","</h2>",$s);
		$s = str_replace("</h3><br/>","</h3>",$s);
		$s = str_replace("</h4><br/>","</h4>",$s);
		$s = str_replace("</ul><br/><ul>","",$s);
		$s = str_replace("</ul><br/>", "</ul>",$s);
		$s = str_replace("</ul><br/>", "</ul>",$s);
		$s = str_replace("</ol><br/><ol>","",$s);
		$s = str_replace("</ol><br/>", "</ol>",$s);
		$s = str_replace("</ol><br/>", "</ol>",$s);
		$s = str_replace("</blockquote><br/><blockquote>","</blockquote><blockquote>",$s);
		$s = str_replace("</blockquote><br/><a name","</blockquote><a name",$s);
		$s = str_replace("</ol><br/>", "</ol>",$s);
		$s = str_replace("<br/><ul/>", "<ul>",$s);
		$s = str_replace("<br/><ol/>", "<ol>",$s);
		$s = str_replace("</li></ol><ol><li>", "</li><li>",$s);
		$s = str_replace("</li></ul><ul><li>", "</li><li>",$s);
		
		$s = preg_replace('#(<div.*?>)<br/>#', '$1', $s); // lines with only a div tag do not create BR.
		$s = str_replace('</div><br/>', '</div>', $s);
		
		$s = str_replace("<b></b>","",$s);
		//$s = str_replace("<br/> <a","<br/><br/> <a",$s);
		
		while (stristr($s,"<br/><br/><br/>")) $s = str_replace("<br/><br/><br/>","<br/><br/>",$s);
		while (substr($s,0,5)=="<br/>") $s = substr($s,5);  // \n is only 1 character!
		while (substr($s,-5,5)=="<br/>") $s = substr($s,0,-5);  // \n is only 1 character!
		while (stristr($s,"<br/><h4>")) $s = str_replace("<br/><h4>","<h4>",$s);
		
		
		$s = str_replace("<td><br/>","<td>",$s);
		$s = str_replace("<th><br/>","<th>",$s);
		$s = str_replace("</div><br/>","</div>",$s);
		$s = str_replace("</div><br>","</div>",$s);
		
		// template block preserve
		$s = str_replace('<nop>','',$s);
		$s = str_replace('</nop>','',$s);
		
		
		// bold and italics
		$s = str_replace("''''''","",$s);
        $s = preg_replace("/'''''(.*)'''''/Um", '<b><i>$1</i></b>', $s);
		$s = str_replace("''''","",$s);
        $s = preg_replace("/'''(.*)'''/Um", '<b>$1</b>', $s);
        $s = preg_replace("/''(.*)''/Um", '<i>$1</i>', $s);
        
        
		$s = swUnescape($s);  		
        
        // make pretty
        $s = str_replace("<a name","\n<a name",$s);
        $s = str_replace("<ul>","\n<ul>",$s);
        $s = str_replace("<ol>","\n<ol>",$s);
        $s = str_replace("</ul>","\n</ul>\n",$s);
        $s = str_replace("</ol>","\n</ol>\n",$s);
        $s = str_replace("<li>","\n<li>",$s);
        $s = str_replace("<br/>","<br/>\n",$s);
        $s = str_replace("<hr/>","<hr/>\n",$s);
        $s = str_replace("<h1>","\n<h1>",$s);
        $s = str_replace("<h2>","\n<h2>",$s);
        $s = str_replace("<h3>","\n<h3>",$s);
        $s = str_replace("<h4>","\n<h4>",$s);
        $s = str_replace("</h1>","</h1>\n",$s);
        $s = str_replace("</h2>","</h2>\n",$s);
        $s = str_replace("</h3>","</h3>\n",$s);
        $s = str_replace("</h4>","</h4>\n",$s);
		$s = str_replace("<br/>\n</pre>","</pre>\n",$s);
		$s = str_replace("</pre>\n<pre>","\n",$s);
		$s = str_replace("</pre><pre>","\n",$s);
		
		$s = str_replace("<table","\n<table",$s);
		$s = str_replace("<caption>","\n <caption>",$s);
		$s = str_replace("</table>","\n</table>\n",$s);
		$s = str_replace("<tr","\n <tr",$s);
		$s = str_replace("</tr>","\n </tr>",$s);
		$s = str_replace("<td>","\n  <td>",$s);
		$s = str_replace("<th>","\n  <th>",$s);
    	
				
		
		$wiki->parsedContent = $s;
		
	}

}
$swParsers["style"] = new swStyleParser;

function swCleanParagraphs($s) 
{ 
	$s = str_replace('<p>','',$s);
	$s = str_replace('</p>','',$s); 
	return $s;
}



?>