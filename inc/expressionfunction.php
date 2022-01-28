<?php

if (!defined("SOFAWIKI")) die("invalid acces");

/**
 *	Contains the swExpressionFunction class to define functions and all its subclasses
 *  
 *  File also popluates the global $swExpressionFunctions array (only once for performance)
 */


/**
 * Provides a class to delegate and execute function.
 * 
 * $label function label as used in RPN (starting with :)
 * $arity number of arguments (must be present, because in RPN it is not present any more)
 * 
 */

class swExpressionFunction
{
	var $label;
	var $arity;
	


	function run(&$stack)
	{
		// stub
	}
}

/**
 * Provides a class to compile functions defined in Relation.
 * 
 * $lines() source text
 * $compiledlines() rpn representation
 * $xp internal expression engine
 * $args() runtime arguments
 * $offset runtime offset state
 * 
 */

class swExpressionCompiledFunction extends swExpressionFunction
{
	var $lines = array();
	var $compiledlines = array();
	var $xp;
	var $args = array();
	var $offset;
	
	function __construct($lb, $source, $off)
	{
		$this->lines = explode(PHP_EOL,$source);
		$this->offset = $off;
		$this->label = ':'.$lb;
		$this->xp = new swExpression();
		$this->compile();
 	}
 	
 	function compile()
 	{
	 	$c = count($this->lines);
	 	$this->compiledlines = array();
	 	$this->arity = 0;
	 	
	 	for($i=0;$i<$c;$i++)
	 	{
		 	$il = $this->offset + $i;
		 	$line = trim($this->lines[$i]);
		 	$ti = $il;
		 	$this->compiledlines[] = '#'.$ti;
		 	if (mb_strpos($line,'// ',0,'UTF-8')>-1)
		 	{
		 		$line = trim(mb_substr($line,0,mb_strpos($line,'// ',0,'UTF-8'),'UTF-8'));
		 	}
		 	if ($line=='') continue;
		 	$fields = explode(' ',$line);
		 	$command = array_shift($fields);
		 	$body = join(' ',$fields);
		 	
		 	switch ($command)
		 	{
			 	case 'function':	$dummy = array_shift($fields);
			 						$body = trim(join(' ',$fields));
									if (mb_substr($body,0,1,'UTF-8') != '(' || mb_substr($body,-1,1,'UTF-8') != ')')
									{
										throw new swExpressionError('Missing paranthesis #'.$ti,66);
									}
									$body = mb_substr($body,1,-1,'UTF-8');
			 						$fields = explode(',',$body);
			 						$this->arity = count($fields);
			 						for ($j = $this->arity-1;$j>=0;$j--)
			 						{
				 						$this->args[] = trim($fields[$j]);
			 						}
			 						break;
			 	case 'set':			$fields = explode(' ',$body);
			 						$f = array_shift($fields);
			 						if ($f == '') throw new swExpressionError('Set empty field #'.$ti,321);
			 						$body = join(' ',$fields);
			 						$fields = explode(' ',$body);
									$eq = array_shift($fields);
									$body = join(' ',$fields);
									if ($eq != '=') throw new swExpressionError('Init missing = #'.$ti,321);
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
				 						if (mb_strpos($line,'// ',0,'UTF-8')>-1) 
				 						{
				 							$line = trim(mb_substr($line,0,mb_strpos($line,'// ',0,'UTF-8'),'UTF-8'));
				 						}
				 						$fields = explode(' ',$line);
				 						$command2 = $fields[0];
				 						switch ($command2)
				 						{
					 						case 'if':		$conditioncount++; 
					 										break;
					 						case 'else':	if ($conditioncount==1)
					 										{
						 										if ($elsefound<0)
						 										{
							 										$this->lines[$j] = '';
							 										$elsefound = $j;
						 										}
						 										else
						 										{
						 											throw new swExpressionError('Duplicate else #'.$ti,321);
						 										}
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
							 									{
							 										$conditioncount--;
							 									}
					 										}
				 						}
			 						}
			 						while(!$found && $j<$c);
			 						
			 						if (!$found) throw new swExpressionError('Missing end if #'.$ti,321);
			 						break;
			 	case 'while':		$conditioncount = 1;
			 						$j = $i;
			 						$found = false;
			 						do 
			 						{
				 						$j++;
				 						$line = trim($this->lines[$j]);
				 						if (mb_strpos($line,'// ',0,'UTF-8')>-1)
				 						{
				 							$line = trim(mb_substr($line,0,mb_strpos($line,'// ',0,'UTF-8'),'UTF-8'));
				 						}
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
							 									{
							 										$conditioncount--;
							 									}
					 										}
				 						}
			 						}
			 						while(!$found && $j<$c);
			 						
