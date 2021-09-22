<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swExpression
{
	var $source;
	var $tokens = array();
	var $rpn = array();
	var $stack = array();
	
	var $operators = array();
	var $functions = array();
	
	// unittest
	var $expectedreturn;
	
	
	function __construct($fn = array())
	{
		$this->operators[] =  new swExpressionOperator('-u', ':neg',1,11,'L');
		$this->operators[] =  new swExpressionOperator('not', ':not',1,100,'L');
		
		$this->operators[] =  new swExpressionOperator('/', ':div',2,9,'L');
		$this->operators[] =  new swExpressionOperator('*', ':mul',2,9,'L');
		$this->operators[] =  new swExpressionOperator('div', ':idiv',2,9,'L');
		$this->operators[] =  new swExpressionOperator('mod', ':mod',2,9,'L');
		$this->operators[] =  new swExpressionOperator('+', ':add',2,8,'L');
		$this->operators[] =  new swExpressionOperator('-', ':sub',2,8,'L');
		
		$this->operators[] =  new swExpressionOperator('.', ':concat',2,7,'L');
		
		$this->operators[] =  new swExpressionOperator('=', ':eqn',2,6,'L');
		$this->operators[] =  new swExpressionOperator('!=', ':nen',2,6,'L');
		$this->operators[] =  new swExpressionOperator('>', ':gtn',2,6,'L');
		$this->operators[] =  new swExpressionOperator('>=', ':gen',2,6,'L');
		$this->operators[] =  new swExpressionOperator('<', ':ltn',2,6,'L');
		$this->operators[] =  new swExpressionOperator('<=', ':len',2,6,'L');
		
		$this->operators[] =  new swExpressionOperator('regex', ':regex',2,5,'L');
		$this->operators[] =  new swExpressionOperator('regexi', ':regexi',2,5,'L');
		$this->operators[] =  new swExpressionOperator('==', ':eqs',2,5,'L');
		$this->operators[] =  new swExpressionOperator('!==', ':nes',2,5,'L');
		$this->operators[] =  new swExpressionOperator('>>', ':gts',2,5,'L');
		$this->operators[] =  new swExpressionOperator('>==', ':ges',2,5,'L');
		$this->operators[] =  new swExpressionOperator('<<', ':lts',2,5,'L');
		$this->operators[] =  new swExpressionOperator('<==', ':les',2,5,'L');
		
		$this->operators[] =  new swExpressionOperator('and', ':and',2,4,'L');
		$this->operators[] =  new swExpressionOperator('or', ':or',2,3,'L');
		$this->operators[] =  new swExpressionOperator('xor', ':xor',2,2,'L');
		
		$this->operators[] =  new swExpressionOperator(',',':comma',2,1,'L');
		
		
		$this->functions[] = new XPNeg;
		$this->functions[] = new XPNot;
		
		$this->functions[] = new XPDiv;
		$this->functions[] = new XPMul;
		$this->functions[] = new XPIdiv;
		$this->functions[] = new XPMod;
		$this->functions[] = new XPAdd;
		$this->functions[] = new XPSub;
		
		$this->functions[] = new XPConcat;
		
		$this->functions[] = new XPEqN;
		$this->functions[] = new XPNeN;
		$this->functions[] = new XPGtN;
		$this->functions[] = new XPGeN;
		$this->functions[] = new XPLtN;
		$this->functions[] = new XPleN;
		
		$this->functions[] = new XPEqS;
		$this->functions[] = new XPNeS;
		$this->functions[] = new XPGtS;
		$this->functions[] = new XPGeS;
		$this->functions[] = new XPLtS;
		$this->functions[] = new XPLeS;
		
		$this->functions[] = new XPAnd;
		$this->functions[] = new XPOr;
		$this->functions[] = new XPXor;
		
		$this->functions[] = new XPComma;
		
		// real functions
		
		$this->functions[] = new XPAbs;
		$this->functions[] = new XPCeil;
		$this->functions[] = new XPCos;
		$this->functions[] = new XpExp;
		$this->functions[] = new XPFloor;
		$this->functions[] = new XpLn;
		$this->functions[] = new XpLog;
		$this->functions[] = new XPPow;
		$this->functions[] = new XPRnd;
		$this->functions[] = new XPRound;
		$this->functions[] = new XPSin;
		$this->functions[] = new XPSign;
		$this->functions[] = new XPSqrt;
		$this->functions[] = new XPTan;
		
		$this->functions[] = new XPLength;
		$this->functions[] = new XPLower;
		$this->functions[] = new XPRegex;
		$this->functions[] = new XPRegexi;
		$this->functions[] = new XPRegexReplace;
		$this->functions[] = new XPRegexReplaceMod;
		$this->functions[] = new XPReplace;
		$this->functions[] = new XPSubstr;
		$this->functions[] = new XPUpper;
		$this->functions[] = new XPurltext;
		$this->functions[] = new XPpad;
		$this->functions[] = new XPtrim;
		
		$this->functions[] = new XPMax;
		$this->functions[] = new XPMin;
		
		
		$this->functions[] = new XPSecondsToSQL;
		$this->functions[] = new XPSQLtoSeconds;
		
		$this->functions[] = new XPFormat;
		
		$this->functions[] = new XpNowiki;
		$this->functions[] = new XpResume;
		$this->functions[] = new XpTemplate;

		$this->expectedreturn = 1 ;

		foreach($fn as $f)
		{
			$this->functions[] = $f;
		}

	}
	
		
	function tokenize($s)
	{
		$i; $c; 
		$ch; $state; $acc;
		

		// strings # hex
		// functions @ (
		// operators 
		// names
		// (
		// )
		// ,

		$this->tokens = array();

		$state = 'start';

		$c = strlen($s);
		
		for($i=0;$i<$c;$i++)
		{
			$ch = substr($s,$i,1);
			
			switch($state)
			{
				case 'start':
				case 'comma':	switch($ch)
								{
									case '"': $state = 'string'; $acc = ''; break;
									case '(': 
									case ')':
									case '-':
									case '/':
									case '*':
									case '+':
									case '.': $state = 'start'; $this->tokens[] = $ch; break;
									case ',': if ($state == 'comma' || count($this->tokens) == 0) { $this->tokens[] = '$'; } 
										      $state = 'comma'; $this->tokens[] = $ch; break;
									case '=': $state = 'equal'; break;
									case '<': $state = 'lower'; break;
									case '>': $state = 'greater'; break;
									case '!': $state = 'not'; break;
									case ' ': $state = 'start'; break;
									case '0':
									case '1':
									case '2':
									case '3':
									case '4':
									case '5':
									case '6':
									case '7':
									case '8':
									case '9': $state = 'number'; $acc = $ch; break;
									
									default: 	if (($ch >= 'A' && $ch <= 'Z') ||
											 	($ch >= 'a' && $ch <= 'z') || $ch == '_' )
											 	{
											 		$state = "name"; $acc = $ch;
											 	}
											 	else
											 	{
												 	throw new swExpressionError('Tokenize unexpected character '.$ch.' in '.$s,12);
											 	}
											 
											
									
									
								} 
								break;
				case 'string':	switch($ch)
								{
									case '"': 	$state = 'string1'; break;
									default:	$acc .= $ch;
								}
								break;
				case 'string1': switch($ch)
								{
									case '"':	$state = 'string1'; $acc .= '"'; break;
									default:	$state = 'start'; $this->tokens[] = '$'.$acc;
												$acc = ''; $i--;
								}
								break;
				case 'number':  switch($ch)
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
									case '9': $acc .= $ch; break;
									case '.': $state = 'numberfraction'; $acc .= $ch; break;
									case 'e': 
									case 'E': $state = 'numberexponent'; $acc .= $ch; break;
									default:  $state = 'start'; $this->tokens[] = $acc;
											  $acc = ''; $i--;
								}
								break;
				case 'numberfraction':  switch($ch)
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
									case '9': $acc .= $ch; break;
									case 'e':
									case 'E': $state = 'numberexponent'; $acc .= $ch; break;
									default:  $state = 'start'; $this->tokens[] = $acc;
											  $acc = ''; $i--;
								}
								break;
				case 'numberexponent':  switch($ch)
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
									case '9': $acc .= $ch; break;
									case '+': 
									case '-': $state = 'numberexponentnegatif'; $acc .= $ch; break;
									default:  $state = 'start'; $this->tokens[] = $acc;
											  $acc = ''; $i--;
								}
								break;
				case 'numberexponentnegatif':  switch($ch)
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
									case '9': $acc .= $ch; break;
									default:  $state = 'start'; $this->tokens[] = $acc;
											  $acc = ''; $i--;
								}
								break;
				case 'name':	switch($ch)
								{
									case '(': $state = 'start'; $this->tokens[] = '@('.$acc;
										  	  $acc = ''; break;
									case '^': $state = 'start'; $this->tokens[] = $acc.'^'; 
											  $acc = ''; break;
									default: if (($ch >= 'A' && $ch <= 'Z') ||
											 	($ch >= 'a' && $ch <= 'z') ||
											 	($ch >= '0' && $ch <= '9') ||
											 	($ch == "_"))
											 	{
												 	$acc .= $ch;
											 	}
											 	else
											 	{
												 	$state = 'start';  $this->tokens[] = $acc;
												 	$acc = ''; $i--;
											 	}
								}	
								break;
				case 'equal':	switch($ch)
								{
									case '=':  $state = 'start'; $this->tokens[] = '=='; break;
									default:   $state = 'start'; $this->tokens[] = '='; $i--; 
								}
								break;
				case 'lower': 	switch($ch)
								{
									case '=':  $state = 'lowerequal'; break;
									case '<':  $state = 'start'; $this->tokens[] = '<<'; break;
									default:   $state = 'start'; $this->tokens[] = '<'; $i--; 
								}
								break;
				case 'lowerequal': 
								switch($ch)
								{
									case '=':  $state = 'start'; $this->tokens[] = '<=='; break;
									default:   $state = 'start'; $this->tokens[] = '<='; $i--; 
								}
								break;	
				case 'greater': 	switch($ch)
								{
									case '=':  $state = 'greaterequal'; break;
									case '>':  $state = 'start'; $this->tokens[] = '>>'; break;
									default:   $state = 'start'; $this->tokens[] = '>'; $i--; 
								}
								break;
				case 'greaterequal': 
								switch($ch)
								{
									case '=':  $state = 'start'; $this->tokens[] = '>=='; break;
									default:   $state = 'start'; $this->tokens[] = '>='; $i--; 
								}
								break;
				case 'not':		switch($ch)
								{
									case '=':  $state = 'notequal'; break;
									default : throw new swExpressionError('Tokenize unexpected character "'.$ch.'"',12);

								}
								break;	
				case 'notequal':switch($ch)
								{
									case '=':  $state = 'start'; $this->tokens[] = '!=='; break;		
									default :  $state = 'start'; $this->tokens[] = '!='; $i--;

								}
								break;	
				
			}
		}

		switch($state)
		{
			case 'start':	break;
			case 'comma':	$this->tokens[] = '$'; break;
			case 'string':	throw new swExpressionError('Tokenize open string '.$acc,13);
			case 'string1':	$this->tokens[] = '$'.$acc; break;
			case 'number':
			case 'numberfraction':
			case 'numberexponent':
			case 'numberexponentnegatif':
			case 'name':	$this->tokens[] = $acc; break;
			default:		throw new swExpressionError('Tokenize unknown state "'.$state.'"',11);
		}

	}
	
	function compile($s)
	{
		
				
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
		
		// print_r($this->tokens);

		
		foreach($this->tokens as $t)
		{
			$ch = substr($t,0,1);
			if ($ch =='-' && $negationpossible) $t = '-u';
			
			
			// echo "<p>".$t."</p>";
			
			
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
				case '9': 	$this->rpn[] = $t; $negationpossible = false; break;
				case '$': 	$this->rpn[] = $t; $negationpossible = false; break;
				case '@': 	$operatorstack[] = $t; $negationpossible = true; break;
				case '(': 	$operatorstack[] = '('; $negationpossible = true; break;
				case ')': 	
							do 
							{
								if (count($operatorstack) == 0)
								throw new swExpressionError('Compile missing open paranthesis '. join(' ',$rpn),21);
								$e = array_pop($operatorstack);
								if (substr($e,0,2)=='@(')
								{
									$fn = ':'.substr($e,2);
									// if ($fn != ':comma')
										$this->rpn[] = $fn;
								}
								// elseif($e != '(' && $e !='1' && $e != ':comma' && floatval($e) == 0)
								elseif($e != '(' && $e !='1' && floatval($e) == 0)
								{
									$this->rpn[] = $e;
								}
								else
								{
									// echo $e.' ';
								}
							} while ( substr($e,0,2) != '@(' && $e != '(' );
							$negationpossible = false; break;
				default: 	$opfound = false;
							foreach($this->operators as $op)
							{
								if ($opfound) continue;
								$opfound = false;
								if ($t == $op->label)
								{
									$foundhigher = true;
									while (count($operatorstack)>0 && $foundhigher )
									{
										$e = array_pop($operatorstack);
										if ($op->associativity == 'L' && $op->precedence <= floatval($e))
										{
											
											$optop = array_pop($operatorstack);
											// if ($optop != ':comma')
												$this->rpn[] = $optop;
										}
										elseif ($op->associativity == 'R' && $op->precedence < floatval($e))
										{
											$optop = array_pop($operatorstack);
											// if ($optop != ':comma')
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
										//print_r($op);
										
									}
									elseif ($op->functionlabel == ':or')
									{
										$rvi = rand(50000,80000);
										$this->rpn[] = $rvi;
										$this->rpn[] = ':orleft';
										$operatorstack[] = ":orright#".$rvi;
									}
									else
									{
										$operatorstack[] = $op->functionlabel;
									}
									$operatorstack[] = $op->precedence;
									$negationpossible = true;
									$opfound = true;
									//print_r($operatorstack);
								}
							}
							if (!$opfound)
							{
								$this->rpn[] = $t;
								$negationpossible = false;
							}
			}
			/*
			print_r($operatorstack);
			echo "<p></p>";
			print_r($this->rpn);
			*/

		}
		
		while(count($operatorstack)>0)
		{
			$e = array_pop($operatorstack);
			$e = array_pop($operatorstack); //2x!
			if ($e != '@(comma')
				$this->rpn[] = str_replace('@(',':',$e);
		}
		for	($i = count($this->rpn)-1;$i >= 0; $i--)
		{
			if (substr($this->rpn[$i],0,9) == ':andright')
			{
				$l = substr($this->rpn[$i],9);
				$this->rpn[$i] = ':andright';
				array_splice($this->rpn,$i+1,0,$l);	
				
											
			}
				
			if (substr($this->rpn[$i],0,8) == ':orright')
			{
				$l = substr($this->rpn[$i],8);
				$this->rpn[$i] = ':orright';
				array_splice($this->rpn,$i+1,0,$l);
			}
		}
		/*
		print_r($operatorstack);
		echo "<p></p>";
		print_r($this->rpn);
		*/
		
	}

	function evaluate($values=array(),$globals=array(),$locals=array())
	{
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
		
		// unescapge
		
		foreach($values as $k=>$v)
		{
			$values[$k] = swUnescape($v);
		}
		
		$this->stack = array();
		
		if (count($this->rpn) == 0) return '';
		
		$c = count($this->rpn);
		
		for ($i=0; $i<$c; $i++)
		{
			$e = $this->rpn[$i];
			if ($e == '') continue;
			
			$ch = substr($e,0,1);
			switch ($ch)
			{
				case ':': 	$found = false;
							switch($e)
							{
								case ':andleft': 	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if (floatval($cond) == 0)
													{
														$this->stack[] = '0';
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
															$i = $j;
														else
															throw new swExpressionError('Goto not defined '.$currentindex,31);
													}
													$found = true;
													break;
								case ':andright': 	$cond = array_pop($this->stack);
													if (floatval($cond) == 0)
														$this->stack[] = '0';
													else
														$this->stack[] = '1';
													$found = true;
													break;
								case ':comma': 		//nop
													$found = true;
													break;	
								case ':goto':		$jump = '#'.array_pop($this->stack);
													$j = array_search($jump,$this->rpn);
													if ($j !== false)
														$i = $j;
													else
														throw new swExpressionError('Goto not defined '.$currentindex,31);
													$found = true; //echo " *GOTO* "; print_r($this->stack);
													break;	
								case ':gotoifn':	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if (floatval($cond) == 0)
													{
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
															$i = $j;
														else
															throw new swExpressionError('Goto not defined '.$currentindex,31);
													}
													$found = true;
													break;	
								case ':gotoif':	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if (floatval($cond) != 0)
													{
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
															$i = $j;
														else
															throw new swExpressionError('Goto not defined '.$currentindex,31);
													}
													$found = true;
													break;	
																
								case ':init':		if (array_key_exists($aggregatorfirstrun,$localdict))
													{
														if ($localdict[$aggregatorfirstrun] != 0)
															$localdict[$currentlabel] = array_pop($this->stack);
														else
															$dummy = array_pop($this->stack);
													}
													else
														throw new swExpressionError('Illegal instruction init '
															.$currentindex,31);
													$found = true;
													break;	
								case ':orleft': 	$jump = '#'.array_pop($this->stack);
													$cond = array_pop($this->stack);
													if (floatval($cond) != 0)
													{
														$this->stack[] = '1';
														$j = array_search($jump,$this->rpn);
														if ($j !== false)
															$i = $j;
														else
															throw new swExpressionError('Goto not defined '.$currentindex,31);
													}
													$found = true;
													break;
								case ':orright': 	$cond = array_pop($this->stack);
													if (floatval($cond) == 0)
														$this->stack[] = '0';
													else
														$this->stack[] = '1';
													$found = true;
													break;
								case ':pop':		$dummy = array_pop($this->stack);
													$found = true;
													break;
								case ':set':		$localdict[$currentlabel] = array_pop($this->stack);
													$found = true;
													break;
								case ':stackcount':	$this->stack[] = count($this->stack);
													$found = true;
													break;
								default:			foreach($this->functions as $f)
													{	
														if ($found) continue;
														if ($e == $f->label)
														{
															$found = true;
															$f->functions = $this->functions;
															$f->run($this->stack);
														}
													}
													
							}
							if (!$found) throw new swExpressionError('Function not defined '.$e,31);
							break;
				case '$':	$this->stack[] = substr($e,1);
							break;
				case '/':	$currentlabel = substr($e,1);
							break;
				case '#':	$currentlabel = $e;
							break;
				default:	if (substr($e,-1)=='^')
							{
								$e2 = substr($e,0,-1);
								
								if (array_key_exists($e2,$localdict))
									$this->stack[] = $localdict[$e2];
								elseif (array_key_exists($e2,$values))
									$this->stack[] = $values[$e2];
								elseif (array_key_exists($e2,$locals))
									$this->stack[] = $locals[$e2];
								elseif (array_key_exists($e2,$globals))
									$this->stack[] = $globals[$e2];
							}
				
				
				
							if (array_key_exists($e,$localdict))
								$this->stack[] = $localdict[$e];
							elseif (array_key_exists($e,$values))
								$this->stack[] = $values[$e];
							elseif (array_key_exists($e,$locals))
								$this->stack[] = $locals[$e];
							elseif (array_key_exists($e,$globals))
								$this->stack[] = $globals[$e];
							else
							{
								if (floatval($e) > 0) 
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
			//print_r($this->stack);
			throw new swExpressionError('Evaluate stack not consumed '.join(' ',$this->rpn)." ".$currentindex,31);
		}
		
		if (count($this->stack) < $this->expectedreturn)
			throw new swExpressionError('Stack too small '.join(' ',$this->rpn)." ".$currentindex,31);
			
		$result = $this->stack[0];
		
		if (is_numeric($result))
			$result = $this->cText12(floatval($result));
			
		return $result;
	}

	
	function cText12($d)
	{        
        $a;
		$t;
		$s;
	


		$a = abs($d);
		
		if ($a > 10.0e12)
			// return format(d,"-0.000000000000e").ToText
			return sprintf('%1.12e',$d);
		elseif ($a < 10.0e-300)
			return "0";
		elseif ($a < 10.0e-12)
			// return format(d,"-0.000000000000e").ToText
			return sprintf('%1.12e',$d);
		
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
		$comps[] = 'a b _add';
		$results[] = '76';
		
		$tests[] = '5+10';
		$comps[] = '5 10 _add';
		$results[] = '15';

		$tests[] = '5*10';
		$comps[] = '5 10 _mul';
		$results[] = '50';
		
		$tests[] = '5/10';
		$comps[] = '5 10 _div';
		$results[] = '0.5';
		
		$tests[] = '2 + 4 +10';
		$comps[] = '2 4 _add 10 _add';
		$results[] = '16';
		
		$tests[] = '2 - 4 +10';
		$comps[] = '2 4 _sub 10 _add';
		$results[] = '8';
		
		$tests[] = '2 + 4 -10';
		$comps[] = '2 4 _add 10 _sub';
		$results[] = '-4';
		
		$tests[] = '2 + 4 * 10';
		$comps[] = '2 4 10 _mul _add';
		$results[] = '42';		
		
		$tests[] = '-5 +10';
		$comps[] = '5 _neg 10 _add';
		$results[] = '5';

		$tests[] = '5 * -10';
		$comps[] = '5 10 _neg _mul';
		$results[] = '-50';

		$tests[] = '5 +-10';
		$comps[] = '5 10 _neg _add';
		$results[] = '-5';

		$tests[] = '2 * 4 +10';
		$comps[] = '2 4 _mul 10 _add';
		$results[] = '18';
		
		$tests[] = '2 * (4 +10)';
		$comps[] = '2 4 10 _add _mul';
		$results[] = '28';

		$tests[] = 'a/c';
		$comps[] = 'a c _div';
		$results[] = '0.55';
		
		$tests[] = 'd+e';
		$comps[] = 'd e _add';
		$results[] = '0';
		
		$tests[] = 'd.e';
		$comps[] = 'd e _concat';
		$results[] = 'asdfjklö';

		$tests[] = 'sqrt(16)';
		$comps[] = '16 _sqrt';
		$results[] = '4';

		$tests[] = 'pow(2,3)';
		$comps[] = '2 3 _pow';
		$results[] = '8';
		
		$tests[] = 'pow(3+1,3)';
		$comps[] = '3 1 _add 3 _pow';
		$results[] = '64';

		$tests[] = 'replace(d.e,"sd","sbb")';
		$comps[] = 'd e _concat $sd $sbb _replace';
		$results[] = 'asbbfjklö';
		
		$tests[] = '100 * (5+pow(3+1,3)) / 10000';
		$comps[] = '100 5 3 1 _add 3 _pow _add _mul 10000 _div';
		$results[] = '0.69';

		$tests[] = '"lorem ipsum" . "RRR"';
		$comps[] = '$lorem ipsum $RRR _concat';
		$results[] = 'lorem ipsumRRR';
		
		$tests[] = 'sqrt(5+4)';
		$comps[] = '5 4 _add _sqrt';
		$results[] = '3';

		$tests[] = 'sqrt(5 + 4)';
		$comps[] = '5 4 _add _sqrt';
		$results[] = '3';

		$tests[] = '2 * (3 + 4)';
		$comps[] = '2 3 4 _add _mul';
		$results[] = '14';

		$tests[] = '2 * 3 +';
		$comps[] = '2 3 _mul _add';
		$results[] = 'ERROR: Stack < 2';
		
		$tests[] = '"lorem ipsum';
		$comps[] = 'ERROR: Tokenize open string';
		$results[] = '';
		
		$tests[] = 'pow(3+1,3)';
		$comps[] = '3 1 _add 3 _pow';
		$results[] = '64';
		
		$tests[] = '(67 + 45 - 66 + 2)';
		$comps[] = '67 45 _add 66 _sub 2 _add';
		$results[] = '48';
		
		$tests[] = '(67 + 2 * 3 - 67 + 2/1 - 7)';
		$comps[] = '67 2 3 _mul 67 _sub 2 1 _div 7 _sub _add _add';
		$results[] = '1';
		
		$tests[] = '(2) + (17*2-30) * (5)+2 - (8/2)*4';
		$comps[] = '2 17 2 _mul 30 _sub 5 _mul 2 _add 8 2 _div 4 _mul _sub _add';
		$results[] = '8';
		
		$tests[] = '(((((5)))))';
		$comps[] = '5';
		$results[] = '5';
				
		$tests[] = '((((2)) + 4))*((5))';
		$comps[] = '2 4 _add 5 _mul';
		$results[] = '30';
		
		$tests[] = '550 > 100';
		$comps[] = '550 100 _gtn';
		$results[] = '1';
		
		$tests[] = 'a*b > c';
		$comps[] = 'a b _mul c _gtn';
		$results[] = '1';
		
		$tests[] = 'd < e';
		$comps[] = 'd e _ltn';
		$results[] = '0';
		
		$tests[] = 'd << e';
		$comps[] = 'd e _lts';
		$results[] = '1';
		
		$tests[] = '-(5)';
		$comps[] = '5 _neg';
		$results[] = '-5';
		
		$tests[] = 'd regex "s.f"';
		$comps[] = 'd $s.f _regex';
		$results[] = '1';

		$tests[] = 'd regex "s..f"';
		$comps[] = 'd $s..f _regex';
		$results[] = '0';

		$tests[] = '-(5)';
		$comps[] = '5 _neg';
		$results[] = '-5';
		
		$tests[] = '-(5)';
		$comps[] = '5 _neg';
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
				$tc = 'ERROR: '+$err->getMessage();
				$this->rpn = array();
			}
			
			if ($tc != $comps[$i])
				throw new swExpressionError('Unit Test compilation failed: '.$t.' : '.$tc,41);
				
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


class swExpressionOperator
{
	var $arity;
	var $associativity;
	var $functionlabel;
	var $label;
	var $precedence;
	
	function __construct($lbl, $fnlabel, $ar, $pr, $ass)
	{
		$this->label = $lbl;
		$this->functionlabel = $fnlabel;
		$this->arity = $ar;
		$this->precedence = $pr;
		$this->associativity = $ass;
	}
}

class swExpressionFunction
{
	var $arity;
	var $functions = array();
	var $label;
	
	function cText($d)
	{
		// double is guranteed 15 decimal digits
        // show 15 first digits and cut 0 at the end
        // move to scientific only if the number cannot be represented (bigger > 10^15, smaller 10^15)
        
        $a;
		$t;
		$s;

		$a = abs($d);
		
		if ($a > 10.0e14)
			// return format(d,"-0.00000000000000e").ToText
			return sprintf('%1.14e',$d);
		elseif ($a < 10.0e-300)
			return "0";
		elseif ($a < 10.0e-14)
			// return format(d,"-0.00000000000000e").ToText
			return sprintf('%1.14e',$d);
		
		// s = format(d,"-0.##############")
		$s = sprintf('%1.14f',$d);
		
		if (strlen($s)>15)
			// s = format(round(d*10000000000000000)/10000000000000000,"-0.##############")
			$s = sprintf('%1.14f',round($d*10000000000000000)/10000000000000000);
			
		$s = TrimTrailingZeroes($s);
		
		if (substr($s,-1)==".")
			$s = substr(s,0,-1);
		
		return $s;
	}
		

	function run(&$stack)
	{
		// stub
	}
}

class swExpressionCompiledFunction extends swExpressionFunction
{
	var $isaggregator = false;
	var $lines = array();
	var $offset;
	var $label;
	var $xp;
	var $compiledlines = array();
	var $compiledinitlines = array();
	var $args = array();
	var $arity;
	
	function __construct($lb, $source, $off, $ia = false)
	{
		$this->isaggregator = $ia;
		$this->lines = explode(PHP_EOL,$source);
		$this->offset = $off;
		$this->label = ':'.$lb;
		$this->xp = new swExpression();
		$this->compile();
 	}
 	
 	function compile()
 	{
	 	//print_r($this->lines);
	 	$c = count($this->lines);
	 	$this->compiledlines = array();
	 	$this->compiledinitlines = array();
	 	$this->arity = 0;
	 	
	 	if ($this->isaggregator)
	 	{
		 	$this->compiledlines[] = '1';
		 	$this->compiledlines[] = '/aggregatorfirstrun';
		 	$this->compiledlines[] = ':set';
		 	$this->compiledlines[] = '#190000';
		 	$this->compiledlines[] = ':stackcount';
		 	$this->compiledlines[] = '#190001';
		 	$this->compiledlines[] = ':gotoifn';
		 	$this->compiledlines[] = '/elem';
		 	$this->compiledlines[] = ':set';		 	
	 	}
	 	
	 	for($i=0;$i<$c;$i++)
	 	{
		 	$il = $this->offset + $i;
		 	$line = trim($this->lines[$i]);
		 	$ti = $il;
		 	$this->compiledlines[] = '#'.$ti;
		 	if (strpos($line,'// ')>-1)
		 		$line = trim(substr($line,0,strpos($line,'// ')));
		 	if ($line=='') continue;
		 	$fields = explode(' ',$line);
		 	$command = array_shift($fields);
		 	$body = join(' ',$fields);
		 	
		 	switch ($command)
		 	{
			 	case 'function':	if($this->isaggregator || $i>0)
			 							throw new swExpressionError('Invalid instruction #'.$ti,66);
			 						$dummy = array_shift($fields);
			 						$body = trim(join(' ',$fields));
									if ( substr($body,0,1) != '(' || substr($body,-1,1) != ')')
									{
										throw new swExpressionError('Missing paranthesis #'.$ti,66);
									}
									$body = substr($body,1,-1);
			 						$fields = explode(',',$body);
			 						$this->arity = count($fields);
			 						for ($j = $this->arity-1;$j>=0;$j--)
			 						{
				 						$this->args[] = trim($fields[$j]);
			 						}
			 						break;
			 						/*
			 	case 'init':		if(!$this->isaggregator || $i>0)
			 							throw new swExpressionError('Invalid instruction #'.$ti,66);
			 						$fields = explode(' ',$body);
			 						$f = array_shift($fields);
			 						if ($f == '')
			 							throw new swExpressionError('Set empty field #'.$ti,321);
			 						$body = join(' ',$fields);
			 						$body = join(' ',$fields);
									if ($eq != '=')
										throw new swExpressionError('Init missing = #'.$ti,321);
			 						$this->xp = new swExpression;
			 						$xp->compile($body);
			 						foreach($xp->rpn as $ti)
			 						{
				 						$this->compiledlines[] = $ti;
			 						}
			 						$this->compiledlines[] = '/'.$f;
			 						$this->compiledlines[] = ':init';
			 						break;
			 						*/
			 	case 'set':			$fields = explode(' ',$body);
			 						$f = array_shift($fields);
			 						if ($f == '')
			 							throw new swExpressionError('Set empty field #'.$ti,321);
			 						$body = join(' ',$fields);
			 						$fields = explode(' ',$body);
									$eq = array_shift($fields);
									$body = join(' ',$fields);
									if ($eq != '=')
										throw new swExpressionError('Init missing = #'.$ti,321);
									if ($body != '')
			 						{
				 						$this->xp = new swExpression;
				 						$this->xp->compile($body);
				 						foreach($this->xp->rpn as $ti)
				 						{
				 							$this->compiledlines[] = $ti;
			 							}
			 						}
			 						else
			 						{
				 						$this->compiledlines[] = '0';
			 						}
			 						$this->compiledlines[] = '/'.$f;
			 						$this->compiledlines[] = ':set';
			 						break;
			 	case 'if':			$conditioncount = 1;
			 						$elsefound = -1;
			 						$found = false;
			 						$j = $i;
			 						do 
			 						{
				 						$j++;
				 						$line = trim($this->lines[$j]);
				 						if (strpos($line,'// ')>-1) 
				 							$line = trim(substr($line,0,strpos($line,'// ')));
				 						//echo $line;
				 						$fields = explode(' ',$line);
				 						$command2 = $fields[0];
				 						switch ($command2)
				 						{
					 						case 'if':		$conditioncount++; break;
					 						case 'else':	if ($conditioncount==1)
					 										{
						 										if ($elsefound<0)
						 										{
							 										$this->lines[$j] = '';
							 										$elsefound = $j;
						 										}
						 										else
						 											throw new swExpressionError('Duplicate else #'.$ti,321);
					 										}
					 										break;
					 						case 'end':		if ($line == 'end if')
					 										{
						 										if ($conditioncount==1)
						 										{
							 										if ($elsefound<0)
							 										{
								 										$this->xp = new swExpression;
								 										$this->xp->compile($body);
								 										foreach($this->xp->rpn as $ti)
												 						{
												 							$this->compiledlines[] = $ti;
											 							}
											 							$this->compiledlines[] = $j+$this->offset+1;
											 							$this->compiledlines[] = ':gotoifn';
							 										}
							 										else
							 										{
								 										$this->xp = new swExpression;
								 										$this->xp->compile($body);
								 										foreach($this->xp->rpn as $ti)
												 						{
												 							$this->compiledlines[] = $ti;
											 							}
											 							$this->compiledlines[] = $elsefound+$this->offset+1;
											 							$this->compiledlines[] = ':gotoifn';
											 							$this->lines[$elsefound] = 'goto '.($j+$this->offset+1);

							 										}
							 										$this->lines[$j]='';
							 										$found = true;
							 									}
							 									else
							 										$conditioncount--;
					 										}
				 						}
			 						}
			 						while(!$found && $j<$c);
			 						if (!$found)
			 							throw new swExpressionError('Missing end if #'.$ti,321);
			 						break;
			 	case 'while':		$conditioncount = 1;
			 						$j = $i;
			 						$found = false;
			 						do 
			 						{
				 						$j++;
				 						$line = trim($this->lines[$j]);
				 						if (strpos($line,'// ')>-1)
				 							$line = trim(substr($line,0,strpos($line,'// ')));
				 						$fields = explode(' ',$line);
				 						$command2 = $fields[0];
				 						switch ($command2)
				 						{
					 						case 'while':	$conditioncount++; break;
					 						case 'end':		if ($line == 'end while')
					 										{
						 										if ($conditioncount==1)
						 										{

							 										$this->xp = new swExpression;
							 										$this->xp->compile($body);
							 										foreach($this->xp->rpn as $ti)
											 						{
											 							$this->compiledlines[] = $ti;
										 							}
										 							$this->compiledlines[] = $j+$this->offset+1;
										 							$this->compiledlines[] = ':gotoifn';
							 										$this->lines[$j]='goto '.($i+$this->offset);;
							 										$found = true;
							 									}
							 									else
							 										$conditioncount--;
					 										}
				 						}
			 						}
			 						while(!$found && $j<$c);
			 						if (!$found)
			 							throw new swExpressionError('Missing end if #'.$ti,321);
			 						break;	
			 	case 'else':		throw new swExpressionError('Duplicate else #'.$ti,321);
			 						break;
			 	case 'end':			if ($line != 'end function')
			 							throw new swExpressionError('Duplicate end #'.$ti,321);
			 						break;
			 	case 'goto':		$this->compiledlines[] = $body;			
			 						$this->compiledlines[] = ':goto';
			 						break;
			 	default:			throw new swExpressionError('Invalid instruction #'.$ti.' '.$line,66);
		 	}
		 	
		 	
	 	}
	 	if ($this->isaggregator)
	 	{
		 	$this->compiledlines[] = '0';
		 	$this->compiledlines[] = '/aggregatorfirstrun';
		 	$this->compiledlines[] = ':set';
		 	$this->compiledlines[] = '190000';
		 	$this->compiledlines[] = ':goto';
		 	$this->compiledlines[] = '#190001';		 	
	 	}
	 	$this->compiledlines[] = 'result';
	 	
	 	//print_r($this->lines);
	 	//echo join(' ',$this->compiledlines);
	 	
 	}
 	
 	function run(&$stack)
	{
		if ($this->xp == NULL) $this->xp = new swExpression();
		$this->xp->rpn = $this->compiledlines;
		$localvalues = array();
		foreach($this->args as $arg)
		{
			$localvalues[$arg] = array_pop($stack);
		}
		$t = $this->xp->evaluate($localvalues);
		$stack[] = $t;
	}
	
	function runAggregator($list)
	{
		if ($this->xp == NULL) $this->xp = new swExpression();
		$this->xp->rpn = array();
		while(count($list)>0)
			$this->xp->rpn[] = array_pop($list);
		foreach($this->compiledlines as $t)
			$this->xp->rpn[] = $t;
		$t = $this->xp->evaluate($localvalues);
		$stack[] = $t;
	}

}

class swExpressionError extends Exception	
{
	
}

class XPabs extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':abs' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(abs($a));		
	}
}

