<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swRelationLineHandler
{
	var $aggregators = array();
	var $context = array();
	var $currentpath;
	var $errors = array();
	var $functions = array();
	var $globalrelations = array();
	var $globals = array();
	var $interactive;
	var $mode;
	var $mythread;
	var $offsets = array();
	var $programs = array();
	var $result;
	var $stack = array();
	var $currentline;
	var $transactiondict;
	var $transactionerror;
	var $transactionprefix;
	var $compiledexpressions = array();
	var $currentdatabase = null;
	var $history = array();
	var $dict = array();
	var $state;
	var $statedict = array();
	var	$walking;
	
	
	var $decoration = array(
		'ptag'=>'',
		'ptagerror'=>'<span class="error">',
		'ptagerrorend'=>'</span>',
		'ptag2'=>PHP_EOL,
		'csv'=>'<nowiki><textarea class="csv">',
		'csvend'=>'</textarea></nowiki>',
		'json'=>'<nowiki><textarea class="json">',
		'jsonend'=>'</textarea></nowiki>',
		'tab'=>'<nowiki><textarea class="tab">',
		'tabend'=>'</textarea></nowiki>',
		
		);
	
	function __construct($m = 'HTML')
	{
		$this->mode = $m;
		$this->context = array();
		
	}
	
	function __destruct()
	{
		if ($this->currentdatabase) $this->currentdatabase->close();
	}
	

	function assert($condition,$error,$il)
	{
		$ptag = $this->decoration['ptag'];
		$ptagerror = $this->decoration['ptagerror'];
		$ptagerrorend = $this->decoration['ptagerrorend'];
		$ptag2 = $this->decoration['ptag2'];
		$ti = (intval($il)+1).''; // to text
		
		if (!$condition)
		{
			$this->result .= $ptag.$ptagerror.$ti.' Error: '.$error.$ptagerrorend.$ptag2;
			$this->errors[]=$il;
			return false;
		}
		return true;
	}


	function getCompiledExpression($s)
	{
		// do not recompile expressions in loops
		if (isset($this->compiledexpressions[$s]))
		{
			$xp = $this->compiledexpressions[$s];
		}
		else
		{ 
			$xp = new swExpression();
			$xp->compile($s);
			$this->compiledexpressions[$s] = $xp;
		}
		
		// we need to update context on currently defined functions
		// expressions could be the same source before or after a function definition
		global $swExpressionFunctions;
		foreach($this->functions as $k=>$v)
			$swExpressionFunctions[$k] = $v;
		return $xp;
	}


	function run($t, $internal = '', $internalbody = '', $usedivider = true)
	{
		
		if ($internal == '')
		{
			$this->functions = array();
			$this->compiledexpressions = array();
			$this->errors = array();
			$this->dict = array();
			$this->state = '';
			$this->statedict = array();
		}
		
		
		echotime('relation code:'.strlen($t));
		try 
		{
			$this->result = $this->run2($t, $internal, $internalbody);
		}
		catch (swExpressionError $err)
		{
		    $this->result  = '';
			$this->assert(false,'Expression error: '.$this->currentline.' '.$err->getMessage(),$internal);
		}
		catch (swRelationError $err)
		{
			$this->result  = '';
			$this->assert(false,'Relation error: '.$this->currentline.' '.$err->getMessage(),$internal);
		}
		
		
		
		$list = p_dump();
		$profiles = array();
		foreach($list as $k=>$v)
		{
			$profiles[] = $k.' '.floor($v['percent']*100).'%';
		}

		
		echotime('relation end '.PHP_EOL.'<br>'.join(PHP_EOL.'<br>',$profiles));
		
		if ($usedivider)
		
			return PHP_EOL.'<div class="relation">'.PHP_EOL.$this->result.'</div>'.PHP_EOL;
		
		else
		
			return $this->result;
		
	}
	
	
	function run2($t, $internal = '', $internalbody = '')
	{
		$lines = array();
		$readlines = array();
		$vlist = array();
		$tlist = array();
		$fields = array();
		$commafields = array();
		$i; $il; $c; $j; $k;
		$locals = array();
		$internals = array();
		$file;
		$r; $r2;
		$command; $line; $mline; $key; $body;
		$found;
		$xp;
		$tx; $ti; $tn;
		$fn;
		$a;
		$firstchart = true;
		$consoleprogress;
		$whilestack = array();
		$repeatstack = array();
		$repeatlabel = array();
		$repeatvalue = array();
		$ifstack = array();
		$loopcounter;
		
		$au;
		$tp;
		
		
		
		if (!$internal)
		{
			 $result = '';
			 $this->transactiondict = array();
			 $this->transactionprefix = '';
			 $this->transactionerror = '';
		}
				
		$plines = array();
		$poffset;
		
		if ($internalbody) $internals = explode(',',$internalbody);
		
		
		
		$t = str_replace("\r\n", PHP_EOL, $t);
		$t = str_replace("\r", PHP_EOL, $t);
		$t = str_replace("\n", PHP_EOL, $t);
		
		
		
		
		$lines = explode(PHP_EOL,$t);
		//if ($internal) print_r($lines);
		$c = count($lines);
		
		$rtime = microtime();
		
		$parameteroffset = 0;
		
		
		$this->currentline = "";
		
		for ($i=0; $i < $c; $i++)
		{
			$line = trim($lines[$i]);
		    global $swMemoryLimit;
			if (memory_get_usage()>$swMemoryLimit)
			{
					echotime('overmemory '.memory_get_usage().' '.$line);
					$this->assert(false,'Out of memory '.memory_get_usage().'>'.$swMemoryLimit,$i);
					break;
			}
			elseif (memory_get_usage()>$swMemoryLimit/2)
			{
				echomem($line);
			}

			
			if ($internal) 
			{
				//echo 'internal'. $this->offsets[$internal].' ';;
				
				if (@$this->offsets[$internal] >0)
					$il = $i + $this->offsets[$internal]+1-$parameteroffset;
				else
					$il = $i - @$this->offsets[$internal]-$parameteroffset;
					
				//echo $il;
			}
			else
			{
				$il = $i;	
			}
			$ti = ($il+1).''; // to text
			$this->currentline = $ti;
	
			$line = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $line);
			$line = str_replace(html_entity_decode("&#x200B;"),'',$line); // editor ZERO WIDTH SPACE
		
			// remove comments
			if (strpos($line,'// ')>-1)
				$line = trim(substr($line,0,strpos($line,'// ')));
			if (! $line or $line == '//') continue;
			// quote
			if (substr($line,0,1)=="'")
			{
				$this->result .= substr($line,2).$this->decoration['ptag2'];
				continue;
			}
			$fields = explode(' ',$line);
			$command = array_shift($fields);
			$body = join(' ',$fields);
			if (isset($lastcommand)) p_close($lastcommand); 
			p_open($command);$lastcommand = $command;
			
			$statefound = false;
			if (count($this->statedict))
			{
				$top = array_pop($this->statedict);
				
				switch($top['type'])
				{
					case 'data'		:   if ($line == 'end data')
										{
											if ($top['mode'] == 'csv')
											{

												$r = end($this->stack);

												array_unshift($top['lines'],join(',',$r->header));

												$fn = swString2File(join(PHP_EOL,$top['lines']));

												$r->setCSV($fn,true,true);

											}
											else
											{
												// done
											}
											$statefound = true;
										}
										else
										{
											if ($top['mode'] == 'csv')
											{
												$top['lines'][] = $line;
												array_push($this->statedict,$top);
											}
											else
											{
												$r = end($this->stack);
												$r->insert($line);
												array_push($this->statedict,$top);
											}
											$statefound = true;
										}
										break;
					case 'function' :	if ($line == 'end function')
										{
											$top['lines'][] = $line;
											$fn = new swExpressionCompiledFunction($top['name'],join(PHP_EOL,$top['lines']),false);
											$this->functions[':'.$top['name']] = $fn;
										}
										else
										{
											$top['lines'][] = $line;
											array_push($this->statedict,$top);
										}
										$statefound = true;
										break;
										
					case 'if' :	    if ($line == 'end if')
									    {
											$xp = $this->GetCompiledExpression($top['expression']);
											
											if($xp->evaluate($this->dict) != '0')
											{
												$tx = join(PHP_EOL,$top['lines']);
												$this->run2($tx,$top['offset']);
												$statefound = true;
											}
											else
											{
												$tx = join(PHP_EOL,$top['elselines']);
												$this->run2($tx,$top['offset']);
											}
											$statefound = true;
										}
										elseif($line == 'else')
										{
											$top['else'] = 1;
											array_push($this->statedict,$top);
											$statefound = true;
										}
										else
										{
											if ($top['else'])
												$top['elselines'][] = $line;
											else
												$top['lines'][] = $line;
											array_push($this->statedict,$top);
											$statefound = true;
										}
										break;
					case 'program' :	if ($line == 'end program')
										{
											$top['lines'][] = $line;
											$this->programs[$top['name']] = join(PHP_EOL,$top['lines']);
										}
										else
										{
											$top['lines'][] = $line;
											array_push($this->statedict,$top);
										}
										$statefound = true;
										break;

					case 'repeat' :	    if ($line == 'end repeat')
										{
											$top['lines'][] = $line;
											$tx = join(PHP_EOL,$top['lines']);
											while($top['counter']>0)
											{
												$this->dict[$top['name']] = $top['counter'];
												$this->run2($tx,$top['offset']);
												
												$top['counter']--;
												$statefound = true;
											}
											unset($this->dict[$top['name']]);
										}
										else
										{
											$top['lines'][] = $line;
											array_push($this->statedict,$top);
											$statefound = true;
										}
										break;
					case 'walk'	 :		if ($line == 'end walk')
										{
											$this->walking = true;
											$tx = join(PHP_EOL,$top['lines']);
											$r = array_pop($this->stack);
											$r3 = new swRelation($r->header);
											foreach($r->tuples as $tp)
											{
												$r2 = new swRelation($r->header);
												$r2->tuples[$tp->hash()] = $tp;
												array_push($this->stack,$r2);
												$this->run2($tx,$top['offset']);
												$r2 = array_pop($this->stack);
												$tp2 = current($r2->tuples);
												$r3->tuples[$tp2->hash()] = $tp2;
											}
											array_push($this->stack,$r3);
											$this->walking = false;
											$statefound = true;
										}
										else
										{
											$top['lines'][] = $line;
											array_push($this->statedict,$top);
											$statefound = true;
										}
										break;
					case 'while' :	    if ($line == 'end while')
										{
											$top['lines'][] = $line;
											$tx = join(PHP_EOL,$top['lines']);
											$xp = $this->GetCompiledExpression($top['expression']);
											
											while($xp->evaluate($this->dict) != '0')
											{
												$this->run2($tx,$top['offset']);
												$statefound = true;
											}
										}
										else
										{
											$top['lines'][] = $line;
											array_push($this->statedict,$top);
											$statefound = true;
										}
										break;
					
					default: 			break;
				}
			}
			if (!$statefound)
			switch($command)
			{
				case 'analyze' : 	if (!$this->assert(count($this->stack),'Analyze Stack empty',$il)) break;
										
									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->analyze($body);
										
									break;  
				case 'assert' : 	if (!$this->assert(count($this->stack),'Assert Stack empty',$il)) break;
										
									$r = end($this->stack);
									$r->globals = $this->dict;
									
									if (!$this->assert($r->assert($body),'Assertion error '.$body,$il)) break;
									
									break; 
				case 'beep' : 		if (!$this->assert(false,'Beep is not supported',$il)) break;
									break; 
				
				case 'compile':		$xp = new swExpression();
									$xp->compile($body);
									$this->result .= join(' ',$xp->rpn).$this->ptag2;
									$xp = null;
									break;
				
				case 'data':		if (!$this->assert(count($this->stack),'Data Stack empty',$il)) break;
				
									$top = array();
									$top['type'] = 'data';
									$top['offset'] = $i+1;
									$top['mode'] = trim($body);
									$top['lines'] = array();
									array_push($this->statedict,$top);
									break;
				case 'database':    if ($this->currentdatabase) $this->currentdatabase->close();
									$xp = new swExpression();
									$xp->compile($body);
									$tx = $xp->evaluate($this->dict);
									if ($tx) 
									{	
											$file = 'site/cache/'.$tx; 
											if (defined('SOFAWIKICLI')) $file = getcwd().'/'.$tx;
									}
									else 
									{
										$file = ':memory:';
									}
									
									try { 
										if (stristr($tx,'/'))
										{
											$file = $tx; 
											$this->currentdatabase = new SQLite3($file,SQLITE3_OPEN_READONLY);
										}
										else
											$this->currentdatabase = new SQLite3($file);
									}
									catch (Exception $err)
									{
										if (!$this->assert(false,'Database open '.$err->getMessage(),$il)) break;
									}
									
									if (!$this->assert($this->currentdatabase->busyTimeout(5000),'Database busy',$il)) break;
									
									$this->currentdatabase->enableExceptions(true);
									break;
				
				case 'delegate':	if (!$this->assert(count($this->stack),'Print Stack empty',$il)) break;
									
									
									$xp = new swExpression();
									$xp->compile($body);
									$tx = $xp->evaluate($this->dict);
									$txs = explode(' ',$tx);
									$tx0 = array_shift($txs);
									$tx = join(' ',$txs);
									$r = end($this->stack);
									$this->result .= '{{'.$tx0.'|';
									$this->result .= $r->getCSVFormatted();
									$this->result .= '|'.$tx.' }}';
									break;
									
				case 'deserialize': if (!$this->assert(count($this->stack),'Deserialize Stack empty',$il)) break;
									
									$r = end($this->stack);
									$r->deserialize();
									break;
									
				case 'difference':	if (!$this->assert(count($this->stack)>1,'Difference Stack empty',$il)) break;
			
									$r = array_pop($this->stack);
									$r2 = array_pop($this->stack);
									$r2->difference($r);
									$this->stack[] = $r2;
									break;
									
				case 'dup':			if (!$this->assert(count($this->stack),'Dup Stack empty',$il)) break;

									$r = end($this->stack);
									$this->stack[] = $r->doClone();
									break;	
														
				case 'echo' :		if (count($this->stack)>0)
									{
										$r = $this->stack[count($this->stack)-1];
										if (count($r->tuples) > 0)
										{
											$tp = current($r->tuples);
											if ($tp)
											{
												$tpfields = $tp->fields();
												foreach($tpfields as $k=>$v)
													$this->dict[$k] = $v;
											}
										}
									}
									
									$xp = $this->GetCompiledExpression($body);
									$tx = $xp->evaluate($this->dict,$this->globals);
									$this->result .= $tx.PHP_EOL; // . $ptag2; // ???
									switch (substr($tx,1))
									{
										case '=':
										case '*':
										case '{':
										case '|': break;
										default: $this->result .= $this->decoration['ptag2'] ;// ???
									}
									$xp = null;
									break;
									

				
				case 'else':		$this->assert(false,'Else not possible here',$il);
									break; 
										
				case 'end':	     	if($line == 'end transaction')
									{
										if (!$this->assert(!$this->transactionerror,'Transaction error',$il)) 
										{
											foreach($this->transactiondict as $p) unlink($p);
											break;
										}
										foreach($this->transactiondict as $k=>$p)
										{
											if (!$this->assert(!file_exists($p),'Transaction errror: missing file '.$p,$il))
											{
												unlink($k);
												rename($p,$k);
											}											
										}

										$this->transactiondict = array();
										$this->transactionprefix = '';
										$this->transactionerror = '';
									}
									else
									{
										// ignore if (!$this->assert(false,'End not possible here '.$line,$il)) break;
									}								
									break; 	

				case 'execute':		if (!$this->assert(isset($_REQUEST['submitexecute']) || isset($_REQUEST['confirmexecute']),'Ignore execute without submit run execute',$il)) break;
									if (!$this->assert(count($this->stack),'Execute stack empty',$il)) break;
									

									$r = array_pop($this->stack);
									$this->stack[] = swRelationExecute($r,$body);
									
									if (!isset($_REQUEST['confirmexecute']))
									{										
										$this->result .='<nowiki><form method="post" action="index.php"></nowiki>';
										global $name;
										$this->result .=  '<nowiki><input type="hidden" name="name" value="'.$name.'"></nowiki>';
	
										$this->result .=  '<nowiki><input type="submit" name="confirmexecute" value="Confirm" style="color:red"></nowiki>';
										$this->result .= '<nowiki><textarea style="display:none" name="q">'.$_REQUEST['q'].'</textarea></nowiki>';
										$this->result .='<nowiki></form></nowiki>';
									}
									break;				

				case 'extend':		if (!$this->assert(count($this->stack),'Extend stack empty',$il)) break;
									
									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->extend($body);
									break;
										
				case 'filter':		$gl = array_merge($this->dict, $this->globals,$locals);
									
									global $swDebugRefresh;
									
									$r = swRelationFilter($body,$gl,$swDebugRefresh);
									$this->stack[] = $r;
									break;
													
				case 'format':		if (!$this->assert(count($this->stack),'Format stack empty',$il)) break;
									
									$r = end($this->stack);
									$r->format1($body);									
									break;
													
				case 'formatdump':	if (!$this->assert(count($this->stack),'Formatdump stack empty',$il)) break;
									
									$r = end($this->stack);
									$vlist = array_keys($r->formats);
									$tlist = array();
									foreach($vlist as $velem)
									{
										$tlist[] = $velem.' '.$r->formats[$velem];
									}
									$this->result.= $ptag.'Formats: '.join(', ',$tlist).$this->decoration['ptag2'];
									break;	
									
				case 'fulltext':	$xp = new swExpression();
									$xp->compile($body);
									$b = $xp->evaluate($this->dict);
									$r = swQueryFulltext($b);
									$this->stack[] = $r;
									break;
									
				case 'fulltexturl':	$xp = new swExpression();
									$xp->compile($body);
									$b = $xp->evaluate($this->dict);
									$r = swQueryFulltextUrl($b);
									$this->stack[] = $r;
									break;
									
				case 'function':	$line = str_replace('(',' (',$line);
			 						$fields = explode(' ',$line);
			 						$command = array_shift($fields);
			 						$body = join(' ',$fields);
									$plines = array();
									$plines[] = $line;
									
									$this->assert(!array_key_exists(trim($body), $this->offsets),'Warning : Symbol "function '.$fields[0].'" overwritten',$il);
									$this->offsets[trim($body)] = $i;
									
									$top = array();
									$top['type'] = 'function';
									$top['name'] = $fields[0];
									$top['lines'] = array($line);
									array_push($this->statedict,$top);
									break; 
									
				case 'if':			$top = array();
									$top['type'] = 'if';
									$top['offset'] = $i+1;
									$top['expression'] = $body;
									$top['lines'] = array();
									$top['elselines'] = array();
									$top['else'] = 0;				
									array_push($this->statedict,$top);
									break;
									
				case 'import':		$xp = new swExpression();
									$xp->compile($body);
									$te = $xp->evaluate($this->globals,$this->dict);
									
									$r = swRelationImport($te);
									$this->stack[] = $r;
									break;

				case 'include':		$xp = new swExpression();
									$xp->compile($body);
									$tn = $xp->evaluate($this->dict);
									if (!$this->assert($this>validFileName($tn),'Invalid name '.$tn,$il)) break;
									
									$tmp = swRelationInclude($tn);
									$this->offsets[$tn] = -$i; // give only fixed number on error include
									$this->result .= $this->run($tmp,$tn,"");									
									break;
									
									
				case 'input': 		$fieldgroup = explode(',',$body);
									$this->result .=  '<nowiki><div class="editzone relationinput">';
									$this->result .= '<div class="editheader">Input</div>';
									
									$this->result .='<form method="post" action="index.php?name=</nowiki>{{nameurl |{{currentname}} }}<nowiki>"></nowiki>';
									$this->result .=  '<nowiki><input type="submit" name="submitinput" value="Submit"></nowiki>';

									if (isset($_REQUEST['q']))
									$this->result .= '<nowiki><textarea style="display:none" name="q">'.$_REQUEST['q'].'</textarea></nowiki>';

									
									$inputfields = array();
									foreach($fieldgroup as $fg)
									{
										$fg = trim($fg);
										$ts = explode(' ',$fg);
										$field = array_shift($ts);
										
										
										
										if (substr($field,-9)== '_textarea')
										{
											$field = substr($field,0,-9);
											$fieldtype = 'textarea';
										}
										elseif (substr($field,-7)== '_select')
										{
											$field = substr($field,0,-7);
											$fieldtype = 'select';
										}
										else
											$fieldtype = 'text';
										
										
										$inputfields[] = $field;
										
										$df = join($ts);
										$xp = new swExpression();
										$xp->compile($df);
										$tx = $xp->evaluate($this->globals, $locals);
										
										$txdefault = $tx;
										
										if (isset($_POST['submitinput']))
											$tx = @$_POST[$field];
											
										switch($fieldtype)
										{
											case 'textarea': $this->result .= '<nowiki><p>'.$field.'<textarea name="'.$field.'" rows="5" >'.$tx.'</textarea></nowiki>'; break;
											
											case 'select': $txoption = explode('|',$txdefault);
													
															$this->result .= '<nowiki><p>'.$field.'<select name="'.$field.'"></nowiki>';										
															
															
															foreach($txoption as $o)
															{
																if ($o == $tx) $sel = 'SELECTED'; else $sel = '';
																
																$this->result .= '<nowiki><option value="'.$o.'" '.$sel.'>'.$o.'</option></nowiki>';
															}
															
															$this->result .= '<nowiki></select></nowiki>';
															break;
											default: $this->result .= '<nowiki><p>'.$field.'<input type="text" name="'.$field.'" value="'.$tx.'"></nowiki>';
											}
										
									}
																		
									$this->result .=  '<nowiki></form></div></nowiki>';
									
									if (isset($_POST['submitinput']))
									{
										//print_r($inputfields);
										
										foreach($inputfields as $key)
										{
											$this->globals[$key] = @$_POST[$key];
											$this->dict[$key] = @$_POST[$key];
										}
										//print_r($this->globals);
									}
									else
									{
										$i=$c; break; 
									}
									
									break;
				case 'insert':		if (!$this->assert(count($this->stack),'Insert stack empty ',$il)) break;
									
									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->insert($body);
									break;	
									
				case 'intersection':if (!$this->assert(count($this->stack)>1,'Intersection stack empty ',$il)) break;
									
									$r = array_pop($this->stack);
									$r2 = array_pop($this->stack);
									$r2->intersection($r);
									$this->stack[] = $r2;
									break;	
									
				case 'join':		if (!$this->assert(count($this->stack)>1,'Join stack empty ',$il)) break;

									$r = array_pop($this->stack);
									$r->globals = $this->dict;
									$r2 = array_pop($this->stack);
									$r2->join($r,$body);
									$this->stack[] = $r2;
									break;	
																	
				case 'label':		if (!$this->assert(count($this->stack),'Label stack empty ',$il)) break;
									
									$r = end($this->stack);
									$r->label($body);
									break;
													
				case 'limit':		if (!$this->assert(count($this->stack),'Limit stack empty ',$il)) break;

									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->limit($body);
									break;

				case 'logs':		$gl = array_merge($this->dict, $this->globals,$locals);
									
									global $swDebugRefresh;
									
									$r = swRelationLogs($body,$gl,$swDebugRefresh);
									$this->stack[] = $r;
									break;					
				case 'memory':	    break; 	
				case 'order':		if (!$this->assert(count($this->stack),'Order stack empty ',$il)) break;
									
									$r = end($this->stack);
									$r->order($body);
									break;	
									
				case 'parameter':	if (!$this->assert(count($internals),'Parameter stack empty ',$il)) break;
									
									$tx = trim(array_shift($internals));
									$xp = $this->GetCompiledExpression($tx);
									$this->dict[trim($body)] = $xp->evaluate($this->dict);
									$parameteroffset++;
									break;									
				
				case 'parse':		if (!$this->assert(count($this->stack),'Parse stack empty ',$il)) break;
									
									$r = end($this->stack);
									$r->parse($body);
									break;	
									
				case 'pivot':		if (!$this->assert(count($this->stack),'Pivot stack empty ',$il)) break;

									$r = end($this->stack);
									$r->pivot($body);
									break;	
				
				case 'pop':			if (!$this->assert(count($this->stack),'Pivot stack empty ',$il)) break;
									
									$r = array_pop($this->stack);
									$r = null;
									break;
										
				case 'print':		if (!$this->assert(count($this->stack),'Print stack empty ',$il)) break;
									
									$r = end($this->stack);

									switch(trim($body))
									{
										case 'csv':		$this->result .= $this->decoration['csv']. $r->getCSV().$this->decoration['csvend']; break;
										case 'fields':	$this->result .= $r->toFields(); break;
										case 'json':	$this->result .= $this->decoration['json']. $r->getJSON().$this->decoration['jsonend']; break;
										case 'space':	$this->result .= $r->getSpace(); break;

										case 'tab':		$this->result .= $this->decoration['tab']. $r->getTab().$this->decoration['tabend']; break;
										case 'raw':		$this->result .= $r->getTab(); break;
										
										case 'text':		$this->result .= $r->toText($body); break;
										
										default: 		switch($this->mode)
														{
															case 'text': $this->result .= $r->toText($body); break;
															case 'html': 
															default: 	$this->result .= $r->toHTML($body); break;
														}
														break;
									}
																		
									break;	
										
				case 'program':		$line = str_replace('(',' (',$line);
			 						$fields = explode(' ',$line);
			 						$command = array_shift($fields);
			 						$body = join(' ',$fields);
									$plines = array();
									$fields = explode(' ',$body);
									$key = array_shift($fields);
									$body = trim(join(' ',$fields));
									
									if (!$this->assert(substr($body,0,1) == '(' && substr($body,-1,1) == ')','Missing paranthesis '.$key,$il)) break;
									$body = substr($body,1,-1);
								
									if ($body != '')
									{
										$top = array();
										$top['type'] = 'program';
										$top['name'] = $key;
										$top['lines'] = array();
										foreach(explode(',',$body) as $commafield) $top['lines'][] = 'parameter '.trim($commafield);
										
										$this->assert(!array_key_exists($key, $this->offsets),'Warning : Symbol "program '.$key.'" overwritten',$il);
										
										$this->offsets[$key] = $i+1;
										$found = false;
																			
										array_push($this->statedict,$top);

										
									}
									break; 
													
				case 'project':		if (!$this->assert(count($this->stack),'Project stack empty ',$il)) break;
									
									$r = end($this->stack); //print_r($r);
									$r->aggregators = $this->aggregators;
									$r->globals = $this->dict;
									$r->project($body);
									break;	
									
				case 'read':		$r = new swRelation('',$locals,$this->dict);
									$enc = 'utf8';
									if (substr(trim($body),0,8)=='encoding')
									{
										$body = trim(substr(trim($body),8));
										$fields = explode(' ',$body);
										$enc = array_shift($fields);
										if (!$this->assert(count($fields),'Missing filename ',$il)) $body = '';
										else $body = join(' ',$fields);
									}
									$jsonpath = '';
									$readformat = '';
									if (substr(trim($body),0,4)=='json')
									{
										$body = trim(substr(trim($body),4));
										$readformat = 'json';
										
									}
									if (substr(trim($body),0,4)=='path')
									{
										$body = trim(substr(trim($body),4));
										$fields = explode(' ',$body);
										$jsonpath = array_shift($fields);
										if (!$this->assert(count($fields),'Missing filename ',$il)) $body = '';
										else $body = join(' ',$fields);
									}

									
									
									$xp = new swExpression();
									$xp->compile($body);
									$tn = $xp->evaluate($this->dict);
									$hooklink = '';
									if ($tn == '') $tn = ' ';
									if (function_exists('swVirtualLinkHook') 
											&& $hooklink = swVirtualLinkHook($tn, '', '')) 
									{
										// ok
										// note that the last characters of the url must be .csv or .txt or .json or .xml

										$file = md5($hooklink);
									} 
									
									$tinfo = pathinfo($tn);
									if ($readformat == 'json') $tinfo['extension'] = 'json';
									
									if (!$this->assert($hooklink || $this->validFileName($tinfo['filename']),'Invalid filename',$il)) break ;
									if (!$this->assert($tn,'Empty filename',$il)) break;
									if (strpos($tn,'.')=== false)
									{
										if (!$this->assert(array_key_exists($tn, $this->globalrelations),'Warning: Saved relation "'.$tn.'" does not exist',$il)) break;
										
										$r = $this->globalrelations[$tn];
										$this->stack[] = $r->doClone();
										break;
									}
									
									if (isset($this->transactiondict[$tn]))
										$tn = $this->transactiondict[$tn];
									
									if($hooklink && !$this->assert(swFileGetContents($hooklink,'site/cache/'.$file),'Invalid URL '.$hooklink,$il)) break;
									
									if($hooklink) $tn = $file;
									
									$file1 = 'site/files/'.$tn;
									$file2 = 'site/cache/'.$tn;
									
									if (defined('SOFAWIKICLI')) $file1 = $file2 = getcwd().'/'.$tn;
									
									if(!$this->assert(file_exists($file1)|| file_exists($file2),'File does not exist '.$file1.' '.$file2,$il))
									{
										$stack[] = new swRelation('');
										break;
									}
									
									if (file_exists($file1) and file_exists($file2))
									{
										 if (filemtime($file1) >= filemtime($file2)) 
										 	$file = $file1;
										 else
										 	$file = $file2;
									}
									elseif (file_exists($file1)) $file = $file1;
									else $file = $file2;
									
									// big files won't like this
									
									if(!$this->assert(filesize($file),'Empty file '.$file,$il)) break;
									

									$r = new swRelation('',$this->dict);
									switch($tinfo['extension'])
									{
										case 'csv': 	$r->setCSV($file,true,false,$enc); $this->stack[] = $r;
														break;
										case 'txt': 	$r->setTab($file,$enc); $this->stack[] = $r;
														break;
										case 'json':	// assume UTF-8 
														$tip = file_get_contents($file); 
														$r->setJSON($tip,$jsonpath); $this->stack[] = $r; 
														break;
										case 'xml':		$tip = file_get_contents($file);
														$r->setXML($tip); $this->stack[] = $r;
														break;
										case 'html':	$tip = file_get_contents($file);
														$r->setHtml($tip); $this->stack[] = $r;
														break;
										case 'rel':		if (!$this->assert(defined('SOFAWIKICLI'),'Not CLI',$il)) break;
														$tip = file_get_contents($file);
														$this->offsets[$tn] = -$i; // give only fixed number on error include
														$this->run($tip,$tn,"", false);
														break;
										default:		$this->assert(false,'Invalid file format',$il);																				break;
									}
									
									break;
										
				case 'relation':	$r = new swRelation($body, $this->dict);
									$this->stack[] = $r;
									break;
										
				case 'rename':		if (!$this->assert(count($this->stack),'Rename stack empty ',$il)) break;
									
									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->rename1($body);
									break;	
																		
				case 'repeat':		$repeatstack[] = $i;
									$fields = explode(' ',$body);
									
									if(!$this->assert(count($fields)>1,'Repeat missing parameters',$il)) break;
									if(!$this->assert(!array_key_exists($fields[0],$this->dict),'Repeat name '.$fields[0].' already used',$il)) break;
									$key = array_shift($fields);
									$body = join(' ',$fields);
									$xp = $this->GetCompiledExpression($body);
									$v = $xp->evaluate($this->dict);
									$this->dict[end($repeatlabel)] = $v;
									
									$top = array();
									$top['type'] = 'repeat';
									$top['offset'] = $i+1;
									$top['name'] = $key;
									$top['counter'] = $v;
									$top['lines'] = array();				
									array_push($this->statedict,$top);
									break;
																			
				case 'run':			// run pg(x) and run pg (x) are both valid
									// so we split on paranthesis, not space
									
									$fields = explode('(',$body);
									$key = trim(array_shift($fields));
									$body = '('.trim(join(' ',$fields));
									
									if(!$this->assert(substr($body,0,1) == '(' && substr($body,-1,1) == ')','Missing paranthesis',$il)) break;
									if(!$this->assert(array_key_exists($key, $this->programs),'Program not defined '.$key,$il)) break;
									$tx = $this->programs[$key];	
									$dict2 = array();
									$dict0 = array();
									foreach($this->dict as $k=>$v)
									{
										$dict2[$k] = $v;
									}
									$dict0 = array_slice($this->dict,0);
									$body = substr($body,1,-1);
									$this->result = $this->run2($tx,$key,$body); 
									$this->dict = array_slice($dict0,0); 
									break; 
									
				case 'select':		if (!$this->assert(count($this->stack),'Select stack empty ',$il)) break;

									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->select1($body);
									break;	
									
				case 'serialize':	if (!$this->assert(count($this->stack),'Serialize stack empty ',$il)) break;
				
									$r = end($this->stack);
									$r->serialize1();
									break;
										
				case 'set':			if (!$this->assert(count($fields)>2,'Set too short',$il)) break;

									$key = array_shift($fields);
								    $eq = array_shift($fields);
									$body = join(' ',$fields);
									if (!$this->assert(!in_array($key,$repeatlabel),'Set name '.$key .' already used',$il)) break;
									if (!$this->assert($eq == '=','Set missing =',$il)) break;
									
									$xp = $this->GetCompiledExpression($body);
									
									
									if (count($this->stack)>0)
									{
										$r = current($this->stack);
										if (count($r->tuples)>0)
										{
											$tp = current($r->tuples);
											foreach($tp->fields() as $k=>$v) $this->dict[$k] =$v;
										}
										
										
											
									}
									$val = $this->dict[$key] = $xp->evaluate($this->dict);
									
									if (count($this->stack)>0 && $this->walking)
									{
										$r = current($this->stack);
										if (in_array($key, $r->header) && count($r->tuples)>0)
										{
											$tp = array_pop($r->tuples);
											$d = $tp->fields();
											$d[$key] = $val;
											$tp = new swTuple($d);
											$r->tuples[$tp->hash()] = $tp;
										}
									}
									break; 
									
				case 'sql':			if (!$this->assert($this->currentdatabase,'No database',$il)) break;

									$xp = new swExpression();
									$xp->compile($body);
									$q = $xp->evaluate($this->dict);
									//$q = SQLite3::escapeString($q);
									try	{ $query = $this->currentdatabase->query($q); }
									catch (Exception $err)
									{
										$this->assert(false,$this->currentdatabase->lastErrorMsg().': '.$q,$il); break;
									}
									if (!$this->assert($query,$this->currentdatabase->lastErrorMsg().': '.$q,$il)) break;
									
									$r = new swRelation('',$this->dict);
									while($fields = @$query->fetchArray(SQLITE3_ASSOC))
									{
										if (!$r->arity())
										{
											foreach($fields as $k=>$v) $r->addColumn($k);							
										}
										$r->insert2(array_values($fields));
									}	
								    if ($r->arity() || substr($q,0,strlen('SELECT'))=='SELECT') $this->stack[] = $r;		
									break;
				
				case 'stack':		$r = new swRelation('stack, cardinality, column', $this->dict);
									$sti = 0;
									foreach($this->stack as $r2)
									{
										$sti++;
										foreach($r2->header as $th)
										{
											$r->insert(($sti).', '.$r2->cardinality().', "'.$th.'"');
										}
									}
									$this->stack[] = $r;
									break;
									
				case 'stop':		$i=$c; break;
				
				case 'swap':		if (!$this->assert(count($this->stack)>1,'Swap stack empty ',$il)) break;

									$r = array_pop($this->stack);
									$r2 = array_pop($this->stack);
									$this->stack[] = $r;
									$this->stack[] = $r2;
									break;	
									
				case 'template':	if (!$this->assert(count($this->stack),'Template stack empty ',$il)) break;
				
									$r = end($this->stack);
									$xp = $this->GetCompiledExpression($body);
									$tn = $xp->evaluate($this->dict);
									if (!$this->assert($this->validFileName($tn),'Invalid template name '.$tn,$il)) break;
									
									$tmp = swRelationTemplate($tn);
									$this->result .= $r->toTemplate($tmp);																	
									break;
									
				case 'transaction' :$this->transactionprefix = 'tmp-';
									$this->transactionerror = '';
									$this->transactiondict = array();
									break;
									
				case 'union':		if (!$this->assert(count($this->stack)>1,'Union stack empty ',$il)) break;
				
									$r = array_pop($this->stack);
									$r2 = array_pop($this->stack);
									$r2->union($r);
									$this->stack[] = $r2;
									break;
									
								
				case 'update':		if (!$this->assert(count($this->stack),'Update stack empty ',$il)) break;

									$r = end($this->stack);
									$r->globals = $this->dict;
									$r->update($body);
									break;	
									
				case 'virtual':		$xp = $this->GetCompiledExpression($body);
									$te = $xp->evaluate($this->globals,$this->dict);
									$r = swRelationVirtual($te);
									$this->stack[] = $r;
									break;
				case 'walk' :		if (!$this->assert(count($this->stack),'Walk stack empty ',$il)) break;
				
									$r = end($this->stack);			
									$pairs = explode(',',$body);
									$wc = array();								
									foreach($pairs as $p)
										if (trim($p))
										{  
											$r->addColumn(trim($p));
											$wc[] = trim($p);
										}
									$top = array();
									$top['type'] = 'walk';
									$top['offset'] = $i+1;
									$top['writablecolumns'] = $wc;
									$top['lines'] = array();				
									array_push($this->statedict,$top);
																	
									break;
									
												
				case 'while':		$top = array();
									$top['type'] = 'while';
									$top['offset'] = $i+1;
									$top['expression'] = $body;
									$top['lines'] = array();				
									array_push($this->statedict,$top);
									break;
									
				case 'write':		if (!$this->assert(count($this->stack),'Write stack empty ',$il)) break;

									$r = end($this->stack);
									
									$xp = $this->GetCompiledExpression($body);
									$tn = $xp->evaluate($this->dict);
									
									
									
									if (substr($tn,0,strlen('database')) == 'database')
									{
										if (!$this->assert($this->currentdatabase,'No database ',$il)) break;
										$table = trim(substr($tn,strlen('database')));
										if (!$this->assert($r->validname($table),'Invalid table name '.$table,$il)) break;
										
										
										$header = ' ('.join(', ',$r->header).') ';
										$this->currentdatabase->query('DROP TABLE IF EXISTS '.$table.'; CREATE  TABLE '.$table.$header.';'); 
										$this->currentdatabase->query('BEGIN TRANSACTION; ');
										foreach($r->tuples as $tuple)
										{
											$fields = $tuple->fields();
											$values  = array();
											foreach($fields as $v)
											{
												$values [] = "'".SQLite3::escapeString($v)."'";												}
											$q = 'INSERT INTO '.$table.' '.$header.
											' VALUES ('.join(',',$values).');';
											// echo $q;
											$this->currentdatabase->query($q); 
										}
										$this->currentdatabase->query('COMMIT; '); 
										break;
									}
									
									
									if ($this->transactionprefix != '')
									{
										$this->transactiondict[$tn]=$this->transactionprefix.$tn;
										$tn = $this->transactionprefix.$tn;
									}
									
									$tinfo = pathinfo($tn); 
									
									if (!$this->assert($this->validFileName($tinfo['filename']),'Invalid filename '.$tn,$il)) break;
									
									$file = 'site/cache/'.$tn;
									if (defined('SOFAWIKICLI')) $file = getcwd().'/'.$tn;
									$written = 0;
									if (!isset($tinfo['extension'])) $tinfo['extension'] = '';
									switch($tinfo['extension'])
									{
										case 'csv': 	$written = file_put_contents($file,$r->getCSV()); 
														break;
										case 'txt': 	$written = file_put_contents($file,$r->getTab()); 
															break;
										case 'json':	$written = file_put_contents($file,$r->getJSON()); 
															break;
															
										case 'rel':	    if (!$this->assert(defined('SOFAWIKICLI'),'Not CLI',$il)) break;
														file_put_contents($file,join(PHP_EOL,$this->history)); 																	break;
															
										default:		if (strpos($tn,'.') === false)
														{
															$this->globalrelations[$tn] = $r->doClone();
															break;
														}
														else
														{
															$this->assert(false,'Invalid filename '.$tn,$il);

														}
															break;
									}
									break;
									
				default: 			$this->assert(false,'Unhandled line '.$line,$il);
			}
			
			
			if (count($this->stack) > 0)
			{
				$top = end($this->stack);
				if (!$this->assert(is_object($top),'Top stack not object at line '.$line,$il)) break;
				if (!$this->assert(is_a($top,'swRelation'),'Top stack not relation at line '.$line,$il)) break;
			}		
		}
		if (isset($lastcommand)) p_close($lastcommand); 
					
		return $this->result;
			
			  
		
		
	}
	
	function setBeep($notes)
	{
		// stub
	}
	
	function setMessage()
	{
		// stub
	}
	
	function validFileName($s)
	{
		if (strlen($s) < 1) return false;
		if (substr($s,0,1) == ".") return false;
		if (stristr($s,':')) return false;
		if (stristr($s,'/')) return false;
		return true;
	}
	
	
		
	
}


