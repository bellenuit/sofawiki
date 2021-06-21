<?php

if (!defined("SOFAWIKI")) die("invalid acces");

// utf8




// from http://www.php.net/manual/fr/function.array-multisort.php 
// modified

// modifiers: ! DESC  # NUMERIC
function array_sort_func($a,$b=NULL) { 
   static $keys; 
   if($b===NULL) return $keys=$a; 
      
   foreach($keys as $k) 
   { 
      $numeric = false;
      $desc = false;
      $familyname = false;
      $url = false;
      $nocase = false;
      if (stristr($k,'#')) { $numeric = true; $k = str_replace('#','',$k);}
      if (stristr($k,'!')) { $desc = true;  $k = str_replace('!','',$k) ;}
      if (stristr($k,'?')) { $familyname = true;  $k = str_replace('?','',$k) ;}
      if (stristr($k,'@')) { $url = true;  $k = str_replace('@','',$k) ;}
      if (stristr($k,'&')) { $nocase = true;  $k = str_replace('&','',$k) ;}
      
      if ($numeric)
      {
      	$fa = floatval(@$a[$k]);
      	$fb = floatval(@$b[$k]);
      	$p = 1;
      	if ($desc) $p = -1;
      	if ($fa > $fb) return $p;
      	if ($fa < $fb) return -$p;
      }
      else
      {
      	if ($familyname)
      	{
      		$ca = swGetFamilyname(@$a[$k]);
      		$cb = swGetFamilyname(@$b[$k]);
      	}
      	elseif ($url)
      	{
      		$ca = swNameURL(@$a[$k]);
      		$cb = swNameURL(@$b[$k]);
      	}
      	elseif ($nocase)
      	{
      		$ca = strtolower(@$a[$k]);
      		$cb = strtolower(@$b[$k]);
      	}
      	else
      	{
      		$ca = @$a[$k];
      		$cb = @$b[$k];
      	}
      	
      	if($ca !== $cb) 
      
         if ($desc)
         	return strcmp($cb,$ca); 
         else
         	return strcmp($ca,$cb); 
      }
    } 
   return 0; 
} 

function array_sort($keys) { 

   $array = reset($keys);
   if (!is_array($array)) $array = array();
   array_shift($keys); 
   array_sort_func($keys); 
   usort($array,"array_sort_func");  
   return $array;
} 