class XPAdd extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':add' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));		
		$stack[] = $this->ctext($b+$a);	
	}
}

class XpAnd extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':and' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($a && $b) $stack[] = '1';
		else $stack[] = '0';			
	}
}

class XpAndRight extends swExpressionFunction //?
{
	function __construct() { $this->arity = 2; $this->label = ':and' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($a && $b) $stack[] = '1';
		else $stack[] = '0';			
	}
}

class XPceil extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':ceil' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(ceil($a));		
	}
}

class Xpcomma extends swExpressionFunction 
{
	function __construct() { $this->arity = 2; $this->label = ':comma' ;}
	function run(&$stack)
	{
		//if (count($this->stack) < 2) throw new swExpressionError('Stack < 2',102);
		// nop			
	}
}

class XPconcat extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':concat' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);		
		$stack[] = $b.$a;		
	}
}

class XPcos extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':cos' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(cos($a));		
	}
}


class XpDiv extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':div' ;}
	function run(&$stack)
	{
		// print_r($stack);
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($a == 0) throw new swExpressionError('Div/0',110);
		$stack[] = $this->ctext($b/$a);		
	}
}

class XPeqN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':eqn' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($b == $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPeqS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':eqs' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		if ($b == $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPexp extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':exp' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(exp($a));		
	}
}

