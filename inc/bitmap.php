<?php

if (!defined("SOFAWIKI")) die("invalid acces");

/**
 *	This file contains the swBitmap class.
 *  
 *  swBitmap provides a memory-effective way to store an array of booleans using only one bit per array. 
 *  The bit-array is n-based. You cannot set the bit 0.
 *  Note that 8 bit are in a byte.
 *  If the length is not a multiple of 8, there are 1-7 bits at the end that are undefined. (To be taken care of)
 */


/**
 * Provides a memory-effective way to store an array of booleans using only one bit per array. 
 */

class swBitmap extends swPersistance 
{
	var $length;
	var $state;
	var $touched;
	var $map;
	var $maphex;
	var $compressionmode;
	var $default = false;
	
	function init($l, $default = false)
	{
		//creates the bit string, filled with default value
		$this->length = 0;
		$this->redim($l,$default);
		$this->touched = true;
		$this->compressionmode = true;
	}
	
	function redim($l, $default = false)
	{
		if (intval($l)<$this->length) return; // can only grow
		$this->touched = true;
		if ($this->map == '') $this->dehexit();	
		$oldlength = $this->length;
		$this->length = intval($l);  
		
		$bytes = ($this->length + 7) >> 3;	
		
		$thisbytes = strlen($this->map);
		
		
		if ($default)
			$this->map = str_pad($this->map,$bytes,chr(255));
		else
			$this->map = str_pad($this->map,$bytes,chr(0));
						
			
		// Note the last current byte is padding.
		// This is ok, if we can never access them further than the length.
		// Because str_pad is only bytewise, we need to set explicitely now set the bits between length and the next byte
		
		for ($i = $oldlength; $i < min($this->length, $oldlength+8); $i++)
		{
			if ($default)
				$this->setbit($i);
			else
				$this->unsetbit($i);
				
		}
		
		$this->default = $default;
		
		
				
	}
	
	function hexit()
	{
		$this->compressionmode = true;
		$this->maphex = bin2hex($this->map);
		if ($this->compressionmode)
		{
			$this->maphex = str_replace('00000000','g',$this->maphex);// simple compression
			$this->maphex = str_replace('ffffffff','h',$this->maphex);
			$this->maphex = str_replace('gggggggg','i',$this->maphex);
			$this->maphex = str_replace('hhhhhhhh','j',$this->maphex);
		}
		$this->map = '';
	}
	function dehexit()
	{
		$this->maphex = str_replace('j','hhhhhhhh',$this->maphex);
		$this->maphex = str_replace('i','gggggggg',$this->maphex);
		$this->maphex = str_replace('h','ffffffff',$this->maphex);
		$this->maphex = str_replace('g','00000000',$this->maphex);
		$this->map = pack("H*" , $this->maphex);
		$this->maphex = '';	
	}
	
	// magic functions store string as hex
	function save()
	{
		$this->hexit();
		parent::save();
	}
		
	function open()
	{
		parent::open();
		$this->dehexit();
	}
	
	function setbit($n)
	{
		// zero based, n >= length will make it grow
			
		$n = intval($n);
		if ($n<0) return; 
		$this->touched = true;
		
		
		if ($n>=$this->length) // setbit 2 needs length 3
			$this->redim($n+1);
			
		
		// sets nth bit to true
		$byte = $n >> 3;
		$bit = $n - ($byte << 3);
		$bitmask = 128 >> $bit;

		if ($this->map == '') $this->dehexit();	
		$ch = $this->map[$byte];
		$ch = ord($ch);
		$ch = $ch | $bitmask;
		$ch = chr($ch);
		$this->map[$byte] = $ch;
	}
	
	function unsetbit($n)
	{
		// zero based, n >= length will make it grow
		
		$n = intval($n);
		
		if ($n<0) return; 
		$this->touched = true;

		if ($n>=$this->length)
			$this->redim($n+1);
			
		// sets nth bit to false  
		$byte = $n >> 3;
		$bit = $n - ($byte << 3);
		$bitmask = 128 >> $bit;
		$bitmask = ~ $bitmask;
		
		if ($this->map == '') $this->dehexit();		
		$ch = $this->map[$byte];
		$ch = ord($ch);
		$ch = $ch & $bitmask;
		$ch = chr($ch);
		$this->map[$byte] = $ch;		
	}
	
