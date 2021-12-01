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
	
	function __construct($m = 'HTML')
	{
		$this->mode = $m;
		$this->context = array();
	}
	
	function run($t, $internal = '', $internalbody = '', $usedivider = true)
	{
		
		
		echotime('relation '.strlen($t));
		try 
		{
			$result = $this->run2($t, $internal, $internalbody);
		}
		catch (swExpressionError $err)
		{
			$result = $this->result.PHP_EOL.'<span class="error">'.$this->currentline.' '.$err->getMessage().'</span>';
		}
		catch (swRelationError $err)
		{
			$result = $this->result.PHP_EOL.'<span class="error">'.$this->currentline.' '.$err->getMessage().'</span>';
		}
		echotime('relation end '.strlen($t));
		
		if ($usedivider)
		
			return PHP_EOL.'<div class="relation">'.PHP_EOL.$result.PHP_EOL.'</div>'.PHP_EOL;
		
		else
		
			return $result;
		
	}
	
	
	function run2($t, $internal = '', $internalbody = '', $d = array())
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
		$ifstack = array();
		$loopcounter;
		$walkstart = 0;
		$walkrelation1;
		$walkrelation2;
		$au;
		$tp;
		$ptag; $ptagerror; $ptag2;
		
		$ptag = '';
		$ptagerror = '<span class="error">';
		$ptagerrorend = '</span>';
		$ptag2 = PHP_EOL;
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
		
		$dict = $d;
		
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
			if ($internal) 
			{
				//echo 'internal'. $this->offsets[$internal].' ';;
				
				if ($this->offsets[$internal] >0)
					$il = $i + $this->offsets[$internal]+1-$parameteroffset;
				else
					$il = $i - $this->offsets[$internal]-$parameteroffset;
					
				//echo $il;
			}
			else
			{
				$this->results = '';
				$this->errors = array();
				$il = $i;	
			}
			$ti = ($il+1).''; // to text
			$this->currentline = $ti;
			$line = trim($lines[$i]); 
			$line = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $line);
			$line = str_replace(html_entity_decode("&#x200B;"),'',$line); // editor ZERO WIDTH SPACE
		
			// remove comments
			if (strpos($line,'// ')>-1)
				$line = trim(substr($line,0,strpos($line,'// ')));
			if (! $line or $line == '//') continue;
			// quote
			if (substr($line,0,1)=="'")
			{
				$this->result .= substr($line,2).$ptag2;
				continue;
			}
			$fields = explode(' ',$line);
			$command = array_shift($fields);
			$body = join(' ',$fields);
			//echo $command;
			switch($command)
			{
				case 'analyze' : 	if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Assert Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										$r->analyze($body);
										$this->stack[]=$r;

									}
									break;  
				case 'assert' : 	if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Assert Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										//$r->locals = $locals;
										if (! $r->assert($body))
										{
											$this->result .= $ptag.$ptagerror.$ti.' Error: Assertion error '.$body.$ptagerrorend.$ptag2;
											$this->errors[]=$il;
										}
										$this->stack[]=$r;

									}
									break; case 'beep' : 		{
										$this->result .= $ptag.$ptagerror.$ti.' Beep is not supported'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									break; // TO DO 
				
				case 'compile':		$xp = new swExpression($this->functions);
									$xp->compile($body);
									$this->result .= join(' ',$xp->rpn).$ptag2;
									$xp = null;
									break;
				
				case 'data':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Data Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										
										echotime('data start');
										// if we know there is no expressionm, then we can read 50% faster a CSV file
										if (trim($body) == "csv") 
										{
											$found = false;
											
											$csvlines = array( join(',',$r->header) );
											while($i<$c && !$found)
											{
												$i++;
												$il++;
												$ti = $il;
												$line = trim($lines[$i]);
												if ($line != 'end data')
													$csvlines[] = $line;
												else
													$found = true;
											}
											$r->setCSV($csvlines);
										}
										else
										{
											$found = false;
											
											$r->globals = $dict;
											$r->functions = $this->functions;
											
											while($i<$c && !$found)
											{
												$i++;
												$il++;
												$ti = $il;
												$line = trim($lines[$i]);
												if ($line != 'end data')
													$r->insert($line);
												else
													$found = true;
											}
										}
										echotime('data end '.$r->cardinality());
										$this->stack[]=$r;
									}
									break;
				case 'delegate':	if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Print Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									
									$xp = new swExpression($this->functions);
									$xp->compile($body);
									$tx = $xp->evaluate($dict);
									$txs = explode(' ',$tx);
									$tx0 = array_shift($txs);
									$tx = join(' ',$txs);
									$r = array_pop($this->stack);
									$this->result .= '{{'.$tx0.'|';
									$this->result .= $r->getCSVFormatted();
									$this->result .= '|'.$tx.' }}';
									$this->stack[]=$r;
									break;
				case 'deserialize': if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Deserialize Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->deserialize();
										$this->stack[] = $r;
									}
									break;
				case 'difference':	if (count($this->stack)<2)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Difference Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r2 = array_pop($this->stack);
										$r2->difference($r);
										$this->stack[] = $r2;
									}
									break;
				case 'dup':			if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Dup Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$this->stack[] = $r;
										$this->stack[] = $r->doClone();
									}
									break;						
				case 'echo' :		//$locals = array();
					
									
									if (count($this->stack)>0)
									{
										$r = $this->stack[count($this->stack)-1];
										if (count($r->tuples) > 0)
										{
											$tp = @$r->tuples[0];
											if ($tp)
												foreach($tp->pfields as $k=>$v)
													$dict[$k] = $v;
										}
									}
									
									$xp = new swExpression($this->functions);
									$xp->compile($body);
									$tx = $xp->evaluate($dict,$this->globals);
									$this->result .= $tx.PHP_EOL; // . $ptag2; // ???
									switch (substr($tx,1))
									{
										case '=':
										case '*':
										case '{':
										case '|': break;
										default: $this->result .= $ptag2 ;// ???
									}
									$xp = null;
									break;
				case 'else':		$loopcounter = 1;
									if (count($ifstack)>0)
									{
										while($i<$c && $loopcounter>0)
										{
											$i++;
											$mline = trim(@$lines[$i]);
											if (strpos($mline,'// ')>-1)
												$mline = trim(substr($mline,0,strpos($mline,'// ')));
											if (substr($mline,0,2) == 'if')
											{
												$loopcounter++;
											}
											elseif (substr($mline,0,4) == 'else')
											{
												if ($loopcounter == 1) $loopcounter--;
											}
											elseif (substr($mline,0,6) == 'end if')
											{
												$loopcounter--;
											}
										}
									}
									else
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Else is not possible here '.$ptagerrorend.$ptag2;
										$this->errors[]=$il;

									}
									break; 	
				case 'end':			if ($line == 'end while' && count($whilestack) > 0)
										$i = array_pop($whilestack) - 1;
									elseif($line == 'end if')
									{
										if (count($ifstack)>0)
											array_pop($ifstack);
										else
										{
											$this->result .= $ptag.$ptagerror.$ti.' Error : Endif not possible here'.$ptagerrorend.$ptag2;
											$this->errors[]=$il;
										}
									}
									elseif($line == 'end transaction')
									{
										if ($this->transactionerror != '')
										{
											$this->result .= $ptag.$ptagerror.$ti.' Error : Transaction error '.$this->transactionerror.$ptagerrorend.$ptag2;
											$this->errors[]=$il;
											foreach($this->transactiondict as $p)
											{
												unlink($p);
											}
										}
										else
										{
											foreach($this->transactiondict as $k=>$p)
											{
												if (file_exists($p))
												{
													unlink($k);
													rename($p,$k);
													$this->result .= $ptag.$ptagerror.$ti.' Error : Transaction errror: missing file  '.$p.$ptagerrorend.$ptag2;
													$this->errors[]=$il;
												}
												
											}

										}
										$this->transactiondict = array();
										$this->transactionprefix = '';
										$this->transactionerror = '';
									}
									elseif($line == 'end walk')
									{
										
										if (count($walkrelation1->tuples))
										{
											$tp = array_shift($walkrelation1->tuples);
											$d = $tp->fields();
											foreach($dict as $k=>$v)
											{
												if (in_array($k, $walkrelation2->header))
													$d[$k] = $v;
											}
											// print_r($d);
											$tp2 = new swTuple($d);
											$walkrelation2->tuples[$tp2->hash()] = $tp2;
											$i = $walkstart;
											
											if (count($walkrelation1->tuples)>0)
											{
												$tp = array_shift($walkrelation1->tuples);
												//print_r($tp);
												foreach($tp->pfields as $k=>$v)
												{
													$dict[$k] =$v;
												}
												array_unshift($walkrelation1->tuples, $tp);	
											}

										}
										else
										{
											array_pop($this->stack);
											$this->stack[] = $walkrelation2;
											$walkstart = 0;
										}
										
									}
									else
									{
										//print_r($lines);echo $line;
										$this->result .= $ptag.$ptagerror.$ti.' Error : End not possible here'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									
									break; 	
				case 'extend':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Extend Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										//$r->locals = $locals;
										$r->extend($body);
										$this->stack[] = $r;
									}
									break;	
				case 'filter':		$gl = array_merge($dict, $this->globals,$locals);
									
									global $swDebugRefresh;
									
									$r = swRelationFilter($body,$gl,$swDebugRefresh);
									$this->stack[] = $r;
									break;				
				case 'format':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Format Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->format1($body);
										$this->stack[] = $r;
									}
									break;				
				case 'formatdump':	if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Formatdump Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$vlist = array_keys($r->formats);
										$tlist = array();
										foreach($vlist as $velem)
										{
											$tlist[] = $velem.' '.$r->formats[$velem];
										}
										$this->result.= $ptag.'Formats: '.join(', ',$tlist).$ptag2;
										$this->stack[] = $r;
									}
									break;	
				case 'function':	$line = str_replace('(',' (',$line);
			 						$fields = explode(' ',$line);
			 						$command = array_shift($fields);
			 						$body = join(' ',$fields);
									$plines = array();
									$plines[] = $line;
									if (array_key_exists(trim($body), $this->offsets))
									{
										$this->result .= $ptag.$ptagerror.$ti.' Warning : Symbol overwritten'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;

									}
									$this->offsets[trim($body)] = $i;
									$found = false;
									while($i<$c && !$found)
									{
										$i++;
										$line = trim($lines[$i]);
										$plines[] = $line;
										if ($line == 'end function')
										$found = true;
									}
									if ($found)
									{
										$fn = new swExpressionCompiledFunction(trim($fields[0]),join(PHP_EOL,$plines),false);
										$this->functions[trim($fields[0])] = $fn;
										
										//print_r($this->functions);
									}
									else
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Function missing end'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									break; 
				case 'if':			$xp = new swExpression($this->functions);
									$xp->compile($body);
									$ifstack[] = $i;
									if ($xp->evaluate($dict) != '0')
									{}
									else
									{
										$loopcounter = 1;
										while($i<$c && $loopcounter>0)
										{
											$i++;
											$mline = trim($lines[$i]);
											if (strpos($mline,'// ')>-1)
												$mline = trim(substr($mline,0,strpos($mline,'// ')));
											if (substr($mline,0,2) == 'if')
											{
												$loopcounter++;
											}
											elseif (substr($mline,0,4) == 'else')
											{
												if ($loopcounter == 1) $loopcounter--;
											}
											elseif (substr($mline,0,6) == 'end if')
											{
												$loopcounter--;
											}
										}
									}
									break; 	
				/*case 'import':		{
										$this->result .= $ptag.$ptagerror.$ti.' Import is not supported'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									break;	*/
				case 'import':		$xp = new swExpression($this->functions);
									$xp->compile($body);
									$te = $xp->evaluate($this->globals,$dict);
									
									$r = swRelationImport($te);
									$this->stack[] = $r;
									break;

				case 'include':		$xp = new swExpression($this->functions);
									$xp->compile($body);
									$tn = $xp->evaluate($dict);
									if (!$this>validFileName($tn))
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Invalid name '.$tn.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$tmp = swRelationInclude($tn);
										$this->offsets[$tn] = -$i; // give only fixed number on error include
										$this->result .= $this->run($tmp,$tn,"");									
									}
				

									break;
				case 'input': 		$fieldgroup = explode(',',$body);
									$this->result .=  $ptag.'<nowiki><form method="post" action="index.php"></nowiki>';
									$this->result .= '<nowiki><input type="hidden" name="name" value="</nowiki>{{currentname}}<nowiki>">'.$ptag2.'</nowiki>';
									if (isset($_REQUEST['q']))
									$this->result .= '<nowiki><textarea style="display:none" name="q">'.$_REQUEST['q'].'</textarea></nowiki>';

									$this->result .=  '<nowiki><table class="input"></nowiki>';
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
										$xp = new swExpression($this->functions);
										$xp->compile($df);
										$tx = $xp->evaluate($this->globals, $locals);
										
										$txdefault = $tx;
										
										if (isset($_POST['submitinput']))
											$tx = @$_POST[$field];
											
										switch($fieldtype)
										{
											case 'textarea': $this->result .= '<nowiki><tr><td>'.$field.'</td><td><textarea name="'.$field.'" rows="12" cols="80">'.$tx.'</textarea></td></tr></nowiki>'; break;
											
											case 'select': $txoption = explode('|',$txdefault);
													
															$this->result .= '<nowiki><tr><td>'.$field.'</td><td><select name="'.$field.'"></nowiki>';										
															
															
															foreach($txoption as $o)
															{
																if ($o == $tx) $sel = 'SELECTED'; else $sel = '';
																
																$this->result .= '<nowiki><option value="'.$o.'" '.$sel.'>'.$o.'</option></nowiki>';
															}
															
															$this->result .= '<nowiki></select></td></tr></nowiki>';
															break;
											default: $this->result .= '<nowiki><tr><td>'.$field.'</td><td><input type="text" name="'.$field.'" value="'.$tx.'"></td></tr></nowiki>';
											}
										
									}
									$this->result .=  '<nowiki><tr><td></td><td><input type="submit" name="submitinput" value="submit"></td></tr></nowiki>';
									$this->result .=  '<nowiki></table></nowiki>';
									$this->result .=  '<nowiki></form></nowiki>';
									
									if (isset($_POST['submitinput']))
									{
										//print_r($inputfields);
										
										foreach($inputfields as $key)
										{
											$this->globals[$key] = @$_POST[$key];
											$dict[$key] = @$_POST[$key];
										}
										//print_r($this->globals);
									}
									else
									{
										$i=$c; break; 
									}
									
									break;
				case 'insert':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Insert Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										//$r->locals = $locals;
										$r->insert($body);
										$this->stack[] = $r;
									}
									break;	
				case 'intersection':	if (count($this->stack)<2)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Intersection Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r2 = array_pop($this->stack);
										$r2->intersection($r);
										$this->stack[] = $r2;
									}
									break;	
				case 'join':	if (count($this->stack)<2)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Join Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										//$r->locals = $locals;
										$r2 = array_pop($this->stack);
										$r2->join($r,$body);
										$this->stack[] = $r2;
									}
									break;									
				case 'label':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Label Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->label($body);
										$this->stack[] = $r;
									}
									break;				
				case 'limit':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Limit Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										
										
										
										//$r->locals = $locals;
										$r->limit($body);
										$this->stack[] = $r;
									}
									break;	
				case 'memory':	    break; 	
				case 'order':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Order Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->order($body);
										$this->stack[] = $r;
									}
									break;	
				case 'parameter':	if (count($internals)==0)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Parameter stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$tx = trim(array_shift($internals));
										$xp = new swExpression($this->functions);
										$xp->compile($tx);
										$dict[trim($body)] = $xp->evaluate($dict);
										$parameteroffset++;
									}
									break;
									
				case 'pivot':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Pivot Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->pivot($body);
										$this->stack[] = $r;
									}
									break;	
				
				case 'pop':			if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Pop Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r = null;
									}
									break;	
				case 'print':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Print Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										
										// nowiki seems not to break on newlines.
										
										
										
										switch(trim($body))
										{
											case 'csv':		$this->result .= '<nowiki><textarea class="csv">'. $r->getCSV().'</textarea></nowiki>'; break;
											case 'fields':	$this->result .= $r->toFields(); break;
											case 'json':	$this->result .= '<nowiki><textarea class="json">'. $r->getJSON().'</textarea></nowiki>'; break;
											case 'space':	$this->result .= $r->getSpace(); break;

											case 'tab':		$this->result .= '<nowiki><textarea class="tab">'. $r->getTab().'</textarea></nowiki>'; break;
											case 'raw':		$this->result .= $r->getTab(); break;
											
											default: 		// pass limit as parameter
															$this->result .= $r->toHTML($body); break;
										}
										
										
										$this->stack[] = $r;
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
									if ( substr($body,0,1) != '(' || substr($body,-1,1) != ')')
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Missing paranthesis '.$key.$ptagerrorend.$ptag2;
										$this->errors[]=$il;

									}
									else
									{
										$body = substr($body,1,-1);
									
									
										if ($body != '')
										{
											$commafields = explode(',',$body);
											$k = count($commafields);
											for($j=0;$j<$k;$j++)
											{
												$plines[] = 'parameter '.trim($commafields[$j]);
											}
											if (array_key_exists($key, $this->offsets))
											{
												$this->result .= $ptag.$ptagerror.$ti.' Warning : Symbol overwritten'.$ptagerrorend.$ptag2;
												$this->errors[]=$il;
											}
											
											$this->offsets[$key] = $i+1;
											$found = false;
											while($i<$c && !$found)
											{
												$i++;
												$line = trim($lines[$i]); 
												if ($line != 'end program')
													$plines[] = $line;
												else
													$found = true;											
											}
											//print_r($plines);
											if ($found)
												$this->programs[$key] = join(PHP_EOL,$plines);
											else
											{
												$this->result .= $ptag.$ptagerror.$ti.' Error : Program missing end'.$ptagerrorend.$ptag2;
												$this->errors[]=$il;
											}										
										}
									}									
									break; 				
				case 'project':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Project Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack); //print_r($r);
										$r->aggregators = $this->aggregators;
										$r->globals = $dict;
										$r->functions = $this->functions;
										//$r->locals = $locals;
										
										
										
										$r->project($body);
										$this->stack[] = $r;
										
										//echo "project"; print_r($r);
									}
									break;	
				case 'read':		$r = new swRelation('',$locals,$dict);
									$enc = 'utf8';
									if (substr(trim($body),0,8)=='encoding')
									{
										$body = trim(substr(trim($body),8));
										$fields = explode(' ',$body);
										$enc = array_shift($fields);
										if (count($fields)==0)
										{
											$this->result .= $ptagerror.$ti.' Error : Missing filename'.$ptagerrorend.$ptag2;
											$this->errors[] = $il;
											$body = '';
										}
										else
											$body = join(' ',$fields);
									}
									$xp = new swExpression($this->functions);
									$xp->compile($body);
									$tn = $xp->evaluate($dict);
									
									if ($tn == '') $tn = ' ';
									if (!$this->validFileName(str_replace('.csv','',str_replace('.txt','',str_replace('.json','',$tn)))))
									{	
										$this->result .= $ptagerror.$ti.' Error : Invalid filename '.$tn.$ptagerrorend.$ptag2;
										$this->errors[] = $il;
										}
									else
									{
										if ($tn=='')
										{
											$this->result .= $ptagerror.$ti.' Error : Empty filename'.$ptagerrorend.$ptag2;
											$this->errors[] = $il;
										}
										
										elseif(strpos($tn,'.') === FALSE)
										{
											if (array_key_exists($tn, $this->globalrelations))
												$r = $this->globalrelations[$tn];
											else
											{
												$this->result .= $ptagerror.$ti.' Error : Relation does not exist'.$ptagerrorend.$ptag2;
												$this->errors[] = $il;
												$r = new swRelation('',$dict);
											}
											$this->stack[] = $r->doClone();
											//print_r($stack);
												
										}
										elseif(true)  // true $currentpath files are in site
										{
											if (isset($this->transactiondict[$tn]))
												$tn = $this->transactiondict[$tn];
											
											$file1 = 'site/files/'.$tn;
											$file2 = 'site/cache/'.$tn;
											if (!file_exists($file1) and !file_exists($file2))
											{
												$this->result .= $ptagerror.$ti.' Error : File does not exist '.$tn.$ptagerrorend.$ptag2;
												$this->errors[] = $il;
												$tip = '';
											}
											else
											{
												if (file_exists($file1) and file_exists($file2))
												{
													 if (filemtime($file1) >= filemtime($file2)) 
													 	$file = $file1;
													 else
													 	$file = $file2;
												}
												elseif (file_exists($file1)) $file = $file1;
												else $file = $file2;
												
												
												$tip = file_get_contents($file);
											}
											switch($enc)
											{
												case 'macroman': $tip = iconv('macinstosh', 'UTF-8', $tip); break;
												case 'windowslatin1': $tip = mb_convert_encoding($tip, 'UTF-8', 'Windows-1252', $tip); break;
												case 'latin1': $tip = utf8_encode($tip); break;
												case 'utd-8': break;
											}
											if (strlen($tip)>0)
											{
												$r = new swRelation('',$dict);
												switch(substr($file,-4))
												{
													case '.csv': 	$r->setCSV(explode(PHP_EOL,$tip));
																	break;
													case '.txt': 	$r->setTab(explode(PHP_EOL,$tip));
																	break;
													case 'json':	$r->setJSON(explode(PHP_EOL,$tip));
																	break;
													default:		$this->result .= $ptagerror.$ti.' Error : Invalid filename '.$tn.$ptagerrorend.$ptag2;
																	$this->errors[] = $il;
																	break;
												}
												$this->stack[] = $r;
											}
											else
											{
												$this->result .= $ptagerror.$ti.' Error : Empty file'.$ptagerrorend.$ptag2;
												$this->errors[] = $il;
											}

										}
									}
									break;

										
										
										
									break;
				case 'relation':	$r = new swRelation($body, $dict);
									$this->stack[] = $r;
									break;	
				case 'rename':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Rename Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->rename1($body);
										$this->stack[] = $r;
									}
									break;										
				case 'run':			// run pg(x) and run pg (x) are both valid
									// so we split on paranthesis, not space
									
									$fields = explode('(',$body);
									$key = trim(array_shift($fields));
									$body = '('.trim(join(' ',$fields));
									if ( substr($body,0,1) != '(' || substr($body,-1,1) != ')')
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Missing paranthesis '.$key.$ptagerrorend.$ptag2;
										$this->errors[]=$il;

									}
									elseif (array_key_exists($key, $this->programs))
									{
										$tx = $this->programs[$key];
										
										
										$dict2 = array();
										$dict0 = array();
										foreach($dict as $k=>$v)
										{
											$dict2[$k] = $v;
										}
										$dict0 = $dict;
										$body = substr($body,1,-1);
										$this->result = $this->run2($tx,$key,$body,$dict); 
										$dict = $dict0; 
									}
									else
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Program not defined '.$key.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}	
									break; 
				case 'select':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Select Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										// $r->locals = $locals;
										$r->select1($body);
										$this->stack[] = $r;
									}
									break;	
				case 'serialize':	if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Serialize Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->serialize1();
										$this->stack[] = $r;
									}
									break;	
				case 'set':			$locals= array();
									if (count($this->stack)>0)
									{
										$r = array_pop($this->stack);
										if (count($r->tuples)>0 && !$walkstart)
										{
											$tp = array_shift($r->tuples);
											//print_r($tp);
											foreach($tp->pfields as $k=>$v)
											{
												$dict[$k] =$v;
											}
											array_unshift($r->tuples, $tp);
											
										}
										$this->stack[] = $r;
									}
										//print_r($dict);
										
										$fields = explode(' ',$body);
										$key = array_shift($fields);
										$body = join(' ',$fields);
										
										$fields = explode(' ',$body);
										$eq = array_shift($fields);
										$body = join(' ',$fields);
										if ($eq != '=')
										{
											$this->result .= $ptag.$ptagerror.$ti.' Error : Set missing ='.$ptagerrorend.$ptag2;
											$this->errors[]=$il;
										}
										else
										{
											$xp = new swExpression($this->functions);
											$xp->compile($body);
											$dict[$key] = $xp->evaluate($dict);
											
										}	

									break; 
				case 'stack':		$r = new swRelation('stack, cardinality, column', $dict);
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
				case 'swap':		if (count($this->stack)<2)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Swap Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r2 = array_pop($this->stack);
										$this->stack[] = $r;
										$this->stack[] = $r2;
									}
									break;	
				case 'template':	if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Template Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$xp = new swExpression($this->functions);
										$xp->compile($body);
										$tn = $xp->evaluate($dict);
										if (!$this->validFileName($tn))
										{
											$this->result .= $ptag.$ptagerror.$ti.' Error : Invalid name '.$tn.$ptagerrorend.$ptag2;
											$this->errors[]=$il;
										}
										else
										{
											$tmp = swRelationTemplate($tn);
											$this->result .= $r->toTemplate($tmp);											
										}
										$this->stack[] = $r;
									}
									break;
				case 'transaction' :$this->transactionprefix = 'tmp-';
									$this->transactionerror = '';
									$this->transactiondict = array();
									break;
				case 'union':		if (count($this->stack)<2)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r2 = array_pop($this->stack);
										$r2->union($r);
										$this->stack[] = $r2;
									}
									break;
								
				case 'update':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Update Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$r->globals = $dict;
										$r->functions = $this->functions;
										// $r->locals = $locals;
										$r->update($body);
										$this->stack[] = $r;
									}
									break;	
				case 'virtual':		$xp = new swExpression($this->functions);
									$xp->compile($body);
									$te = $xp->evaluate($this->globals,$dict);
									$r = swRelationVirtual($te);
									$this->stack[] = $r;
									break;
				case 'walk' :		$walkstart = $i;
									$walkrelation1 = array_pop($this->stack);			
									$walkrelation2 = new swRelation($walkrelation1->header);
									$pairs = explode(',',$body);								
									foreach($pairs as $p)
									{
										if (!in_array(trim($p),$walkrelation2->header))
											$walkrelation2->addColumn(trim($p));
									}
									$r = array_pop($this->stack);
									if (count($walkrelation1->tuples)>0)
									{
										$tp = array_shift($walkrelation1->tuples);
										//print_r($tp);
										foreach($tp->pfields as $k=>$v)
										{
											$dict[$k] =$v;
										}
										array_unshift($walkrelation1->tuples, $tp);	
									}
									array_unshift($this->stack,$walkrelation1);
									break;
									
												
				case 'while':		$xp = new swExpression($this->functions);
									$xp->compile($body);
									if ($xp->evaluate($dict) != '0') // no locals
									{
										$whilestack[] = $i;
									}
									else
									{
										$loopcounter = 1;
										while($i<$c && $loopcounter >0)
										{
											$i++;
											$mline = trim($lines[$i]);
											if (strpos($mline,'// ')>-1)
												$mline = trim(substr($mline,0,strpos($mline,'// ')));

											if (substr($mline,0,5)=='while')
												$loopcounter++;
											elseif (substr($mline,0,9)=='end while')
												$loopcounter--;
										}
									}
										
									break; 	
				case 'write':		if (count($this->stack)<1)
									{
										$this->result .= $ptag.$ptagerror.$ti.' Error : Write Stack empty'.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
									else
									{
										$r = array_pop($this->stack);
										$xp = new swExpression($this->functions);
										$xp->compile($body);
										$tn = $xp->evaluate($dict);
										if ($this->transactionprefix != '')
										{
											$this->transactiondict[$tn]=$this->transactionprefix.$tn;
											$tn = $this->transactionprefix.$tn;
										}
										if (!$this->validFileName(str_replace('.csv', '',str_replace('.txt','',str_replace('.json','',$tn)))))
										{	
										$this->result .= $ptagerror.$ti.' Error : Invalid filename '.$tn.$ptagerrorend.$ptag2;
										$this->errors[] = $il;
										}
										else
										{
											$file = 'site/cache/'.$tn;
											$written = 0;
											switch(substr($file,-4))
											{
												case '.csv': 	$written = file_put_contents($file,$r->getCSV()); 
																break;
												case '.txt': 	$written = file_put_contents($file,$r->getTab()); 
																	break;
												case 'json':	$written = file_put_contents($file,$r->getJSON()); 
																	break;
												default:		if (strpos($tn,'.') === FALSE)
																	$this->globalrelations[$tn] = $r->doClone();
																else
																{
																	$this->result .= $ptagerror.$ti.' Error : Invalid filename '.$tn.$ptagerrorend.$ptag2;
																	$this->errors[] = $il;
																}
																	break;
											}
											//print_r($this->globalrelations[$tn]);
											if ($written === FALSE)
											{
												$this->result .= $ptagerror.$ti.' Error : File not written '.$tn.$ptagerrorend.$ptag2;
												$this->errors[] = $il;
											}

										}	
										$this->stack[] = $r;
									}
									break;
				default: 			{
										$this->result .= $ptag.$ptagerror.$ti.' Error : '.$line.$ptagerrorend.$ptag2;
										$this->errors[]=$il;
									}
			}
			
			
			if (count($this->stack) > 0)
			{
				$top = $this->stack[count($this->stack)-1];
				if (! is_object($top))
				{
					$this->result .= $ptag.$ptagerror.$ti.' Error : top stack not object '.$line.$ptagerrorend.$ptag2;
					$this->errors[]=$il;
					//print_r($top);
					break;
				}
				elseif (! is_a($top,'swRelation'))
				{
					$this->result .= $ptag.$ptagerror.$ti.' Error : top stack not relation '.$line.$ptagerrorend.$ptag2;
					$this->errors[]=$il;
					//print_r($top);
					break;
				}
			}		
		}
		
		global $swOvertime; 
		global $lang;		
		$overtimetext = '';
		if ($swOvertime)
			$overtimetext .= '<nowiki><div class="overtime">'.swSystemMessage('there-may-be-more-results',$lang).'</div></nowiki>'; 
		if (!$internal)
			return $this->result.$overtimetext; 
		else
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
	
	function ValidFileName($s)
	{
		if (strlen($s) < 1) return false;
		if (substr($s,0,1) == ".") return false;
		if (stristr($s,':')) return false;
		if (stristr($s,'/')) return false;
		return true;
	}
		
	
}

