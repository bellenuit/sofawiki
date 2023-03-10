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
	var $isOperator;
	
	function run0($args)
	{
		if ($this->arity>-1 && count($args) != $this->arity) throw new swExpressionError('wrong number of arguments for '.$this->label.' is '.intval(count($args)).', should be '.intval($this->arity),102);
		return $this->run($args);
	}

	function run($args)
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
 	
 	function run($args)
	{
		if ($this->xp == NULL) $this->xp = new swExpression();
		$this->xp->rpn = $this->compiledlines;
		$localvalues = array();
		foreach($this->args as $arg)
		{
			$localvalues[$arg] = array_pop($args);
		}
		return $this->xp->evaluate($localvalues);
	}
}

class xpAbs extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':abs' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '⦵') return $a;
		if ($a === '-∞') return '∞';
		$a = floatval($a);
		return swConvertText15(abs($a));		
	}
}

class xpAdd extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':add' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '-∞') return '⦵';
		if ($a === '-∞' && $b === '∞') return '⦵';
		if ($a === '∞' || $b === '∞') return '∞';
		if ($a === '-∞' || $b === '-∞') return '-∞';
		$a = floatval($a);
		$b = floatval($b);			
		return swConvertText15($b+$a);	
	}
}

class xpAnd extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':and' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞') $a = '1';
		if ($b === '∞') $b = '1';
		if ($a === '-∞') $a = '1';
		if ($b === '-∞') $b = '1';
		$a = floatval($a);
		$b = floatval($b);
		if ($a && $b) return '1';
		return '0';
	}
}

class xpAndRight extends swExpressionFunction //?
{
	function __construct() { $this->arity = 2; $this->label = ':and' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '0';
		if ($a === '∞' || $a === '-∞') $a = '1';
		if ($b === '∞' || $b === '-∞') $b = '1';
		$a = floatval($a);
		$b = floatval($b);		
		if ($a && $b) return '1';
		return '0';			
	}
}

class xpCeil extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':ceil' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' || $a === '⦵') return $a;
		$a = floatval($a);
		return swConvertText15(ceil($a));		
	}
}

class xpBigramStat extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':bigramstat' ;}
	function run($args)
	{
		$a = $args[0];
		return swBigramStat($a);		
	}
}


/**
 * Nop function
 */

class xpComma extends swExpressionFunction 
{
	function __construct() { $this->arity = 2; $this->label = ':comma' ;}
	function run($args)
	{
		return '';		
	}
}

class xpConcat extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':concat' ; $this->isOperator = true ;}
	function run($args)
	{
		
		$a = $args[0];
		$b = $args[1];
		
		if (is_a($a,"swExpressionFunctionBoundary")) throw new swExpressionError('swExpressionFunctionBoundary',321);
		if (is_a($b,"swExpressionFunctionBoundary")) throw new swExpressionError('swExpressionFunctionBoundary',321);
		
		return $a.$b;		
	}
}

class xpCos extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':cos' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' || $a === '⦵') return '⦵';
		$a = floatval($a);
		return swConvertText15(cos($a));		
	}
}

class xpCosineSimilarity extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':cosinesimilarity' ;}
	
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		$alist = explode(' ',$a);
		$blist = explode(' ',$b);
		$alistw = array();
		$alistsum = 0;
		foreach($alist as $elem)
		{
			$fields = explode('=',$elem);
			if (!isset($fields[1])) continue;
			$alistw[$fields[0]] = $fields[1];
			$alistsum += intval($fields[1]);
		}
		$blistw = array();
		$blistsum = 0;
		foreach($blist as $elem)
		{
			$fields = explode('=',$elem);
			if (!isset($fields[1])) continue;
			$blistw[$fields[0]] = $fields[1];
			$blistsum += intval($fields[1]);
		}
		
		if ($alistsum == 0 || $blistsum  == 0)
		{
			$stack[] = 0;
			return;
		}
		$dotxy = 0;
		$dotxx = 0;
		$dotyy = 0;
				
		foreach($alistw as $k=>$v)
		{
			$dotxx += intval($v)*intval($v);
			if (isset($blistw[$k])) $dotxy += intval($v)*intval($blistw[$k]);
		}
		foreach($blistw as $k=>$v)
		{
			$dotyy += intval($v)*intval($v);
		}
		
		return $dotxy / sqrt($dotxx*$dotyy);	
	}}