	function getbit($n)
	{
		// zero based, n >= length will return default
		
		$n = intval($n);
		
		if ($n>=$this->length) return $this->default;
		if ($n<0) return false;
		
		// gets the value of the nth bit
		$byte = $n >> 3;
		$bit = $n - ($byte << 3);
		$bitmask = 128 >> $bit;
		
		if ($this->map == '') $this->dehexit();	
		$ch = $this->map[$byte];
		$ch = ord($ch);
		$ch = $ch & $bitmask;
		if ($ch) 
			return true;
		else
			return false;
	}
	
	function getnext($n)
	{
		// returns the offset of the next bit set to true after n, or false.
	}
	
	function duplicate()
	{
		$result = new swBitmap;
		$result->length = $this->length;
		if ($this->map == '') $this->dehexit();		
		$s = $this->map;
		$result->map = $s; //force non mutable
		$result->touched = true;
		return $result;
	}
	
	function andop($bitmap)
	{
		// returns a bitmap as result of OR between this and bitmap
		
		$result = new swBitmap;
		$result->length = max($this->length, $bitmap->length);
		
		$b1 = $this->duplicate(); 
		$b1->redim($result->length,0);
		$b2 = $bitmap->duplicate(); 
		$b2->redim($result->length,0);
				
		$result->map = $b1->map & $b2->map;		

		$result->touched = true;
		return $result;

	}
	
	/**
	 * Returns a bitmap as result of OR between this and bitmap	
	 */
	
	function orop($bitmap)
	{
		// returns a bitmap as result of OR between this and bitmap
		
		$result = new swBitmap;
		$result->length = max($this->length, $bitmap->length);
		
		$b1 = $this->duplicate(); 
		$b1->redim($result->length,0);
		$b2 = $bitmap->duplicate(); 
		$b2->redim($result->length,0);
				
		$result->map = $b1->map | $b2->map;		

		$result->touched = true;
		return $result;
	}
	
	function notop()
	{
		
		$result = new swBitmap;
		$result->redim($this->length, true);
		if ($this->map == '') $this->dehexit();		
		$map = $result->map ^ $this->map;
		$result->map = $map;
		$result->touched = true;
		return $result;
	}
	
	function dump()
	{
		// returns bit string in groups of 8
		$result = '';
		$c = strlen($this->map);
		if ($this->map == '') $this->dehexit();		
		for($i=0;$i<$c;$i++)
		{
			$ch = $this->map[$i];
			$ch = ord($ch);
			$result .= sprintf('%08d',decbin($ch)).' ';
		}
		
		// cut if too long
		$mod = 8 - $this->length % 8;
		
		$result = trim($result);
		
		if ($mod < 8)
			$result = substr($result,0,-$mod);
		
		$result = trim($result);
		
				
		return $result;
	}
	
	function countbits()
	{
		// Counting bits set, Brian Kernighan's way
		// http://graphics.stanford.edu/~seander/bithacks.html#CountBitsSetKernighan
		
		$bytes = ($this->length + 7) >> 3;
		$c = 0;
		if ($this->map == '') $this->dehexit();		
		for($i=0; $i<$bytes;$i++)
		{
			$ch = substr($this->map,$i,1);
			$v = ord($ch);
			
			while ($v)
			{
				$v &= $v - 1;
				$c++;
			}
			
		}
		return $c;
	}
	
	function toarray()
	{
		$result = array();
		for($i=0;$i<$this->length;$i++)
		{
			if ($this->getbit($i))
				$result[$i] = $i;
				
		}
		return $result;
	}

	
}