function swQueryTupleExpression($row, $term) 
{
	// on success, returns a string
	// on error, returns an array
	// lexical analysis, to preserve quoted strings.
	
	$quotes = explode('"',$term);
	$arguments = array();
	$even = true;
	for($i=0;$i<count($quotes);$i++)
	{
		if ($even)
		{
			if ($quotes[$i])
			{
				$args = explode(" ",$quotes[$i]);
				foreach($args as $arg)
				{
					if ($arg != '')
						$arguments[]=$arg;
				}
			}
			elseif (count($arguments) && $i<count($quotes)-1)	// double quote
			{
				$arg = array_pop($arguments);
				$even = !$even;
				$i++;
				$arg.='"'.$quotes[$i];
				$arguments[] = $arg;
			}
		}
		else
		{
			$arguments[] = '"'.$quotes[$i];
		}
		$even = !$even;
	}
	$calcstack = array();
	foreach($arguments as $arg)
	{
		if (array_key_exists($arg,$row))
		{
			array_push($calcstack,$row[$arg]);
		}
		else
		{
			switch ($arg)
			{
				case '': break;
				case '+' : 
					  if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty error +');	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b+$a); break;
				case '-' : 
					  if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty error -');	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b-$a); break;
				case '*' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty *'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b*$a); break;
				case '/' :
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty /'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack);
					  if ($a == 0)  { return array('_error'=>'Expression division by zero'); 	}
					  array_push($calcstack,$b/$a); break;
				case '.' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty .'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b.$a); break;
				case '::' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty ::'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  array_push($calcstack,$b.'::'.$a); break;
				case 'SUBSTR' : 
				      if (count($calcstack)<3) { return array('_error'=>'Expression Stack empty SUBSTR'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  $s= array_pop($calcstack); 
					  array_push($calcstack,substr($s,$b,$a)); break;
				case 'STRLEN' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty STRLEN'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,strlen($a)); break;
				case 'REPLACE' : 
				      if (count($calcstack)<3) { return array('_error'=>'Expression Stack empty REPLACE'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  $s= array_pop($calcstack); 
					  array_push($calcstack,str_replace($b,$a,$s)); break;
				case 'REGEX' : 
				      if (count($calcstack)<3) { return array('_error'=>'Expression Stack empty REGEX'); 	}
					  $a = array_pop($calcstack); 
					  $b= array_pop($calcstack); 
					  $s= array_pop($calcstack); 
					  array_push($calcstack,preg_replace($b,$a,$s)); break;
				case 'TRIM' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty TRIM'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,trim($a)); break;
				case 'URLIFY' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty URLIFY'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,swNameURL($a)); break;
				case 'ABS' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty ABS'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,abs($a)); break;
				case 'SQRT' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty SQRT'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,sqrt(floatval($a))); break;
				case 'SIGN' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty SIGN');	}
				      $a = array_pop($calcstack); 
					  if ($a>0) $b=1; elseif($a<0) $b=-1; else $b=0;
					  array_push($calcstack,$b); break;
				case 'COPY' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty COPY'); 	}
					  $a = array_pop($calcstack); 
					  array_push($calcstack,$a);
					  array_push($calcstack,$a);break;
				case 'POP' : 
				      if (count($calcstack)<1) { return array('_error'=>'Expression Stack empty POP'); 	}
					  $a = array_pop($calcstack);  break;
				case 'RAND' : 
				      $a = rand(0,100);
					  array_push($calcstack,$a);break;
				case 'SWAP' : 
				      if (count($calcstack)<2) { return array('_error'=>'Expression Stack empty SWAP'); 	}
					  $a = array_pop($calcstack); $b = array_pop($calcstack); 
					  array_push($calcstack,$a); array_push($calcstack,$b); break;
				default: 
				
				
						if (substr($arg,0,9) == "FUNCTION-")
						{
							$fn = substr($arg,9);
							global $swFunctions;
							if (!isset($swFunctions[$fn])) { return array('_error'=>'Fnction '.$fn.' does not exist'); } 
							$f2 = $swFunctions[$fn];
							$fargs =array();
							if ($f2->arity()<0) { return array('_error'=>'Invalid function '.$fn); } 
							$a = $f2->arity(); 
							while ($a>0)
							{
								
								if (count($calcstack)>0)
									$fargs[] = array_pop($calcstack); 
								else
									return array('_error'=>'Empty calc stack'); 
								$a--;
							}
							$fargs[] =  $fn;
							$fargs = array_reverse($fargs);
							//print_r($fargs);

							$fresult = $f2->dowork($fargs);
							
							array_push($calcstack,$fresult);
						}
						else
						switch(substr($arg,0,1))
						{
							case '-': case '0': case '1': case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9': 
								array_push($calcstack,$arg); break; //should check also all other letters.
							
							case '"': array_push($calcstack,substr($arg,1)); break;  
							
							default : array_push($calcstack,$arg); break; 
								
							
							
						}
			}
		}
	
	}
	if (count($calcstack) < 1) return array('_error'=>'Expression Stack empty error 44');
	return array_pop($calcstack); 

}

function swQuerySetKey($set, $term)
{
	$set2 = array();
	if (!is_array($set)) return array('_error'=>'QuerySetKey Set is not array');
	if (!is_string($term)) return array('_error'=>'QuerySetKey Term is not string');
	foreach($set as $key=>$row)
	{
		$sortkey = swQueryTupleExpression($row,$term);
		if (is_array($sortkey)) //error
			return array('_error'=>'QuerySetKey sortkey Expression error');
		$set2[$sortkey] = $row;
	}
	return $set2;
}

	
function swCleanTupleFieldOrder($rows)
{
	// make sure that all rows have the same order of fields
	// rows may have not all keys or not in the same order
	// rowkey is maintained
	
	$keys = array();
	foreach($rows as $k=>$row)
	{
		foreach ($row as $key=>$v)
		{
			$keys[$key] = 1;  
		}
	}
	
	$rows2 = array();
	
	foreach($rows as $k=>$row)
	{
		$row2 = array();
		foreach ($keys as $key=>$foo)
		{
			$row2[$key] = @$row[$key];
		}
		$rows2[$k] = $row2;
	}
	return $rows2;
}




function swUniqueTuples($set)
{
	
	// make sure that there are no duplicate tuples
	
	$set2 = array();
	foreach($set as $line)
	{
		$set2[join('::',$line)] = $line;
	}
	unset($set);
	
	// uasort($set2, 'swMultifieldSort'); // do not sort any more, only uniqueness
	$set3 = array();
	foreach($set2 as $line)
	{
		$set3[] = $line;
	}
	return $set3;
}

function swMultifieldSort($a,$b) { 

if (is_array($a)) $a = join('::',$a);
if (is_array($b)) $b = join('::',$b);
return $a>$b;

}

function swQuery($args)
{
	$query = new swQueryFunction;
	$query->searcheverywhere = true;
	$query->outputraw = true;
	$table = $query->dowork($args);	
	if (is_array($table))
		// $tuple = array_pop($table); //why??
		$tuple = $table;
	else
		$tuple = array();
	return $tuple;
}



class swQueryFunction extends swFunction
{
	
	
	var $searcheverywhere = false;
	var $outputraw = false; // used in PHP code returns arrays.
	
	function info()
	{
	 	return "(arguments) returns a database search as table";
	}

	
	function dowork($args)
	{
		
		$starttime = microtime(true);
		$datatablestyle='';
		
		$rowstack = array();
		$errorline = '';
		$result = '';
		$error = '';
		$extra = '';
		$numbercolumns = array();
		$outputformat = 'HTML';
		$navigationlimit = 0;
		$verbose = false;
		global $lang;
		
		
		global $swSearchNamespaces;
		global $swTranscludeNamespaces;
		
		$ns = array();
		$searcheverywhere = FALSE;
		foreach($swSearchNamespaces as $sp)
		{
			if (stristr($sp,'*')) $searcheverywhere = TRUE;
			$sp = swNameURL($sp);
			if (!stristr($sp,':')) $sp .= ':';
			if ($sp != ':') $ns[$sp]= $sp;
		}
		foreach($swTranscludeNamespaces as $sp)
		{
			if (stristr($sp,'*')) $searcheverywhere = TRUE;
			$sp = swNameURL($sp);
			if (!stristr($sp,':')) $sp .= ':';
			if ($sp != ':') $ns[$sp]= $sp;
		}
		ksort($ns);
		$ns = join('|',$ns); // first pipe
		
		if ($this->searcheverywhere || $searcheverywhere) $ns = '*';
						
		foreach($args as $line)
		{
			//echotime("arg $line");
			$outerjoin = $leftjoin = false;
			
			if ($error) continue;
			
			$line = str_replace(" "," ",$line);
			$line = trim($line);
			if ($line=='') continue;
			
			$fs = explode(' ',$line);
			$command = array_shift($fs);
			$command = trim($command); 

			
			$datatablestyle = 'queryfunction';
			$addgroupflag = false;
			switch ($command)
			{
				case '#': 		// comment ignore
								break;
				case 'STYLE': 	if (!array_key_exists(0,$fs)) { $error = 'Missing style';  $errorline = $line; break; }
								$datatablestyle = join(' ',$fs);
								break;

				case 'SELECT':  // allow peak values from top stack when value starts with $
								// but this must be done rewriting line.
								if ($p = strpos($line,' WHERE'))
								{
									
									$q1 = substr($line,$p+strlen(' WHERE')+1);
									$ws = explode(' ',$q1);
									
									
									if (count($ws)==3 && substr($ws[2],0,1) =='$')
									{
										
										$r = array_pop($rowstack);
										array_push($rowstack,$r);	
										$l = array_pop($r);
										$k = substr($ws[2],1);
										if (!isset($l[$k])) { $error = 'Unknown field'; $errorline = $line; break; }
										$line = substr($line,0,- strlen($ws[2])).$l[$k];
										//echo $line;
									}
								}
				
								$set = swFilter($line,$ns,'query','current');
								if (isset($set['_error'])) { $error = $set['_error']; $errorline = $line; break; }
								array_push($rowstack,swUniqueTuples($set)); 
								break;

				case 'IMPORT':  if (!array_key_exists(0,$fs)) { $error = 'Missing pagename'; $errorline = $line; break; }
								$mode = array_shift($fs);
								$iname = swNameURL(array_shift($fs));
								if (stristr($ns,'*'))
									$ns = '*';
								else
									$ns = swNameURL($ns);
								if (stristr($iname,':')) // page has namespace
								{
									if ($ns =='') 
									{ $error = 'invalid name'; $errorline = $line; break; }

									$inamens = substr($iname,0,strpos($iname,':')); // get namespace of page
									if (!stristr(str_replace(':','-',$ns).'-',$inamens.'-') && $ns!='*')
										{ $error = 'invalid name'; $errorline = $line; break; }
	   							}			
				
								$w = new swWiki;
								$w->name = $iname;
								
								if (function_exists('swVirtualLinkHook') && $hooklink = swVirtualLinkHook($iname)) 
								{
									
									//echo "<p>l ".$hooklink.".";
									if (!$s = swFileGetContents($hooklink))
										return(array('_error'=>'invalid url '.$hooklink));
									//echo "<p>s ".print_r($s).".";
									
									$w->content = $s;
								}
								else
								{
									$w->lookup();
									if ($w->revision == 0) return(array('_error'=>'unknown name '.$urlname));
								}
								
								
								
								switch ($mode)
								{ 								
									case "TAB":
										$rows = explode("\n",$w->content);
										$firstline = array_shift($rows);
										$fs = explode("\t",trim($firstline));
										$rs = array();
										$j = 0;
										foreach($rows as $row)
										{
											$l = explode("\t",$row);
											$r = array();
											for($i=0;$i<count($fs);$i++)
											{	
												$r[$fs[$i]] = @$l[$i];
											}
											$rs[$iname.'/'.$j] = $r;
											$j++;
										}
										break;
									
									case "FIELDS":
										$maxrows = 0;
										foreach($w->internalfields as $k=>$v)
										{
											$maxrows = max($maxrows, count($v)+1);
											foreach($v as $vi=>$vv)
												$rs[$vi][$k] = $vv;
										}
										// pad
										foreach($w->internalfields as $k=>$v)
										{
											if (count($v))
											for($vi=count($v);$vi<$maxrows;$vi++)
											{
												$rs[$vi][$k] = $rs[count($v)-1][$k];
											}
										}
										break;
									
									default: { $error = 'Missing mode'; $errorline = $line; break; }
								}
								
								if (isset($rs))
									array_push($rowstack,swUniqueTuples($rs)); 
								break;
				
				case "DATA":    $d = join(' ',$fs);
								$datafields = swGetAllFields($d, true);
								//print_r($datafields);
								$maxrows = 0;
										foreach($datafields as $k=>$v)
										{
											$maxrows = max($maxrows, count($v)+1);
											foreach($v as $vi=>$vv)
												$rs[$vi][$k] = $vv;
										}
										// pad
										foreach($datafields as $k=>$v)
										{
											for($vi=count($v);$vi<$maxrows;$vi++)
											{
												$rs[$vi][$k] = $rs[count($v)-1][$k];
											}
										}
								
								if (isset($rs))
									array_push($rowstack,swUniqueTuples($rs)); 
								break;
								
				case 'WHERE':   if (!array_key_exists(0,$fs)) { $error = 'Missing field'; $errorline = $line; break; }
								if (!array_key_exists(1,$fs)) { $error = 'Missing operator'; $errorline = $line; break; }
								if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }

								$field = array_shift($fs);
								$operator = array_shift($fs);
								$term = join(' ',$fs);
								
								$comparefields = false;
								if (substr($operator,0,1) == '$')
								{	
									$operator = substr($operator,1);
									$comparefields = true;
								}
								
								$r = array_pop($rowstack);
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								
								$r2 = array();
								
								foreach($r as $k=>$v)
								{									
									if ($comparefields)
									{
										$term2 = swQueryTupleExpression($v, $term);
										if (is_array($term2)) { $error = $term2['_error']; $errorline = $line; break; }
										$test = swFilterCompare($operator,$v[$field],$term2);
									}
									else
									{
										$test = swFilterCompare($operator,@$v[$field],$term);
									}
									if ($test)
										$r2[$k] = $v;
								}
								
								array_push($rowstack,$r2); // if they were unique, they are still
								break;
									
									
					
				case 'FIELDS': array_push($rowstack,swUniqueTuples(swFilter('FIELDS',$ns,'query'))); break;
				
				case 'ORDER': 	if (!array_key_exists(0,$fs)) { $error = 'Missing fieldlist'; $errorline = $line; break; }
				                if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								$orders = explode(',',join(' ',$fs));
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$keys = array();
								$keys[] = $r;
								
								foreach($orders as $o)
								{
									$pars = explode(' ',$o);
									if ($pars[0] == '') array_shift($pars);
									
									$key = $pars[0];
									
									if (stristr($o,'DESC'))
										$key = '!'.$key;
									if (stristr($o,'NUMERIC'))
										$key = '#'.$key;
									if (stristr($o,'FAMILYNAME'))
										$key = '?'.$key;
									if (stristr($o,'URL'))
										$key = '@'.$key;
									if (stristr($o,'NOCASE'))
										$key = '&'.$key;
									
									$keys[] = $key;
								}
								$r2 = array_sort($keys);
								array_push($rowstack,$r2); // if they were unique, they are still
								break;
							  
				case "UNION": 	if (count($rowstack)<2) { $error = 'Empty stack'; $errorline = $line; break; }
				   
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$rfields = @array_keys(reset($r));
								if (!isset($rfields)) $rfields = array();
								$r2 = array_pop($rowstack); 
								if (!is_array($r2)) { $error = 'Stack element 2 is not array'; $errorline = $line; break; }
								$r2fields = @array_keys(reset($r2));
								if (!isset($r2fields)) $r2fields = array();
								$unionfields = array_unique(array_merge($rfields,$r2fields)); // union of fields. 
								// UNION is relaxed and pads missing fields
								$rows2 = array();
								
								$r2diff = array_diff($unionfields,$r2fields); // missing fields in $r2
								foreach($r2 as $row) 
								{
									foreach($r2diff as $v) $row[$v] = '';
									$rows2[] = $row;
								}
								
								$rdiff = array_diff($unionfields,$rfields);
								foreach($r as $row) 
								{
									foreach($rdiff as $v) $row[$v] = '';
									$rows2[] = $row;
								}

								array_push($rowstack,swUniqueTuples($rows2));  
								break;
	
				case "EXCEPT": 	if (count($rowstack)<2) { $error = 'Empty stack'; $errorline = $line; break; }
								$term = @$fs[0];
								if (!trim($term)) { $error = 'Term for EXCEPT must be specied'; $errorline = $line; break; }
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2 = array_pop($rowstack);
								if (!is_array($r2)) { $error = 'Stack element 2 is not array'; $errorline = $line; break; }
								
								if ($term)
								{
									$r = swQuerySetKey($r, $term);
									if (isset($r2['_error']))
									{
										 $error = $r2['_error']; $errorline = $line; break;
									}
									$r2 = swQuerySetKey($r2, $term);
									if (isset($r2['_error']))
									{
										 $error = $r2['_error']; $errorline = $line; break;
									}
								}
								
								$rows2 = array();
								foreach($r2 as $key=>$value)
								{
									if (!array_key_exists($key,$r))
										$rows2[$key] = $value;
								}
								array_push($rowstack,$rows2); // if they were unique, they are still
								break;
								
				case 'CROSS':   if (count($rowstack)<2) { $error = 'Empty stack'; $errorline = $line; break; }
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2 = array_pop($rowstack); 
								if (!is_array($r2)) { $error = 'Stack element 2 is not array'; $errorline = $line; break; }
								$r3 = array();
								foreach ($r as $key=>$row)
								{
									foreach($r2 as $key2=>$row2)
									{
										$r3[$key.'::'.$key2] = $row + $row2;
									}
								}
								array_push($rowstack,swUniqueTuples($r3));
								break;
				
				case 'OUTERJOIN': $outerjoin = true;
				case 'LEFTJOIN': $leftjoin = true;
				case 'JOIN':	if (count($rowstack)<2) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing field'; $errorline = $line; break; }
								$term = join(' ',$fs);
								$r2 = array_pop($rowstack); 
								if (!is_array($r2)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2fields = @array_keys(reset($r2));
								if (!isset($r2fields)) $r2fields = array();
								$r= array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 2 is not array'; $errorline = $line; break; }
								$rfields = @array_keys(reset($r));
								if (!isset($rfields)) $rfields = array();
								$unionfields = array_unique(array_merge($rfields,$r2fields)); // union of fields. 
								$r2diff = array_diff($unionfields,$r2fields); // missing fields in $r2
								$rdiff = array_diff($unionfields,$rfields); // missing fields in $r2
								// OUTERJOIN and LEFTJOIN are relaxed and pad missing fields
								
								foreach ($r as $key=>$row)
								{
									$joinvalue = swQueryTupleExpression($row, $term);
									
									if (is_array($joinvalue))
									{
										$error = $joinvalue['_error']; $errorline = $line; break;
									}
									$r[$key]['_joinvalue'] = $joinvalue;
								}
								foreach ($r2 as $key=>$row)
								{
									$joinvalue = swQueryTupleExpression($row, $term);
									
									if (is_array($joinvalue))
									{
										$error = $joinvalue['_error']; $errorline = $line; break;
									}
									$r2[$key]['_joinvalue'] = $joinvalue;
								}
								if ($error) break;
									
								$rows2 = array();
								$r2found = array();
								
								foreach ($r as $key=>$row)
								{
									$found = false;
									foreach($r2 as $key2=>$row2)
									{
										
										if ($row['_joinvalue'] == $row2['_joinvalue'])
										{
											foreach($row as $k=>$v)
												$rows2[$key.'..'.$key2][$k] = $v;
											foreach($row2 as $k=>$v)
												$rows2[$key.'..'.$key2][$k] = $v;
											unset($rows2[$key.'..'.$key2]['_joinvalue']);
											$found = true;
											$r2found[$key2] = true;
										}
										
										
									}
									if (!$found && $leftjoin)
									{
										foreach($row as $k=>$v)
												$rows2[$key.'..'][$k] = $v;
										foreach($rdiff as $k)
												$rows2[$key.'..'][$k] = '';
										unset($rows2[$key.'..']['_joinvalue']);
									}
								}
								if ($outerjoin)
								{
									foreach($r2 as $key2=>$row2)
									{
										if (!isset($r2found[$key2]))
										{
											//retrieve empty fields from first, therefore other syntax then leftjoin
											foreach($rfields as $k)
												if (isset($row2[$k]))
													$rows2['..'.$key2][$k] = $row2[$k];
												else
													$rows2['..'.$key2][$k] = '';
													
											foreach($rdiff as $k)	
												$rows2['..'.$key2][$k] = $row2[$k];
												
											unset($rows2['..'.$key2]['_joinvalue']);
										}
									}
								}
								
								array_push($rowstack,$rows2);
								break;
								
				case "ADDGROUP": $addgroupflag = true; // keeps grouped lines and does not add aggregate to names TODO
				case "GROUP": 	if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								$rollup = false;
								if (stristr($line ,' ROLLUP BY ' ))
									$rollup = true;
								$line = str_replace(' ROLLUP BY ', ' BY ', $line);
								$parts = explode(" BY ", substr($line,strlen("GROUP ")));
								$columns = explode(", ",@$parts[0]);
								$bys = explode(",",@$parts[1]);
	
								$r = array_pop($rowstack);
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
							
								// first order
								$keys = array();
								$groupkeys = array();
								
								// preserve original order as last key
								$i=0;
								foreach($r as $key=>$row)
								{
									$r[$key]['_order'] = $i;
									$i++;
								}
								
								$keys[] = $r;
								foreach($bys as $o)
								{
									$pars = explode(' ',$o);
									if ($pars[0] == '') array_shift($pars);
									
									$key = @$pars[0];
									$groupkeys[] = $key;
									if (stristr($o,' DESC'))
										$key = '!'.$key;
									if (stristr($o,' NUMERIC'))
										$key = '#'.$key;
									if (stristr($o,'FAMILYNAME'))
										$key = '?'.$key;
									if (stristr($o,'URL'))
										$key = '@'.$key;
									if (stristr($o,'NOCASE'))
										$key = '&'.$key;
									
									$keys[] = $key;
								}
								$keys[] = '#_order';
								
								
								
								
								$r2 = array_sort($keys);
								if (!is_array($r2)) $r2 = array();
								
								// now group actually
								
								$r3 = array();
								foreach($r2 as $row)
								{
									$g = '';
									foreach($groupkeys as $k)
									{
										$g .= @$row[$k].'::';
									}
									foreach($row as $k=>$v)
									{
										if ($k != '_order')
											$r3[$g][$k][] = $v;
									}
								}
								
								
								
								// now aggregate
								$r4 = array();
								
								
								foreach($r3 as $k=>$rowgroup)
								{
									
									foreach($columns as $c)
									{
										//parse function element
										$c = trim($c);
										if (stristr($c," "))
										{
											$pars = explode(' ',$c);
											$agg = $pars[1];
											$key = $pars[0];
											
										}
										else
										{
											$key = $c;
											$agg = '';
										}
										
										if (!isset($r3[$k][$key]) && $agg != 'SUM' && $agg != 'COUNT' && $agg != 'CONCAT')
										{ $error = 'Invalid field '.$key; $errorline = $line; break; }
										
										if ($rollup)
										{
											$i=0;
											foreach($r3[$k][$key] as $v)
											{
												switch ($agg)
												{
													case '': $r4[$k.'-'.$i][$key] = $v; break;
													
													case 'COUNT': $r4[$k.'-'.$i][$key.'-'.strtolower($agg)] = 1; break;
													
													default: $r4[$k.'-'.$i][$key.'-'.strtolower($agg)] = $v; break;
													
												}
												$i++;
											}
										}
										
										switch($agg)
										{
											case '': if ($rollup)
													 {
														$aun = array_unique($r3[$k][$key]);
														if (count($aun) == 1)
															$r4[$k][$key] = $aun[0];
														else
															$r4[$k][$key] = '';
													 }
													 else
														$r4[$k][$key] = @$r3[$k][$key][0]; break;
											
											case 'MAX' : $r4[$k][$key.'-max'] = max(@$r3[$k][$key]); break;
											case 'NULL' : $r4[$k][$key.'-null'] = ''; break;
											case 'MIN' : $r4[$k][$key.'-min'] = min(@$r3[$k][$key]); break;
											case 'COUNT' : $r4[$k][$key.'-count'] = count(@$r3[$k][$key]); break;
											case 'SUM' : 	$s = 0; if(isset($r3[$k][$key]))
															{ foreach($r3[$k][$key] as $v) $s+=floatval($v); } 
															$r4[$k][$key.'-sum'] = $s; break;
											case 'CONCAT' : $c = array();
															if(isset($r3[$k][$key]))
															{ foreach($r3[$k][$key] as $v) 
															$c[$v] = $v;} 
															$r4[$k][$key.'-concat'] = join("::",$c); break;
											case 'AVG' : 	$s = 0; foreach($r3[$k][$key] as $v) $s+=$v; 
															$count = count($r3[$k][$key]);
															if ($count>0) $s /= $count;
															$r4[$k][$key.'-avg'] = $s; break;
											
											default : { $error = 'Invalid aggregator '.$agg; $errorline = $line; break; }
										
										}
										
									}
								
								}
								
								
								// if there is no row, COUNT should be zero
								foreach($columns as $c)
								{
										$c = trim($c);
										if (stristr($c," "))
										{
											$pars = explode(' ',$c);
											$agg = @$pars[1];
											$key = @$pars[0];
											
										}
										
										if (@$agg == 'COUNT' && count($r4) == 0)
										{
											$r4[][$key.'-count'] = 0;
										}
										
										if (@$agg == 'SUM' && count($r4) == 0)
										{
											$r4[][$key.'-sum'] = 0;
										}
										if (@$agg == 'CONCAT' && count($r4) == 0)
										{
											$r4[][$key.'-concat'] = 0;
										}
								}
								
								
								array_push($rowstack,$r4);
								break;
				
				case "LIMIT":   if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing start'; $errorline = $line; break; }
								$start = intval($fs[0]);
								$r = array_pop($rowstack);
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
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
				case "COPY":	if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								$r = array_pop($rowstack);
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2 = $r;
								array_push($rowstack,$r);
								array_push($rowstack,$r2);
								break;
				case "POP":		if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								$r = array_pop($rowstack);
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								break;
				case "SWAP":	if (count($rowstack)<2) { $error = 'Empty stack'; $errorline = $line; break; }
								$r = array_pop($rowstack);
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2 = array_pop($rowstack);
								if (!is_array($r2)) { $error = 'Stack element 2 is not array'; $errorline = $line; break; }
								array_push($rowstack,$r);
								array_push($rowstack,$r2);
								break;
				case "TEMPLATE":if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing field'; $errorline = $line; break; }
								
								if (!array_key_exists(1,$fs)) { $error = 'Missing template'; $errorline = $line; break; }
								$columns = array();
								$column = $columns[] = $fs[0];
								for ($i=1;$i<count($fs)-1;$i++)
								{
									$columns[] = $fs[$i];
								}
								$template = $fs[count($fs)-1];	
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								
								foreach($r as $key=>$row)
								{
									$cargs = '';
									foreach($columns as $c)
									{
										if (!isset($row[$c])) { $error = 'Missing column '.$c; $errorline = $line; break; }
										$cargs .= '|'.$row[$c];
									}
									
									$r[$key][$column] = '{{'.$template.$cargs.'}}';
								}
								
								array_push($rowstack,$r);
								break;
				
				case "OUTPUT" : if (!array_key_exists(0,$fs)) { $error = 'Missing output'; $errorline = $line; break; }
								$outputformat = array_shift($fs);
								switch($outputformat)
								{
									case "LISTPAGED":
									case "HTMLPAGED":  // HTML htable with pages and navigation included
														$navigationlimit = 50;
														if (count($fs)>0) $navigationlimit = array_shift($fs);
														
									case "HTML":  // HTML htable
									case "FIXED": // SQL style as pre
									case "FIELDS": // sofawiki fields as pre
									case "FIELDSCOMPACT": // sofawiki fields as pre
									case "TAB": // tabs as pre
									case "TABHEADER": // tabs as pre
									case "LIST": // as HTML list
									case "TEXTSPACE": // text with one space
									case "TEXT":  $numbercolumns = explode(', ',join(' ',$fs));
													break;
									case "ROWTEMPLATE": if (!array_key_exists(0,$fs)) { $error = 'Missing rowtemplate'; $errorline = $line; break; }
														$rowtemplate = $fs[0]; break;
									case "CHART": $chartoptions = join(' ',$fs);  break;// SVG charts						
									default:  { $error = 'Missing or wrong outputformat'; $errorline = $line; break; }
								}
								
								break;
								
				

				case "PROJECT": if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing fieldlist'; $errorline = $line; break; }
									$columns = ($fs);
									$newcolumn = join(" ",$fs);
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2 =  array();
								foreach($r as $key=>$value)
								{
									foreach($columns as $c)
									{
										$cp = str_replace(',','',$c);
										$r2[$key][$cp] = @$value[$cp];
										
									}
								}
								
								array_push($rowstack,swUniqueTuples($r2));
							   break;

				case 'ROTATE' : if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing old column'; $errorline = $line; break; }
								if (!array_key_exists(1,$fs)) { $error = 'Missing new column'; $errorline = $line; break; }
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								$r2 = array();
								
								foreach($r as $key=>$tuple)
								{
									$c = $tuple[$fs[0]];
									
									foreach($tuple as $k=>$v)
									{
										if ($k != $fs[0])
											$r2[$k][$fs[1]] = $k;
										if ($k != $fs[0])
											$r2[$k][$c] = $v;
										
									}
									
								}
								
							   	array_push($rowstack,swUniqueTuples($r2));
							   	break;

				case 'RENAME':  if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing old field'; $errorline = $line; break; }
								if (!array_key_exists(1,$fs)) { $error = 'Missing new field'; $errorline = $line; break; }
								
								
								$renames = explode(', ',join(' ',$fs));
								
								$allcolumns = array();
								foreach($renames as $arename)
								{
									$renamefields = explode(' ',trim($arename));
									$ro = array_shift($renamefields);
									$rn = join(" ",$renamefields);
									$allcolumns[$ro] = $rn;
								}
								
								
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								foreach($r as $key=>$value)
								{
									$elem = $value;
									
									foreach($allcolumns as $column=>$newcolumn)
									{
										if ($column == '*')
										{
											foreach($elem as $k=>$v)
											{
												$elem[$k.$newcolumn] = @$elem[$k];
												unset($elem[$k]);
											}
										}
										elseif($column == '#')
										{
											$elem[$newcolumn]=$key;
										}
										else
											$elem[$newcolumn]=@$elem[$column];
										
										unset($elem[$column]);
									}
									
									$r[$key] = $elem;
								}
								
								array_push($rowstack,swUniqueTuples($r));
								break;

				case "FORMAT":  if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing field'; $errorline = $line; break; }
								if (!array_key_exists(1,$fs)) { $error = 'Missing formatstring'; $errorline = $line; break; }
									$column = array_shift($fs);
									$newformat = join(" ",$fs);
								
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								foreach($r as $key=>$value)
								{
									$r[$key][$column] = sprintf($newformat,@$r[$key][$column]); 
								}
								
								array_push($rowstack,swUniqueTuples($r));
								break;
				case "NUMBERFORMAT":  if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing field'; $errorline = $line; break; }
								
								$numberformats = explode(', ',join(' ',$fs));
								
								$columns = array();
								
								foreach($numberformats as $nf)
								{
									$nfs = explode(' ',$nf);
									$nc = array_shift($nfs);
									$nd = @array_shift($nfs); 
									$columns[$nc] = $nd;
								}
								
									
								
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								foreach($r as $key=>$value)
								{
									foreach($columns as $column=>$decimals)
									if (isset($r[$key][$column]))
										$r[$key][$column] = number_format(floatval($r[$key][$column]),$decimals,'.',"'"); 
								}
								
								array_push($rowstack,swUniqueTuples($r));
								break;
				case "CALC": 	if (count($rowstack)<1) { $error = 'Empty stack'; $errorline = $line; break; }
								if (!array_key_exists(0,$fs)) { $error = 'Missing field'; $errorline = $line; break; }
								if (!array_key_exists(1,$fs)) { $error = 'Missing expression'; $errorline = $line; break; }
									$column = array_shift($fs);
									$arguments = $fs;
									$term = join(' ',$fs);
								
								$r = array_pop($rowstack); 
								if (!is_array($r)) { $error = 'Stack element 1 is not array'; $errorline = $line; break; }
								
								foreach($r as $key=>$row)
								{
									$c = swQueryTupleExpression($row, $term);
									if (is_array($c))
									{
										$error = $c['_error']; $errorline = $line; break;
									}
									$r[$key][$column] = $c;
								}
								
								array_push($rowstack,$r); // new column at the end, no unique tuples needed
								break;
			case 'VERBOSE':  $verbose = true; break;
				
			case 'query'  : break; //ignore;
			default:         
								 $error = 'Invalid keyword "'.$command.'"'; 
								 $errorline = $line; 
			}
		}
		
		if ($error=='')
			$rows = array_pop($rowstack);
		else
			$rows = array();
		
		$endtime = microtime(true);
		
		$verbosetext = '';
		if ($verbose)
		{
			$a2 = $args;
			//array_shift($a2);
			
			$a3 = array();
			$prefix = array();
			$hasswap = false;
			$haspop = false;
			foreach($a2 as $a)
			{
				$a = trim($a);
				$fs = explode(' ',$a);
				$f = array_shift($fs);
				
				if ($f == 'VERBOSE') continue;
				if (strtolower($f) == 'query') continue;

				if ($hasswap) { array_pop($prefix); array_pop($prefix); $prefix[] = '|'; $prefix[] = '|'; $hasswap = false; }
				if ($haspop) { array_pop($prefix); $haspop = false;}
				
				
				
				switch($f)
				{
					case 'SELECT':
					case 'DATA':
					case 'FIELDS':
					case 'IMPORT': if (count($prefix)) 
									{ array_pop($prefix); $prefix[] = '|'; } 
									$prefix[] = '+'; break;
					case 'COPY': array_pop($prefix); $prefix[] = '|';  $prefix[] = '\\'; break;
					case 'SWAP': array_pop($prefix); array_pop($prefix); $prefix[] = 'x';  $prefix[] = 'x'; $hasswap = true; break;
					case 'POP': array_pop($prefix);  $prefix[] = '-';  $haspop = true; break;
					case 'JOIN':
					case 'LEFTJOIN':
					case 'OUTERJOIN':
					case 'UNION':
					case 'EXCEPT':
					case 'CROSS': array_pop($prefix);   $prefix[] = '/'; $haspop = true; break;
					
					
					default: array_pop($prefix); $prefix[] = '|';
				}
				$a3[] = join('',$prefix).'  '.$a;
			}
			
			$source = join('<br>',$a3);
			//$source = str_replace('<br> VERBOSE','',$source);
			$verbosetext = "\n".'<nowiki><div class="queryverbose"><pre>'.$source;
			if (count($rows)==1)
				$verbosetext .= '<br>= '.sprintf('%0d row',count($rows)).' '. sprintf('%0d msec', ($endtime-$starttime)*1000).'</pre></div></nowiki>';
			else
				$verbosetext .= '<br>= '.sprintf('%0d rows',count($rows)).' '. sprintf('%0d msec', ($endtime-$starttime)*1000).'</pre></div></nowiki>';
		}
		
		$errortext = '';
		if ($error) 
			$errortext .= "\n".'<div class="queryerror">'. swSystemMessage('Query Error - '.$error,$lang).'<br>'.$errorline.'</div><br>';
		
		global $swOvertime;
		
		$overtimetext = '';
		if ($swOvertime)
			$overtimetext .= "\n".'<div class="queryovertime">'.
		swSystemMessage('there-may-be-more-results',$lang)
		.'</div>';
		
		
		$navigationstart = 0; 
		if (isset($_REQUEST['start'])) $navigationstart = $_REQUEST['start'];
		$navigationstart = max(0,$navigationstart);
		$navigationcount = @count($rows);
		$navigationstart = min($navigationcount-1,$navigationstart);
		// remove unused rows
		if ($navigationlimit>0) 
		{
			for ($i=0;$i<$navigationstart;$i++) 
				if (count($rows)>0) array_shift($rows);
				while (count($rows)>$navigationlimit) array_pop($rows);
		}
		// prepare navigation
		
		$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$url = preg_replace('/&start=\d*/','', $url);
		$navigation = '<div class="htmlpagednavigation"><nowiki>';
		if ($navigationstart>0)
		$navigation .= '<a href="'.$url.'&start='.sprintf("%0d",$navigationstart-$navigationlimit).'"> '.swSystemMessage('back',$lang).'</a> ';
		$navigation .= " ".sprintf("%0d",min($navigationstart+1,$navigationcount))." - ".sprintf("%0d",min($navigationstart+$navigationlimit,$navigationcount))." / ".$navigationcount;
		if ($navigationstart+$navigationlimit<$navigationcount)
		$navigation .= ' <a href="'.$url.'&start='.sprintf("%0d",$navigationstart+$navigationlimit).'">'.swSystemMessage('forward',$lang).'</a>';
		$navigation .= '</nowiki></div>';
		
		if (@count($rows) > 0)
		{
			if ($this->outputraw) return $rows;
			$rows = swCleanTupleFieldOrder($rows);
			switch ($outputformat)
			{
				case 'HTMLPAGED': 
				case 'HTML': 	if ($navigationlimit>0) $result  .= $navigation;
								$result .= '<table class="'.$datatablestyle.'">';
								$result .= '<thead><tr>';
								reset($rows);
								$row = current($rows);
								foreach ($row as $key=>$v)
								{
									if (in_array($key,$numbercolumns))
										$result .=	'<th style="text-align:right">'.$key.'</th>';
									else
										$result .= '<th>'.$key.'</th>';
								}
								$result .=	'</tr></thead>';
								$result .= '<tbody>';
								foreach ($rows as $rev=>$row)
								{
									$result .= '<tr>';
									foreach ($row as $key=>$v)
									{
										if ($v=='') $v = '&nbsp;'; // no collapse
										if (in_array($key,$numbercolumns))
										 	$result .=	'<td style="text-align:right">'.swHTMLSanitize($v).'</td>';
										 else
										 	$result .=	'<td>'.swHTMLSanitize($v).'</td>';
									}
									$result .= '</tr>';
								}
								$result .= '</tbody></table>';
								if ($navigationlimit>0) $result  .= $navigation;
								break;
								
				case 'LISTPAGED': 
				case 'LIST': 	if ($navigationlimit>0) $result  .= $navigation;
								$result  .= '<ul class="'.$datatablestyle.'">';
								reset($rows);
								$row = current($rows);
								foreach ($rows as $rev=>$row)
								{
									$result .= '<li>'.swHTMLSanitize(join(' ',$row)).'</li>';
								}
								$result .= '</ul>';
								if ($navigationlimit>0) $result  .= $navigation;
								break;
				case 'FIXED'  : $lens = array();
								foreach ($rows as $rev=>$row)
								{
									foreach ($row as $k=>$v)
									{
										$lens[$k] = max(@$lens[$k],mb_strlen($v));
										$lens[$k] = max(@$lens[$k],mb_strlen($k));
									}
								}
								$result .= '<pre>';
								reset($rows);
								$row = current($rows);
								foreach ($row as $key=>$v)
								{
									if (in_array($key,$numbercolumns))
										$result .= str_pad($key,$lens[$key]," ",STR_PAD_LEFT).' ';
									else
										$result .= str_pad($key,$lens[$key]+1);
								}
								$result .= "<br>";
								foreach ($row as $key=>$v)
								{
									$result .= str_pad("-",$lens[$key],"-").' ';
								}
								$result .= "<br>";

								foreach ($rows as $rev=>$row)
								{
									
									foreach ($row as $key=>$v)
									{
										if (in_array($key,$numbercolumns))
											$result .= str_pad(swHTMLSanitize($v),$lens[$key]," ",STR_PAD_LEFT).' ';
										else
											$result .= str_pad(swHTMLSanitize($v),$lens[$key]+1);
									}
									$result .= "<br>";
								}
								$result .= '</pre>';
								break;
				
				case 'FIELDS'  :		 
				case 'FIELDSCOMPACT':   
								foreach ($rows as $rev=>$row)
								 {
									foreach ($row as $k=>$v)
									{
										  $result .= '[<leftsquare>'.$k.'::'.swHTMLSanitize($v).']] ';
										  if ($outputformat == 'FIELDS') $result .='<br>';
									}
									$result .= '<br>';
								 }
								 break;
				case 'TABHEADER' :  
				case 'TAB'   : 	
								$h = $outputformat == 'TABHEADER' ? (count($rows)+1)*16+5 : count($rows)*16+5;
								$h = min(400,max(32,$h));
								
								$result  .= '<nowiki><textarea style="width:100%; height:'.$h.'px">';
		
												
								if ($outputformat == 'TABHEADER')
								{
									reset($rows);
									$row = current($rows);
									$result .= join("\t",array_keys($row))."\n";
								}  
				
								foreach ($rows as $rev=>$row)
								{
									$result .= join("\t",array_values($row));
									if(end($rows) !== $row) $result .= "\n";  // we are sure that all rows are different
								}
				
								$result  .= '</textarea></nowiki>';
								break;		
				case 'TEXT'   :  
				case 'TEXTSPACE'   :  
								 foreach ($rows as $rev=>$row)
								 {
									foreach ($row as $k=>$v)
									{
										  $result .= swHTMLSanitize($v);
										  if ($outputformat == 'TEXTSPACE') $result .= ' ';
									}
								}
								
								
								 break;
				
				case 'ROWTEMPLATE' :
									
									foreach ($rows as $rev=>$row)
								 	{
										  $result .= '{{'.$rowtemplate;
										foreach ($row as $k=>$v)
										  $result .= '|'.swHTMLSanitize($v);
										  $result .= '}}';
									}
									break;									
									
				case 'CHART' :		$result .= swChart($rows,$chartoptions);
									break;
		   }
		}
		
		 $result = $verbosetext.$result.$errortext.$overtimetext.$extra;		
 		
		return $result;
	
	}
}
	
	$swFunctions["query"] = new swQueryFunction;	
	
	?>