class xpDiv extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':div' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '∞') return '⦵';
		if ($a === '∞' && $b === '-∞') return '⦵';
		if ($a === '∞') return '0';
		if ($a === '-∞' && $b === '∞') return '⦵';
		if ($a === '-∞' && $b === '-∞') return '⦵';
		if ($a === '-∞') return '0';
		if (floatval($a)>0 && $b === '∞') return '∞';
		if (floatval($a)>0 && $b === '-∞') return '-∞';
		if (floatval($a)<0 && $b === '∞') return '-∞';
		if (floatval($a)<0 && $b === '-∞') return '∞';
		
		$a = floatval($a);
		$b = floatval($b);	
		if ($a == 0 && $b == 0) return '⦵';
		if ($a == 0 && $b > 0) return '∞';
		if ($a == 0 && $b < 0) return '-∞';
		return swConvertText15($b/$a);		
	}
}

class xpEqN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':eqn' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($a === '⦵' || $b === '⦵') return '0';
		if ($a === '∞' && $b === '∞') return '1';
		if ($a === '-∞' && $b === '-∞') return '1';
		if ($a === '∞' || $b === '∞') return '0';
		if ($a === '-∞' || $b === '-∞') return '0';
		$a = floatval($a);
		$b = floatval($b);
		if ($b == $a) return '1';
		return '0';		
	}
}

class xpEqS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':eqs' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];	
		if ($b == $a) return '1';
		return '0';		
	}
}

class xpFileExists extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':filexists' ;}
	function run($args)
	{
		$a = $args[0];
		$file1 = 'site/files/'.$a;
		$file2 = 'site/cache/'.$a;	
		return (file_exists($file1) || file_exists($file2));	
	}
}


class xpExp extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':exp' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '⦵') return $a;
		if ($a === '-∞') return '0';
		$a = floatval($a);
		return swConvertText15(exp($a));		
	}
}

class xpFalse extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':false' ; }
	function run($args)
	{
		return '0';	
	}
}

class xpFloor extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':floor' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' ||$a === '⦵') return $a;
		$a = floatval($a);
		return swConvertText15(floor($a));		
	}
}


class xpFormat extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':format' ;}
	function run($args)
	{
		$a = floatval($args[0]);	
		$b = $args[1];
		return swNumberformat($a,$b);		
	}
}


class xpGeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gen' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '0';
		if ($a === '∞' && $b === '∞') return '0';
		if ($a === '-∞' && $b === '-∞') return '0';
		if ($a === '∞') return '0';
		if ($b === '∞') return '1';
		if ($a === '-∞') return '1';
		if ($b === '-∞') return '0';
		$a = floatval($a);
		$b = floatval($b);
		if ($b >= $a) return '1';
		return '0';			
	}
}

class xpGeS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':ges' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($b >= $a) return '1';
		return '0';		
	}
}

class xpGtN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gtn' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '0';
		if ($a === '∞' && $b === '∞') return '0';
		if ($a === '-∞' && $b === '-∞') return '0';
		if ($a === '∞') return '0';
		if ($b === '∞') return '1';
		if ($a === '-∞') return '1';
		if ($b === '-∞') return '0';
		$a = floatval($a);
		$b = floatval($b);
		if ($b > $a) return '1';
		return '0';		
	}
}

class xpGtS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':gts' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];	
		$a = $args[0];
		if ($b > $a) return '1';
		return '0';		
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
	function run($args)
	{
		$a = $args[0];  // needle
		$b = $args[1]; // haystack
		$b = swNameURL($b);
		
		if ($a == "*") 
		{
			return '1';
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
			return '1';
		}
		else
		{
			return '0';
		}		
	}
}

