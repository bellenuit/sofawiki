<?php

if (!defined("SOFAWIKI")) die("invalid acces");


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
		$this->list[] = $t;
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
			if ($t === '⦵' || $t === '∞' || $t === '-∞') continue;
			$acc += floatval($t);
			$i++;
		}
		if (!$i) return '⦵';
		$v = $acc / $i;
		return swConvertText12($v);
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
		return $xp->DoRunAggregator($this->list);
	}
	
	private function pFirst()
	{
		return array_shift($this->list);
	}
	
	private function pGeometricMean()
	{
		$acc = 1; $i=0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t === '∞' || $t === '-∞') continue;
			if ($t < 0) return '⦵';
			$acc *= floatval($t);
			$i++;
		}
		if (!$i) return '⦵';
		$v = pow($acc,1 / $i);
		return swConvertText12($v);
	}
	private function pHarmonicMean()
	{
		$acc = 0; $i=0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t === '∞' || $t === '-∞') continue;
			if ($t == 0) return '⦵';
			$acc += 1/floatval($t);
			$i++;
		}
		if (!$acc) return '⦵';
		$v = $i / $acc;
		return swConvertText12($v);
	}
	
	private function pGiniSimpson()
	{
		$acc = 0; $i=0; $acc2= 0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t === '∞' || $t === '-∞') continue;
			if ($t == 0) continue;
			if ($t < 0) return '⦵';
			$acc  += $t * $t;
			$acc2 += $t;
			$i++;
		}
		if (!$acc) return '0';
		$v = 1 - $acc / ($acc2 * $acc2);
		return swConvertText12($v);
	}

	
	private function pHill()
	{
		$acc = 0; $i=0; $acc2= 0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t === '∞' || $t === '-∞') continue;
			if ($t == 0) continue;
			if ($t < 0) return '⦵';
			$acc  += $t * log($t);
			$acc2 += $t;
			$i++;
		}
		if (!$acc) return '0';
		$v = exp(log($acc2) - $acc / $acc2);
		return swConvertText12($v);
	}



	private function pLast()
	{
		return array_pop($this->list);
	}

	private function pMax()
	{
		if (count($this->list)==0) return "";
		$acc = '⦵';
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			if ($acc === '⦵') $acc = floatval($t);
			if (floatval($t) > $acc)
				$acc = floatval($t);
		}
		return swConvertText12($acc);
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
		//print_r($this->list);
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc[] = floatval($t);
		}
		if (count($acc)==0) return '⦵';
		//print_r($acc);
		sort($acc,SORT_NUMERIC);
		//print_r($acc);
		
		if (count($acc) % 2 != 0)
			$v = $acc[(count($acc)-1)/2];
		else
			$v = ($acc[count($acc)/2] + $acc[count($acc)/2-1])/2;
		
		return swConvertText12($v);
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
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			if ($acc === '⦵') $acc = floatval($t);
			if (floatval($t) < $acc)
				$acc = floatval($t);
		}
		return swConvertText12($acc);
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
	
	private function pPRF()
	{
       // calculates the multiplication factor you need so that the sum of floor of each elememt is the round of the sum. 
       // consider it the simulation of the hagenbach-bishoff proportional election
       // this allows f.e. to show percentages in a table consistant with the sum 
       // the formula cannot handle edge cases, where a random would be needed (eg 66.6 and 33.3)
       // usage 
       // project inline value prf
       // extend rounded = floor(value * value_prf)
		if (count($this->list)==0) return 1;
		$target = round(array_sum($this->list));
		$e = 0.0000000001;
		$delta = 1;
		$result = 1;
		while(abs($delta)>$e)
		{
			$test = 0;
			foreach($this->list as $elem) $test +=floor(floatval($elem)*$result);
			if ($test > $target) $result -= $delta;
			elseif ($test < $target) $result += $delta;
			else return $result;
			$delta /= 2;
		}
		return $result;
	}

	private function pProd()
	{
		if (count($this->list)==0) return "";
		$acc = 1;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc *= floatval($t);
		}
		return swConvertText12($acc);
	}
	
	private function pShannon()
	{
		$acc = 0; $i=0; $acc2= 0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t === '∞' || $t === '-∞') continue;
			if ($t == 0) continue;
			if ($t < 0) return '⦵';
			$acc  += $t * log($t);
			$acc2 += $t;
			$i++;
		}
		if (!$acc) return '0';
		$v = log($acc2) - $acc / $acc2;
		return swConvertText12($v);
	}


	private function pStdev()
	{
		$acc = 0;
		$acc2 = 0;
		$i = 0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
			$acc2 += floatval($t) * floatval($t);
			$i++;
		}
		if ($i == 0) return '⦵';
		$v = sqrt($acc2/$i - $acc/$i*$acc/$i);
		return swConvertText12($v);
	}

	private function pSum()
	{
		if (count($this->list)==0) return "";
		$acc = 0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
		}
		return swConvertText12($acc);
	}

	private function PVar()
	{
		$acc = 0;
		$acc2 = 0;
		$i = 0;
		foreach($this->list as $t)
		{
			if ($t === '⦵' || $t == '∞' || $t == '-∞') continue;
			$acc += floatval($t);
			$acc2 += floatval($t) * floatval($t);
			$i++;
		}
		if ($i == 0) return '⦵';
		$v = $acc2/$i - $acc/$i*$acc/$i;
		return swConvertText12($v);
	}
	
	


	function reduce()
	{
		switch ($this->method)
		{
			case 'avg'  	:	return $this->pAvg();
			case 'concat'  	:	return $this->pConcat();
			case 'count'	: 	return $this->pCount();
			case 'first'  	:	return $this->pFirst();
			case 'last'  	:	return $this->pLast();
			case 'max'  	:	return $this->pMax();
			case 'maxs'  	:	return $this->pMaxs();
			case 'gini' 	: 	return $this->pGiniSimpson();
			case 'gm' 		: 	return $this->pGeometricMean();
			case 'hill' 	: 	return $this->pHill();
			case 'hm' 		: 	return $this->pHarmonicMean();
			case 'median'  	:	return $this->pMedian();
			case 'medians'  :	return $this->pMedianS();
			case 'min'  	:	return $this->pMin();
			case 'mins'  	:	return $this->pMinS();
			case 'prf'		:	return $this->pPRF();
			case 'prod'  	:	return $this->pProd();
			case 'shannon' 	: 	return $this->pShannon();
			case 'stddev'  	:	return $this->pStdev();
			case 'sum'  	:	return $this->pSum();
			case 'var'  	:	return $this->PVar();
			case 'custom'  	:	return $this->pCustom();
		}
	}
	
}