			 						if (!$found) throw new swExpressionError('Missing end if #'.$ti,321);
			 						break;	
			 	case 'else':		throw new swExpressionError('Duplicate else #'.$ti,321);
			 						break;
			 	case 'end':			if ($line != 'end function') throw new swExpressionError('Duplicate end #'.$ti,321);
			 						break;
			 	case 'goto':		$this->compiledlines[] = $body;			
			 						$this->compiledlines[] = ':goto';
			 						break;
			 	default:			throw new swExpressionError('Invalid instruction #'.$ti.' '.$line,66);
		 	}
		 	
		 	
	 	}
	 	$this->compiledlines[] = 'result';
	 	
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

class xpAbs extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':abs' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '⦵') 
		{
			$stack[] = $a; 
			return;
		}
		if ($a == '-∞') 
		{ 
			$stack[] = '∞';
			return;
		}
		$a = floatval($a);
		$stack[] = swConvertText15(abs($a));		
	}
}

class xpAdd extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':add' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '-∞')
		{ 
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞' && $b == '∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' || $b == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if ($a == '-∞' || $b == '-∞')
		{
			$stack[] = '-∞';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);			
		$stack[] = swConvertText15($b+$a);	
	}
}

class xpAnd extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':and' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
	if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
		}
		else
		{
			if ($a == '∞') $a = '1';
			if ($b == '∞') $b = '1';
			if ($a == '-∞') $a = '1';
			if ($b == '-∞') $b = '1';
			$a = floatval($a);
			$b = floatval($b);
			if ($a && $b)
			{
				$stack[] = '1';
			}
			else
			{
				$stack[] = '0';
			}	
		}
	}
}

class xpAndRight extends swExpressionFunction //?
{
	function __construct() { $this->arity = 2; $this->label = ':and' ;}
	function run(&$stack)
	{
		echo "ans"; if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞' || $a == '-∞') $a = '1';
		if ($b == '∞' || $b == '-∞') $b = '1';
		$a = floatval($a);
		$b = floatval($b);		
		if ($a && $b) $stack[] = '1';
		else $stack[] = '0';			
	}
}

class xpCeil extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':ceil' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '-∞' || $a == '⦵')
		{
			$stack[] = $a;
			return;
		}
		$a = floatval($a);
		$stack[] = swConvertText15(ceil($a));		
	}
}

/**
 * Nop function
 */

class xpComma extends swExpressionFunction 
{
	function __construct() { $this->arity = 2; $this->label = ':comma' ;}
	function run(&$stack)
	{
		//if (count($this->stack) < 2) throw new swExpressionError('Stack < 2',102);
		// nop			
	}
}

class xpConcat extends swExpressionFunction
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

class xpCos extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':cos' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '-∞' || $a == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		$a = floatval($a);
		$stack[] = swConvertText15(cos($a));		
	}
}


class xpDiv extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':div' ;}
	function run(&$stack)
	{
		// print_r($stack);
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '-∞')
		{ 
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' && $b == '∞')
		{ 
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞')
		{ 
			$stack[] = '0';
			return;
		}
		if (floatval($a)>0 && $b == '∞')
		{ 
			$stack[] = '∞';
			return;
		}
		if (floatval($a)>0 && $b == '-∞')
		{
			$stack[] = '-∞';
			return;
		}
		if (floatval($a)<0 && $b == '∞')
		{ 
			$stack[] = '-∞';
			return;
		}
		if (floatval($a)<0 && $b == '-∞')
		{
			$stack[] = '∞';
			return;
		}
		
		$a = floatval($a);
		$b = floatval($b);	
		if ($a == 0 && $b == 0)
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == 0 && $b > 0)
		{
			$stack[] = '∞';
			return;
		}
		if ($a == 0 && $b < 0)
		{
			$stack[] = '-∞';
			return;
		}
		$stack[] = swConvertText15($b/$a);		
	}
}