class xpIdiv extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':idiv' ; $this->isOperator = true ;}
	function run($args)
	{	
		
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '∞') return '⦵';
		if ($a === '∞' && $b === '-∞') return '⦵';
		if ($a === '∞') return '0';
		if ($a === '-∞' && $b === '∞') return '⦵';
		if ($a === '-∞' && $b === '-∞') return '⦵';
		if ($a === '-∞') return '0';
		if (floatval($a)>0 && $b === '∞') return '∞';
		if (floatval($a)>0 && $b === '-∞') return '-∞';
		if (floatval($a)<0 && $b === '∞') return '-∞';
		if (floatval($a)<0 && $b === '-∞') return '∞';
		
		$a = floatval($a);
		$b = floatval($b);	
		if ($a == 0 && $b == 0) return '⦵';
		if ($a == 0 && $b > 0) return '∞';
		if ($a == 0 && $b < 0) return '-∞';

		$v = ($b - ($b % $a)) / $a ; 
		return swConvertText15($v);
	}
}

class xpJaccardDistance extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':jaccarddistance' ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		$alist = explode(' ',$a);
		$blist = explode(' ',$b);
		$u = array_unique(array_merge($alist,$blist));	
		$i = array_intersect($alist,$blist);
		$jaccard = 0;
		if (count($u)>0) $jaccard = 1.0*count($i)/count($u); // /count($u);
		return $jaccard;	
	}
}


class xpLeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':len' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '0';
		if ($a === '∞' && $b === '∞') return '1';
		if ($a === '-∞' && $b === '-∞') return '1';
		if ($a === '∞') return '1';
		if ($b === '∞') return '0';
		if ($a === '-∞') return '0';
		if ($b === '-∞') return '1';
		$a = floatval($a);
		$b = floatval($b);
		if ($b <= $a) return '1';
		return '0';		
	}
}

class xpLength extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':length' ;}
	function run($args)
	{
		$a = $args[0];
		return swConvertText15(mb_strlen($a,'UTF-8'));		
	}
}


class xpLeS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':les' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];	
		if ($b <= $a) return '1';
		return '0';		
	}
}

class xpLevenshtein extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':levenshtein' ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];		
		return levenshtein($a,$b);	
	}
}

class xpLink extends swExpressionFunction
{
	function __construct() { $this->arity = -1; $this->label = ':link' ;}
	function run($args)
	{
		if(!count($args)) return '';
		return '[['.join('|',$args).']]'; //echo $w->parsedContent;		
	}
}


class xpLn extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':ln' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '⦵') return '⦵';
		if ($a === '∞') return '∞';
		if ($a === '-∞') return '⦵';

		$a = floatval($a);
		if (!$a) return '-∞';
		if ($a <= 0) return '⦵';
		
		return swConvertText15(log($a));		
	}
}

class xpLog extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':log' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '⦵') return '⦵';
		if ($a === '∞') return '∞';
		if ($a === '-∞') return '⦵';
		$a = floatval($a);
		if (!$a) return '-∞';
		if ($a <= 0) return '⦵';
		return swConvertText15(log10($a));	
	}
}

class xpLower extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':lower' ;}
	function run($args)
	{
		$a = $args[0];
		return strtolower($a);		
	}
}

class xpLtN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':ltn' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '∞') return '⦵';
		if ($a === '-∞' && $b === '-∞') return '0';
		if ($a === '∞')  return '1';
		if ($b === '∞') return '0';
		if ($a === '-∞') return '0';
		if ($b === '-∞')  return '1';
		$a = floatval($a);
		$b = floatval($b);
		if ($b < $a) return '1';
		else return '0';		
	}
}

class xpLtS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':lts' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($b < $a) return '1';
		else return '0';		
	}
}

class xpMax extends swExpressionFunction
{
	function __construct() { $this->arity = -1; $this->label = ':max' ;}
	function run($args)
	{
		foreach($args as $a) if ($a === '⦵') return '⦵';
		foreach($args as $a) if ($a === '∞') return '∞';
		$args = array_filter($args, function($v) { return $v !== '-∞' ;}, 0 );
		$args = array_map(function($v) { return floatval($v) ;}, $args);		
		if (! count($args))	return '⦵';
		return swConvertText15(max($args));		
	}
}

