<?php
	
	
/**
 *	Contains the swExpression class to evaluate expressions, the swExpressionError class and functions to convert numbers to text
 *  
 *  File also popluates the global $swExpressionFunctions array (only once for performance)
 */

if (!defined("SOFAWIKI")) die("invalid acces");


/**
 * Provides a class evaluate algebraic expressions
 *
 * Expressions are tokenized, then compiled to an RPN array.
 * Compiled expressions can be evaluated provided a value dictionary.
 * The expression class also initates the native functions in $swExpressionFunctions using swFunctions
 * This is done late because configuration.php can add functions.
 * 
 * $source
 * $tokens() numbers 0..9, strings $hex, functions @(id, operators, names, (, ), ","
 * $rpn()
 * $stack() value stack during exections
 * $expectedreturn number of results an expression is expected to return (can be more than 1)
 * 
 */

class swExpression
{
	var $source;
	var $tokens = array();
	var $rpn = array();
	var $stack = array();
	
	var $expectedreturn;
	
	function __construct()
	{		
		// we should this do only once after configuration is read

		if (!isset($swExpressionFunctions[':currenturl'])) // this function must be present
		{
			global $swExpressionFunctions;
			global $swFunctions;
			if (isset($swFunctions))
			foreach($swFunctions as $k=>$v)
			{
				// if ($v->arity() < 0) continue;
				if (isset($swExpressionFunctions[':'.$k])) continue; // do not override
				$fn  = new XpNative;
				$fn->init($k,$v);
				$swExpressionFunctions[':'.$k] = $fn;
			}
		}
		$this->expectedreturn = 1 ;
	
	}
		