class xpEqN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':eqn' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '∞' || $b == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' || $b == '-∞')
		{
			$stack[] = '0';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);
		if ($b == $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class xpEqS extends swExpressionFunction
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

class xpFileExists extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':filexists' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		
		$a = array_pop($stack);
		$file1 = 'site/files/'.$a;
		$file2 = 'site/cache/'.$a;	
		$stack[] = (file_exists($file1) || file_exists($file2));	
	}
}


class xpExp extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':exp' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '⦵')
		{
			$stack[] = $a;
			return;
		}
		if ($a == '-∞')
		{ 
			$stack[] = '0';
			return;
		}
		$a = floatval($a);
		$stack[] = swConvertText15(exp($a));		
	}
}

class xpFalse extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':false' ;}
	function run(&$stack)
	{
		$stack[] = '0';	
	}
}

class xpFloor extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':floor' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '⦵')
		{
			$stack[] = $a;
			return;
		}
		$a = floatval($a);
		$stack[] = swConvertText15(floor($a));		
	}
}


class xpFormat extends swExpressionFunction
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


class xpGeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gen' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($b == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '1';
			return;
		}
		if ($b == '-∞')
		{
			$stack[] = '0';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);
		if ($b >= $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class xpGeS extends swExpressionFunction
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

class xpGtN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gtn' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{ 
			$stack[] = '0';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($b == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '1';
			return;
		}
		if ($b == '-∞')
		{
			$stack[] = '0';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);
		if ($b > $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class xpGtS extends swExpressionFunction
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

/**
 * Returns true if a text matches the hint pattern.
 *
 * A hint is a combination fof urltext, separated by spaces (AND) and by pipes (OR). 
 * "foo bar" foo AND bar
 * "foo|bar" foo OR bar
 * "alice bob/foo" (alice AND bob) OR foo
 * There can not be nested levels of space and pipes  
*/ 


class XpHint extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':hint' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$b = swNameURL($b);
		
		if ($a == "*") 
		{
			$stack[] = 1;
			return;
		}
		
		$hors = explode('|',$a);
		$hors2 = array();
		foreach ($hors as $hor)
		{
			$hands = explode(' ',$hor);
			$hands2 = array();
			
			foreach($hands as $hand)
			{
				$hands2[] = swNameURL($hand);
			}
			$hors2[] = $hands2;
		}
		
		$orfound = false;
		foreach($hors2 as $hor)
		{
			$andfound = true;
			foreach($hor as $hand)
			{
				if ($hand != '' && !strstr($b,$hand)) $andfound = false;
			}
			if ($andfound) $orfound = true;
		}
				
		if ($orfound) 
		{
			$stack[] = 1;
		}
		else
		{
			$stack[] = 0;
		}		
	}
}

class xpIdiv extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':idiv' ;}
	function run(&$stack)
	{	
		
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '-∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' && $b == '∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if (floatval($a)>0 && $b == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if (floatval($a)>0 && $b == '-∞')
		{
			$stack[] = '-∞';
			return;
		}
		if (floatval($a)<0 && $b == '∞')
		{
			$stack[] = '-∞';
			return;
		}
		if (floatval($a)<0 && $b == '-∞')
		{
			$stack[] = '∞';
			return;
		}
		
		$a = floatval($a);
		$b = floatval($b);	
		if ($a == 0 && $b == 0)
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == 0 && $b > 0)
		{
			$stack[] = '∞';
			return;
		}
		if ($a == 0 && $b < 0)
		{
			$stack[] = '-∞';
			return;
		}

		$v = ($b - ($b % $a)) / $a ; 
		$stack[] = swConvertText15($v);
	}
}

class xpLeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':len' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack); 
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($b == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if ($b == '-∞')
		{
			$stack[] = '1';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);
		if ($b <= $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class xpLength extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':length' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = swConvertText15(mb_strlen($a,'UTF-8'));		
	}
}


class xpLeS extends swExpressionFunction
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

class xpLn extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':ln' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '⦵';
			return;
		} 
		$a = floatval($a);
		if ($a <= 0)
		{
			$stack[] = '⦵';
			return;
		}
		$stack[] = swConvertText15(log($a));		
	}
}

