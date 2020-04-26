<?php

if (!defined("SOFAWIKI")) die("invalid acces");


class swKeyValueStore
{
	var $path;
	var $handle;
	var $cache;
	var $blocksize;
	var $writecount;
	var $readcount;
	var $closed;
	
	function open($p)
	{
// 		echotime('open kvs');
		
		$this->blocksize = 1024;
		$this->path = $p;
		$this->cache = array();
		$this->handle = fopen($this->path,'c+');
// 		echo $this->handle;
		$this->readcount = 0;
		$this->writecount = 0;
		
		assert ($this->handle, 'kvs open no handle');
		
		
		// create first leaf if empty
		if (filesize($p) == 0)
		{
			$arr = array();
			$arr['#type'] = 'free';
			$arr['#free'] = '';
			$this->writeblock(0,$arr);
			
			$arr = array();
			$arr['#type'] = 'leaf';
			$arr['#root'] = '';
			$this->writeblock(1,$arr);
		}
		
		$this->closed = false;
	}
	
	function close()
	{
// 		echotime('close kvs');

		$this->commit();
		if ($this->handle)
			fclose($this->handle);
		$this->closed = true;
	}
	
	function getValueLeaf($key)
	{
		$key = swNameURL($key);
		$i=1;
		do 
		{
			$arr = $this->readblock($i);
			assert(is_array($arr),'kvs getvalue no array');
			$type = $arr['#type'];
			$root = $arr['#root'];
			assert($type == 'node' || $type == 'leaf', 'kvs getvalue invalid type not node or leaf '.$i.' '.$type.' '.$this->path);
			assert(substr($key,0,strlen($root)) == $root, 'kvs getvalue invalid root');
			
			if ($key == $root) return $i;
			elseif ($type == 'node') $test = substr($key, strlen($root),1);
			else $test = substr($key, strlen($root));

			
			if (!isset($arr[$test]))
			{
					return $i;
			}

			if ($type == 'node') 
			{
				assert($arr[$test] > $i, 'kvs getvalue node decreasing root='.$root.' test='.$test.' arr='.$arr[$test].' < i='.$i);
				$i = $arr[$test];
				assert($i > 0, 'getvalueleaf i zero "'.$root.' / '.$test.'"');
				
			}
		
		} while ($type == 'node' && $key != $root);
			
		return $i;
		
	}
	
	function getValue($key) 
	{
		$key = swNameURL($key);
		$leaf = $this->getValueLeaf($key);
// 		if ($key == '-rev:2') echo 'leaf '.$leaf.' ';
		assert($leaf > 0, 'getvalue leaf zero "'.$key.'"');
		$arr = $this->readBlock($leaf);
		$root = $arr['#root'];
		$test = substr($key, strlen($root));
		if ($key == $root) $test = ' ';
		if (!isset($arr[$test])) return false;

		$v = $arr[$test];
// 		if ($key == '-rev:2') echo ' test '. $test.' v '.$v.' '.print_r($arr, true);
		if (substr($v,0,1) == '#') return substr($v,1);
	
		$blocks = explode('::',$v);
		$list = array();
		foreach ($blocks as $block) 
		{
			
			$arr = $this->readblock($block);
			
			assert( $arr['#type'] == 'data', 'kvs getvalue invalid type not data '.$key.' block '.$block.' leaf '.$leaf);
			
			assert (isset($arr['#data']), 'kvs getvalue no data');
			
			$list[] = $arr['#data'];
			
		}
		
		return join('',$list);
		
	}
	
	function removeValue($key)
	{
		$key = swNameURL($key);
		$leaf = $this->getValueLeaf($key);
		$arr = $this->readBlock($leaf);
		$root = $arr['#root'];
		$test = substr($key, strlen($root));
		if ($key ==  $root) $test = ' ';
		if (!isset($arr[$test])) return $leaf;
		
		$v = $arr[$test];
		if (substr($v,0,1) != '#')
		{
			$freeblocks = explode('::',$v);
			foreach ($freeblocks as $free)
				$this->setFree($free);			
		}
		unset($arr[$test]);
		$arr['#touched'] = true;
		$this->cache[$leaf] = $arr;
		return $leaf;
	}
	
	
	function setValue($key, $value) 
	{
		$key = swNameURL($key);		
		if ($value == $this->getValue($key)) { return false; }

		$leaf = $this->removeValue($key);

		$arr = $this->readBlock($leaf);
		$type = $arr['#type'];
		$root = $arr['#root'];
		assert($type == 'node' || $type == 'leaf',
		'kvs setvalue invalid type not node or leaf');
		
		$test = substr($key, strlen($root));
		if ($key == $root) $test = ' ';
		
		
		if ($type == 'leaf') 
		{ 
			if (strlen($value) < 64) $arr[$test] = '#'.$value; 
			else 
			{
				$arr[$test]  = $this->WriteDataBlocks($value);
			}
			// echotime ('write leaf ' . $test);
			$arr['#touched'] = true;
			$this->cache[$leaf] = $arr;
			return true;
		}

		// we are in a node 
		if ($key == $root) // it is the root value, save here
		{ 
			if (strlen($value) < 64) $arr[' '] = '#'.$value; 
			else 
			{
				$arr[' ']=$this->writeDataBlocks($value);
			}
			$arr['#touched'] = true;
			$this->cache[$leaf] = $arr;

			return true;
		}

		// we must add a leaf because it exists not yet on this node
		$arr2['#type'] = 'leaf';
		$arr2['#root'] = substr($key,0,strlen($root)+1);
		$test2 = substr($key,strlen($root)+1);
		if ($key == $arr2['#root']) $test2 = ' ';
		assert($test2 != '', 'setValue test2 empty '.$key.' '.$root);
		
		if (strlen($value) < 64) $arr2[$test2] = '#'.$value; 
		else $arr2[$test2]=$this->writeDataBlocks($value);
			
		$t = substr($key,strlen($root),1);
		if ($key == $root) $t = ' ';
		assert($t != '', 'setValue t empty '.$key.' '.$root);
				
		$arr[$t] = $blocknumber = $this->createBlock($leaf);
		$this->writeBlock($blocknumber, $arr2);
		
		$arr['#touched'] = true;
		$this->cache[$leaf] = $arr;
		return true;
	}
	