class XPfalse extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':false' ;}
	function run(&$stack)
	{
		$stack[] = '0';	
	}
}

class Xpfloor extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':floor' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(floor($a));		
	}
}


class XPgeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gen' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($b >= $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPgeS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':ges' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		if ($b >= $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPgtN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gtn' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($b > $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPgtS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gts' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		if ($b > $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPidiv extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':idiv' ;}
	function run(&$stack)
	{	
		
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = intval(array_pop($stack));
		$b = intval(array_pop($stack));	
		if ($a == 0) throw new swExpressionError('Div/0',110);
		$v = ($b - ($b % $a)) / $a ; 
		$stack[] = $this->ctext($v);
	}
}

class XPleN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':len' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($b <= $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPlength extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':length' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = $this->ctext(strlen($a));		
	}
}


class XPleS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':les' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		if ($b <= $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPln extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':ln' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(log($a));		
	}
}

class XPlog extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':log' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(log10($a));		
	}
}

class XPlower extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':lower' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = strtolower($a);		
	}
}

class XPltN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':ltn' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($b < $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPltS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':lts' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		if ($b < $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPmax extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':max' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));		
		$stack[] = $this->ctext(max($b,$a));		
	}
}

class XPmin extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':min' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));		
		$stack[] = $this->ctext(min($b,$a));		
	}
}