	function tokenize($s)
	{
		//p_open('tokenize');
		
		$i; $c; 
		$ch; $state; $acc;
		
		// numbers 0..9
		// strings $ hex
		// functions @ (
		// operators 
		// names
		// (
		// )
		// ,

		$this->tokens = array();

		$state = 'start';

		$c = mb_strlen($s);
		
		for($i=0;$i<$c;$i++)
		{
			$ch = mb_substr($s,$i,1,'UTF-8');; // unicode ∞ and ⦵
			
			switch($state)
			{
				case 'start':
				case 'comma':					switch($ch)
												{
													case '"': $state = 'string';
															  $acc = ''; 
															  break;
													case '(': 
													case ')':
													case '-':
													case '/':
													case '*':
													case '+':
													case '.': 
													case "∞":
													case "⦵": $state = 'start';
													       	  $this->tokens[] = $ch; 
															  break;
													case ',': if ($state == 'comma' || count($this->tokens) == 0) $this->tokens[] = '$'; 
														      $state = 'comma'; 
														      $this->tokens[] = $ch; 
														      break;
													case '=': $state = 'equal'; 
															  break;
													case '<': $state = 'lower'; 
													          break;
													case '>': $state = 'greater';
													          break;
													case '!': $state = 'not';
													          break;
													case ' ': $state = 'start'; 
															  break;
													case '0':
													case '1':
													case '2':
													case '3':
													case '4':
													case '5':
													case '6':
													case '7':
													case '8':
													case '9': $state = 'number';
													          $acc = $ch;
															  break;
													
													default: if (($ch >= 'A' && $ch <= 'Z') || ($ch >= 'a' && $ch <= 'z') || $ch == '_' )
															 {
															 		
															 	$state = "name"; $acc = $ch;
															 }
															 else
															 {
																throw new swExpressionError('Tokenize unexpected character '.$ch.' in '.$s,12);
															 }
												} 
												break;
				case 'string':					switch($ch)
												{
													case '"': 	$state = 'string1'; 
																break;
													default:	$acc .= $ch;
												}
												break;
				case 'string1': 				switch($ch)
												{
													case '"':	$state = 'string1';
													            $acc .= '"';
													            break;
													default:	$state = 'start';
																$this->tokens[] = '$'.$acc;
																$acc = ''; $i--;
												}
												break;
				case 'number':  				switch($ch)
												{
													case '0':
													case '1':
													case '2':
													case '3':
													case '4':
													case '5':
													case '6':
													case '7':
													case '8':
													case '9': $acc .= $ch; 
															  break;
													case '.': $state = 'numberfraction'; 
															  $acc .= $ch;
															  break;
													case 'e': 
													case 'E': $state = 'numberexponent';
													          $acc .= $ch;
													          break;
													default:  $state = 'start';
													          $this->tokens[] = $acc;
															  $acc = ''; $i--;
												}
												break;
				case 'numberfraction':  		switch($ch)
												{
													case '0':
													case '1':
													case '2':
													case '3':
													case '4':
													case '5':
													case '6':
													case '7':
													case '8':
													case '9': $acc .= $ch; 
															  break;
													case 'e':
													case 'E': $state = 'numberexponent'; 
														      $acc .= $ch;
														      break;
													default:  $state = 'start';
															  $this->tokens[] = $acc;
															  $acc = '';
															  $i--;
												}
												break;
				case 'numberexponent':  		switch($ch)
												{
													case '0':
													case '1':
													case '2':
													case '3':
													case '4':
													case '5':
													case '6':
													case '7':
													case '8':
													case '9': $acc .= $ch; 
															  break;
													case '+': 
													case '-': $state = 'numberexponentnegatif';
													          $acc .= $ch;
													          break;
													default:  $state = 'start';
													          $this->tokens[] = $acc;
															  $acc = '';
															  $i--;
												}
												break;
				case 'numberexponentnegatif':  	switch($ch)
												{
													case '0':
													case '1':
													case '2':
													case '3':
													case '4':
													case '5':
													case '6':
													case '7':
													case '8':
													case '9': $acc .= $ch; 
													          break;
													default:  $state = 'start';
													          $this->tokens[] = $acc;
															  $acc = ''; $i--;
												}
												break;
				case 'name':					switch($ch)
												{
													case '(': $state = 'start';
													          $this->tokens[] = '@('.$acc;
														  	  $acc = ''; break;
													case '^': $state = 'start';
													          $this->tokens[] = $acc.'^'; 
															  $acc = ''; break;
													default:  if (($ch >= 'A' && $ch <= 'Z') ||
															 	 ($ch >= 'a' && $ch <= 'z') ||
															 	 ($ch >= '0' && $ch <= '9') ||
															 	 ($ch == "_"))
															 	 {
																    $acc .= $ch;
															 	 }
															 	 else
															 	 {
																 	$state = 'start'; 
																 	$this->tokens[] = $acc;
																 	$acc = ''; $i--;
															 	 }
												}	
												break;
				case 'equal':					switch($ch)
												{
													case '=':  	$state = 'start'; 
																$this->tokens[] = '=='; 
																break;
													default:   	$state = 'start'; 
																$this->tokens[] = '=';
																$i--; 
												}
												break;
				case 'lower': 					switch($ch)
												{
													case '=':  	$state = 'lowerequal';
																break;
													case '<':  	$state = 'start';
																$this->tokens[] = '<<';
																break;
													default:   	$state = 'start';
																$this->tokens[] = '<';
																$i--; 
												}
												break;
				case 'lowerequal': 				switch($ch)
												{
													case '=':  	$state = 'start';
																$this->tokens[] = '<==';
																break;
													default:   	$state = 'start';
																$this->tokens[] = '<=';
																$i--; 
												}
												break;	
				case 'greater': 				switch($ch)
												{
													case '=':  	$state = 'greaterequal';
																break;
													case '>':  	$state = 'start';
																$this->tokens[] = '>>';
																break;
													default:   	$state = 'start';
																$this->tokens[] = '>';
																$i--; 
												}
												break;
				case 'greaterequal':			switch($ch)
												{
													case '=':  	$state = 'start';
																$this->tokens[] = '>==';
																break;
													default:   	$state = 'start';
																$this->tokens[] = '>=';
																$i--; 
												}
												break;
				case 'not':						switch($ch)
												{
													case '=': 	 $state = 'notequal';
																break;
													default : 	throw new swExpressionError('Tokenize unexpected character "'.$ch.'"',12);
				
												}
												break;	
				case 'notequal':				switch($ch)
												{
													case '=':  	$state = 'start';
																$this->tokens[] = '!==';
																break;		
													default :  	$state = 'start';
																$this->tokens[] = '!=';
																$i--;
				
												}
												break;	
				
			}
		}
		
		

		switch($state)
		{
			case 'start':					break;
			case 'comma':					$this->tokens[] = '$';
											break;
			case 'string':					throw new swExpressionError('Tokenize open string "'.$acc.'"',13);
			case 'string1':					$this->tokens[] = '$'.$acc;
											break;
			case 'number':
			case 'numberfraction':
			case 'numberexponent':
			case 'numberexponentnegatif':
			case 'name':					$this->tokens[] = $acc;
											break;
			default:						throw new swExpressionError('Tokenize unknown state "'.$state.'"',11);
		}
		
		
	}
	