class xpLog extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':log' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '⦵';
			return;
		} 
		$a = floatval($a);
		if ($a <= 0)
		{
			$stack[] = '⦵';
			return;
		}
		$stack[] = swConvertText15(log10($a));		
	}
}

class xpLower extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':lower' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = strtolower($a);		
	}
}

class xpLtN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':ltn' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack); 
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($b == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if ($b == '-∞')
		{
			$stack[] = '1';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);
		if ($b < $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class xpLtS extends swExpressionFunction
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

class xpMax extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':max' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack); 
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' || $b == '∞' )
		{
			$stack[] = '∞';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = $b;
			return;
		}
		if ($b == '-∞')
		{
			$stack[] = $a;
			return;
		}
		$a = floatval($a);
		$b = floatval($b);		
		$stack[] = swConvertText15(max($b,$a));		
	}
}

class xpMin extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':min' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack); 
			if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞' || $b == '-∞' )
		{
			$stack[] = '-∞';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = $b;
			return;
		}
		if ($b == '∞')
		{
			$stack[] = $a;
			return;
		}
		$a = floatval($a);
		$b = floatval($b);		
		$stack[] = swConvertText15(min($b,$a));		
	}
}

class xpMod extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':mod' ;}
	function run(&$stack)
	{
		// print_r($stack);
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞' && $b == '-∞')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞')
		{ 
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' && $b == '∞')
		{ 
			$stack[] = '⦵'; 
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{ 
			$stack[] = '⦵';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if (floatval($a)>0 && $b == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if (floatval($a)>0 && $b == '-∞')
		{
			$stack[] = '-∞';
			return;
		}
		if (floatval($a)<0 && $b == '∞')
		{ 
			$stack[] = '-∞';
			return;
		}
		if (floatval($a)<0 && $b == '-∞')
		{
			$stack[] = '∞';
			return;
		}
		
		$a = floatval($a);
		$b = floatval($b);	
		if ($a == 0 && $b == 0)
		{
			$stack[] = '⦵'; 
			return; 
		}
		if ($a == 0 && $b > 0) 
		{ 
			$stack[] = '∞'; 
			return; 
		}
		if ($a == 0 && $b < 0) 
		{ 
			$stack[] = '-∞'; 
			return;
		}		
		$stack[] = swConvertText15($b%$a);		
	}
}


class xpMul extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':mul' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
	
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
			return;
		}	
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if ($a == '∞' && $b == '-∞')
		{
			$stack[] = '-∞';
			return;
		}
		if ($a == '∞' && floatval($b) > 0)
		{
			$stack[] = '∞';
			return;
		}
		if ($a == '∞' && floatval($b) < 0)
		{ 
			$stack[] = '-∞'; 
			return; 
		}
		if ($a == '∞' && floatval($b) == 0) 
		{ 
			$stack[] = '⦵'; 
			return;
		}		
		if ($a == '-∞' && $b == '∞')
		{ 
			$stack[] = '-∞'; 
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{ 
			$stack[] = '∞'; 
			return;
		}
		if ($a == '-∞' && floatval($b) > 0)
		{ 
			$stack[] = '∞';
			return;
		}
		if ($a == '-∞' && floatval($b) < 0)
		{
			$stack[] = '-∞';
			return;
		}
		if ($a == '-∞' && floatval($b) == 0)
		{
			$stack[] = '⦵';
			return;
		}
		
		if (floatval($a) > 0 && $b == '∞')
		{
			$stack[] = '∞';
			return;
		}
		if (floatval($a) > 0 && $b == '-∞')
		{
			$stack[] = '-∞';
			return;
		}

		if (floatval($a) < 0 && $b == '∞')
		{ 
			$stack[] = '-∞'; 
			return;
		}
		if (floatval($a) < 0 && $b == '-∞') 
		{ 
			$stack[] = '∞';
			return;
		}

		if (floatval($a) == 0 && $b == '∞')
		{ 
			$stack[] = '⦵';
			return;
		}
		if (floatval($a) == 0 && $b == '-∞')
		{ 
			$stack[] = '⦵';
			return;
		}
		
		$a = floatval($a);
		$b = floatval($b);			
		$stack[] = swConvertText15($b*$a);		
	}
}

/**
 * Provides a class to access wiki functions.
 *
 * $nativefunction instance of wiki function class
 *
 * Only functions with a defined arity can be included
 * Native function do not handle infinity and undefined
 * Native function do not expression functions
 */