class XPmod extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':mod' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($a == 0) throw new swExpressionError('Div/0',110);
		$stack[] = $this->ctext($b%$a);		
	}
}


class XPmul extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':mul' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));		
		$stack[] = $this->ctext($b*$a);		
	}
}


class XPneN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':nen' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($b != $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPneg extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':neg' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(-$a);		
	}
}

class XPneS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':nes' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		if ($b != $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class XPnot extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':not' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		if ($a != 0) $stack[] = '0';
		else $stack[] = '1';	
	}
}

class XpOr extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':or' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if ($a || $b) $stack[] = '1';
		else $stack[] = '0';			
	}
}

class XPpad extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':pad' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);	
		$stack[] = str_pad($b, $a,' ');
	}
}

class XPpow extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':pow' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));		
		$stack[] = $this->ctext(pow($b,$a));		
	}
}

class XPregex extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':regex' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$a = str_replace('/','\/',$a);  // does not 
		$a = str_replace('∫','\\',$a);	// Backslash does not always work well, so we allow ∫
		if (preg_match('/'.$a.'/',$b)) $stack[] = '1';
		else $stack[] = '0';
	}
}

class XPregexi extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':regexi' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$a = str_replace('/','\/',$a);  // does not 
		$a = str_replace('∫','\\',$a);	// Backslash does not always work well, so we allow ∫
		if (preg_match('/'.$a.'/i',$b)) $stack[] = '1';
		else $stack[] = '0';
	}
}


