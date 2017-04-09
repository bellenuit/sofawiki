<?php

if (!defined("SOFAWIKI")) die("invalid acces");

// write fast data in blocks
// leave headroom for smaller changes
// index later, keep journal
// index albhabetically on url, - as folder separator to keep indexes reasonable

// [[key::size::block1::block2::block3]]
// [[_index::
// [[_free::


class swStore
{
	var $path;
	var $handle;
	var $cache;
	var $blocksize;
	var $journal;
	var $indexroot;
	var $freeblocks;
	var $lastblock;
	
	function open($p)
	{
		$this->blocksize = 1024;
		$this->path = $p;
		$this->handle = fopen($this->path,'c+');
		
		assert ($this->handle, 'store open no handle');
		
		fseek($this->handle,- $this->blocksize,SEEK_END);
		
		$this->lastblock = ftell($this->handle) / $this->blocksize;
		
		$s = fread($this->handle,$this->blocksize);
		$this->journal = unserialize($s);
		$this->indexroot = $this->journal['_index'];
		$this->freeblocks = $this->journal['_free'];
		 
	}
	
	function close()
	{
		$this->journal['_index'] = $this->indexroot;
		$this->journal['_free'] = $this->freeblocks;
		
		$s = serialize($this->journal);
		
		if (strlen($s) > $this->blocksize)
		{
			$this->UpdateIndex();
		}
		
		$s = str_pad(serialize($this->journal),$this->blocksize,' ',STR_PAD_RIGHT);
		fseek($this->handle,- $this->blocksize,SEEK_END);
		fwrite($this->handle,$s);
		
		if ($this->handle)
			fclose($this->datahandle);
	}
	
	function updateIndex()
	{
		// read $this->journal 
		// pout in root or leafs
		// empty this journal
	}
	
	function readBlock($blocks)
	{
		$size = array_shift($block);
		
		foreach ($blocks as $block) 
		{
			fseek($this->handle,$block*$this->blocksize);
			$list[] = fread(min($size,$this->blocksize));
			$size -= $this->blocksize;
		}
		
		return join('',$list);
	}
	
	function writeBlocks($s, $ownblocks)
	{
		$list = array();
		$list[] = strlen($s);
		
		$blocks = chunk_split($s,$this->blocksize);
		
		foreach($blocks as $block)
		{
			if (count(ownblocks)>0)
				$f = array_shift(ownblocks);
			elseif (count($this->freeblocks)>0)
				$f = array_shift($this->freeblocks);
			else
			{
				$f = $this->lastblock;
				$this->lastblock++;
			}
			fseek($this->handle, $f * $this->blocksize);
			fwrite(str_pad($this->handle,str_pad($block,$this->blocksize,' ',STR_PAD_RIGHT));
			$list[] = $f;
		}
		foreach($ownblocks as $block)
			$this->freeblocks[] = $block;	

		return $list;
	
	}
	
	function getValue($key) 
	{
		$key = swNameURL($key);
		
		$leaf = $this->getValueLeaf($key);
		
		return $this->readBlocks($leaf);
	}
	
	function removeValue($key)
	{
		$key = swNameURL($key);
		
		$leaf = $this->getValueLeaf($key);
		
		if (count($leaf)>0)
		{
			array_shift($leaf);
			foreach($leaf as $block)
				$this->freeblocks[] = $block;
				
			$this->journal[$key] = array();
		}
	}
	
	
	function setValue($key, $value) 
	{
		$key = swNameURL($key);		
		if ($value == $this->getValue($key)) { return false; }

		
		$leaf = $this->getValueLeaf($key);
		$leaf = $this->writeBlocks($value,$leaf);
		
		$this->journal[$key] = $leaf;
		 
	}
	
		
	function getValueLeaf($key)
	{
	
	}
	
	function updateIndex()
	{
	
	
	}

	
	
		
	
}


?>