class xpMin extends swExpressionFunction
{
	function __construct() { $this->arity = -1; $this->label = ':min' ;}
	function run($args)
	{
		foreach($args as $a) if ($a === '⦵') return '⦵';
		foreach($args as $a) if ($a === '-∞') return '-∞';
		$args = array_filter($args, function($v) { return $v !== '∞' ;}, 0 );
		$args = array_map(function($v) { return floatval($v) ;}, $args);		
		if (! count($args))	return '⦵';
		return swConvertText15(min($args));	
	}
}

class xpMod extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':mod' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵')  return '⦵';
		if ($a === '∞' && $b === '∞')  return '⦵';
		if ($a === '∞' && $b === '-∞')  return '⦵';
		if ($a === '∞')  return '0';
		if ($a === '-∞' && $b === '∞')  return '⦵';
		if ($a === '-∞' && $b === '-∞')  return '⦵';
		if ($a === '-∞') return '0';
		if (floatval($a)>0 && $b === '∞') return '∞';
		if (floatval($a)>0 && $b === '-∞') return '-∞';
		if (floatval($a)<0 && $b === '∞') return '-∞';
		if (floatval($a)<0 && $b === '-∞') return '∞';
		
		$a = floatval($a);
		$b = floatval($b);	
		if ($a == 0 && $b == 0) return '⦵';
		if ($a == 0 && $b > 0) return '∞';
		if ($a == 0 && $b < 0) return '-∞';
		return swConvertText15($b%$a);		
	}
}


class xpMul extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':mul' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
	
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '∞') return '∞';
		if ($a === '∞' && $b === '-∞') return '-∞';
		if ($a === '∞' && floatval($b) > 0) return '∞';
		if ($a === '∞' && floatval($b) < 0) return '-∞';
		if ($a === '∞' && floatval($b) == 0) return '⦵';
		if ($a === '-∞' && $b === '∞') return '-∞';
		if ($a === '-∞' && $b === '-∞') return '∞';
		if ($a === '-∞' && floatval($b) > 0) return '∞';
		if ($a === '-∞' && floatval($b) < 0) return '-∞';
		if ($a === '-∞' && floatval($b) == 0) return '⦵';
		if (floatval($a) > 0 && $b === '∞') return '∞';
		if (floatval($a) > 0 && $b === '-∞') return '-∞';
		if (floatval($a) < 0 && $b === '∞') return '-∞';
		if (floatval($a) < 0 && $b === '-∞') return '∞';
		if (floatval($a) == 0 && $b === '∞') return '⦵';
		if (floatval($a) == 0 && $b === '-∞') return '⦵';
		
		$a = floatval($a);
		$b = floatval($b);			
		return swConvertText15($b*$a);		
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
	function run($args)
	{
		array_unshift($args, mb_substr($this->label,1,null,'UTF-8'));
		
		$result = $this->nativefunction->dowork($args);
			
		return $result;		
	}
}


class xpNeN extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':nen' ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($a === '⦵' || $b === '⦵') return '1';
		if ($a === '∞' && $b === '∞') return '0';
		if ($a === '-∞' && $b === '-∞')  return '0';
		if ($a === '∞' || $b === '∞') return '1';
		if ($a === '-∞' || $b === '-∞') return '1';
		$a = floatval($a);
		$b = floatval($b);
		if ($b != $a) return '1';
		return '0';	
	}
}

class xpNeg extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':neg' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '⦵') return '⦵';
		if ($a === '∞')  return  '-∞';
		if ($a === '-∞')  return  '∞';
		$a = floatval($a);

		return swConvertText15(-$a);		
	}
}

class xpNeS extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':nes' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($b != $a) return '1';
		return '0';		
	}
}

class xpNot extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':not' ; $this->isOperator = true ;}
	function run($args)
	{
		if (count($args) < 1) throw new swExpressionError('Stack < 1',102);
		$a = $args[0];
		if ($a === '⦵') return '⦵';
		if($a === '∞') return '0';
		if($a === '-∞') return '0'; 
		if(floatval($a) != 0) return '0';
		return '1';
	}
}