class XPregexreplace extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':regexreplace' ;}
	function run(&$stack)
	{
		if (count($stack) < 3) throw new swExpressionError('Stack < 3',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$c = array_pop($stack);	
		$b = str_replace('/','\/',$b);  // does not 
		$b = str_replace('∫','\\',$b);	// Backslash does not always work well, so we allow ∫
		$stack[] = preg_replace('/'.$b.'/',$a,$c);		
	}
}

class XPregexreplaceMod extends swExpressionFunction
{
	function __construct() { $this->arity = 4; $this->label = ':regexreplacemod' ;}
	function run(&$stack)
	{
		if (count($stack) < 4) throw new swExpressionError('Stack < 4',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$c = array_pop($stack);	
		$d = array_pop($stack);	
		$b = str_replace('/','\/',$b);  // does not 
		$b = str_replace('∫','\\',$b);	// Backslash does not always work well, so we allow ∫
		// echo "a $a<br> b $b<br> c $c<br> d $d";
		$stack[] = preg_replace('/'.$c.'/'.$a,$b,$d);		
	}
}


class XPreplace extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':replace' ;}
	function run(&$stack)
	{
		if (count($stack) < 3) throw new swExpressionError('Stack < 3',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$c = array_pop($stack);		
		$stack[] = str_replace($b,$a,$c);		
	}
}