class swRelation 
{
	var $header = array();
	var $tuples = array();
	var $globals = array();
	var $formats = array();
	var $labels = array();
	var $functions = array();
	var $aggregators = array();
	
	function __construct($columns, $g = array())
	{
		$this->globals = $g;
		
		if (! is_array($columns))
			$columns = explode(',',$columns);
			
		foreach($columns as $s)
		{
			if ($s != '')
			$this->addColumn($s);
		}
	}
	
	function addColumn($s)
	{
		$s = $this->validName(trim($s));
		if ($s == '') throw new swRelationError('Invalid name '.$s,102);
		if (! in_array($s,$this->header)) $this->header[] = $s;
	}
	
	function analyze($t)
	{
		
		echotime('analyze '.$t);
		$h = $this->header;
		if (strstr($t,'first'))
		{
			$t = str_replace('first','',$t);
		}
		else
		{
			array_shift($h);
		}
		
		if (trim($t) == '' ) $t = 'means';
		
		switch(trim($t))
		{
			case 'means' :  $this->labels = array();
			
							$aggs = array('count','sum','min','max','avg','median','stddev','var');
							
							$r0 = $this->doClone();
							$this->select1('0'); false;
							$this->extend('means = ""');
							$this->project('means, '.join(', ',$h));
							
							foreach($aggs as $agg)
							{	
								$r1 = $r0->doClone();
								$p = array();
								$rn = array();							

								foreach($h as $v)
								{
									$p[] = $v.' '.$agg;
									$rn[] = $v.'_'.$agg.' '.$v;
								}
								// print_r($rn);
								
								$r1->project2($p);
								$r1->rename12($rn);
								$r1->extend('means = "'.$agg.'"');
								
															
								$this->union($r1);
							
							}
								break;
							
			case 'correlation' : $acc = array();
								 
								 foreach($h as $keyx)
								 {									
									foreach($h as $keyy)
									{
										$acc[$keyx][$keyy]['x'] = new swAccumulator('avg');
										$acc[$keyx][$keyy]['y'] = new swAccumulator('avg');
										$acc[$keyx][$keyy]['xstddev'] = new swAccumulator('stddev');
										$acc[$keyx][$keyy]['ystddev'] = new swAccumulator('stddev');
										$acc[$keyx][$keyy]['xy'] = new swAccumulator('avg');
									}
								 }
			
								 foreach($this->tuples as $t)
								 {
									 foreach($h as $keyx)
									 {
										 $x = $t->value($keyx);
										 										 	
										 foreach($h as $keyy)
										 {
										 	$y = $t->value($keyy);
										 	if ($x != '' && $y != '')
										 	{
										 		$acc[$keyx][$keyy]['x'] ->add(floatVal($x));
										 		$acc[$keyx][$keyy]['y'] ->add(floatVal($y));
										 		$acc[$keyx][$keyy]['xstddev'] ->add(floatVal($x));
										 		$acc[$keyx][$keyy]['ystddev'] ->add(floatVal($y));
										 		$acc[$keyx][$keyy]['xy']->add(floatVal($x)*floatVal($y));
										 	}
										 }
									 }
								 }
								 								 
								 $this->select1('0'); false;
								 $this->extend('correlation = ""');
								 $this->project('correlation, '.join(', ',$h));
								 
								 foreach($h as $keyx)
								 {
									 $cols = array($keyx);
									 
									 foreach($h as $keyy)
									 {
										 $xvar = $acc[$keyx][$keyy]['xstddev']->reduce();
										 $yvar = $acc[$keyx][$keyy]['ystddev']->reduce();
										 $xavg = $acc[$keyx][$keyy]['x']->reduce();
										 $yavg = $acc[$keyx][$keyy]['y']->reduce();
										 $xyavg = $acc[$keyx][$keyy]['xy']->reduce();
										 
										 if (intval($xvar) * intval($yvar) != 0)
										 	$cov = ($xyavg - $xavg * $yavg) / ($xvar * $yvar);
										 else
										 	$cov = 0;	
										 								 	
										 	
										 $cols[] = $cov;
										 
										 /*
											 
											 E(X) = avg(X)

											 cov(X,Y) = E(XY) - E(X)E(Y)

											 var(X) = E(XX) - E(X)*E(X)

											 r(X,Y) = cov(X,Y) / sqrt( var(x) * var(y))
 
										 */
										 
										 
										 
										 
									 }
									 
									 $this->insert2($cols);
								 }

								 
								 
								 				
		}
		
		echotime('analyze end');
	}