	function compile($s)
	{
		global $swExpressionOperators;		
		$operatorstack = array();
		$t; $e; $fl; $ch; 
		$negationpossible;
		$opprec = array();
		$op; $op2;
		$rv;
		$rvi; $i; $c;
		$fn;
		
		$this->source = $s;
		$this->rpn = array(); 
		$this->tokenize($s); 
		$negationpossible = true;
				
		if (count($this->tokens) == 0) return;
				
		foreach($this->tokens as $t)
		{
			$ch = mb_substr($t,0,1);
			if ($ch =='-' && $negationpossible) $t = '-u';
			
			
			switch($ch)
			{
				case '0':
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
				case '6':
				case '7':
				case '8':
				case '9': 
				case '∞':
				case '⦵':	$this->rpn[] = $t;
							$negationpossible = false;
							break;
				case '$': 	$this->rpn[] = $t;
							$negationpossible = false;
							break;
				case '@': 	$this->rpn[] = ':fn';
							$operatorstack[] = $t;
							
							$negationpossible = true;
							break;
				case '(': 	
							$operatorstack[] = '(';
							$negationpossible = true;
							break;
				case ')': 	
							do 
							{
								if (count($operatorstack) == 0)
								{
									throw new swExpressionError('Compile missing open paranthesis '. join(' ',$this->rpn),21);
								}
								$e = array_pop($operatorstack);
								if (mb_substr($e,0,2,'UTF-8')=='@(')
								{
									$fn = ':'.mb_substr($e,2,null,'UTF-8');
									$this->rpn[] = $fn;
									
								}
								elseif($e != '(' && $e !='1' && floatval($e) == 0)
								{
									$this->rpn[] = $e;
								}
								else
								{
									// echo $e.' ';
								}
							} while ( mb_substr($e,0,2,'UTF-8') != '@(' && $e != '(' );
							$negationpossible = false; break;
				default: 	$opfound = false;
							if(isset($swExpressionOperators[$t]))
							{
								$op = $swExpressionOperators[$t];
								$foundhigher = true;
								while (count($operatorstack)>0 && $foundhigher )
								{
									$e = array_pop($operatorstack);
									if ($op->associativity == 'L' && $op->precedence <= floatval($e))
									{
										
										$optop = array_pop($operatorstack);
										$this->rpn[] = $optop;
									}
									elseif ($op->associativity == 'R' && $op->precedence < floatval($e))
									{
										$optop = array_pop($operatorstack);
										$this->rpn[] = $optop;
									}
									else
									{
										$foundhigher = false;
										$operatorstack[] = $e;
									}
								}
								if ($op->functionlabel == ':and')
								{
									$rvi = rand(50000,80000);
									$this->rpn[] = $rvi;
									$this->rpn[] = ':andleft';
									$operatorstack[] = ':andright#'.$rvi;
								}
								elseif ($op->functionlabel == ':or')
								{
									$rvi = rand(50000,80000);
									$this->rpn[] = $rvi;
									$this->rpn[] = ':orleft';
									$operatorstack[] = ":orright#".$rvi;
								}
								elseif ($op->functionlabel)
								{
									$operatorstack[] = $op->functionlabel;
 									
								}
								$operatorstack[] = $op->precedence;
								$negationpossible = true;
								$opfound = true;
							}
							else
							{
								
								$this->rpn[] = $t;
								$negationpossible = false;
							}
			}
		}
		
		while(count($operatorstack)>0)
		{
			$e = array_pop($operatorstack);
			$e = array_pop($operatorstack); //2x!
			if ($e != '@(comma') $this->rpn[] = str_replace('@(',':',$e);
		}
		for	($i = count($this->rpn)-1;$i >= 0; $i--)
		{
			if (mb_substr($this->rpn[$i],0,9,'UTF-8') == ':andright')
			{
				$l = mb_substr($this->rpn[$i],9,null,'UTF-8');
				$this->rpn[$i] = ':andright';
				array_splice($this->rpn,$i+1,0,$l);	
			}
				
			if (mb_substr($this->rpn[$i],0,8,'UTF-8') == ':orright')
			{
				$l = mb_substr($this->rpn[$i],8,null,'UTF-8');
				$this->rpn[$i] = ':orright';
				array_splice($this->rpn,$i+1,0,$l);
			}
		}		
		
	}