class XPrnd extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':rnd' ;}
	function run(&$stack)
	{
		
		$stack[] = $this->ctext(rand());	
	}
}


class XPround extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':round' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(round($a));		
	}
}

class XPsecondstosql extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':secondstosql' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = date('Y-m-d H:i:s',$a);		
	}
}

class XPsign extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sign' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		if ($a > 0) $stack[] = '1';
		elseif 	($a < 0) $stack[] = '-1';
		else $stack[] = '0';
	}
}

class XPsin extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sin' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(sin($a));		
	}
}

class XPsqltoseconds extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sqltoseconds' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = $this->ctext(strtotime($a));	
	}
}

class XPsqrt extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sqrt' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(sqrt($a));		
	}
}

class XPSub extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':sub' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));		
		$stack[] = $this->ctext($b-$a);		
	}
}


class XPsubstr extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':substr' ;}
	function run(&$stack)
	{
		if (count($stack) < 3) throw new swExpressionError('Stack < 3',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$c = array_pop($stack);		
		$stack[] = substr($c,intval($b),intval($a));		
	}
}


class XPtan extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':tan' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = floatval(array_pop($stack));
		$stack[] = $this->ctext(tan($a));		
	}
}

class Xptrim extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':trim' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = trim($a);		
	}
}