class xpOr extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':or' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($a === '⦵' || $b === '⦵') return  '⦵';
		if ($a === '∞') $a = '1';
		if ($b === '∞') $b = '1';
		if ($a === '-∞') $a = '1';
		if ($b === '-∞') $b = '1';
		$a = floatval($a);
		$b = floatval($b);
		if ($a || $b) return '1';
		return '0';
	}
}


class xpPad extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':pad' ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $a === '∞' || $a === '-∞') return $b;
		$a = floatval($a);	
		return str_pad($b, $a,' ');
	}
}

class xpPow extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':pow' ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '-∞') return '-∞';
		if ($a === '∞' && $b === '∞')return '∞';
		if ($a === '-∞') return '1';
		if ($a == '0' && $b == '0') return '1';
		$a = floatval($a);
		$b = floatval($b);			
		return swConvertText15(pow($b,$a));
	}
}

/**
 * Provides a class to handle case-sensitive regex search
 *
 * As an alternative for backslash \ the ∫ can be used
 */

class xpRegex extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':regex' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		$a = str_replace('/','\/',$a);  // does not 
		$a = str_replace('∫','\\',$a);	// Backslash does not always work well, so we allow ∫
		if (preg_match('/'.$a.'/',$b)) return '1';
		else return '0';
	}
}

/**
 * Provides a class to handle case-insensitive regex search
 *
 * As an alternative for backslash \ the ∫ can be used
 */

class xpRegexi extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':regexi' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		$a = str_replace('/','\/',$a);  // does not 
		$a = str_replace('∫','\\',$a);	// Backslash does not always work well, so we allow ∫
		if (preg_match('/'.$a.'/i',$b)) return '1';
		return '0';
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
	function run($args)
	{
		$c = $args[0];
		$b = $args[1];
		$a = $args[2];
		$b = str_replace('/','\/',$b);  // does not 
		$b = str_replace('∫','\\',$b);	// Backslash does not always work well, so we allow ∫
		return preg_replace('/'.$b.'/',$a,$c);		
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
	function run($args)
	{
		$d = $args[0];
		$c = $args[1];
		$b = $args[2];
		$a = $args[3];
		$c = str_replace('/','\/',$c);  // does not 
		$c = str_replace('∫','\\',$c);
		$b = str_replace('/','\/',$b);  // does not 
		$b = str_replace('∫','\\',$b);	// Backslash does not always work well, so we allow ∫
		return preg_replace('/'.$c.'/'.$a,$b,$d);		
	}
}

class xpReplace extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':replace' ;}
	function run($args)
	{
		$c = $args[0];
		$b = $args[1];
		$a = $args[2];
		return str_replace($b,$a,$c);		
	}
}

class xpResume extends swExpressionFunction
{
	function __construct() { $this->arity = 3; $this->label = ':resume' ;}
	function run($args)
	{
		$s = $args[0];
		$length = $args[1];
		$raw = $args[2];
		
		return swResumeFromText($s,$length,$raw);		
	}
}

/**
 * Returns a random number between 0 and 999999
 */

class xpRnd extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':rnd' ;}
	function run($agrs)
	{
		
		return swConvertText15(rand(0,1000000000)/1000000000);	
	}
}

/**
 * Rounds to integer
 */


class xpRound extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':round' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' || $a === '⦵') return $a;
		$a = floatval($a);
		return swConvertText15(round($a));	
	}
}

/**
 * Converts unix seconds to SQL date-time string
 */

class XPSecondsToSql extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':secondstosql' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' || $a === '⦵') $a = 0;
		$a = floatval($a);
		return date('Y-m-d H:i:s',$a);		
	}
}

class xpSign extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sign' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '⦵') return '⦵';
		if ($a === '∞') return '1'; 
		if ($a === '-∞') return '-1'; 
		$a = floatval($a);
		if ($a > 0) return '1';
		if ($a < 0) return '-1';
		return '0';
	}
}

class xpSin extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sin' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' || $a === '⦵') return '⦵';
		$a = floatval($a);
		return swConvertText15(sin($a));
	}
}