	function arity()
	{
		return count($this->header);
	}
	
	function assert($t)
	{
		$list = explode(' ',$t);
		if (count($list) < 2 ) throw new swRelationError('Assert missing expression',402); 
		$s = array_shift($list);
		return $this->assert2($s,join(' ',$list));
	}
	
	function assert2($method, $expression)
	{
		$xp = new swExpression;
		$d = array();
		$test = array();
		$s; 
		$list = array();
		$i; $c; 
		
		$c = count($this->tuples);
		
		try
		{
		
			switch ($method)
			{
				case 'all': $xp->compile($expression);
							foreach($this->tuples as $tp)
							{
								$d = $tp->fields();
								if ($xp->evaluate($d, $this->globals) == '0')
									return false;
							}
							return true;
				case 'exists': $xp->compile($expression);
							foreach($this->tuples as $tp)
							{
								$d = $tp->fields();
								if ($xp->evaluate($d, $this->globals) == '0')
									return true;
							}
				case 'unique': $xp->compile($expression);
							$test = array();
							foreach($this->tuples as $tp)
							{
								$d = $tp->fields();
								$s = $xp->evaluate($d, $this->globals);
								if (array_key_exists($s, $test))
									return false;
								$test[$s] = true;
							}
							return true;
				case 'columns': $list = explode(',',$expression);
							foreach($list as $s)
							{
								if (! in_array(trim($s), $this->header)) return false;
							}
							return true;
				default: 	throw new swRelationError('Assert invalid method',402);
			}
		}
		catch (Exception $e )
		{
			throw new swRelationError('Assert Runtime Error '.$e->getMessage() ,402);	
		}
		
	}
	
	function cardinality()
	{
		return count($this->tuples);
	}
	