	function readBlock($blocknumber, $fromfile = false)
	{
		if (!$fromfile && isset($this->cache[$blocknumber])) 
		{
			$arr = $this->cache[$blocknumber];
			return $arr;
		}
		
		if ($this->closed) $this->open($this->path);
		assert($this->handle,'kvs readblock no handle');
		rewind($this->handle); 
		assert ($blocknumber*$this->blocksize < filesize($this->path), 'kvs readblock out of bounds '.$blocknumber);
		fseek($this->handle,$blocknumber*$this->blocksize);
		$s = fread($this->handle,$this->blocksize);
		$arr = unserialize($s);
		$this->readcount++;
		assert(is_array($arr),'kvs readblock no array '.$blocknumber. ' '.$s);
		ksort($arr);
		if ($arr['#type'] == 'node' || $arr['#type'] == 'leaf' || $arr['#type'] == 'free')
			$this->cache[$blocknumber] = $arr;
		
		return $arr;
		
	}
	
	function commit()
	{
		foreach($this->cache as $blocknumber=>$arr)
		{
			if (isset($arr['#touched']))
			{
				//echo "<p>blocknumber $blocknumber ".print_r($arr, true);
				unset($arr['#touched']);
				assert (! isset($arr['#touched']), '#touched should not be saved');
				assert(! isset($arr['']), 'commit empty index '.$blocknumber); 
				$this->writeBlock($blocknumber,$arr);
				$this->cache[$blocknumber] = $arr; 
			}
		}
		if ($this->readcount>0 || $this->writecount>0)
		{
			$l = explode('/',$this->path);
			$p = array_pop($l);
			$p = str_replace('.txt','',$p);
			echotime($p.' r '.$this->readcount.' w '.$this->writecount);
		}
		$this->readcount = 0;
		$this->writecount = 0;
	}