class xpSoundex extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':soundex' ;}
	function run($args)
	{
		$a = $args[0];
		return soundex($a);		
	}
}

class xpSoundexLong extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':soundexlong' ;}
	function run($args)
	{
		$a = $args[0];
		$a = preg_replace('/[^a-zA-Z&;0-9 ]/','',$a);
		$list = array();
		foreach(explode(' ',$a) as $w)
		{
			$list[] = soundex($w);
		}
		return join(' ',$list);	
		}
}


/**
 * Converts  SQL date-time string to unix seconds
 */

class xpSqlToSeconds extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sqltoseconds' ;}
	function run($args)
	{
		$a = $args[0];
		return swConvertText15(strtotime($a));	
	}
}

class xpSqrt extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':sqrt' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '⦵') return '⦵';
		if ($a === '∞') return '∞';
		if ($a === '-∞') return '⦵';
		$a = floatval($a);
		if ($a < 0) return '⦵'; 
		return swConvertText15(sqrt($a));	
	}
}

class xpStrip extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':strip' ;}
	function run($args)
	{
		$a = $args[0];
		
		$a = swResumeFromText($a,999999999,true);
		return $a;		
	}
}


class xpSub extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':sub' ; $this->isOperator = true ;}
	function run($args)
	{
		$b = $args[0];
		$a = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞' && $b === '-∞') return '-∞'; 
		if ($a === '-∞' && $b === '∞') return '∞';
		if ($a === '∞' && $b === '∞') return '⦵'; 
		if ($a === '-∞' && $b === '-∞') return '⦵'; 
		if ($a === '∞') return '-∞'; 
		if ($a === '-∞') return '∞'; 
		if ($b === '∞') return '∞'; 
		if ($b === '-∞') return '-∞'; 
		$a = floatval($a);
		$b = floatval($b);			
		return swConvertText15($b-$a);	
	}
}


class xpSubstr extends swExpressionFunction
{
	function __construct() { $this->arity = -1; $this->label = ':substr' ;}
	function run($args)
	{
		if (count($args)<1) return '';
		if (count($args)<2) return $args[0];
		
		$a = $args[0];
		$b = $args[1];
		
		if (count($args)<3) return mb_substr($a,intval($b));
		
		$c = $args[2];
		
		return mb_substr($a,intval($b),intval($c),'UTF-8');		
	}
}

class xpTag extends swExpressionFunction
{
	function __construct() { $this->arity = -1; $this->label = ':tag' ;}
	function run($args)
	{
		if(!count($args)) return '';
		if (count($args) == 1) return '<'.$args[0].'/>';
		if (count($args) == 2) return '<'.$args[0].'>'.$args[1].'</'.$args[0].'>';    
		if (count($args) == 3) return '<'.$args[0].' '.$args[1].'>'.$args[2].'</'.$args[0].'>';  
		return '';  
	}
}


class xpTan extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':tan' ;}
	function run($args)
	{
		$a = $args[0];
		if ($a === '∞' || $a === '-∞' || $a === '⦵') return '⦵';
		return swConvertText15(tan(floatval($a)));		
	}
}

class xpTemplate extends swExpressionFunction
{
	function __construct() { $this->arity = -1; $this->label = ':template' ;}
	function run($args)
	{
		if(!count($args)) return '';
		$w = new swWiki;
		$w->parsedContent = '{{'.join('|',$args).'}}'; //echo $w->parsedContent;
		$p = new swTemplateParser;
		$p->dowork($w);
		$a = $w->parsedContent;
		return $a;		
	}
}


class xpTrim extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':trim' ;}
	function run($args)
	{
		$a = $args[0];
		return trim($a);		
	}
}




