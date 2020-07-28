<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swDataTableFunction extends swFunction
{
	var $searcheverywhere = false;
	
	function info()
	{
	 	return "(arguments) returns a database search as table DEPRECIATED";
	}

	
	function dowork($args)
	{
		$datatablestyle='';
		$rowstack = array();
		
		global $swMaxOverallSearchTime;
		$swMaxOverallSearchTime = $swMaxOverallSearchTime*5/(count($args)+1);
		
		global $swSearchNamespaces;
		$ns = join(' ',$swSearchNamespaces);
		
		if ($this->searcheverywhere) $ns = '*';
		
		foreach($args as $line)
		{
			$line = trim($line);
			if ($line=='') continue;
			
			$fs = explode(' ',$line);
			$command = array_shift($fs);
			
			
			switch ($command)
			{
				case 'STYLE': 	if (!array_key_exists(0,$fs)) break;
								$datatablestyle = join(' ',$fs);
								break;
				case 'SELECT': 	
								array_push($rowstack,swFilter($line,$ns,'data')); break;
				case 'FIELDS': array_push($rowstack,swFilter('FIELDS',$ns,'data')); break;
				case 'ORDER': 	if (!array_key_exists(0,$fs)) break;
								$sortdirection = SORT_ASC;
								$sortmethod = SORT_STRING;
								$sortcase = '';
								foreach($fs as $item)
								{
									if ($item == 'DESC') $sortdirection = SORT_DESC;
									if ($item == 'CASE') $sortcase = 'lowercase';
									if ($item == 'RELAXED') $sortcase = 'relaxed';
									if ($item == 'NUMERIC') $sortmethod = SORT_NUMERIC;  
								}
								$r = array_pop($rowstack); 
								
								$rows2 = array();
								$column = $fs[0];
								foreach($r as $key=>$value)
								{
									if (is_array($value[$column]))
										$sortkey = join("\n",$value[$column]);
									else
										$sortkey = $value[$column];
									$sortkey .= "\n".$key;
									$elem['key'] = $key;
									$elem['value'] = $value;
									if ($sortcase == 'lowercase')
										$sortkey = strtolower($sortkey);
									elseif ($sortcase == 'relaxed') 
										$sortkey = swNameURL($sortkey);
										
									$rows2[$sortkey] = $elem;
								}
								if ($sortdirection == SORT_DESC)
									krsort($rows2, $sortmethod);
								else
									ksort($rows2, $sortmethod);
								$rows3 = array();
								foreach($rows2 as $v)
								{
									$key = $v['key'];
									$value = $v['value'];
									$rows3[$key] = $value;
								}
								
								array_push($rowstack,$rows3);
								break;
				case "SUBSTR": 	if (!array_key_exists(0,$fs)) break;
								if (!array_key_exists(1,$fs)) break;
									$column = $fs[0];
									$start = $fs[1];
								if (array_key_exists(2,$fs))
									$length = $fs[2];
								else
									$length = 0;
								
								$r = array_pop($rowstack); 
								
								//print_r($r);
								foreach($r as $key=>$value)
								{
									$elem = $value;
									
									$a = array();
									foreach($elem[$column] as $v)
									{
										if ($length>0)
											$a[] = substr($v,$start,$length);
										else
											$a[] = substr($v,$start);
										
									}
									$elem[$column] = $a;
									$r[$key] = $elem;
								}
								
								array_push($rowstack,$r);
								break;
							  
				case "OR": 		$r = array_pop($rowstack); 
								$r2 = array_pop($rowstack); 
								$rows2 = array();
								foreach($r as $key=>$value)
								{
									$rows2[$key] = $value;
								}
								foreach($r2 as $key=>$value)
								{
									$rows2[$key] = $value;
								}
								array_push($rowstack,$rows2); 
								break;
	
				case "AND": 	$r = array_pop($rowstack); 
								$r2 = array_pop($rowstack); 
								$rows2 = array();
								foreach($r as $key=>$value)
								{
									if (array_key_exists($key,$r2))
										$rows2[$key] = $value;
								}
								array_push($rowstack,$rows2);
								break;
				
				case "LIMIT":   if (!array_key_exists(0,$fs)) break;
								$start = intval($fs[0]);
								$r = array_pop($rowstack);
								if (array_key_exists(1,$fs))
									$ende = $fs[1];
								else
									$ende=count($r);
								$i=0;
								
								$rows2 = array();
								foreach($r as $key=>$value)
								{
									if ($i>=$start && $i<$start+$ende)
										$rows2[$key] = $value;
									$i++;
								}
								array_push($rowstack,$rows2);
								break;
				case "COPY":	$r = array_pop($rowstack);
								$r2 = $r;
								array_push($rowstack,$r);
								array_push($rowstack,$r2);
								break;
				case "COUNT":	$r = array_pop($rowstack);
								$c = array();
								$c['count'][] = count($r);
								$r = array();
								$r[] = $c;
								array_push($rowstack,$r);
								
								break;
				case "SUM":		if (!array_key_exists(0,$fs)) break;
								$column = $fs[0];
								$r = array_pop($rowstack); 
								$s = 0;
								foreach($r as $key=>$value)
								{
									if ($value=='') continue;
									$elem = $value;
									foreach($elem[$column] as $v)
									{
										$s += $v;
									}
								}
								$r = array();
								$c['sum'][] = $s;
								$r[] = $c;
								array_push($rowstack,$r);
								break;
				case "MAX":		if (!array_key_exists(0,$fs)) break;
								$column = $fs[0];
								$r = array_pop($rowstack); 
								unset($s);
								foreach($r as $key=>$value)
								{
									if ($value=='') continue;
									$elem = $value;
									foreach($elem[$column] as $v)
									{
										if (!isset($s)) $s = $v;
										$s = max($s,$v);
									}
								}
								$r = array();
								$c['max'][] = $s;
								$r[] = $c;
								array_push($rowstack,$r);
								break;
				case "MIN":		if (!array_key_exists(0,$fs)) break;
								$column = $fs[0];
								$r = array_pop($rowstack); 
								unset($s);
								foreach($r as $key=>$value)
								{
									if ($value=='') continue;
									$elem = $value;
									foreach($elem[$column] as $v)
									{
										if (!isset($s)) $s = $v;
										$s = min($s,$v);
									}
								}
								$r = array();
								$c['min'][] = $s;
								$r[] = $c;
								array_push($rowstack,$r);
								break;

				case "AVG":		if (!array_key_exists(0,$fs)) break;
								$column = $fs[0];
								$r = array_pop($rowstack); 
								$s = 0;
								$counter = 0;
								foreach($r as $key=>$value)
								{
									if ($value=='') continue;
									$elem = $value;
									foreach($elem[$column] as $v)
									{
										$s += $v;
										$counter++;
									}
								}
								$r = array();
								if ($counter>0)
									$c['avg'][] = $s/$counter;
								else
									$c['avg'][] = 0;
								$r[] = $c;
								array_push($rowstack,$r);
								break;
				case "TEMPLATE": if (!array_key_exists(0,$fs)) break;
								if (!array_key_exists(1,$fs)) break;
									$column = $fs[0];
									$template = $fs[1];
								
								$r = array_pop($rowstack); 
								
								//print_r($r);
								foreach($r as $key=>$value)
								{
									$elem = $value;
									if (!is_array($elem[$column])) continue;
									$a = array();
									
									
									foreach($elem[$column] as $v)
									{
										$a[] = '{{'.$template.'|'.$v.'}}';
										
									}
									$elem[$column] = $a;
									$r[$key] = $elem;
								}
								
								array_push($rowstack,$r);
								break;
				case "HEADER":	$header = str_replace('HEADER ','',$line); break;
				case "COLSEPARATOR":$colseparator = str_replace('COLSEPARATOR ','',$line);break;
				case "ROWSEPARATOR":$rowseparator = str_replace('ROWSEPARATOR ','',$line);break;
				case "FOOTER":	$footer = str_replace('FOOTER ','',$line);break;
				case "ALIAS": if (!array_key_exists(0,$fs)) break;
								if (!array_key_exists(1,$fs)) break;
									$column = array_shift($fs);
									$newcolumn = join(" ",$fs);
								
								$r = array_pop($rowstack); 
								foreach($r as $key=>$value)
								{
									$elem = $value;
									
									
									$elem[$newcolumn]=$elem[$column];
									$elem[$column]=NULL;
									unset($elem[$column]);
									$r[$key] = $elem;
								}
								
								array_push($rowstack,$r);
								break;
				case '': //ignore;
			}
		}
		
		if (count($rowstack)>0) 
			$rows = array_pop($rowstack); 
		else 
			$rows = array();
		
	
		$result = '<table class="'.$datatablestyle.'">
		<thead><tr>';
		
		$first = true;
		foreach($rows as $k=>$r)
		{
			if (!$first) continue;
			
			foreach ($r as $key=>$v)
				$result .= '<th>'.$key.'</th>';
			$first = false;
		}	
			
		$result .= '</tr></thead><tbody>';
	
		//print_r($rows);
	
		foreach ($rows as $rev=>$row)
		{
		
			if (count($row)>0)
			{
			
				$result .= '<tr>';
				foreach ($row as $k=>$v)
				{
					if (is_array($v))
						$result .=	'<td>'.join('<br>',$v).'</td>';
					else
						$result .=	'<td>'.$v.'</td>';
				}
				$result .= '</tr>';
			}
		}
		$result .= '</tbody></table>';
		
		if (isset($header) || isset($footer) || isset($colseparator) || isset($rowseparator)) 
		{
		
			$lines = array();
			foreach($rows as $rev=>$row)
			{
				$fields = array();
				foreach ($row as $k=>$v)
				{
					if (is_array($v))
						$fields[] .=	join('<br>',$v);
					else
						$fields[] .=	$v;
						
					
				}
				if (!isset($colseparator)) $colseparator = '';
				$lines[] = join($colseparator,$fields);
			}
			if (!isset($rowseparator)) $rowseparator = '';
			if (!isset($header)) $header = '';
			if (!isset($footer)) $footer = '';
			
			$result = $header.join($rowseparator,$lines).$footer;
		}
		
		return $result;
	
	}
}
	
	$swFunctions["datatable"] = new swDataTableFunction;
	
	
	?>