class XpNative extends swExpressionFunction
{
	var $nativefunction;
	
	function __construct() { $this->arity = 1; $this->label = ':native' ;}
	function init($key, $fn) // swFunction
	{
		$this->nativefunction = $fn;
		$this->arity = $fn->arity();
		$this->label = ':'.$key;
	}
	function run(&$stack)
	{
		if (count($stack) < $this->arity) throw new swExpressionError('Stack < '.$this->arity,101);
		$args = array();
		
		for ($i = 0; $i < $this->arity ;$i++)
		
		$args[] = array_pop($stack);
		$args[] = mb_substr($this->label,1,null,'UTF-8');
		$args = array_reverse($args);
		
		$result = $this->nativefunction->dowork($args);
			
		$stack[] = $result;		
	}
}


class xpNeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':nen' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '∞' && $b == '∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '-∞' && $b == '-∞')
		{
			$stack[] = '0';
			return;
		}
		if ($a == '∞' || $b == '∞')
		{
			$stack[] = '1';
			return;
		}
		if ($a == '-∞' || $b == '-∞')
		{
			$stack[] = '1';
			return;
		}
		$a = floatval($a);
		$b = floatval($b);
		if ($b != $a) $stack[] = '1';
		else $stack[] = '0';		
	}
}

class xpNeg extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':neg' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '⦵')
		{
			$stack[] = '⦵';
			return;
		}
		if ($a == '∞')
		{
			$stack[] = '-∞';
			return;
		}
		if ($a == '-∞')
		{
			$stack[] = '∞';
			return;
		}
		$a = floatval($a);

		$stack[] = swConvertText15(-$a);		
	}
}

class xpNeS extends swExpressionFunction
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

class xpNot extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':not' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '⦵') $stack[] = '⦵';
		elseif($a == '∞') $stack[] = '0';
		elseif($a == '-∞') $stack[] = '0'; 
		elseif(floatval($a) != 0) $stack[] = '0';
		else $stack[] = '1';
	}
}

class xpOr extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':or' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵')
		{
			$stack[] = '⦵';
		}
		else
		{
			if ($a == '∞') $a = '1';
			if ($b == '∞') $b = '1';
			if ($a == '-∞') $a = '1';
			if ($b == '-∞') $b = '1';
			$a = floatval($a);
			$b = floatval($b);
			if ($a || $b)
			{
				$stack[] = '1';
			}
			else
			{
				$stack[] = '0';
			}	
		}
	}
}

class xpNoWiki extends swExpressionFunction
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

class xpPad extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':pad' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $a == '∞' || $a == '-∞')
		{
			$stack[] = $b;
			return;
		}		
		$a = floatval($a);	
		$stack[] = str_pad($b, $a,' ');
	}
}

class xpPow extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':pow' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵') $stack[] = '⦵';
		elseif ($a == '∞' && $b == '-∞') $stack[] = '-∞';
		elseif ($a == '∞' && $b == '∞') $stack[] = '∞';
		elseif ($a == '-∞') $stack[] = '1';
		elseif ($a == '0' && $b == '0') $stack[] = '1';
		else
		{
			$a = floatval($a);
			$b = floatval($b);			
			$stack[] = swConvertText15(pow($b,$a));
		}				
	}
}

/**
 * Provides a class to handle case-sensitive regex search
 *
 * As an alternative for backslash \ the ∫ can be used
 */

class xpRegex extends swExpressionFunction
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

/**
 * Provides a class to handle case-insensitive regex search
 *
 * As an alternative for backslash \ the ∫ can be used
 */

class xpRegexi extends swExpressionFunction
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

/**
 * Provides a class to handle regex replace
 *
 * As an alternative for backslash \ the ∫ can be used
 */


class xpRegexReplace extends swExpressionFunction
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

/**
 * Provides a class to handle regex replace with a modifier as argument
 *
 * As an alternative for backslash \ the ∫ can be used
 */

class xpRegexReplaceMod extends swExpressionFunction
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

class xpReplace extends swExpressionFunction
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

class xpResume extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':resume' ;}
	function run(&$stack)
	{
		if (count($stack) < 3) throw new swExpressionError('Stack < 3',101);
			
		$raw = array_pop($stack);
		$length = array_pop($stack);
		$s = array_pop($stack);
			
		$stack[] = swResumeFromText($s,$length,$raw);		
	}
}