class xpWeightedJaccardDistance extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':weightedjaccarddistance' ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		$alist = explode(' ',$a);
		$blist = explode(' ',$b);
		$alistw = array();
		$alistsum = 0;
		foreach($alist as $elem)
		{
			$fields = explode('=',$elem);
			if (!isset($fields[1])) continue;
			$alistw[$fields[0]] = $fields[1];
			$alistsum += intval($fields[1]);
		}
		$blistw = array();
		$blistsum = 0;
		foreach($blist as $elem)
		{
			$fields = explode('=',$elem);
			if (!isset($fields[1])) continue;
			$blistw[$fields[0]] = $fields[1];
			$blistsum += intval($fields[1]);
		}
		
		if ($alistsum == 0 || $blistsum  == 0)
		{
			$stack[] = 0;
			return;
		}
		$score = 0;
				
		foreach($alistw as $k=>$v)
		{
			// Bray-Curtis using relative weights to compensate for text size differences
			if (isset($blistw[$k])) $score += min(1.0*intval($v)/intval($alistsum),1.0*intval($blistw[$k])/intval($blistsum));
		}
		
		
		
		return $score;	
	}
}


class xpTrue extends swExpressionFunction
{
	function __construct() { $this->arity = 0; $this->label = ':true' ; $this->isOperator = true ;}
	function run($args)
	{
		return '1';	
	}
}

class xpUpper extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':upper' ;}
	function run($args)
	{
		$a = $args[0];
		return strtoupper($a);		
	}
}

/**
 * Converts text to urltext
 */

class xpUrlText extends swExpressionFunction
{
	function __construct() { $this->arity = 1; $this->label = ':urltext' ;}
	function run($args)
	{
		$a = $args[0];
		return swNameURL($a);		
	}
}

class xpXor extends swExpressionFunction
{
	function __construct() { $this->arity = 2; $this->label = ':xor' ; $this->isOperator = true ;}
	function run($args)
	{
		$a = $args[0];
		$b = $args[1];
		if ($a === '⦵' || $b === '⦵') return '⦵';
		if ($a === '∞') $a = '1';
		if ($b === '∞') $b = '1';
		if ($a === '-∞') $a = '1';
		if ($b === '-∞') $b = '1';
		$a = floatval($a);
		$b = floatval($b);		
		if (!$b && $a) return '1';
		if ($b && !$a) return '1';
		return '0';
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
$swExpressionFunctions[':tan'] = new XPTan;

$swExpressionFunctions[':length'] = new XPLength;
$swExpressionFunctions[':lower'] = new XPLower;
$swExpressionFunctions[':regex'] = new XPRegex;
$swExpressionFunctions[':regexi'] = new XPRegexi;
$swExpressionFunctions[':regexreplace'] = new XPRegexReplace;
$swExpressionFunctions[':regexreplacemod'] = new XPRegexReplaceMod;
$swExpressionFunctions[':replace'] = new XPReplace;
$swExpressionFunctions[':soundex'] = new XPSoundex;
$swExpressionFunctions[':soundexlong'] = new XPSoundexLong;
$swExpressionFunctions[':substr'] = new XPSubstr;
$swExpressionFunctions[':upper'] = new XPUpper;
$swExpressionFunctions[':urltext'] = new XPurltext;
$swExpressionFunctions[':pad'] = new XPPad;
$swExpressionFunctions[':trim'] = new XPTrim;
$swExpressionFunctions[':strip'] = new XPStrip;

$swExpressionFunctions[':max'] = new XPMax;
$swExpressionFunctions[':min'] = new XPMin;

$swExpressionFunctions[':secondstosql'] = new XPSecondsToSQL;
$swExpressionFunctions[':sqltoseconds'] = new XPSQLtoSeconds;

$swExpressionFunctions[':format'] = new XPFormat;

$swExpressionFunctions[':resume'] = new XpResume;
$swExpressionFunctions[':template'] = new XpTemplate;
$swExpressionFunctions[':link'] = new XPLink;
$swExpressionFunctions[':tag'] = new XPTag;

$swExpressionFunctions[':fileexists'] = new xpFileExists;
$swExpressionFunctions[':jaccarddistance'] = new xpJaccardDistance;
$swExpressionFunctions[':weightedjaccarddistance'] = new xpWeightedJaccardDistance;
$swExpressionFunctions[':cosinesimilarity'] = new xpCosineSimilarity;
$swExpressionFunctions[':levenshtein'] = new xpLevenshtein;
$swExpressionFunctions[':bigramstat'] = new xpBigramStat;