	function evaluate($values=array(),$globals=array())
	{
		if (count($this->rpn) == 0) return '';
		global $swExpressionFunctions;
		
		$e; $e2; $ch; $dummy; $currentlabel; $jump; $cond; $currentindex; $result; 
		$f;
		$found;
		$localdict = array(); 
		$i; $j; $c; 
		$aggregatorfirstrun = 'aggregatorfirstrun';
		$currentindex = '';
		
		$globals['_pipe'] = '|';
		$globals['_colon'] = ':';
		$globals['_leftsquare'] = '[';
		$globals['_rightsquare'] = ']';
		$globals['_leftcurly'] = '{';
		$globals['_rightcurly'] = '}';
		$globals['_lt'] = '<';
		$globals['_gt'] = '>';
		$globals['_amp'] = '&';
		$globals['_quote'] = '"';
		$globals['_singlequote'] = "'";
		$globals['_backslash'] = '\\';
		$globals['_slash'] = '/';
		
		foreach($values as $k=>$v)
		{
			$values[$k] = swUnescape($v);
		}
		$this->stack = array();
		
		$c = count($this->rpn);
		
		for ($i=0; $i<$c; $i++)
		{
			$e = $this->rpn[$i];
			if ($e == '') continue;
			
			$ch = mb_substr($e,0,1,'UTF-8');
			switch ($ch)
			{
				case ':': 	
							switch($e)
							{
								case ':andleft': 	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if ($cond == '⦵') $cond = '0';
													if ($cond == '∞' || $cond == '-∞') $cond = '1';
													if ($cond = floatval($cond) == 0)
													{
														$this->stack[] = '0';
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
														{
															$i = $j;
														}
														else
														{
															throw new swExpressionError('Goto not defined '.$currentindex,31);
														}
													}
													break;
								case ':andright': 	$cond = array_pop($this->stack);
													if ($cond == '⦵') $cond = '0';
													if ($cond == '∞' || $cond == '-∞') $cond = '1';
													if (floatval($cond) == 0)
													{
														$this->stack[] = '0';
													}
													else
													{
														$this->stack[] = '1';
													}
													break;
								case ':comma': 		//nop
													break;	
								case ':fn': 		// add functionargumentstopper to stack
													$this->stack[] = new swExpressionFunctionBoundary;
													break;	
								case ':goto':		$jump = '#'.array_pop($this->stack);
													$j = array_search($jump,$this->rpn);
													if ($j !== false)
													{
														$i = $j;
													}
													else
													{
														throw new swExpressionError('Goto not defined '.$currentindex,31);
													}
													break;	
								case ':gotoifn':	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if ($cond == '⦵') $cond = '0';
													if ($cond == '∞' || $cond == '-∞') $cond = '1';
													if (floatval($cond) == 0)
													{
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
														{
															$i = $j;
														}
														else
														{
															throw new swExpressionError('Goto not defined '.$currentindex,31);
														}
													}
													break;	
								case ':gotoif':		$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if ($cond == '⦵') $cond = '0';
													if ($cond == '∞' || $cond == '-∞') $cond = '1';
													if (floatval($cond) != 0)
													{
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
														{
															$i = $j;
														}
														else
														{
															throw new swExpressionError('Goto not defined '.$currentindex,31);
														}
													}
													break;	
																
								case ':init':		if (array_key_exists($aggregatorfirstrun,$localdict))
													{
														if ($localdict[$aggregatorfirstrun] != 0)
														{
															$localdict[$currentlabel] = array_pop($this->stack);
														}
														else
														{
															$dummy = array_pop($this->stack);
														}
													}
													else
													{
														throw new swExpressionError('Illegal instruction init '.$currentindex,31);
													}
													break;	
								case ':orleft': 	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if ($cond == '⦵') $cond = '0';
													if ($cond == '∞' || $cond == '-∞') $cond = '1';
													if (floatval($cond) != 0)
													{
														$this->stack[] = '1';
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
														{
															$i = $j;
														}
														else
														{
															throw new swExpressionError('Goto not defined '.$currentindex,31);
														}
													}
													break;
								case ':orright': 	$cond = array_pop($this->stack);
													if ($cond == '⦵') $cond = '0';
													if ($cond == '∞' || $cond == '-∞') $cond = '1';
													if (floatval($cond) == 0)
													{
														$this->stack[] = '0';
													}
													else
													{
														$this->stack[] = '1';
													}
													break;
								case ':pop':		$dummy = array_pop($this->stack);
													break;
								case ':set':		$localdict[$currentlabel] = array_pop($this->stack);
													break;
								case ':stackcount':	$this->stack[] = count($this->stack);
													break;
								default:			if(isset($swExpressionFunctions[$e]))
													{	
														$f = $swExpressionFunctions[$e];
														
														
														
														$args = array();
														
														if ($f->isOperator)
														{
															if (count($this->stack)< $f->arity) throw new swExpressionError('Empty stack for '.$f->label,31);
															for ($opi = 0; $opi < $f->arity; $opi++) $args[] = array_pop($this->stack);
														}
														else
														{		
															do 	
															{
																$arg = array_pop($this->stack);
																
																if (!is_a($arg, 'swExpressionFunctionBoundary'))$args[] = $arg;
															}
															while(!is_a($arg, 'swExpressionFunctionBoundary') && count($this->stack));
														}
														
														$args = array_reverse($args);  // normal functional order
														
														$this->stack[] = $f->run0($args);
														
													}
													else
													{
														throw new swExpressionError('Function not defined '.$e.' '.
														print_r(array_keys($swExpressionFunctions),true),31);
													}
													
							}
							break;
				case '$':	$this->stack[] = mb_substr($e,1,null,'UTF-8');
							break;
				case '/':	$currentlabel =  mb_substr($e,1,null,'UTF-8');
							break;
				case '#':	$currentlabel = $e;
							break;
				default:	if (mb_substr($e,-1,null,'UTF-8')=='^')
							{
								$e2 = mb_substr($e,0,-1,'UTF-8');
								
								if (array_key_exists($e2,$localdict)) $this->stack[] = $localdict[$e2];
								elseif (array_key_exists($e2,$values)) $this->stack[] = $values[$e2];
								elseif (array_key_exists($e2,$globals)) $this->stack[] = $globals[$e2];
							}
				
							if (array_key_exists($e,$localdict)) $this->stack[] = $localdict[$e];
							elseif (array_key_exists($e,$values)) $this->stack[] = $values[$e];
							elseif (array_key_exists($e,$globals)) $this->stack[] = $globals[$e];
							else
							{
								if ($e == '∞' || $e == '-∞' || $e == '⦵') 
									$this->stack[] = $e;
								elseif (floatval($e) > 0) 
									$this->stack[] = $e;
								elseif ($e == '0')
									$this->stack[] = '0';
								else
									$this->stack[] = '';
							}
			}
		}

		if (count($this->stack) > $this->expectedreturn)
		{
			throw new swExpressionError('Evaluate stack not consumed '.join(' ',$this->rpn)." ".$currentindex,31);
		}
		
		if (count($this->stack) < $this->expectedreturn)
		{
			throw new swExpressionError('Stack too small '.join(' ',$this->rpn)." ".$currentindex,31);
		}
			
		$result = $this->stack[0];
		
		
		if ($result == '∞' || $result == '-∞' || $result == '⦵') return $result;
		
		if (is_numeric($result)) $result = swConvertText12(floatval($result));
			
		return $result;
	}

	
	function unitTest()
	{
		
		$v = array();
		$t; $tc; $te;
		$i; $c;
		
		$t = 'a';
		$te = '55';
		$v[$t]= $te;
		
		$t = 'b';
		$te = '21';
		$v[$t]= $te;
		
		$t = 'c';
		$te = '100';
		$v[$t]= $te;
		
		$t = 'd';
		$te = 'asdf';
		$v[$t]= $te;
		
		$t = 'e';
		$te = 'jklö';
		$v[$t]= $te;

		$tests = array();
		$comps = array();
		$results = array();
		
		$tests[] = 'a+b';
		$comps[] = 'a b :add';
		$results[] = '76';
		
		$tests[] = '5+10';
		$comps[] = '5 10 :add';
		$results[] = '15';

		$tests[] = '5*10';
		$comps[] = '5 10 :mul';
		$results[] = '50';
		
		$tests[] = '5/10';
		$comps[] = '5 10 :div';
		$results[] = '0.5';
		
		$tests[] = '2 + 4 +10';
		$comps[] = '2 4 :add 10 :add';
		$results[] = '16';
		
		$tests[] = '2 - 4 +10';
		$comps[] = '2 4 :sub 10 :add';
		$results[] = '8';
		
		$tests[] = '2 + 4 -10';
		$comps[] = '2 4 :add 10 :sub';
		$results[] = '-4';
		
		$tests[] = '2 + 4 * 10';
		$comps[] = '2 4 10 :mul :add';
		$results[] = '42';		
		
		$tests[] = '-5 +10';
		$comps[] = '5 :neg 10 :add';
		$results[] = '5';

		$tests[] = '5 * -10';
		$comps[] = '5 10 :neg :mul';
		$results[] = '-50';

		$tests[] = '5 +-10';
		$comps[] = '5 10 :neg :add';
		$results[] = '-5';

		$tests[] = '2 * 4 +10';
		$comps[] = '2 4 :mul 10 :add';
		$results[] = '18';
		
		$tests[] = '2 * (4 +10)';
		$comps[] = '2 4 10 :add :mul';
		$results[] = '28';

		$tests[] = 'a/c';
		$comps[] = 'a c :div';
		$results[] = '0.55';
		
		$tests[] = 'd+e';
		$comps[] = 'd e :add';
		$results[] = '0';
		
		$tests[] = 'd.e';
		$comps[] = 'd e :concat';
		$results[] = 'asdfjklö';

		$tests[] = 'sqrt(16)';
		$comps[] = ':fn 16 :sqrt';
		$results[] = '4';

		$tests[] = 'pow(2,3)';
		$comps[] = ':fn 2 3 :comma :pow';
		$results[] = '8';
		
		$tests[] = 'pow(3+1,3)';
		$comps[] = ':fn 3 1 :add 3 :comma :pow';
		$results[] = '64';

		$tests[] = 'replace(d.e,"sd","sbb")';
		$comps[] = ':fn d e :concat $sd :comma $sbb :comma :replace';
		$results[] = 'asbbfjklö';
		
		$tests[] = '100 * (5+pow(3+1,3)) / 10000';
		$comps[] = '100 5 :fn 3 1 :add 3 :comma :pow :add :mul 10000 :div';
		$results[] = '0.69';

		$tests[] = '"lorem ipsum" . "RRR"';
		$comps[] = '$lorem ipsum $RRR :concat';
		$results[] = 'lorem ipsumRRR';
		
		$tests[] = 'sqrt(5+4)';
		$comps[] = ':fn 5 4 :add :sqrt';
		$results[] = '3';

		$tests[] = 'sqrt(5 + 4)';
		$comps[] = ':fn 5 4 :add :sqrt';
		$results[] = '3';

		$tests[] = '2 * (3 + 4)';
		$comps[] = '2 3 4 :add :mul';
		$results[] = '14';

		$tests[] = '2 * 3 +';
		$comps[] = '2 3 :mul :add';
		$results[] = 'ERROR: Empty stack for :add';
		
		$tests[] = '"lorem ipsum';
		$comps[] = 'ERROR: Tokenize open string "lorem ipsum"';
		$results[] = '';
		
		$tests[] = 'pow(3+1,3)';
		$comps[] = ':fn 3 1 :add 3 :comma :pow';
		$results[] = '64';
		
		$tests[] = '(67 + 45 - 66 + 2)';
		$comps[] = '67 45 :add 66 :sub 2 :add';
		$results[] = '48';
		
		$tests[] = '(67 + 2 * 3 - 67 + 2/1 - 7)';
		$comps[] = '67 2 3 :mul :add 67 :sub 2 1 :div :add 7 :sub';
		$results[] = '1';
		
		$tests[] = '(2) + (17*2-30) * (5)+2 - (8/2)*4';
		$comps[] = '2 17 2 :mul 30 :sub 5 :mul :add 2 :add 8 2 :div 4 :mul :sub';
		$results[] = '8';
		
		$tests[] = '(((((5)))))';
		$comps[] = '5';
		$results[] = '5';
				
		$tests[] = '((((2)) + 4))*((5))';
		$comps[] = '2 4 :add 5 :mul';
		$results[] = '30';
		
		$tests[] = '550 > 100';
		$comps[] = '550 100 :gtn';
		$results[] = '1';
		
		$tests[] = 'a*b > c';
		$comps[] = 'a b :mul c :gtn';
		$results[] = '1';
		
		$tests[] = 'd < e';
		$comps[] = 'd e :ltn';
		$results[] = '0';
		
		$tests[] = 'd << e';
		$comps[] = 'd e :lts';
		$results[] = '1';
		
		$tests[] = '-(5)';
		$comps[] = '5 :neg';
		$results[] = '-5';
		
		$tests[] = 'd regex "s.f"';
		$comps[] = 'd $s.f :regex';
		$results[] = '1';

		$tests[] = 'd regex "s..f"';
		$comps[] = 'd $s..f :regex';
		$results[] = '0';

		$tests[] = '-(5)';
		$comps[] = '5 :neg';
		$results[] = '-5';
		
		$tests[] = '-(5)';
		$comps[] = '5 :neg';
		$results[] = '-5';
		
		$c = count($tests);
		
		for ($i=0;$i<$c;$i++)
		{
			$tc = '';
			$te = '';
			$t = $tests[$i];
			
			try
			{
				$this->compile($t);
				$tc = join(' ',$this->rpn);
			}
			catch (swExpressionError $err)
			{
				$tc = 'ERROR: '. $err->getMessage();
				$this->rpn = array();
			}
			
			if ($tc != $comps[$i])
			{
				throw new swExpressionError('Unit Test compilation failed: '.$t.' : '.$tc,41);
			}
			try
			{
				$te = $this->evaluate($v);
			}
			catch (swExpressionError $err)
			{
				$te = 'ERROR: '.$err->getMessage();
			}
			
			if ($te != $results[$i])
				throw new swExpressionError('Unit Test evaluation failed: '.$t.' : '.$te,42);			
		}		
	}
	
}