class XPtrue extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':true' ;}
	function run(&$stack)
	{
		$stack[] = '1';	
	}
}

class XPupper extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':upper' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = strtoupper($a);		
	}
}

class XPurltext extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':urltext' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = swNameURL($a);		
	}
}

class XpxOr extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':xor' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = floatval(array_pop($stack));
		$b = floatval(array_pop($stack));	
		if (!$b && $a) $stack[] = '1';
		elseif ($b && !$b) $stack[] = '1';
		else $stack[] = '0';			
	}
}

class XpFormat extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':format' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = floatval(array_pop($stack));	
		$stack[] = swNumberformat($b,$a);		
	}
}

class XpNowiki extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':nowiki' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',101);
		$a = array_pop($stack);
		
		$w = new swWiki;
		$w->parsedContent = $a;
		$p = new swNoWikiParser;
		$p->dowork($w);
		$a = $w->parsedContent;
		
		
			
		$stack[] = $a;		
	}
}

class XpResume extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':resume' ;}
	function run(&$stack)
	{
		if (count($stack) < 3) throw new swExpressionError('Stack < 3',101);
		
		
		$raw = array_pop($stack);
		$length = array_pop($stack);
		$s = array_pop($stack);
			
		$stack[] = swResumeFromtext($s,$length,$raw);		
	}
}

class XpTemplate extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':template' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',101);
		$a = array_pop($stack);
		
		$w = new swWiki;
		$w->parsedContent = '{{'.$a.'}}'; //echo $w->parsedContent;
		$p = new swTemplateParser;
		$p->dowork($w);
		$a = $w->parsedContent;
		
		
			
		$stack[] = $a;		
	}
}


function TrimTrailingZeroes($nbr) {
    if(strpos($nbr,'.')!==false) $nbr = rtrim($nbr,'0');
    return rtrim($nbr,'.') ?: '0';
}

/*
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
*/


?>