	function cleanColumn($s)
	{
		//echo $s . "= ";
		
		//if ($s == '*') return '_all';
		
		$result; 
		$i; $c; 
		$ch; 
		$list1 = '_abcdefghijklmnoprsqtuvwxyz';
		$list = 'abcdefghijklmnoprsqtuvwxyz0123456789_';
		
		$c = strlen($s);
		$ch = substr($s,0,1);
		
		if (!stristr($list1,$ch)) $result = '_'; else $result = '';
		for($i = 0; $i<$c; $i++)
		{
			$ch = substr($s,$i,1);
			if (!stristr($list,$ch)) $result .= '_'; else $result .= $ch;
		}
		
		//echo $result . "; ";
		
		return $result;		
	}
	
	function doClone()
	{
		$result = new swRelation($this->header,$this->globals);
		$result->tuples = array_clone($this->tuples);
		$result->formats = array_clone($this->formats); 
		$result->labels = array_clone($this->labels);
		$result->functions = $this->functions;
		return $result;
		
	}
	
	function delete($condition)
	{
		$xp = new swExpression($this->functions);
		$xp->compile($condition);
		foreach($this->tuples as $k=>$t)
		{
			$d = $t->fields();
			if ( $xp->evaluate($d) != '0' ) unset($this->tuples[$k]);
		}
	}
	
	function deserialize()
	{
		$r;
		$i;$j;$m;$n;
		$list = array();
		$list2 = array();
		$observationkey;
		$tp = array();
		$tp2 = array();
		$d = array();
		$lastobservation;
		$nulltext = '';
				
		if (count($this->header)< 3)
			throw new swRelationError('Deserialize less than 3 properties',602);
		if (count($this->header)> 3)
			throw new swRelationError('Deserialize more than 3 properties',602);
			
		$this->order($this->header[0]);
		
		$r = new swRelation('',$this->globals);
		// $r->addColumn($this->header[0]);
		
		$n = count($this->tuples);
		
		foreach($this->tuples as $tp)
		{
			$cc = $this->cleanColumn($tp->value($this->header[1]));
			
			if (!in_array($cc,$this->header))
			{
				$r->addColumn($cc);
				if (isset($this->formats[$this->header[1]]))
				$this->formats[$cc] = $this->formats[$this->header[1]];
				$this->labels[$cc] = $tp->value($this->header[1]);
			}
			
		}	
		
		$theformat = @$this->formats[$this->header[2]];
		
		sort($r->header);
		// star must be at the end
		
		array_unshift($r->header,$this->header[0]);
				
		$lastobservation = null;
		$d = null;
		foreach($this->tuples as $tp)
		{
			if ($tp->value($this->header[0]) != $lastobservation)
			{
				if ($d)
				{
					foreach($r->header as $mf)
					{
						if (!array_key_exists($mf, $d))
							$d[$mf] = $nulltext;
					}
					$tp2 = new swTuple($d);
					$r->tuples[$tp2->hash()] = $tp2;
				}
				$d = array();
				$d[$this->header[0]] = $tp->value($this->header[0]);
				$lastobservation = $tp->value($this->header[0]);
			}
			$d[$this->cleanColumn($tp->value($this->header[1]))] = $tp->value($this->header[2]);
		}
		
		
	
		if ($d)
		{
			foreach($r->header as $mf)
			{
				if (!array_key_exists($mf, $d))
					$d[$mf] = $nulltext;
					
				if ($mf != $this->header[0])
					$this->formats[$mf] = $theformat;
			}
			$tp2 = new swTuple($d);
			$r->tuples[$tp2->hash()] = $tp2;
		}
		
		$this->header = $r->header;
		$this->tuples = $r->tuples;
		
	}
	
	function difference($r)
	{
		if (count($this->tuples)+count($this->tuples)>10000) echotime('difference '.count($this->tuples));
		
		$e; $tp;
		$i; $c;
		
		$e = $this->emptyTuple();
		$c = count($r->tuples);
		foreach($r->tuples as $tp)
		//for ($i = $c-1; $i>0; $i--)
		{
			//$tp = $r->tuples[$i];
			if ($tp->sameFamily($e))
			{
				if (array_key_exists($tp->hash(), $this->tuples))
					unset($this->tuples[$tp->hash()]);
			}
			else
			{
				
				throw new swRelationError('Difference different columns',302);
			}
			
		}
		
	}
	
	function emptyTuple()
	{
		$d = array();
		foreach($this->header as $t)
		{
			$d[$t] = '';
		}
		$t = new swTuple($d);
		return $t;
	}
	
	function extend($t)
	{
		if (count($this->tuples)>10000) echotime('extend '.count($this->tuples));
		
		$fields = explode(' ',$t);
		$first = array_shift($fields);
		$body = join(' ',$fields);
		$fields = explode(' ',$body);
		$eq = array_shift($fields);
		$body = join(' ',$fields);
		if ($eq != '=')
			throw new swRelationError('Extend missing =',121);

		$this->extend2($first, $body); 
	}
	
	function extend2($label, $expression)
	{
		if (in_array($label, $this->header))
			throw new swRelationError('Extend column exists already',121);
		
		$newtuples = array();
		
		$this->addColumn($label);
		
		$xp = new swExpression($this->functions);
		$xp->compile($expression);
		
		$c = count($this->tuples);
		$i=0;
		foreach($this->tuples as $tp)
		//for ($i=0;$i<$c;$i++)
		{
			$d = $tp->fields();
			$this->globals['rownumber'] = strval($i+1); $i++;
			$v = $xp->evaluate($d, $this->globals);
			$d[$label] = $v;
			$tp = new swTuple($d);
			$newtuples[$tp->hash()] = $tp;
			//print_r($newtuples);
		}
		
		$this->tuples = $newtuples;
		
	}
	
	function format1($t)
	{
		$pairs = explode(',',$t);
		$this->format12($pairs);
	}
	
	function format12($pairs)
	{		
		foreach($pairs as $p)
		{
			$fields = explode(' ',trim($p));
			$fields[0] = $this->validName($fields[0]);
			if (count($fields) != 2)
				throw new swRelationError('Invalid format',121);
			$fields[0] = trim($fields[0]);
			$fields[1] = trim($fields[1]);
			if (!in_array($fields[0], $this->header))
				throw new swRelationError('Unknown format '.$fields[0],122);
			$this->formats[$fields[0]] = $fields[1];
		}
	}
	
	function format2($d, $f)
	{
		if (substr($f,0,1)=='"') $f = substr($f,1);
		if (substr($f,-1,1)=='"') $f = substr($f,0,-1);
		$result = swNumberformat($d,$f);
		//if (abs($d)< 10.0E-12) $result = '';
		if (substr($result,0,1)=="'") $result = substr($result,1);
		return $result;
		
	}
	
	function import($value)
	{
		$lines = explode(PHP_EOL,$value);
		$this->header = array('lines');
		$this->tuples = array();
		foreach($lines as $line)
		{
			$d = array();
			$d['line'] = swEscape($line);
			$tp = new swTuple($d);
			$this->tuples[$tp->hash] = $tp;

		}
	}
	
	function insert($t)
	{
		$xp = new swExpression($this->functions);
		$xp->expectedreturn =  count($this->header);
		$xp->compile($t);
		$test = $xp->evaluate($this->globals);
		$pairs = array();
		while (count($xp->stack) >0)
		{
			$pairs[] = array_shift($xp->stack);
		}
		$this->insert2($pairs);
	}
	
	function insert2($fields)
	{
		if (count($fields) != count($this->header))
			throw new swRelationError('Insert Arity',101);
		$d = array();
		$c = count($this->header); 
		for($i=0;$i<$c;$i++)
		{
			$d[$this->header[$i]] = $fields[$i];
		}
		$tp = new swTuple($d); 
		if ($tp->hasValues())
			$this->tuples[$tp->hash()] = $tp;
		
	}
	
	function intersection($r)
	{
		$newtuples = array();
		$e = $this->emptyTuple();
		$c = count($r->tuples);
		foreach($r->tuples as $tp)
		//for($i = $c-1; $i>=0; $i--)
		{
			if ($tp->sameFamily($e))
			{
				if (array_key_exists($tp->hash(), $this->tuples))
					$newtuples[$tp->hash()] = $tp;
		
			}
			else
				throw new swRelationError('Intersection different columns',301);
		}
	}
	
	function join($r,$expression)
	{
		if (count($this->tuples)+count($this->tuples)>10000) echotime('join '.count($this->tuples));
		
		if ($expression == 'cross') { $this->join($r,'1'); return; }
		if ($expression == 'rightsemi') { $r->join($this, 'leftsemi'); $this->header = $r->header; $this->tuples = $r->tuples; return ; }
		if ($expression == 'rightanti') { $r->join($this, 'leftanti'); $this->header = $r->header; $this->tuples = $r->tuples; return ; }
		if ($expression == 'right') { $r->join($this, 'left'); $this->header = $r->header; $this->tuples = $r->tuples; return ; }
		if ($expression == 'outer') { $r2 = $this->doClone(); $r3 = $r->doClone(); $this->join($r,'left'); $r3->join($r2,'left'); $this->union($r3); return; }
		
		if (in_array($expression,array('left','leftsemi','leftanti','natural')))
		{	$this->joinHash($r, $expression); return; }
		
		$commonheader = array();
		$leftheader = array();
		$rightheader = array();
		
		foreach($this->header as $f)
		{
			if (in_array($f,$r->header))
				$commonheader[] = $f;
			else
				$leftheader[] = $f;	
		}
		
		foreach($r->header as $f)
		{
			if (!in_array($f,$this->header))
			{
				$rightheader[] = $f;
				if (array_key_exists($f, $r->formats))
					$this->formats[$f] = $r->formats[$f];
				if (array_key_exists($f, $r->labels))
					$this->labels[$f] = $r->labels[$f];
			}	
		}
		
		$header = array_clone($this->header);
		$this->header = array();
		foreach($header as $f)
		{
			if (in_array($f, $commonheader))
				$this->addColumn($f.'_1');
			else
				$this->addColumn($f);
		}
		foreach($r->header as $f)
		{
			if (in_array($f, $commonheader))
				$this->addColumn($f.'_2');
			else
				$this->addColumn($f);
		}
		//print_r($this->header);
		
		$xp = new swExpression;
		$xp->compile($expression);
		
		$c = count($this->tuples);
		$c2 = count($r->tuples);
		
		//print_r($r->tuples);
	
		//print_r($this->header);
		$newtuples = array();
		foreach($this->tuples as $tp1)
		{
			$d10 = $tp1->fields();
			foreach($commonheader as $cf)
			{
				$d10[$cf.'_1'] = $d10[$cf];
				unset($d10[$cf]);
			}
			foreach($leftheader as $cf)
			{
				$d10[$cf] = $d10[$cf];
			}
			$leftfound = false;
			
			// print_r($d10);
			
			
			
			foreach($r->tuples as $tp2)
			{
				$d1 = array_clone($d10);
				$d2 = $tp2->fields();
				foreach($commonheader as $cf)
				{
					$d1[$cf.'_2'] = $d2[$cf];
				}
				foreach($rightheader as $cf)
				{
					$d1[$cf] = @$d2[$cf];
				}	
				$tx = $xp->evaluate($d1,$this->globals);
				if ($tx != '0')
				{
					$tp = new swTuple($d1);
					$newtuples[$tp->hash()] = $tp;
				}			
			}
			
		}
		//print_r($this->header);
		/*
		foreach($commonheader as $cf)
		{
			if (!in_array($cf, $this->header))
				array_unshift($this->header,$cf);
		}
		*/
		$this->tuples = $newtuples;
		
		
	}
	