	function WriteDataBlocks($value) // return array of blocknumber
	{
		$l = strlen($value);
		$chunksize = $this->blocksize - 128;
		$list = array();
		for ($i=0;$i<$l;$i+=$chunksize)
		{
			$chunk = substr($value,$i,$chunksize);
			$arr = array();
			$arr['#type'] = 'data';
			$arr['#data'] = $chunk;
			$list[] = $blocknumber = $this->createBlock();
			$s = serialize($arr);
			if ($this->closed) $this->open($this->path);
			swSemaphoreSignal();
			rewind($this->handle);
			fseek($this->handle,$blocknumber*$this->blocksize);
			fwrite($this->handle,str_pad($s,$this->blocksize-strlen(PHP_EOL.PHP_EOL)).PHP_EOL.PHP_EOL);
			$this->writecount++;
			swSemaphoreRelease();

			
      	}
		return join("::",$list);
	}
	
	
	function CreateBlock($minblocknumber = 0) 
	{
		
		assert($this->handle,'kvs createblock no handle');
		
		if (filesize($this->path)>0)
			$blocknumber = $this->getfree($minblocknumber);
		else
			$blocknumber = '';
			
		if ($this->closed) $this->open($this->path);

		
		if ( $blocknumber == '' )
		
		{
			fseek($this->handle,0,SEEK_END);
			$blocknumber = ceil(ftell($this->handle) / $this->blocksize);
			// echotime('kvs create block '.$blocknumber);
		}
		
		
		swSemaphoreSignal();
		rewind($this->handle);
		fseek($this->handle,$blocknumber*$this->blocksize);
		fwrite($this->handle,str_pad(".",$this->blocksize-strlen(PHP_EOL.PHP_EOL)).PHP_EOL.PHP_EOL);
		swSemaphoreSignal();
		
		//echo " $blocknumber; ";
		return $blocknumber;
	}
	
	
	function WriteBlock($blocknumber, $arr) // write and expand, if necessary 
	{
		assert($this->handle,'kvs writeblock no handle');
		assert (! isset($arr['#touched']), 'kvs writeblock #touched should not be saved');
		
// 		if ($blocknumber == 9) print_r($arr);
		
		foreach($arr as $k=>$v)
		{
			if (($k == '' || !$k) && $k != '0')
			{
				unset($arr[$k]);
				$arr[' '] = $v;	
			}
		}			

		unset($this->cache[$blocknumber]);
		ksort($arr);
		$s = serialize($arr);
		if (strlen($s) > $this->blocksize - 4)
		{
			//echo "<p>long ".strlen($s);
			
			assert($arr['#type'] == 'leaf','kvs writeblock wrong type');
		
			// convert leaf to a node and create a leaf for each letter
		
			$arr2 = array();
			$arr2['#type'] = 'node';
			$arr2['#root'] = $arr['#root'];

			unset($arr['#type']);
			unset($arr['#root']);
		
			$leafs = array();
		
			foreach($arr as $k=>$v)
			{
				if ($k == ' ')
				{
					$arr2[' '] = $v;
					continue;
				}
				
				$first = substr($k,0,1);
				$rest = substr($k,1);
				$leafs[$first][$rest] = $v;
			}
			foreach($leafs as $k=>$leaf)
			{
				if ($k == ' ') continue;
				$leaf['#type'] = 'leaf';
				$leaf['#root'] = $arr2['#root'].$k;
				$arr2[$k] = $bn = $this->CreateBlock($blocknumber);

				$this->WriteBlock($bn, $leaf);
			}	
			
			$arr = $arr2;
			$s = serialize($arr);
			
		}	
		
		if ($this->closed) $this->open($this->path);
		swSemaphoreSignal();
		rewind($this->handle);
		fseek($this->handle,$blocknumber*$this->blocksize);
		fwrite($this->handle,str_pad($s,$this->blocksize-strlen(PHP_EOL.PHP_EOL)).PHP_EOL.PHP_EOL);
		$this->writecount++;
		swSemaphoreRelease();
			
		if ($arr['#type'] == 'node' || $arr['#type'] == 'leaf')
			$this->cache[$blocknumber] = $arr;
		

	}
	
	function dump()
	{
		$nextblocknumber = ceil(filesize($this->path) / $this->blocksize);
		
		
		//$s = '';
		$list= array();
		for ($i = 0; $i < $nextblocknumber; $i++)
		{
			$list[] = $this->readblock($i, true);
		}		
		return '<pre>'.print_r($list, true).'</pre>';		
		
	}
	
	
	function dumpBlock($blocknumber, $baselink='', $parent = false)
	{
		$arr = $this->readBlock($blocknumber);
		
		$s = '<p>';
		if ($parent)
			$s .= '<a href="'.$baselink.'&block='.$parent.'">'.$parent.'</a>';
		$s .= '<br>'.$blocknumber.' '.$arr['#type'];
		if (isset($arr['#root'])) $s.= ' '.$arr['#root'];
		
		if ($arr['#type']=='data') 
			{$s.='<br><pre>'.$arr['#data'].'</pre>';return $s;}
		
		foreach($arr as $k=>$v)
		{
			if ($k[0] == '#') continue;
			if ($v[0] == '#') 
				$s .= '<br>'.$k.' = '. substr($v,1);
			else
			{
				$l = explode('::',$v);
				$s .= '<br>'.$k.' = ';
				foreach($l as $leaf)
					$s .= ' <a href="'.$baselink.'&block='.$leaf.'&parent='.$blocknumber.'">'.$leaf.'</a>';
			}
		}
		
		return $s;
	}
	
	
	
	
	
	function getFree($minblocknumber=0)
	{
// 		echo ' getfree '.$minblocknumber;
		$arr = $this->readBlock(0);
		
		if (!isset($arr['#free'])) return '';
		if ($arr['#free'] == '') return '';
		
		$freeblocks = explode('::',$arr['#free']);
		$found = false;
		for($i=0;$i<count($freeblocks);$i++)
		{
			if ($freeblocks[$i] > $minblocknumber)
			{
				$found = $freeblocks[$i];
				unset($freeblocks[$i]);
				break;
			}			
		}
		if (!$found) return false;

		$arr['#free'] = join('::',$freeblocks);
		$arr['#touched'] = true;
		$this->cache[0] = $arr;
		
		return $found;
	}
	
	function setFree($free)
	{
// 		echo ' setfree ' .$free;
		$arr = $this->readBlock(0);
		$freeblocks = explode('::',$arr['#free']);
		if (count($freeblocks) < 64) // prevent overflow, need to calculate what
// max length of serialize in block 0 could be
// type
// root 
// letters
// free 
		array_push($freeblocks, $free);
		$arr['#free'] = join('::',$freeblocks);
		$arr['#touched'] = true;
		$this->cache[0] = $arr;		
	}
	
		
	
}


?>