/**
 * Returns a random number between 0 and 999999
 */

class xpRnd extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':rnd' ;}
	function run(&$stack)
	{
		
		$stack[] = swConvertText15(rand(0,999999));	
	}
}

/**
 * Rounds to integer
 */


class xpRound extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':round' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '-∞' || $a == '⦵') $stack[] = $a;
		else
		{
			$a = floatval($a);
			$stack[] = swConvertText15(round($a));	
		}	
	}
}

/**
 * Converts unix seconds to SQL date-time string
 */

class XPSecondsToSql extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':secondstosql' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '-∞' || $a == '⦵') $a = 0;
		$a = floatval($a);
		$stack[] = date('Y-m-d H:i:s',$a);		
	}
}

class xpSign extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sign' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '⦵') $stack[] = '⦵';
		elseif ($a == '∞') $stack[] = '1'; 
		elseif ($a == '-∞') $stack[] = '-1'; 
		else
		{
			$a = floatval($a);
			if ($a > 0) $stack[] = '1';
			elseif 	($a < 0) $stack[] = '-1';
			else $stack[] = '0';
		}
	}
}

class xpSin extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sin' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '-∞' || $a == '⦵') $stack[] = '⦵';
		else
		{
			$a = floatval($a);
			$stack[] = swConvertText15(sin($a));
		}		
	}
}

/**
 * Converts  SQL date-time string to unix seconds
 */

class xpSqlToSeconds extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sqltoseconds' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = swConvertText15(strtotime($a));	
	}
}

class xpSqrt extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sqrt' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '⦵') $stack[] = '⦵';
		elseif ($a == '∞') $stack[] = '∞';
		elseif ($a == '-∞') $stack[] = '⦵';
		else
		{
			$a = floatval($a);
			if ($a < 0) $stack[] = '⦵'; 
			else $stack[] = swConvertText15(sqrt($a));	
		}	
	}
}

class xpSub extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':sub' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵') $stack[] = '⦵';
		elseif ($a == '∞' && $b == '-∞') $stack[] = '-∞'; 
		elseif ($a == '-∞' && $b == '∞') $stack[] = '∞';
		elseif ($a == '∞' && $b == '∞') $stack[] = '⦵'; 
		elseif ($a == '-∞' && $b == '-∞') $stack[] = '⦵'; 
		elseif ($a == '∞') $stack[] = '-∞'; 
		elseif ($a == '-∞') $stack[] = '∞'; 
		elseif ($b == '∞') $stack[] = '∞'; 
		elseif ($b == '-∞') $stack[] = '-∞'; 
		else
		{
			$a = floatval($a);
			$b = floatval($b);			
			$stack[] = swConvertText15($b-$a);	
		}	
	}
}


class xpSubstr extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':substr' ;}
	function run(&$stack)
	{
		if (count($stack) < 3) throw new swExpressionError('Stack < 3',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		$c = array_pop($stack);		
		$stack[] = mb_substr($c,intval($b),intval($a),'UTF-8');		
	}
}


class xpTan extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':tan' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		if ($a == '∞' || $a == '-∞' || $a == '⦵') $stack[] = '⦵';
		else 
		{
			$stack[] = swConvertText15(tan(floatval($a)));		
		}
	}
}

class xpTemplate extends swExpressionFunction
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


class xpTrim extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':trim' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = trim($a);		
	}
}

class xpTrue extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':true' ;}
	function run(&$stack)
	{
		$stack[] = '1';	
	}
}

class xpUpper extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':upper' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = strtoupper($a);		
	}
}

/**
 * Converts text to urltext
 */

class xpUrlText extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':urltext' ;}
	function run(&$stack)
	{
		if (count($stack) < 1) throw new swExpressionError('Stack < 1',102);
		$a = array_pop($stack);
		$stack[] = swNameURL($a);		
	}
}