	function joinHash($r, $expression)
	{
		$commonheader = array();
		foreach($this->header as $f)
		{
			if (in_array($f,$r->header))
				$commonheader[] = $f;
			else
				$leftheader[] = $f;	
		}
		if (count($commonheader) == 0)
		{
			$this->header = array();
			$this->tuples = array();
			return;
		}	
		
		$rightheader = array();
		foreach($r->header as $f)
		{
			if (!in_array($f,$this->header))
			{
				$rightheader[] = $f;
				if (array_key_exists($f, $r->formats))
					$this->formats[$f] = $r->formats[$f];
				if (array_key_exists($f, $r->labels))
					$this->labels[$f] = $r->labels[$f];
			}	
		}
		
		$expression2 = '';
		foreach($commonheader as $f)
		{
			if ($expression2 == '')
				$expression2 = $f.'_1 == '.$f.'_2';
			else
				$expression2 .= ' and '.$f.'_1 == '.$f.'_2';
		}
		
		switch ($expression)
		{
			case 'leftanti' : break;
			case 'natural'  : foreach($rightheader as $f) $this->addColumn($f); break;
			case 'left'  	: foreach($rightheader as $f) $this->addColumn($f); break;
			case 'leftsemi' : break;
			default 		: throw new swRelationError('Join Hash invalid expression ',671);
		}
		
		$dhash = array();
		$tpstack = array();
		
		$c = count($this->tuples);
		$c2 = count($r->tuples);		
		
		foreach($r->tuples as $tp2)
		{
			$d10 = array();
			foreach($commonheader as $cf)
				$d10[$cf] = $tp2->value($cf);
			$tp = new swTuple($d10);
			
			if (array_key_exists($tp->hash(),$dhash))
				$tpstack = $dhash[$tp->hash()];
			else
				$tpstack = array();
			$tpstack[] = $tp2;
			$dhash[$tp->hash()] = $tpstack;
		}
		
		$newtuples = array();
		
		foreach($this->tuples as $tp1)
		{
			$d1 = array();
			foreach($commonheader as $cf)
			{
				$d1[$cf] = $tp1->value($cf);
			}
			$tp = new swTuple($d1);
			if (array_key_exists($tp->hash(),$dhash))
			{
				$tpstack = $dhash[$tp->hash()];
				$tpstack = array_clone($tpstack);
				
				switch ($expression)
				{
					case 'left'		:
					case 'natural'	:   $d10 = $tp1->fields();
										while(count($tpstack)>0)
										{
											$d11 = array_clone($d10);
											$tp2 = array_shift($tpstack);
											$d2 = $tp2->fields();
											foreach($r->header as $t)
												$d11[$t] = $d2[$t];
											$tp11 = new swTuple($d11);
											$newtuples[$tp11->hash()] = $tp11;
										}
										break;
					case 'leftanti' : 	break;
					case 'leftsemi' :	$newtuples[$tp1->hash()] = $tp1;
										break;										
				}			
			}
			else
			{
				switch ($expression)
				{
					case 'left'		:	$d11 = $tp1->fields();
										foreach($r->header as $t)
											if (! in_array($t, $this->header))
												$d11[$t] = '';
										$tp11 = new swTuple($d11);
										$newtuples[$tp11->hash()] = $tp11;
										break;
					case 'natural'	:   break;
					case 'leftanti' : 	$newtuples[$tp1->hash()] = $tp1;
										break;
					case 'leftsemi' :	break;										
				}
			}
			
		}
		
		$this->tuples = $newtuples;

	}
	
	function label($t)
	{
		$pairs = explode(',',$t);
		$this->label2($pairs);
	}
	
	function label2($pairs)
	{
		foreach($pairs as $p)
		{
			$fields = explode(' ',trim($p));
			if (count($fields) < 2)
				throw new swRelationError('Invalid label',121);
			$f0 = trim(array_shift($fields));
			$f0 = $this->validName($f0);
			$f1 = str_replace('"','',join($fields,' '));
			if (!in_array($f0, $this->header))
				throw new swRelationError('Unknown label '.$f0,122);
			$this->labels[$f0] = $f1;
		}
	}
	
	function limit($t)
	{
		$xp = new swExpression($this->functions);
		$xp->expectedreturn = 2;
		$xp->compile($t);
		$pairs = array();
		$pairs[] = $xp->evaluate($this->globals);
		if (count($xp->stack) > 1)
			$pairs[] = $xp->stack[1];
		if (count($pairs) == 1)
			$this->limit2($pairs[0],count($this->tuples));
		else
			$this->limit2($pairs[0],$pairs[1]);
		
	}
	
	function limit2($start, $length)
	{
		//echo $start.'/'.$length;
		if ($start == 0)
			throw new swRelationError('Limit Start 0',88);
		if ($length < 0)
			throw new swRelationError('Limit Length < 0',88);
			
		$c = count($this->tuples);
		
		if ($start > $c)
			{ $this->tuples = array(); return; }
		
		
		if ($start < 0)
		$start = count($this->tuples)-$start-1;
		
		if ($length < $c/3 || true)
		{
			$tuples2 = array();
			$i=0;
			foreach($this->tuples as $tp)
			{
				$i++;
				if ($i<intval($start)) continue;
				if ($i>=intval($start)+intval($length)) continue;
				$tuples2[$tp->hash()] = $tp;
			}
			$this->tuples = $tuples2;
			return;
		}
		/*
		for($i=$c-1;$i>=$start+$length-1;$i--)
			unset($this->tuples[$i]);
		for($i=$start-2;$i>=0;$i--)
			unset($this->tuples[$i]);	
		*/	
	}
	
	function order($t)
	{
		if (count($this->tuples)<2) return;
		
		if (count($this->tuples)>10000) echotime('order '.count($this->tuples));

		
		$pairs = explode(',',$t);
		$this->order2($pairs);	
	}
	
	
	function order2($pairs)
	{
				
		$pairs2 = array();
		
		foreach($pairs as $p)
		{
			$fields = explode(' ',$p);
			$fields[0] = $this->validName($fields[0]);
			$pairs2[] = join(' ',$fields);
		}
		
		$od = new swOrderedDictionary;
		$od->tuples = $this->tuples;
		$od->pairs = $pairs;
		$od->order();
		$this->tuples = $od->tuples;
		
	}
	
	function parse($t)
	{
		$list = explode(' ',$t);
		$field = array_shift($list);
		$field = $this->validName($field);
		$reg = array_shift($list);
		$rest = join(' ',$list);
		$this->parse2($field,$reg,$rest);
	}
	
	function parse2($field, $reg, $labels)
	{
		$list = explode(',', $labels);
		$list2 = array();
		foreach($list as $l)
		{
			$list2[] = trim($l);
		}
		$k = count($list2);
		
		$reg = '/'.$reg.'/';
		
		$c = count($this->tuples);
		
		$newtuples = array();
		for ($i=0;$i<$c;$i++)
		{
			$tp = $this->tuples[$i];
			$d = $tp->fields();
			$v = $d[$field];
			
			
			
			if (preg_match($reg,$v,$matches))
			{
				for($j=0;$j<$k;$j++)
				{
					$d[$list2[$j]] = $matches[$j+1];
				}	
			}
			else
			{
				for($j=0;$j<$k;$j++)
				{
					$d[$list2[$j]] = '';
				}	
			}
			$tp = new swTuple($d); 
			$newtuples[$tp->hash()] = $tp;		
						
		}
		$this->tuples = $newtuples;
	}
	
	function print2()
	{
		$c = count($this->header);
		for ($i=0;$i<$c;$i++)
		{
			$widths[] = stren($this->header[$i]);
		}
		foreach($this->tuples as $t)
		{
			$widths[$i] = max($widths[$i], stren($t->value[$i]));
		}
		$firstline = str_repeat('-',sum($widths)+count($widths)-1);
		
		$lines = array();
		$lines[]= $firstline;
		
		$fields = array();
		for($i=0;$i<$c;$i++)
		{
			$fields[]= str_pad($header[$i],$widths[$i],' ');
		}
		$lines[] = join(' ',$fields);
		
		foreach($this->tuples as $t)
		{
			$fields = array();
			for($i=0;$i<$c;$i++)
			{
				$fields[]= str_pad($t->value($header[$i]),$widths[$i],' ');
			}
			$lines[] = join(' ',$fields);
		}
		
		return join(PHP_EOL,$lines);
		
	}
		
	function project($t)
	{
		if (count($this->tuples)>10000) echotime('project '.count($this->tuples));
		
		if (substr($t,0,4)=='drop')
		{
			$t = substr($t,4);
			$pairs = explode(',',$t);
			$ps = array();
			foreach($pairs as $p)
			{
				$ps[] = trim($p);
			}
			$newps = array_diff($this->header,$ps);
			
			$this->project(join(', ',$newps));
			return;
			
		}
		
		if (substr($t,0,6)=='inline')
		{
			$r2 = $this->doClone();
			$r2->project(substr($t,7));
			if (count(array_intersect($this->header,$r2->header))>0)
				$this->join($r2,'natural');
			else
				$this->join($r2,'cross');
			return;
		}
		
		if (substr($t,0,5)=='pivot')
		{
			$list = explode(',',$t);
			$aggregation = array_pop($list);
			$list2 = explode(' ',$aggregation);
			$aggregator = array_pop($list2);
			$aggregandum = array_pop($list2);
			
			$aggregatformat = @$this->formats[$aggregandum];
			
			
			$first = $this->doClone();
			$second = $this->doClone();
			$all = $this->doClone();
			$this->project(substr($t,6));
			
			$first->project($this->header[0].', '.$aggregandum.' '.$aggregator);
			$first->extend($this->header[1]. ' = "_all" ');
			
			$this->union($first);
			
			$second->project($this->header[1].', '.$aggregandum.' '.$aggregator);
			$second->extend($this->header[0]. ' = "_all" ');
			
			$this->union($second);
			
			$all->project($aggregandum.' '.$aggregator);
			$all->extend($this->header[0]. ' = "_all" ');
			$all->extend($this->header[1]. ' = "_all" ');
			
			$this->union($all);
			
			if ($aggregatformat)
				$this->format1($aggregandum.'_'.$aggregator.' '.$aggregatformat);
					
			$this->deserialize();
				
			return;
		}

		
		if (substr($t,0,6)=='rollup')
		{
			// check aggregators, there must be only one per field
			$t2 = substr($t,7);
			$pairs = explode(',',$t2);
			$c = count($pairs);
			$rolluppairs = array();
			$rollupfields = array();
			$rollupremoved = array();
			for($i=0;$i<$c;$i++)
			{
				$p = trim($pairs[$i]);
				if ($p == '')
			 		throw new swRelationError('Project rollup empty pair',11);
				$fields = explode(' ',trim($p));
				$f = $fields[0];
				if (in_array($f, $rollupfields))
					throw new swRelationError('Project rollup duplicate column '.$f,11);
				$rollupfields[] = $f;
				$tr = str_replace(' ','_',$p).' '.$f;
				$rolluppairs[] = $tr;
				$rollup[] = $p;				 
			}
			$r2 = $this->doClone();
			$found = true;
			while ($found)
			{
				//print_r($rollup);
				$r2->project2($rollup);
				
				// add removed columns
				//print_r($rollupremoved);
				foreach($rollupremoved as $f)
				{
					//echo "extend ".$f;
					$r2->extend2($f,'" "');
				}
								
			 	$r2->rename12($rolluppairs);
			 	$this->union($r2);
			 	
			 	// remove each time a column
			 	$found = false;
			 	$c = count($rollup);
			 	for($i=$c-1;$i>=0;$i--)
			 	{
				 	$p = trim($rollup[$i]);
				 	//echo $p.','; 
				 	if (strpos($p,' ')===FALSE) 
				 	{
					 	//echo "found";
					 	$found = true;
					 	$rollupremoved[] = $rollup[$i];
					 	unset($rollup[$i]);
					 	$rollup = array_values($rollup);
					 	$i=0; // exit for i
				 	}
				 	//print_r($rollup);
			 	}
			}
			return;
		}
		$pairs = explode(',',$t);
		$this->project2($pairs);
	}
	