/**
 * Stub class
 */

class swExpressionError extends Exception	
{
	
}

/**
 * Stub class
 */

class swExpressionFunctionBoundary	
{
	
}


/**
 * Converts a number of a string with 12 digit precision
 * 
 */

function swConvertText12($d)
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
		
	$s = swTrimTrailingZeroes($s);
		
	if (substr($s,-1)==".")
		$s = substr(s,0,-1);
	
	return $s;
	
}

/**
 * Converts a number of a string with 15 digit precision
 * 
 */


function swConvertText15($d)
{
	// double is guranteed 15 decimal digits
    // show 15 first digits and cut 0 at the end
    // move to scientific only if the number cannot be represented (bigger > 10^15, smaller 10^15)
    
    $a;
	$t;
	$s;
	
	if ($d == '∞' || $d == '-∞' || $d == '⦵') return $d;


	$a = abs($d);
	
	if ($a > 10.0e12)
		// return format(d,"-0.000000000000e").ToText
		return sprintf('%1.15e+2',$d);
	elseif ($a < 10.0e-300)
		return "0";
	elseif ($a < 10.0e-15)
		// return format(d,"-0.000000000000e").ToText
		return sprintf('%%1.15e+2',$d);
	
	// s = format(d,"-0.##############")
	$s = sprintf('%1.15f',$d);
	
	if (strlen($s)>15)
		// s = format(round(d*10000000000000000)/10000000000000000,"-0.##############")
		$s = sprintf('%1.15f',round($d*10000000000000000)/10000000000000000);
		
	$s = swTrimTrailingZeroes($s);
		
	if (substr($s,-1)==".")
		$s = substr(s,0,-1);
	
	return $s;

}
	


/**
 * Removes trailing zeros in fraction of a number 
 * 
 * 1000 returns 1000
 * 105.32 returns 105.32
 * 105.3200 returns 15.32
 */


function swTrimTrailingZeroes($nbr) {
    if(mb_strpos($nbr,'.',0,'UTF-8')!==false) $nbr = rtrim($nbr,'0');
    return rtrim($nbr,'.') ?: '0';
}


/* obsolote
// mb_str_split only in 7.4
function swmb_str_split($string, $split_length = 1, $encoding = 'UTF-8')
{
     
    $result = [];
    $length = mb_strlen($string, $encoding);
    for ($i = 0; $i < $length; $i += $split_length) {
        $result[] = mb_substr($string, $i, $split_length, $encoding);
    }
    return $result;
}
*/



$exp = new swExpression();
try 
{
	$exp->unitTest();
}

catch (swExpressionError $err)
{
	
	echo $err->getMessage(); echo "<br>";
	//print_r($exp->source); echo "<br>";
	print_r($exp->tokens); echo "<br>";
	print_r($exp->rpn);
	print_r($exp->stack);
	
	// print_r($exp->operators);
}



