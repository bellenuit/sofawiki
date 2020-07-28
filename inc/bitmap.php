<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swBitmap extends swPersistance 
{
	var $length;
	var $state;
	var $touched;
	var $map;
	var $maphex;
	var $compressionmode;
	
	function init($l, $default = false)
	{
		//creates the bit string, filled with default value
		$this->length = intval($l);
		$bytes = ($l + 7) >> 3;
		if ($default)
			$this->map = str_repeat(chr(255),$bytes);
		else
			$this->map = str_repeat(chr(0),$bytes);
			
		$compressionmode = true;
	}
	
	function redim($l, $default = false)
	{
		if ($this->map == '') $this->dehexit();	
		$oldlength = $this->length;
		$this->length = max(intval($l),$this->length);
		
		
		
		
		
		$bytes = ($this->length + 7) >> 3;
		$thisbytes = strlen($this->map);
		if ($bytes <= $thisbytes) return;  // can only grow.
		
		if ($default)
			$this->map = str_pad($this->map,$bytes,chr(255));
		else
			$this->map = str_pad($this->map,$bytes,chr(0));
			
			
		// we need a special operation on the last current byte
		
		for ($i = $oldlength; $i < ((($oldlength-1) >> 3)+1) << 3; $i++)
		{
			if ($default)
				$this->setbit($i);
			else
				$this->unsetbit($i);
				
		}
		
	}
	
	function hexit()
	{
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
		$n = intval($n);
		if ($n<0) return; 
		if ($n=='') return; 
		$this->touched = true;
		
		if ($n>=$this->length)
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
		if ($n<0) return; 
		if ($n=='') return; 
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
		if ($n>=$this->length) return false;
		if ($n<0) return false;
		if ($n=='') return false;
		
		// gets the value of the nth bit
		$byte = $n >> 3;
		$bit = $n - ($byte << 3);
		$bitmask = 128 >> $bit;
		
		//echo $this->length.' '.$n.' '.$byte.' ';
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
		// returns a bitmap as result of AND between this and bitmap
		if ($this->map == '') $this->dehexit();		
		$s1 = $this->map;
		if ($bitmap->map == '') $bitmap->dehexit();		
		$s2 = $bitmap->map;
		
		$pl=strlen($s1);
		if (strlen($s1) != strlen($s2))
		{
			$pl = max(strlen($s1),strlen($s2));
			$s1 = str_pad($s1,$pl,chr(0));
			$s2 = str_pad($s1,$pl,chr(0));
		}
		
		$s3 = $s1 & $s2;
		
		$result = new swBitmap;
		$result->length = $pl*8;
		$result->map = $s3;
		$result->touched = true;
		return $result;
	}
	
	function orop($bitmap)
	{
		// returns a bitmap as result of OR between this and bitmap
		if ($this->map == '') $this->dehexit();		
		$s1 = $this->map;
		if ($bitmap->map == '') $bitmap->dehexit();		
		$s2 = $bitmap->map;

		$pl=strlen($s1);
		if (strlen($s1) != strlen($s2))
		{
			$pl = max(strlen($s1),strlen($s2));
			$s1 = str_pad($s1,$pl,chr(0));
			$s2 = str_pad($s1,$pl,chr(0));
		}

		$s3 = $s1 | $s2;
		
		$result = new swBitmap;
		$result->length = $pl*8;
		$result->map = $s3;
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



?>