	function project2($pairs)
	{	
		$columns = array();
		$stats = array();
		$hasstats = false;
		$newcolumns = array();
		foreach($pairs as $p)
		{
			$fields = explode(' ',trim($p));
			$fields[0] = $this->validName($fields[0]);
			$columns[] = $fields[0];
			if (count($fields) >= 2)
			{
				$stats[] = $fields[1];
				$nc = $fields[0].'_'.$fields[1];
				$haststats = true;
			}
			else
			{
				$stats[] = '';
				$nc = $fields[0];
			}
			if (in_array($nc, $newcolumns))
				throw new swRelationError('Project duplicate column '.$nc,141);
			if (array_key_exists($fields[0],$this->formats))
				$this->formats[$nc] = $this->formats[$fields[0]];
			if (array_key_exists($fields[0],$this->labels))
				$this->labels[$nc] = $this->labels[$fields[0]];
			$newcolumns[] = $nc;
		}
		$c = count($columns);
		 
		foreach($columns as $s)
		{
			if (!in_array($s, $this->header))
				throw new swRelationError('Project unknown column '.$s,141);
		}
		$newtuples = array();
		//$k = count($this->tuples);
		foreach($this->tuples as $tp)
		//$j = 0;
		//for($j=0;$j<$k;$j++)
		{
			//if ($j>=$k) continue; $j++;
			//$tp = $this->tuples[$j];
			$d = array();
			for ($i=0;$i<$c;$i++)
			{
				if ($stats[$i]=='')
					$d[$newcolumns[$i]] = $tp->value($columns[$i]);
			}
			//print_r($d);
			//print_r($stats);
			$tp2 = new swTuple($d);
			$h = $tp2->hash();
			if (array_key_exists($h,$newtuples))
			{
				$d = $newtuples[$h];
			}
			else
			{
				for($i=0;$i<$c;$i++)
				{
					if ($stats[$i] != '')
					{
						if (array_key_exists($stats[$i], $this->aggregators))
						{
							$a = $this->aggregators[$stats[$i]];
							$a = $a->doClone();
							$a->functions = $this->functions;
							$d[$newcolumns[$i]] = $a;
						}
						else
						{
							$d[$newcolumns[$i]] = new swAccumulator($stats[$i]);
						}
					}
				}
			}
			for($i=0;$i<$c;$i++)
			{
				if ($stats[$i] != '')
				{
					$a = $d[$newcolumns[$i]];
					$a->add($tp->value($columns[$i]));
					$d[$newcolumns[$i]] = $a;
				}
			}
			$newtuples[$h] = $d;
		}
		//print_r($newtuples);
		
		$this->header = $newcolumns;
		$this->tuples = array();
		
		foreach($newtuples as $t)
		{
			$d2 = array();
			foreach($t as $k=>$v)
			{
				if (is_a($v,'swAccumulator'))
				{
					$d2[$k] = $v->reduce();
				}
				else
				{
					$d2[$k] = $v;
				}
			}
			$tp = new swTuple($d2);
			$this->tuples[$tp->hash()] = $tp;
		}
		
		// special case no tuple, still have count and sum
		
		if (count($this->tuples) == 0)
		{
			$d2 = array();
			for($i=0;$i<$c;$i++)
			{
				switch($stats[$i])
				{
					case 'count' : $d2[$newcolumns[$i]] = 0; break;
					case 'sum' : $d2[$newcolumns[$i]] = 0;  break;
				}
			}
			if (count($d2)>0)
			{
				// we need to add all other columns too, with empty values
				foreach($newcolumns as $nc)
				{
					if (!isset($d2[$nc])) $d2[$nc] = '';
				}
				
				$tp = new swTuple($d2);
				$this->tuples[$tp->hash()] = $tp;
			}
		}	
	}
	
	function rename1($t)
	{
		$pairs = explode(',',$t);
		$this->rename12($pairs);
	}
	
	function rename12($pairs)
	{
		$dict = array();
		//print_r($this->header);
		foreach($pairs as $p)
		{
			$fields = explode(' ',trim($p));
			if (count($fields) != 2)
				throw new swRelationError('Invalid rename '.$p,121);
			$fields[0] = $this->validName(trim($fields[0]));
			$fields[1] = $this->validName(trim($fields[1]));
			if (! in_array($fields[0], $this->header))
				throw new swRelationError('Unknown rename '.$fields[0],122);
			$i = array_search($fields[0], $this->header);
			unset($this->header[$i]);
			$this->addColumn($fields[1]);
			$this->header = array_values($this->header);
			$dict[$fields[0]] = $fields[1];
		}
		//print_r($this->header);
		$c = count($this->tuples);
		$newtuples = array();
		foreach($this->tuples as $tp)
		{
			$d = $tp->fields();
			
			foreach($dict as $k=>$v)
			{
				$val = $d[$k];
				unset($d[$k]);
				$d[$v] = $val;				
			}
			$tp = new swTuple($d);
			$newtuples[$tp->hash()] = $tp;
		}
		$this->tuples = $newtuples;
	}
	
	function select1($condition)
	{
		if (count($this->tuples)>10000) echotime('select '.count($this->tuples));
		
		$xp = new swExpression($this->functions);
		$xp->compile($condition);
		
		$i = 0;
		foreach($this->tuples as $k=>$tp)
		{
			$d = $tp->fields();
			$locals['rownumber'] = $i+1;
			$result = $xp->evaluate($d,$this->globals);
			if ($result == '0')
			{
				unset($this->tuples[$k]);
			}
			$i++;			
		}
	}
	
	function serialize1()
	{
		if (count($this->header)<2)
			throw new swRelationError('Serialize less than 2 properties',602);
		$observationkey = $this->header[0];
		$n = count($this->header);
		$list = array();
		for($i=1;$i<$n;$i++)
		{
			$list[]= $this->header[$i];
		}
		
		$list2[0] = $observationkey;
		$list2[1] = 'key';
		$list2[2] = 'value';
		if ($observationkey == 'key') $list2[1] = 'key0';
		if ($observationkey == 'value') $list2[1] = 'value0';
		
		$r = new swRelation($list2,$this->globals);
		
		foreach($this->tuples as $tp)
		{
			for($i=0;$i<$n-1;$i++) // 0 or 1???
			{
				$d = array();
				$d[$list2[0]] = $tp->value($observationkey);
				$d[$list2[1]] = $list[$i];
				$d[$list2[2]] = $tp->value($list[$i]);
				$tp2 = new swTuple($d);
				$r->tuples[$tp2->hash()] = $tp2;				
			}
		}		
		$this->header = $list2;
		$this->tuples = $r->tuples;		
	}
	
	function getCSV()
	{
		$lines = array();
		$c = count($this->header);
		
		$lines[] = join(',',$this->header);
		
		foreach($this->tuples as $tp)
		{
			$fields = array();
			for($i=0;$i<$c;$i++)
			{
				$f = $this->header[$i];
				$test = $tp->value($f);
				if (is_numeric($test))
					$fields[] = $test;
				else
					$fields[] = '"'.str_replace('"','""', swUnescape($test)).'"';
				
			}
			$lines[] = join(',',$fields);
		}
		return join(PHP_EOL,$lines);
	}

	function getCSVFormatted()
	{
		$lines = array();
		
		$fields = array();
		foreach($this->header as $f)
		{
			if (array_key_exists($f,$this->labels))
				$fields[] = '"'.$this->labels[$f].'"';
			else
				$fields[] = $f;
				
		}
		
		$c = count($this->header);
		
		$lines[] = join(',',$fields);
		
		
		foreach($this->tuples as $tp)
		{
			$fields = array();
			for($i=0;$i<$c;$i++)
			{
				$f = $this->header[$i];
				$test = $tp->value($f);
				if (array_key_exists($f,$this->formats))
				{
					$fm = $this->formats[$f];
					if ($fm != '')
						$test = $this->format2(floatval($test),$fm);
				}
				if (is_numeric($test))
					$fields[] = $test;
				else
					$fields[] = '"'.str_replace('"','""', swUnescape($test)).'"';
				
			}
			$lines[] = join(',',$fields);
		}
		return join(PHP_EOL,$lines);
	}
	
	function setCSV($lines,$pad = false)
	{
		$this->header = array();
		$this->tuples = array();
		$separator = ';';
		$firstline = true;
		
		$k = count($lines);
		for($j=0;$j<$k;$j++)
		{
			$line = $lines[$j];
			if ($line == '') continue;
			if ($firstline && substr($line,0,1)=='#') continue;
			if ($firstline)
			{
				//echo $line; echo strpos($line,$separator); echo "k";
				if (strpos($line,$separator)===FALSE) $separator = ','; 
				$fields = explode($separator,$line);
				foreach($fields as $field)
					$this->addColumn($this->cleanColumn(trim($field)));
				$c = count($fields);
				$firstline = false;				
			}
			else
			{
				$fields = array();
				$state = 'start';
				$acc = '';
				$quoted = false;
				$chlist = str_split($line); 
				foreach($chlist as $ch)
				{
					//echo $state.' '.$ch.' '; echo $separator;
					
					switch ($state)
					{
						case 'start' :	switch($ch)
										{
											case '"': $acc='';$quoted=true;$state='quote';break;
											case $separator: if($quoted) $fields[]=$acc;
															 else $fields[]=trim($acc);
															 $acc='';$quoted = false;break;
											default: if (!$quoted) $acc.=$ch;
										}
										break;
						case 'quote' :	switch($ch)
										{
											case '"':$state='quotesuspend';break;
											default: $acc.=$ch;
										}
										break;
						case 'quotesuspend' : switch($ch)
										{
											case '"':$acc.='"';$state='quote';break;
											case $separator: if($quoted) $fields[]=$acc; 
															 else $fields[]=trim($acc);
															 $acc='';$quoted = false;
															 $state='start';break;
											default: $state = 'start';
										}
										break;
					}
				}
				
				switch ($state)
				{
					case 'start' : if($quoted) $fields[]=$acc;
								   else $fields[]=trim($acc); break;
					case 'quote' : // 'error missing end quote, we need to read the following line
									if ($j<$k-1)
									{
										$lines[$j].=' '.@$lines[$j+1];
										unset($lines[$j+1]);
										$k -= 1;
										$j -= 2;
										continue 2; // for($j=0;$j<$k;$j++) PHP 7.3 needs argument
									}
									else
									{
										if($quoted) $fields[]=$acc;
										else $fields[]=trim($acc); 
									}
									break;
					case 'quotesuspend' : 	if($quoted) $fields[]=$acc;
											else $fields[]=trim($acc); 
				}
				$c = count($fields);
				$d = array();
				//echo $line;
				//print_r($fields);
				//print_r($this->header);
				if ($c == count($this->header))
				{
					for($i=0;$i<$c;$i++)
						$d[$this->header[$i]] = swEscape($fields[$i]);
				}
				else
				{
					if (!$pad)
						throw new swRelationError('Read CSV field count in row not header count (line: '.($j+1).'", header: '.$count($this->header).', fields:'.($c+1).')',99);
					for($i=0;$i<count($this->header);$i++)
					{
						$d[$this->header[$i]] = '';
					}
					for($i=0;$i<$c;$i++)
					{
						$d[$this->header[$i]] = $fields[$i];
					}
				}
				$tp = new swTuple($d);
				$this->tuples[$tp->hash()] = $tp;
			}
		}
		
	}
	
		function getTab()
	{
		$lines = array();
		$c = count($this->header);
		
		$lines[] = join("\t",$this->header);
		
		foreach($this->tuples as $tp)
		{
			$fields = array();
			for($i=0;$i<$c;$i++)
			{
				$f = $this->header[$i];
				$test = $tp->value($f);
				if (array_key_exists($f,$this->formats))
				{
					$fm = $this->formats($f);
					if ($fm != '')
						$test = $this->format2($floatval($test),$fm);
				}
				$fields[] = swUnescape($test);
				
			}
			$lines[] = join("\t",$fields);
		}
		return join(PHP_EOL,$lines);
	}

	
	function getSpace()
	{
		$lines = array();
		$c = count($this->header);
				
		foreach($this->tuples as $tp)
		{
			$fields = array();
			for($i=0;$i<$c;$i++)
			{
				$f = $this->header[$i];
				$test = $tp->value($f);
				if (array_key_exists($f,$this->formats))
				{
					$fm = $this->formats($f);
					if ($fm != '')
						$test = $this->format2($floatval($test),$fm);
				}
				$fields[] = $test;
				
			}
			$lines[] = join(' ',$fields);
		}
		return join(PHP_EOL,$lines);
	}
	



	function setTab($lines)
	{
		$this->header = array();
		$this->tuples = array();
		
		$firstline = true;
		foreach($lines as $line)
		{
			$fields = explode("\t",$line);
			if ($firstline)
			{
				foreach($fields as $field)
				{
					$this->addColumn($field);
				}
				$c = count($fields);
				$firstline = false;
			}
			else
			{
				$d = array();
				$i=0;
				foreach($fields as $field)
				{
					if ($i>=$c) continue;
					$d[$this->header[$i]] = swEscape($field);
					$i++;
				}
				while($i<$c)
				{
					$d[$this->header[$i]] = '';
					$i++;
				}
				$tp = new swTuple($d);
				$this->tuples[$tp->hash()] = $tp;
				
			}
		}
	}
	