function bitmapUnitTest()
{
	
	$b2 = new swBitmap;
	
	$b1 = new swBitmap;
	$dump = $b1->dump();
	if ($dump !== '') { echo '<br>1 []  <> ['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->setbit(0);
	$dump = $b1->dump();
	if ($dump !== '1') { echo '<br>2 [1]  <> ['.$dump.']'.$b1->length; }

	$b1 = new swBitmap;
	$b1->unsetbit(0);
	$dump = $b1->dump();
	if ($dump !== '0') { echo '<br>3 [0]  <> ['.$dump.']'; }

	
	$b1 = new swBitmap;
	$b1->init(1, true);
	$dump = $b1->dump();
	if ($dump !== '1') { echo '<br>4   1 <>['.$dump.']'; }

	$b1 = new swBitmap;
	$b1->init(2, true);
	$dump = $b1->dump();
	if ($dump !== '11') { echo '<br>5  11 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(6, true);
	$dump = $b1->dump();
	if ($dump !== '111111') { echo '<br>6   111111 <>['.$dump.']'; }

	
	$b1 = new swBitmap;
	$b1->init(7, true);
	$dump = $b1->dump();
	if ($dump !== '1111111') { echo '<br>7   1111111 <>['.$dump.']'; }

	$b1 = new swBitmap;
	$b1->init(8, true);
	$dump = $b1->dump();
	if ($dump !== '11111111') { echo '<br>8   11111111 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(10, true);
	$dump = $b1->dump();
	if ($dump !== '11111111 11') { echo '<br>9   11111111 11 <>['.$dump.']'; }

	$b1 = new swBitmap;
	$dump = $b1->dump();
	if ($dump !== '') { echo '<br>10 []  <> ['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(1, false);
	$dump = $b1->dump();
	if ($dump !== '0') { echo '<br>11   0 <>['.$dump.']'; }

	$b1 = new swBitmap;
	$b1->init(2, false);
	$dump = $b1->dump();
	if ($dump !== '00') { echo '<br>12  00 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(6, false);
	$dump = $b1->dump();
	if ($dump !== '000000') { echo '<br>13   000000 <>['.$dump.']'; }

	
	$b1 = new swBitmap;
	$b1->init(7, false);
	$dump = $b1->dump();
	if ($dump !== '0000000') { echo '<br>14   0000000 <>['.$dump.']'; }

	$b1 = new swBitmap;
	$b1->init(8, false);
	$dump = $b1->dump();
	if ($dump !== '00000000') { echo '<br>15   000000000 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(10, false);
	$dump = $b1->dump();
	if ($dump !== '00000000 00') { echo '<br>16   000000000 00 <>['.$dump.']'; }

	$b1 = new swBitmap;
	$b1->init(4, true);
	$b1->redim(7, false);
	$dump = $b1->dump();
	if ($dump !== '1111000') { echo '<br>17   1111000 <>['.$dump.']'; }


	$b1 = new swBitmap;
	$b1->init(4, false);
	$b1->redim(7, true);
	$dump = $b1->dump();
	if ($dump !== '0000111') { echo '<br>18   0000111 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(4, false);
	$b1->redim(7, true);
	$b1->setbit(1);
	$dump = $b1->dump();
	if ($dump !== '0100111') { echo '<br>19   0100111 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(4, false);
	$b1->redim(9, true);
	$b1->setbit(1);
	$dump = $b1->dump();
	if ($dump !== '01001111 1') { echo '<br>20   0100111 1 <>['.$dump.']'; }
	
	$b1 = new swBitmap;
	$b1->init(4, false);
	$b1->redim(12, true);
	$b1->setbit(1);
	$dump = $b1->dump();
	if ($dump !== '01001111 1111') { echo '<br>21   0100111 1111 <>['.$dump.']'; }
	
		
	for ($i = 0; $i < 10; $i++)
	{
		$list = array();
		for ($j = 0; $j < 8; $j++)
		{
			$list[] = rand(0,40);
		}
		$b1 = new swBitmap;
		foreach($list as $v)
		{
			$b1->setbit($v);
		}
		foreach($list as $v)
		{
			if (!$b1->getbit($v))
			{
				sort($list);
				echo join(" ",$list);
				echo " setbit failed for $v";
				echo "<p>".$b1->dump()."<p>";
				
				break;
			}
		}
		//sort($list);
		//echo "<p>".$b1->dump().'  '.array_pop($list)."<p>";
	}
	
	for ($i = 0; $i < 10; $i++)
	{
		$list = array();
		for ($j = 0; $j < 8; $j++)
		{
			$list[] = rand(0,40);
		}
		$b1 = new swBitmap;
		foreach($list as $v)
		{
			$b1->unsetbit($v);
		}
		foreach($list as $v)
		{
			if ($b1->getbit($v))
			{
				sort($list);
				echo join(" ",$list);
				echo " setbit failed for $v";
				echo "<p>".$b1->dump()."<p>";
				
				break;
			}
		}
	}
	//not op
	
	for ($i = 0; $i < 1; $i++)
	{
		$list = array();
		for ($j = 0; $j < 8; $j++)
		{
			$list[] = rand(0,40);
		}
		$b1 = new swBitmap;
		foreach($list as $v)
		{
			$b1->setbit($v);
		}
		$b2 = $b1->notop();
		
		foreach($list as $v)
		{
			if ($b2->getbit($v))
			{
				sort($list);
				echo join(" ",$list);
				echo " failed for $v";
				echo "<p>".$b2->dump()."<p>";
				
				break;
			}
		}
		//echo "<p>".$b1->dump().' '.$b1->length;
		//echo "<br>".$b2->dump().' '.$b2->length;

		
	}
	
	
	// orop
	for ($i = 0; $i < 1; $i++)
	{
		
		//echo ".";
		$list = array();
		for ($j = 0; $j < 8; $j++)
		{
			$list[] = rand(0,40);
			$list2[] = rand(0,40);
		}
		$b1 = new swBitmap;
		$b2 = new swBitmap;
		foreach($list as $v)
		{
			$b1->setbit($v);
		}
		foreach($list2 as $v)
		{
			$b2->setbit($v);
		}
		$b3 = $b1->orop($b2);
		

		foreach($list as $v)
		{
			if (!$b3->getbit($v))
			{
				sort($list);
				echo join(" ",$list);
				echo " failed for $v";
				echo "<p>".$b1->dump()."<p>";
				
				//break;
			}
		}
		foreach($list2 as $v)
		{
			if (!$b3->getbit($v))
			{
				sort($list);
				echo join(" ",$list);
				echo " failed for $v";
				echo "<p>".$b3->dump()."<p>";
				
				// sbreak;
			}
		}
		//echo "<p>".$b1->dump().' '.$b1->length;
		//echo "<br>".$b2->dump().' '.$b2->length;
		//echo "<br>".$b3->dump().' '.$b3->length;

	}
	
		// andop
	for ($i = 0; $i < 1; $i++)
	{
		
		//echo ".";
		$list = array();
		for ($j = 0; $j < 15; $j++)
		{
			$list[] = rand(0,40);
			$list2[] = rand(0,40);
		}
		$b1 = new swBitmap;
		$b2 = new swBitmap;
		foreach($list as $v)
		{
			$b1->setbit($v);
		}
		foreach($list2 as $v)
		{
			$b2->setbit($v);
		}
		$b3 = $b1->andop($b2);
		

		foreach($list as $v)
		{
			if (!$b3->getbit($v) and in_array($v, $list2))
			{
				sort($list);
				echo join(" ",$list);
				echo " failed for $v";
				echo "<p>".$b1->dump()."<p>";
				
				//break;
			}
		}
		//echo "<p>".$b1->dump().' '.$b1->length;
		//echo "<br>".$b2->dump().' '.$b2->length;
		//echo "<br>".$b3->dump().' '.$b3->length;

	}


	// 
	for($i = 0; $i < 30; $i++)
	{
		$b1 = new swBitmap;
		$r = rand(0,4000);
		$c = rand(1,20);
		for ($j = 0; $j < $c; $j++)
			$b1->setbit($r+$j*10);
		
		if ($b1->countbits()<>$c) echo "<p>setbit failed for ".$r;
		$list = $b1->toarray();
		if (count($list)<>$c) "<p>toarray failed for ".$r;
		if($list[0] <> $r) "<p>toarray failed value for ".$r;
	}
	


	

}


// bitmapUnitTest();



?>