class xpXor extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':xor' ;}
	function run(&$stack)
	{
		if (count($stack) < 2) throw new swExpressionError('Stack < 2',102);
		$a = array_pop($stack);
		$b = array_pop($stack);
		if ($a == '⦵' || $b == '⦵') $stack[] = '⦵';
		else
		{
			if ($a == '∞') $a = '1';
			if ($b == '∞') $b = '1';
			if ($a == '-∞') $a = '1';
			if ($b == '-∞') $b = '1';
			$a = floatval($a);
			$b = floatval($b);		
			if (!$b && $a) $stack[] = '1';
			elseif ($b && !$a) $stack[] = '1';
			else $stack[] = '0';
		}			
	}
}

$swExpressionFunctions = array();

$swExpressionFunctions[':neg'] = new XPNeg;
$swExpressionFunctions[':not'] = new XPNot;

$swExpressionFunctions[':div'] = new XPDiv;
$swExpressionFunctions[':mul'] = new XPMul;
$swExpressionFunctions[':idiv'] = new XPIdiv;
$swExpressionFunctions[':mod'] = new XPMod;
$swExpressionFunctions[':add'] = new XPAdd;
$swExpressionFunctions[':sub'] = new XPSub;

$swExpressionFunctions[':concat'] = new XPConcat;

$swExpressionFunctions[':eqn'] = new XPEqN;
$swExpressionFunctions[':nen'] = new XPNeN;
$swExpressionFunctions[':gtn'] = new XPGtN;
$swExpressionFunctions[':gen'] = new XPGeN;
$swExpressionFunctions[':ltn'] = new XPLtN;
$swExpressionFunctions[':len'] = new XPleN;

$swExpressionFunctions[':eqs'] = new XPEqS;
$swExpressionFunctions[':nes'] = new XPNeS;
$swExpressionFunctions[':gts'] = new XPGtS;
$swExpressionFunctions[':ges'] = new XPGeS;
$swExpressionFunctions[':lts'] = new XPLtS;
$swExpressionFunctions[':les'] = new XPLeS;

$swExpressionFunctions[':and'] = new XPAnd;
$swExpressionFunctions[':or'] = new XPOr;
$swExpressionFunctions[':xor'] = new XPXor;
$swExpressionFunctions[':hint'] = new XPHint;

$swExpressionFunctions[':comma'] = new XPComma;

// real functions

$swExpressionFunctions[':abs'] = new XPAbs;
$swExpressionFunctions[':ceil'] = new XPCeil;
$swExpressionFunctions[':cos'] = new XPCos;
$swExpressionFunctions[':exp'] = new XpExp;
$swExpressionFunctions[':floor'] = new XPFloor;
$swExpressionFunctions[':ln'] = new XpLn;
$swExpressionFunctions[':log'] = new XpLog;
$swExpressionFunctions[':pow'] = new XPPow;
$swExpressionFunctions[':rnd'] = new XPRnd;
$swExpressionFunctions[':round'] = new XPRound;
$swExpressionFunctions[':sin'] = new XPSin;
$swExpressionFunctions[':sign'] = new XPSign;
$swExpressionFunctions[':sqrt'] = new XPSqrt;
$swExpressionFunctions[':xptan'] = new XPTan;

$swExpressionFunctions[':length'] = new XPLength;
$swExpressionFunctions[':lower'] = new XPLower;
$swExpressionFunctions[':regex'] = new XPRegex;
$swExpressionFunctions[':regexi'] = new XPRegexi;
$swExpressionFunctions[':regexreplace'] = new XPRegexReplace;
$swExpressionFunctions[':regexreplacemod'] = new XPRegexReplaceMod;
$swExpressionFunctions[':replace'] = new XPReplace;
$swExpressionFunctions[':substr'] = new XPSubstr;
$swExpressionFunctions[':upper'] = new XPUpper;
$swExpressionFunctions[':urltext'] = new XPurltext;
$swExpressionFunctions[':pad'] = new XPpad;
$swExpressionFunctions[':trim'] = new XPtrim;

$swExpressionFunctions[':max'] = new XPMax;
$swExpressionFunctions[':min'] = new XPMin;

$swExpressionFunctions[':secondstosql'] = new XPSecondsToSQL;
$swExpressionFunctions[':sqltoseconds'] = new XPSQLtoSeconds;

$swExpressionFunctions[':format'] = new XPFormat;

$swExpressionFunctions[':resume'] = new XpResume;
$swExpressionFunctions[':template'] = new XpTemplate;

$swExpressionFunctions[':fileexists'] = new xpFileExists;