	function getJSON()
	{
		$lines = array();
		
		$c = count($this->header);
		
		foreach($this->tuples as $tp)
		{
			$pairs = array();
			for($i=0;$i<$c;$i++)
			{
				$f = $this->header[$i];
				$test = $tp->value($f);
				if (array_key_exists($f,$this->formats))
				{
					$fm = $this->formats($f);
					if ($fm != '')
						$test = $this->format2($floatval($test),$fm);
				}
				if (is_numeric($test))
					$pairs[] = '"'.$f.'" : '.$test;
				else
					$pairs[] = '"'.$f.'" : "'.str_replace('"','""', $test).'"';
				
			}
			$lines[] = '{'.join(',',$pairs).'}';
		}
		return '{ "relation" : ['.PHP_EOL.join(','.PHP_EOL,$lines).PHP_EOL.']}';
	}
	
	function setJson($s)
	{
		throw new swRelationError('Read JSON not yet supported',77);
		// TO DO
	}
	
	function toChart($options)
	{
		// TO DO
	}
	
	function toFields($limit = 0)
	{
		$lines = array();
		
		foreach($this->tuples as $tp)
		{
			
			$d = $tp->fields();
			
			foreach($d as $k=>$v)
			{
				$lines[] = '<leftsquare><leftsquare>'.$k.'::'.$v.'<rightsquare><rightsquare>';
			}
			
			$lines[] = '';
		}
		$result = PHP_EOL.join(PHP_EOL,$lines).PHP_EOL;
		return $result;
		
	}
	
	function toHTML($limit = 0)
	{
				
		if (count($this->tuples)>10000) echotime('toHTML '.count($this->tuples));
		
		$grid = false;
		$edit = '';
		$editfile = '';
		
		
		
		if (substr($limit,0,4) == 'grid')
		{
			$limit = substr($limit,4);
			// $limit = '';
			$grid = true;
		}
		elseif (substr($limit,0,4) == 'edit')
		{
			
			$edit = ' contenteditable ';
			$grid = true;
			$editfile = substr($limit,4);
			
			$xp = new swExpression($this->functions);
			$xp->compile($editfile);
			$dict = array();
			$editfile = $xp->evaluate($dict);	
			$limit = '';
		}

		
		
		
		$lines = array();
		global $name;
		global $q;
		
		$id = floor(rand(0,10000));
		
		if ($edit)
		{
			$lines[] = '<nowiki><form id="form'.$id.'" method="post" action="index.php" file="'.$editfile.'" target="_blank"><input type="submit" id="submit'.$id.'" value="Code" disabled>'
			. '<input type="hidden" name="name" value="special:relation">'
			. '<textarea name="q" style="display:none">'.$this->getCSV().'</textarea>'
			. '</form></nowiki>';
		}
		
		/*
		if ($grid && $limit)
		{
			$lines[] = '<nowiki><button>Up</button><button>Down</button></nowiki>';
		}
		*/
		
		if ($grid)
		{
			$lines[] = '<nowiki><div><input type="text" id="input'.$id.'" class="sortable" onkeyup="tablefilter('.$id.')" placeholder="Filter..." title="Type in a name"></div></nowiki>';

		}
		
		if ($grid)
			$lines[]= '{| class="sortable" maxgrid="'.$limit.'" id="table'.$id.'"';
		else
			$lines[]= '{| class="print" ';
		
		$k = count($this->header);
		
		$line = '';
		foreach($this->header as $f)
		{
			if (isset($this->labels[$f]))
				$ls = $this->labels[$f];
			else
				$ls = $f;
			
			if (@$this->formats[$f])
			{
				$td = ' class="numberformat" | '; 				
			}
			else
			{
				$td = '';
			}
				
			if ($line) $line .= ' !! '.$td.$ls;
			
			else  $line = '! '.$td.$ls;
		}
		//$line = '! '.join(' !! ',$ls);
		$lines[]= '|-';
		$lines[]= $line;
		
		$c = count($this->tuples);
		$i0 = 0;
		if ($limit>0 && $limit<$c)
		{ 	
			$c = $limit;
			$i0 = 0;
		}
		elseif($limit <0 && -$limit<$c)
		{
			$i0 = $c + $limit; // to test
		}
		
		$i = 0;
		foreach($this->tuples as $tp)
		{
			// if ($i<$i0) { $i++; continue;}
			if ($i>=$c && !$grid) continue; 
			// if ($i>=$c) continue; 
			$i++;
			
			$fields = array();
			for($j=0;$j<$k;$j++)
			{
				$f = $this->header[$j];
				$t = $tp->value($f);
				$td = '';
				if (array_key_exists($f, $this->formats))
				{
					$fm = $this->formats[$f];
					if ($fm != '')
					{
						$t = $this->format2($t,$fm);
						$td = ' class="numberformat" | '; 						
					}
				}
				if ($edit && $td)
				{
					$td = $edit.$td;
				}
				elseif ($edit)
				{
					$td = $edit.' | ';
				}		
				if ($t==='')  $t = ' ';  // empty table cell would collapse
				//$t = str_replace('|','<pipe>',$t); // protect wikitext not a good idea if it is already wikitext like link
				//$t = str_replace(PHP_EOL,'<br>',$t); // protect wikitext not a good idea if it is already wikitext like link 
				$fields[]=$td.$t;				
			}
			
			$line = '| '.join(' || ',$fields);
			
			if ($i>$c && $grid) 
				$lines[] = '|- style="display: none"';
		    else
		    $lines[] = '|-';
			$lines[] = $line.' '; // add space to force line not to be empty
		}
		$lines[] = '|-';
		$lines[] = '|}';
		if ($grid)
			$lines[] = '<nowiki><script src="inc/skins/table.js"></script></nowiki>';
		$result = PHP_EOL.join(PHP_EOL,$lines).PHP_EOL;
		return $result;
		
	}
	
	function toTemplate($tmp)
	{	
		$lasti = 0;
		$lastvalidi = 0;
		$selectors = array();
		$templates = array();
		$dicts = array();
		$selectors[] = '';
		do
		{
			$i = strpos($tmp,'{{',$lasti);
			if ($i !== FALSE)
			{
				
				$i2 = strpos($tmp,'}}',$i);
				if ($i2 >= 0)
				{
					$sel = substr($tmp,$i+2,$i2-$i-2);
					if (substr($sel,0,6) == 'group ')
					{
						$sel2 = $sel;
						$sel = 'group';
					}
					//echo $sel.$lasti.' '.$i.$i2.";";
					switch($sel)
					{
						case 'first':
						case 'each':
						case 'last':
						case 'end':		$selectors[] = $sel;
										$templates[] = substr($tmp,$lastvalidi,$i-$lastvalidi);
										$lastvalidi = $i2+2; 			
										break;
						case 'group':	$selectors[] = $sel2;
										$templates[] = substr($tmp,$lastvalidi,$i-$lastvalidi); 
										$lastvalidi = $i2+2; 
										break;
						default:		//ignore					
					}
					$lasti = max($lasti+2,$i2+2);
						
				}
			} 
			//if(count($selectors)>3) echo '<br>'.$lasti.' '.print_r($selectors);
			
		} while($i !== FALSE) ;
		
				
		if (count($selectors)>0)
			$templates[] = substr($tmp,$lastvalidi);
		
		foreach($this->tuples as $tp)
		{
			$d = $tp->fields();
			foreach($this->header as $f)
			{
				$t = $d[$f];
				if (array_key_exists($f,$this->formats))
				{
					$fm = $this->formats[$f];
					$d[$f]  = $this->format2($t,$fm);
				}
			}
			$dicts[] = $d;
		}
		$c = count($dicts);
		$k = count($this->header);
		$result = '';
		
		//print_r($selectors);
		//print_r($templates);
		
		for($i=0;$i<$c;$i++)
		{
			$d = $dicts[$i];
			if ($i>0) $lastd = $dicts[$i-1]; else $lastd = null;
			if ($i<$c-1) $nextd = $dicts[$i+1]; else $lastd = null;
			
			$eachhashappened = false;
			$m = count($selectors);
			for($l=0;$l<$m;$l++)
			{
				$selt = $selectors[$l];
				$line = $templates[$l];
				//echo $selt.' '.$line.'  ';
				
				switch($selt)
				{
					case 'first' :	if ($i==0)
									{
										foreach($this->header as $f)
										{
											$line = str_replace('{{'.$f.'}}',$d[$f],$line);
										}
										$result.= $line;
									}
									break;
					case 'each' :	
									
									foreach($this->header as $f)
									{
										$line = str_replace('{{'.$f.'}}',$d[$f],$line);
									}
									$result.= $line;
									$eachhashappened = true;
									break;
					case 'last' :	if ($i==$c-1)
									{
										foreach($this->header as $f)
										{
											$line = str_replace('{{'.$f.'}}',$d[$f],$line);
										}
										$result.= $line;
									}
									break;
					case 'end'	:	//ignore
					
					
					
									break;
					default		:	if (substr($selt,0,6)=='group ')
									{
										
										$line = $templates[$l];
										$key = trim(substr($selt,6));
										//echo $key;
										if (array_key_exists($key,$d))
										{
											if (!$eachhashappened) // must be first
											{
												if ($i==0 or $lastd[$key] != $d[$key])
												{
													foreach($this->header as $f)
													{
														$line = str_replace('{{'.$f.'}}',$d[$f],$line);
													}
													$result.= $line;
												}
											}
											else // must be last
											{
												
												if ($i==0 or $nextd[$key] != $d[$k])
												{
													foreach($this->header as $f)
													{
														$line = str_replace('{{'.$f.'}}',$d[$f],$line);
													}
													$result.= $line;
												}
											}
										}
									}
				}
			}
		}
		return $result;
	}
	
	
	function union($r)
	{
		if (count($this->tuples)+count($this->tuples)>10000) echotime('union '.count($this->tuples));
		
		if (!count($r->tuples)) return;
		
		
		$e = $this->emptyTuple();
		$e2 = $r->emptyTuple();
		
		if (!$e->sameFamily($e2))
			throw new swRelationError('Union different columns ('.join(',',$this->header).') ('.join(',',$r->header).')' ,302);
		
		$this->tuples = array_merge($this->tuples, $r->tuples);
		
		/*
		
		
		foreach($r->tuples as $tp)
		{
		    $this->tuples[$tp->hash()] = $tp;
		}
		*/
		
		//echotime('union end ='.count($this->tuples));
	}
	
	function update($t)
	{
		if (count($this->tuples)>10000) echotime('update '.count($this->tuples));
		
		$fields = explode(' ',$t);
		$first = array_shift($fields);
		$first = $this->validName($first);
		$body = join(' ',$fields);
		$fields = explode(' ',$body);
		$eq = array_shift($fields);
		$body = join(' ',$fields);
		if ($eq != '=')
			throw new swRelationError('Update missing =',121);
		
		$p = stripos($body,' where ');
		if ($p !== FALSE)
		{
			$condition = substr($body,$p + strlen(' where '));
			$body = substr($body,0,$p);
		}
		else
		{
			$condition = "1";
		}

		$this->update2($condition, $first, $body); 
	}
	
	function update2($condition, $label, $expression)
	{
		$xp = new swExpression($this->functions);
		$xp->compile($condition);
		$xp2 = new swExpression($this->functions);
		$xp2->compile($expression);
		
		$newtuples = array();
		$i=0;
		foreach($this->tuples as $tp)
		//for ($i=0;$i<$c;$i++)
		{
			$d = $tp->fields();
			$this->globals['rownumber'] = strval($i+1); $i++;
			if ($xp->evaluate($d, $this->globals))
			{
				$v = $xp2->evaluate($d, $this->globals);
				$d[$label] = $v;
				$tp = new swTuple($d);
			}
			if ($tp->hasvalues())
				$newtuples[$tp->hash()] = $tp;
		}
		$this->tuples = $newtuples;

	}

		
	function validName($s)
	{
		if (strlen($s)<1) return '';
		if (substr($s,-1) == '^')
		{
			$s2 = substr($s,0,-1);
			if (array_key_exists($s2,$this->globals))
				return $this->validName($this->globals[$s2]);
	
		}
		if (strlen($s)< 31 and preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$s))
			return $s; 
		else
			return '';
	}
}

class swTuple
{
	var $pfields = array();
	var $phash;
	
