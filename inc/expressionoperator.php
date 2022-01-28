<?php
	
/**
 *	Contains the swExpressionOperator class to define operator parameters
 *  
 *  File also popluates the global $swExpressionOperators array (only once for performance)
 */


if (!defined("SOFAWIKI")) die("invalid acces");

/**
 * Provides a class to hold parameters for the expression tokenizer and parser.
 * 
 * $label expression term as written by the user
 * $functionlabel function term used in RPN (starting with :)
 * $arity unary or binary
 * $precedence algrebra precedence rules
 * $associativity only left is used
 */

class swExpressionOperator
{
	var $label;
	var $functionlabel;
	var $arity;
	var $precedence;
	var $associativity;
	
	function __construct($lbl, $fn, $ar, $pr, $ass)
	{
		$this->label = $lbl;
		$this->functionlabel = $fn;
		$this->arity = $ar;
		$this->precedence = $pr;
		$this->associativity = $ass;
	}
}

$swExpressionOperators = array();

$swExpressionOperators['-u'] =  new swExpressionOperator('-u', ':neg',1,11,'L'); // notable exception, handled by tokenizer
$swExpressionOperators['not'] =  new swExpressionOperator('not', ':not',1,10,'L');
		
$swExpressionOperators['/'] =  new swExpressionOperator('/', ':div',2,9,'L');
$swExpressionOperators['*'] =  new swExpressionOperator('*', ':mul',2,9,'L');
$swExpressionOperators['div'] =  new swExpressionOperator('div', ':idiv',2,9,'L');
$swExpressionOperators['mod'] =  new swExpressionOperator('mod', ':mod',2,9,'L');
$swExpressionOperators['+'] =  new swExpressionOperator('+', ':add',2,8,'L');
$swExpressionOperators['-'] =  new swExpressionOperator('-', ':sub',2,8,'L');

$swExpressionOperators['.'] =  new swExpressionOperator('.', ':concat',2,7,'L');

$swExpressionOperators['='] =  new swExpressionOperator('=', ':eqn',2,6,'L');
$swExpressionOperators['!='] =  new swExpressionOperator('!=', ':nen',2,6,'L');
$swExpressionOperators['>'] =  new swExpressionOperator('>', ':gtn',2,6,'L');
$swExpressionOperators['>='] =  new swExpressionOperator('>=', ':gen',2,6,'L');
$swExpressionOperators['<'] =  new swExpressionOperator('<', ':ltn',2,6,'L');
$swExpressionOperators['<='] =  new swExpressionOperator('<=', ':len',2,6,'L');

$swExpressionOperators['regex'] =  new swExpressionOperator('regex', ':regex',2,5,'L');
$swExpressionOperators['regexi'] =  new swExpressionOperator('regexi', ':regexi',2,5,'L');
$swExpressionOperators['=='] =  new swExpressionOperator('==', ':eqs',2,5,'L');
$swExpressionOperators['!=='] =  new swExpressionOperator('!==', ':nes',2,5,'L');
$swExpressionOperators['>>'] =  new swExpressionOperator('>>', ':gts',2,5,'L');
$swExpressionOperators['>=='] =  new swExpressionOperator('>==', ':ges',2,5,'L');
$swExpressionOperators['<<'] =  new swExpressionOperator('<<', ':lts',2,5,'L');
$swExpressionOperators['<=='] =  new swExpressionOperator('<==', ':les',2,5,'L');

$swExpressionOperators['and'] =  new swExpressionOperator('and', ':and',2,4,'L');
$swExpressionOperators['or'] =  new swExpressionOperator('or', ':or',2,3,'L');
$swExpressionOperators['xor'] =  new swExpressionOperator('xor', ':xor',2,2,'L');

$swExpressionOperators[','] =  new swExpressionOperator(',',':comma',2,1,'L');







