<?php

if (!defined("SOFAWIKI")) die("invalid acces");


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
		$s0 = $s;
		$s = $this->validName(trim($s));
		if ($s == '') throw new swRelationError('Invalid name '.$s0,102);
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
		
		if (bin2hex(substr($s, 0, 3)) == 'efbbbf' ) $s = substr($s, 3);  // BOM UTF-8
		
		$c = strlen($s);
		$ch = substr($s,0,1);
		
		if ($ch == '' || !stristr($list1,$ch)) $result = '_'; else $result = '';
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
		return $result;
		
	}
	
	function delete($condition)
	{
		$xp = new swExpression();
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
		
		$xp = new swExpression();
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
		//p_open('insertcompile');
		$xp = new swExpression();
		$xp->expectedreturn =  count($this->header);
		$xp->compile($t);
		//p_close('insertcompile');
		//p_open('insertevaluate');
		$test = $xp->evaluate($this->globals); // we don't use the result directly
		//p_close('insertevaluate');
		//p_open('insertinsert');
		$this->insert2($xp->stack); // but use the stack
		//p_close('insertinsert');
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
		{
			if ($tp->sameFamily($e))
			{
				if (array_key_exists($tp->hash(), $this->tuples))
				{
					$newtuples[$tp->hash()] = $tp;
					echo "samesame ";
				}
				else
					echo "notsame ";
			}
			else
				throw new swRelationError('Intersection different columns',301);
		}
		$this->tuples = $newtuples;
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
			$f1 = str_replace('"','',join(' ',$fields));
			if (!in_array($f0, $this->header))
				throw new swRelationError('Unknown label '.$f0,122);
			$this->labels[$f0] = $f1;
		}
	}
	
	function limit($t)
	{
		$xp = new swExpression();
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
		foreach ($this->tuples as $tp)
		{
			//$tp = $this->tuples[$i];
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
		
		if (!count($pairs)) 
			throw new swRelationError('Project zero columns',11);

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
		
		$xp = new swExpression();
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
	
	function setCSV($file,$pad = true,$append = true,$encoding='utf-8')
	{
		if(!$append)
		{
			$this->header = array();
			$this->tuples = array();
		}
		$separator = ';';
		$firstline = true;
		$retainline = '';
		$filesize = filesize($file);
		
		if (defined('SOFAWIKICLI') && $filesize>1000000)
		{
			echo str_repeat("----------",5).PHP_EOL; 
			$offset = 1;
			$stars = 0;
		}
		
		
		$j = 0;
		foreach(swFileStreamLineGenerator($file, $encoding) as $line)
		{
			
			$j++;
			if (defined('SOFAWIKICLI') && $filesize>1000000 )
			{
				$offset += strlen($line);
				if ($offset/$filesize*50>$stars) { echo "*"; $stars++; }	
			}
			
			if ($retainline)  // should be eol
			{
				$line = $retainline.$line; // PHP_EOL included
				//echo "retainline ".$line.PHP_EOL; 
				$retainline = '';
			}
			if ($line == '') continue;
			if ($firstline && substr($line,0,1)=='#') continue;
			if ($firstline)
			{
				if (strpos($line,$separator)===FALSE) $separator = ','; 
				$fields = explode($separator,$line);
				
				if (!$append)
				{
					foreach($fields as $field)
					{
						$this->addColumn($this->cleanColumn(trim($field)));
					}
				}
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
									$retainline = $line;
									
									
									
									break;
					case 'quotesuspend' : 	if($quoted) $fields[]=$acc;
											else $fields[]=trim($acc); 
				}
				
				if (!$retainline)
				{
				
					$c = count($fields);
					$d = array();
	
	
					if ($c == count($this->header))
					{
						for($i=0;$i<$c;$i++)
							$d[$this->header[$i]] = swEscape($fields[$i]);
					}
					else
					{
						if (!$pad)
							throw new swRelationError('Read CSV field count in row not header count (line: '.($j+1).', header: '.count($this->header).', fields:'.($c+1).')',99);
						
						for($i=0;$i<count($this->header);$i++)
						{
							$d[$this->header[$i]] = '';
						}
						for($i=0;$i<min($c,count($this->header));$i++)
						{
							$d[$this->header[$i]] = $fields[$i];
						}
					}
					$tp = new swTuple($d);
					$this->tuples[$tp->hash()] = $tp;
		
				}
			}
		}
		
		if ($retainline)
		{
		 $this->setCSV(swString2File($retainline.'"'),true,true);
		}
		
	
		
		if (defined('SOFAWIKICLI') && $filesize>1000000 )
		{
			echo PHP_EOL;
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
					$fm = $this->formats[$f];
					if ($fm != '')
						$test = $this->format2(floatval($test),$fm);
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
					$fm = $this->formats[$f];
					if ($fm != '')
						$test = $this->format2(floatval($test),$fm);
				}
				$fields[] = $test;
				
			}
			$lines[] = join(' ',$fields);
		}
		return join(PHP_EOL,$lines);
	}
	



	function setTab($file,$encoding='utf-8')
	{
		$this->header = array();
		$this->tuples = array();
		
		$firstline = true;
		foreach(swFileStreamLineGenerator($file, $encoding) as $line)
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
					$fm = $this->formats[$f];
					if ($fm != '')
						$test = $this->format2(floatval($test),$fm);
				}
				$pairs[$f] = $test;
				
			}
			$lines[] = $pairs;
		}
		$j = array('relation' => $lines) ;
		
		return json_encode($j);
	}
	
	function setJson($s,$path='')
	{
		
		if ($s && $path)
		{
			$s = stripslashes(html_entity_decode($s)); 
			//$s = iconv('UTF-8', 'UTF-8//IGNORE', $s);	
			
			//$regex = '/( [\x00-\x7F] | [\xC0-\xDF][\x80-\xBF] | [\xE0-\xEF][\x80-\xBF]{2} | [\xF0-\xF7][\x80-\xBF]{3} ) | ./x';
	        //$s = preg_replace($regex, '$1', $s);
	        
	        	        
	        $s = preg_replace('/[[:^print:]]/', '', $s);	
	        
			
	        
			$json = json_decode($s,true); 
			
			//print_r($json);
			
			if ($json == null) throw new swRelationError("JSON null: ".json_last_error_msg());
			
			$list = explode('/',$path);
			array_pop($list); // $path must end with slash
			foreach($list as $elem)
			{
// 				if (!$elem) throw new  swRelationError('invalid json path');
				if (!isset($json[$elem])) return; // not found
				$json = $json[$elem];
			}
// 			print_r($json);
			$this->header = array_keys(end($json));
			foreach($json as $elem)
			{
				if(!is_array($elem)) continue; //???
				$d = array(); 
				foreach($elem as $k=>$v)
				{
					if (is_array($v)) $v = json_encode($v);
					$d[$k]=$v;
				}
				$tp = new swTuple($d);
				$this->tuples[$tp->hash()] = $tp;
			}
	
		}
		elseif ($s)
		{
			$s = stripslashes(html_entity_decode($s));
			$s = preg_replace('/[[:^print:]]/', '', $s);
			$list = json_decode($s,true);
			
			if ($list == null) throw new swRelationError("JSON null: ".json_last_error_msg());	
	
			$list = swFlattenArray($list);
		
			$this->header = array('path','value');
		
			foreach($list as $key=>$value)
			{
				$d = array(); 
				$d['path'] = $key;
				$d['value'] = $value;
				$tp = new swTuple($d);
				$this->tuples[$tp->hash()] = $tp;
			}
		}
	}
	
	function setXml($s)
	{
		$xml = simplexml_load_string($s);
		$json = json_encode($xml);
		$list = json_decode($json,true);
		
		$list = swFlattenArray($list);
		
		$this->header = array('path','value');
		
		foreach($list as $key=>$value)
		{
			$d = array(); 
			$d['path'] = $key;
			$d['value'] = $value;
			$tp = new swTuple($d);
			$this->tuples[$tp->hash()] = $tp;
		}
		
	}

	function setHtml($s,$n=0)
	{
		// set error level
		$internalErrors = libxml_use_internal_errors(true);
		
		$doc = new DOMDocument;
		$doc->loadHTML($s);
		
		
		
		// Restore error level
		libxml_use_internal_errors($internalErrors);

		
		$tables = $doc->getElementsByTagName('table');
		
		$list = array();
		foreach($tables as $t)
		{
			if ($n>0) { $n--; continue;}
					
			$rows = $t->getElementsByTagName('tr');
			$fields = array();
			$i = 0;
			
			foreach($rows as $r)
			{
				$cells = $r->getElementsByTagName('th');
				foreach($cells as $c)
				{
					$fields[] = $this->cleanColumn(trim($c->nodeValue));
				}
				
				$cells = $r->getElementsByTagName('td');
				$j = 0;
				
				foreach($cells as $c)
				{
					if (!isset($fields[$j])) $fields[$j] = $this->cleanColumn(trim($c->nodeValue)); // no th tag
					else
						$list[$i][$fields[$j]]=trim($c->nodeValue);
					$j++;
				}
				$i++;
				

			}
		}
		
		// print_r($list);
		

		foreach($list as $line)
		{
			$tp = new swTuple($line);
			$this->tuples[$tp->hash()] = $tp;
		}
		
		$this->header = array_keys($line);
		
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
				if ($this->mode = 'text')
					$lines[] = '[['.$k.'::'.$v.']]';
				else
					$lines[] = '<leftsquare><leftsquare>'.$k.'::'.$v.'<rightsquare><rightsquare>';
			}
			
			$lines[] = '';
		}
		$result = PHP_EOL.join(PHP_EOL,$lines).PHP_EOL;
		return $result;
		
	}
	
	function toHTML($limit = 0)
	{
		
		
		if (!count($this->header)) return '';
				
		//echotime('tohtml');
		if (count($this->tuples)>10000) echotime('tohtml '.count($this->tuples));
		
		$grid = false;
		$linegrid = false;
		$spacegrid = false;
		$edit = '';
		$editfile = '';
		
		
		
		if (substr($limit,0,4) == 'grid')
		{
			$limit = substr($limit,4);
			$grid = true;
		}
		elseif (substr($limit,0,8) == 'linegrid')
		{
			$limit = substr($limit,8);
			$linegrid = true; 
			$grid = true;
		}
		elseif (substr($limit,0,9) == 'spacegrid')
		{
			$limit = substr($limit,9);
			$spacegrid = true; 
			$grid = true;
		}
		elseif (substr($limit,0,4) == 'edit')
		{
			
			$edit = ' contenteditable ';
			$grid = true;
			$editfile = substr($limit,4);
			
			$xp = new swExpression();
			$xp->compile($editfile);
			$dict = array();
			$editfile = $xp->evaluate($dict);	
			$limit = '';
		}

		$limit = intval($limit);
		
		
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
			$lines[] = '<nowiki><div><input type="text" id="input'.$id.'" class="sortable" onkeyup="tablefilter('.$id.')" placeholder="Filter..." title="gridfilter"></div></nowiki>';

		}
		
		if ($linegrid)
			$lines[]= '{| class="sortable" maxgrid="'.$limit.'" id="table'.$id.'"';
		elseif ($spacegrid)
			$lines[]= '{| class="sortable spacegrid" maxgrid="'.$limit.'" id="table'.$id.'"';
		elseif ($grid)
			$lines[]= '{| class="print sortable" maxgrid="'.$limit.'" id="table'.$id.'"';
		else
			$lines[]= '{| class="print" ';
		
		$k = count($this->header);
		
		$line = '';
		$alllables = '';
		foreach($this->header as $f)
		{
			if ($spacegrid) continue;
			if (isset($this->labels[$f]))
				$ls = $this->labels[$f];
			else
				$ls = $f;
			
			$alllables .= $ls;
			
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
		if ($alllables) // not all are empty
		{
			$lines[]= '|-';
			$lines[]= $line;
		}
		
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
			
			$line = '| '.join(PHP_EOL.'| ',$fields);
			
			if ($i>$c && $grid) 
				$lines[] = '|- style="display: none"';
		    else
		    $lines[] = '|-';
			$lines[] = $line.' '; // add space to force line not to be empty
		}
		$lines[] = '|-';
		$lines[] = '|}';
		if ($grid)
		{
			if ($i>$c)
				$lines[] = '<nowiki><button type="button" id="plus'.$id.'" onclick="tableplus('.$id.','.$limit.')" class="sortable" href="#">+</button></nowiki>';
			else
				$lines[] = '<nowiki><button type="button" id="plus'.$id.'" onclick="tableplus('.$id.','.$limit.')" class="sortable" style="display: none" href="#">+</button></nowiki>';

		}
		if ($grid)
			$lines[] = '<nowiki><script src="inc/skins/table.js"></script><script>tablefilter('.$id.')</script></nowiki>';
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
			if ($i<$c-1) $nextd = $dicts[$i+1]; else $nextd = null;
			
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
												
												if ($i==$c-1 or $nextd[$key] != $d[$key])
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
	
	function toText($limit=0,$single=false)
	{
		if(!defined('BOLD')) define('BOLD','');
		if(!defined('NORMAL')) define('NORMAL','');
		if(!defined('INVERT')) define('INVERT','');
		
		
		// if limit is a string, use it as filter
		$filter = trim($limit);	
		
		if (strlen($filter) && !intval($filter)) $limit = 0; else $filter = '';
		$filter = swNameURL($filter); 
		
		
		$lines = array();
		$c = count($this->header);
		
		if (!$c) return '';
		
		$fieldformats = array();
		$padformats = array();
		$widths= array();
		$header = '       ';
		
		foreach($this->header as $h)
		{
			$widths[$h] = 0;
		}
		
		foreach($this->tuples as $tp)
		{
			foreach($this->header as $h)
			{
				$widths[$h] = max(@$widths[$h],mb_strlen($tp->value($h),'UTF-8'));
			}
		}

		foreach($this->header as $h)
		{
			$label = $h;
			if (isset($this->labels[$h]) && $this->labels[$h]) 
				$label = $this->labels[$h];
						
			$c = min(32,max($widths[$h],mb_strlen($label,'UTF-8')));
			
			if (@$this->formats[$h]) 
			{
				$fieldformats[$h] = $this->formats[$h];	
				$c = mb_strlen($this->format2($label,$fieldformats[$h])); ;
			}
			else 
				$fieldformats[$h] =  '"%-'.$c.'s"';	
									
			if (stristr($fieldformats[$h],'%-')) 
				$padformats[$h] =  '"%-'.$c.'.'.$c.'w"';	
			else
				$padformats[$h] =  '"%'.$c.'.'.$c.'w"';	
				
			//if (substr($fieldformats[$h],-1,1) == 's') $padformats[$h] = $fieldformats[$h];
	
			$header .= $this->format2($label,$padformats[$h]).'   ';
		}
		
		$header = substr($header,0,-2);
		$lines[] = INVERT.BOLD.$header.NORMAL;
		$r = 0;
		$c = count($this->header);
		foreach($this->tuples as $tp)
		{
			$r++; 
			if ($limit && $r>$limit) break;
			if ($single && $r<$limit) continue;
			$row = ' ';
			
			$fields = array();
			$linecount = 0;
			
			for($i=0;$i<$c;$i++)
			{
				$f = $this->header[$i];
				$test = $tp->value($f);
				$test = swUnescape($test);
				$test = $this->format2($test,$fieldformats[$f]);
				$test = $this->format2($test,$padformats[$f]);  
				
				$fields[$i] = explode(PHP_EOL,$test);
				$linecount = max($linecount, count($fields[$i]));
			}
			$rows = array();
			for ($j=0;$j<$linecount;$j++)
			{
				
				$rows[$j] = '';
				for($i=0;$i<$c;$i++)
				{
					if (isset($fields[$i][$j]))
					{
						$rows[$j] .= $fields[$i][$j].'   ';
					}
					else
					{
						$f = $this->header[$i];
						$test = $this->format2('','%s');
						$rows[$j] .= $this->format2($test,$padformats[$f]).'   ';  
					}
				}
			}
			
			if ($filter && !stristr(swNameURL($row),$filter)) continue;
			
			$first = true;
			foreach($rows as $row)
			{
				if ($first) $lines[] = INVERT.sprintf(' %3d ',$r).NORMAL.'  '.substr($row,0,-2);
				else $lines[] = INVERT.sprintf(' %3s ','').NORMAL.'  '.substr($row,0,-2);
				$first = false;
			}
		}
		return join(PHP_EOL,$lines).PHP_EOL;
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
		$xp = new swExpression();
		$xp->compile($condition);
		$xp2 = new swExpression();
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
			if (count($fields)<2) $fields[] = 'A';
			
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
							if ($atext === '∞') { if ($btext !== '∞') return 1 ; $test = 0; break; }
							if ($btext === '∞') { return -1 ; }
							if ($atext === '-∞') { if ($btext !== '⦵') return 1; if ($btext !== '-∞') return -1 ; $test = 0; break; }
							if ($btext === '-∞') { if ($atext !== '⦵') return -1; return 1 ; }
							if ($atext === '⦵') { if ($btext !== '⦵') return -1 ; $test = 0; break; }
							if ($btext === '⦵') { return 1 ; }
							if (floatval($atext) > floatval($btext)) return 1;
							if (floatval($atext) < floatval($btext)) return -1;
							break;
				case '9' : 	if ($atext === '∞') { if ($btext !== '∞') return -1 ; $test = 0; break; }
							if ($btext === '∞') { return 1 ; }
							if ($atext === '-∞') { if ($btext !== '⦵') return -1; if ($btext !== '-∞') return 1 ; $test = 0; break; }
							if ($btext === '-∞') { if ($atext !== '⦵') return 1; return -1 ; }
							if ($atext === '⦵') { if ($btext !== '⦵') return 1 ; $test = 0; break; }
							if ($btext === '⦵') { return -1 ; }
							if (floatval($atext) > floatval($btext)) return -1;
							if (floatval($atext) < floatval($btext)) return 1;
							break;
				default: 	throw new swRelationError('Invalid order parameter '.$this->porders[$i],501);

			}
		}
		return 0;
		
	}
	
}


class swRelationError extends Exception	
{
	
}