	function __construct($list)
	{
		$keys = array();
		$values = array();
		$this->pfields = array_clone($list);
		if (is_array($list))
			$keys = array_keys($list);
		sort($keys);
		foreach($keys as $k)
		{
			$values[$k] = $list[$k]; 
		}
		$this->phash = md5(join(PHP_EOL,$values));
	}
	
	function arity()
	{
		return count($this->pfields);
	}
	
	function fields()
	{
		return array_clone($this->pfields);
	}
	
	function hash()
	{
		return $this->phash;
	}
	
	function hasKey($k)
	{
		return array_key_exists($k, $this->pfields);
	}

	function hasValues()
	{
		foreach ($this->pfields as $k=>$v)
		{
			if ($v) return true;
		}
	}

	
	function sameFamily($t)
	{
		if ($this->arity() != $t->arity()) {  return false; }
		foreach($this->pfields as $k=>$e)
		{
			if (!array_key_exists($k, $t->pfields)) 
			{
				//echo $k;	
				return false;
			}
		}
		return true;
	}
	function value($s)
	{
		$result = @$this->pfields[$s];
		return $result;
	}
	
}

class swOrderedDictionary
{
	var $pairs = array();
	var $tuples = array();
	var $pfields = array();
	var $porders = array();
	var $pfieldcount = 0;
	
	function order()
	{
		if (count($this->tuples)<2) return;
		
		// test on first
		$tp = array_pop($this->tuples);
		$tpfields = $tp->fields();
		array_push($this->tuples,$tp);
		
		// init orderfields only once
		$this->pfields = array();
		$this->porders = array();
		$this->pfieldcount = 0;
	
		
		foreach($this->pairs as $p)
		{
			$fields = explode(' ',trim($p));
			if (@count($fields < 2)) $fields[] = 'A';
			
			if (! array_key_exists($fields[0],$tpfields) ) 
				throw new swRelationError('Dict Compare missing field '. $fields[0],609);
			
			$this->pfields[] = $fields[0];
			$this->porders[] = $fields[1];		
			$this->pfieldcount++;
		}
		
		
		uasort($this->tuples, function($a,$b) { return $this->compare($a, $b) ; } );
	}
	
	function compare($a, $b)
	{
		$atp = $a;
		$btp = $b;
		$adict = $atp->fields();
		$bdict = $btp->fields();
		
		if (empty($adict) || empty($bdict) ) return 0; // should not happen with tuples, as they should not be empty.
		
		for($i=0;$i<$this->pfieldcount;$i++)
		{
			
			$atext = $adict[$this->pfields[$i]];
			$btext = $bdict[$this->pfields[$i]];
						
			switch ($this->porders[$i])
			{
				case 'A' : $test = strcmp($atext,$btext); if ($test != 0) return $test; break;
				case 'Z' : $test = strcmp($atext,$btext); if ($test != 0) return -$test; break;
				case 'a' : $test = strcasecmp($atext,$btext); if ($test != 0) return $test; break;
				case 'z' : $test = strcasecmp($atext,$btext); if ($test != 0) return -$test; break;
				case '1' :  // order infinity, numbers, -infinity, undefined
							if ($atext == '∞') { if ($btext != '∞') return 1 ; $test = 0; break; }
							if ($btext == '∞') { return -1 ; }
							if ($atext == '-∞') { if ($btext != '⦵') return 1; if ($btext != '-∞') return -1 ; $test = 0; break; }
							if ($btext == '-∞') { if ($atext != '⦵') return -1; return 1 ; }
							if ($atext == '⦵') { if ($btext != '⦵') return -1 ; $test = 0; break; }
							if ($btext == '⦵') { return 1 ; }
							if (floatval($atext) > floatval($btext)) return 1;
							if (floatval($atext) < floatval($btext)) return -1;
							break;
				case '9' : 	if ($atext == '∞') { if ($btext != '∞') return -1 ; $test = 0; break; }
							if ($btext == '∞') { return 1 ; }
							if ($atext == '-∞') { if ($btext != '⦵') return -1; if ($btext != '-∞') return 1 ; $test = 0; break; }
							if ($btext == '-∞') { if ($atext != '⦵') return 1; return -1 ; }
							if ($atext == '⦵') { if ($btext != '⦵') return 1 ; $test = 0; break; }
							if ($btext == '⦵') { return -1 ; }
							if (floatval($atext) > floatval($btext)) return -1;
							if (floatval($atext) < floatval($btext)) return 1;
							break;
				default: 	throw new swRelationError('Invalid order parameter '.$this->porders[$i],501);

			}
		}
		return 0;
		
	}
	
}

class swAccumulator
{
	var $list = array();
	var $method;
	var $label;
	var $source;
	var $offset;
	var $functions;
	
	
	function add($t)
	{
		if ($t != '') $this->list[] = $t;
	}
	
	function doClone()
	{
		$a = new swAccumulator($this->method);
		if ($this->method = 'custom')
		{
			$a->label = $this->label;
			$a->source = $this->source;
			$a->offset = $this->offset;
		}
		return $a;	
	}
	
	function __construct($m)
	{
		$this->method = $m;
	}
	
	private function pAvg()
	{
		$acc = 0; $i=0;
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
			$i++;
		}
		if (!$i) return '⦵';
		$v = $acc / $i;
		return cText12($v);
	}
	
	private function pConcat()
	{
		return join('::',array_unique($this->list));
	}
	
	private function pCount()
	{
		return count($this->list);
	}
	
	private function pCustom()
	{
		$xp = new swExpressionCompiledFunction($label, $source, $offset, true);
		$result = 'result';
		$elem = 'elem';
		$xp->fndict = $this->functions;
		return $xp->DoRunAggregator($this->list);
	}
	
	private function pMax()
	{
		if (count($this->list)==0) return "";
		$acc = '⦵';
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			if ($acc == '⦵') $acc = floatval($t);
			if (floatval($t) > $acc)
				$acc = floatval($t);
		}
		return cText12($acc);
	}
	
	private function pMaxS()
	{
		if (count($this->list)==0) return "";
		$acc = array_pop($this->list);
		foreach($this->list as $t)
			if (strcasecmp($t,$acc) > 0)
				$acc = $t;
		return $acc;
	}
	
	private function pMedian()
	{
		
		$acc = array();
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc[] = floatval($t);
		}
		if (count($acc)==0) return '⦵';
		sort($acc,SORT_NUMERIC);
		if (count($acc) % 2 != 0)
			$v = $acc[(count($acc)-1)/2];
		else
			$v = $acc[count($acc)/2] + $acc[count($acc)/2-1];
		return cText12($v);
	}
	
	private function pMedianS()
	{
		if (count($this->list)==0) return "";
		asort($this->list);
		if (count($this->list) % 2 != 0)
			$v = $this->list[(count($this->list)-1)/2];
		else
			$v = $this->list[(count($this->list)/2)];
		return $v;
	}
	
	private function pMin()
	{
		if (count($this->list)==0) return "";
		$acc = '⦵';
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			if ($acc == '⦵') $acc = floatval($t);
			if (floatval($t) < $acc)
				$acc = floatval($t);
		}
		return cText12($acc);
	}
	
	private function pMinS()
	{
		if (count($this->list)==0) return "";
		$acc = array_pop($this->list);
		foreach($this->list as $t)
			if (strcasecmp($t,$acc) < 0)
				$acc = $t;
		return $acc;
	}
	
	private function pStdev()
	{
		$acc = 0;
		$acc2 = 0;
		$i = 0;
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
			$acc2 += floatval($t) * floatval($t);
			$i++;
		}
		if ($i == 0) return '⦵';
		$v = sqrt($acc2/$i - $acc/$i*$acc/$i);
		return cText12($v);
	}

	private function PVar()
	{
		$acc = 0;
		$acc2 = 0;
		$i = 0;
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
			$acc2 += floatval($t) * floatval($t);
			$i++;
		}
		if ($i == 0) return '⦵';
		$v = $acc2/$i - $acc/$i*$acc/$i;
		return cText12($v);
	}
	
	private function pSum()
	{
		if (count($this->list)==0) return "";
		$acc = 0;
		foreach($this->list as $t)
		{
			if ($t == '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
		}
		return cText12($acc);
	}
	
	private function pFirst()
	{
		return array_shift($this->list);
	}

	private function pLast()
	{
		return array_pop($this->list);
	}

	function reduce()
	{
		switch ($this->method)
		{
			case 'count'	: 	return $this->pCount();
			case 'sum'  	:	return $this->pSum();
			case 'avg'  	:	return $this->pAvg();
			case 'min'  	:	return $this->pMin();
			case 'max'  	:	return $this->pMax();
			case 'median'  	:	return $this->pMedian();
			case 'mins'  	:	return $this->pMinS();
			case 'maxs'  	:	return $this->pMaxs();
			case 'medians'  :	return $this->pMedianS();
			case 'stddev'  	:	return $this->pStdev();
			case 'var'  	:	return $this->PVar();
			case 'concat'  	:	return $this->pConcat();
			case 'first'  	:	return $this->pFirst();
			case 'last'  	:	return $this->pLast();
			case 'custom'  	:	return $this->pCustom();
		}
	}
	
}



class swRelationError extends Exception	
{
	
}

function array_clone($arr) 
{ 
  if (is_array($arr)) 
	return array_slice($arr, 0, null, true);
  else return array();
 }

function swNumberformat($d,$f)
{	
	if ($d == '∞' || $d == '-∞' || $d == '⦵') { return $d; }
	
	if (substr($f,-1,1)=='n')
	{
		$f = substr($f,0,-1)."f";
		$s = sprintf($f,$d);
		$sign='';
		if (substr($s,0,1)=='-')
		{
			$s = substr($s,1);
			$sign='-';
		}
		if (stristr($s,'.'))
		{
			$prefix = substr($s,0,strpos($s,'.'));
			$postfix = substr($s,strpos($s,'.'));
		}
		else
		{
			$prefix = $s;
			$postfix = '';
		}
		
		$prefix = strrev($prefix);
		$prefix = chunk_split($prefix,3,' ');
		$prefix = trim(strrev($prefix));
		$s = $sign.$prefix.$postfix;
		return $s;

	}
	if (substr($f,-1,1)=='N')
	{
		$f = substr($f,0,-1)."f";
		$s = sprintf($f,$d);
		$sign='';
		if (substr($s,0,1)=='-')
		{
			$s = substr($s,1);
			$sign="-";
		}
		if (stristr($s,'.'))
		{
			$prefix = substr($s,0,strpos($s,'.'));
			$postfix = substr($s,strpos($s,'.'));
		}
		else
		{
			$prefix = $s;
			$postfix = '';
		}
		
		$prefix = strrev($prefix);
		$prefix = chunk_split($prefix,3,"'");
		$prefix = trim(strrev($prefix));
		$s = $sign.$prefix.$postfix;
		$s = str_replace("-'","-",$s); // strange bug
		return $s;


	}
	if (substr($f,-1,1)=='p')
	{
		$f = substr($f,0,-1)."f";
		$s = sprintf($f,$d*100);
		return $s.'%';
	}
	if (substr($f,-1,1)=='P')
	{
		$f = substr($f,0,-1)."f";
		$s = sprintf($f,$d*100);
		return $s.' %';
	}
	return sprintf($f,$d); // waiting for excel style format
}

function cText12($d)
{        
    $a;
	$t;
	$s;
	
	if ($d == '∞' || $d == '-∞' || $d == '⦵') return $d;


	$a = abs($d);
	
	if ($a > 10.0e12)
		// return format(d,"-0.000000000000e").ToText
		return sprintf('%1.12e+2',$d);
	elseif ($a < 10.0e-300)
		return "0";
	elseif ($a < 10.0e-12)
		// return format(d,"-0.000000000000e").ToText
		return sprintf('%%1.12e+2',$d);
	
	// s = format(d,"-0.##############")
	$s = sprintf('%1.12f',$d);
	
	if (strlen($s)>12)
		// s = format(round(d*10000000000000000)/10000000000000000,"-0.##############")
		$s = sprintf('%1.12f',round($d*10000000000000)/10000000000000);
		
	$s = TrimTrailingZeroes($s);
		
	if (substr($s,-1)==".")
		$s = substr(s,0,-1);
	
	return $s;
	
}



?>