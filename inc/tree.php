<?php

if (!defined("SOFAWIKI")) die("invalid acces");
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors",1); 

/*

implements a file based radix tree
the key can be any string that does not contain the nul character

node: 
- type : 1 byte N
- selector : 1 byte
- offset_if: 4 bytes
- offset_else: 4 bytes

stop-selector:  nul

values:
- type 1 byte V
- length: 4 bytes
- next: 4 bytes
- data: length bytes

except offset_else (where 0 offset may be overwritten), data is always written at the end. 

set replaces the current value (no garbage collection)
add completes the value (unique values only)
get retrieves a value
match retrieves an array of values 

value is returned as string. field separator :: in result string

space used:
- 10 bytes per key character, 5 bytes + length per value. 
if key is 12 bytes, half of it shared with other words, and value is 12 bytes, 2 values per key, this makes 62 bytes per key/value pair. a list would be 26 bytes per key-value pair.

constant access speed depends only on key length
- if the alphabet has 50 characters, 25 comparisons per character, if key is 12 bytes, this makes 300 single character comparisons.
- on a linear list with 1000 entries, there are 525 comparisons, with 100'000 entries there are 52'500 comparisons.
- on a binary search, with 1000 entries, there are 15 comparisions, with 100'000 there are 30 comparisons.

(NOT READY YET
IMPORTANT NOTE FOR WRITING CLASS: 
For speed, $this->filesize caches the end. SEEK_END is only used when opened. Is much faster.
It has therefore be set manually each time you write at the end of the file. )

*/

class swTree
{
	public $path = '';
	private $handle = null; 
	private $touched = false;
	private $filesize = 0;
	
	function swTree($p='')
	{
		if($p)
		{
			global $swRoot;
			$this->path = $swRoot.'/site/tree/'.$p.'.txt';
			$this->open(true);
		}
	}
	
	
	function open($writable = false)
	{
		if ($writable) $mode = 'c+'; else $mode = 'r';

		if ($this->path == '') 
		{
			echotime("swTree::open missing path"); 
			return false;
		}
				
		if($handle = @fopen($this->path,$mode)) 
        { 
            $this->handle = $handle;
            if ($writable)
            	@flock($this->handle, LOCK_EX);
        }
        else
        {
        	echotime("swTree::open no valid handle for ".$this->path); 
        	return false; 
        }
        fseek($this->handle,0 , SEEK_END);
        $this->filesize =  ftell($this->handle);
        return true;
	}
	
	function close()
	{
		@flock($this->handle, LOCK_UN);
        @fclose($this->handle); 
	}
	
	private function readNode($offset)
	{
		fseek($this->handle, $offset);
		$test = unpack('a1type/aselector/Nif/Nelse',fread($this->handle,10));
		if ($test['type'] != 'N') 
			throw new ErrorException('swTree:readNode Type not node', 0, E_USER_ERROR, $this->path, $offset);
		return $test;
	}
	
	private function readValue($offset)
	{
		fseek($this->handle, $offset); 
		$test = unpack('a1type/Nlength/Nnext',fread($this->handle,9));
		if ($test['type'] != 'V') 
			throw new ErrorException('swTree:readValue Type not value first '.dechex($offset), 0, E_USER_ERROR, $this->path, $offset);
		if ($test['length']>0)
			$result = fread($this->handle,$test['length']);
		else
			$result = '';
		while($test['next']>0)
		{
			fseek($this->handle, $test['next']); 
			$test = unpack('a1type/Nlength/Nnext',fread($this->handle,9));
			if ($test['type'] != 'V') 
				throw new ErrorException('swTree:readValue Type not value '.dechex($offset), 0, E_USER_ERROR, $this->path, $offset);
			if ($test['length']>0)
				$result .= '::'.fread($this->handle,$test['length']);
			else
				$result .= '::';
		}
		return $result;
	}
	
	private function hasValue($offset,$value)
	{
		fseek($this->handle, $offset); 
		$test = unpack('a1type/Nlength/Nnext',fread($this->handle,9));
		if ($test['type'] != 'V') 
			throw new ErrorException('swTree:hasValue Type not value first '.dechex($offset), 0, E_USER_ERROR, $this->path, $offset);
		if ($value == fread($this->handle,$test['length']))
			return true;
		while($test['next']>0)
		{
			fseek($this->handle, $test['next']); 
			$test = unpack('a1type/Nlength/Nnext',fread($this->handle,9));
			if ($test['type'] != 'V') 
				throw new ErrorException('swTree:hasValue Type not value '.dechex($offset), 0, E_USER_ERROR, $this->path, $offset);
			if ($value == fread($this->handle,$test['length']))
			return true;
		}
		return false;
	}
	
	function add($key,$value)
	{
		$offset = $this->getOffset($key,false);
		if (!$offset)
			$this->set($key,$value);
		else
		{
			if ($this->hasValue($offset, $value)) 
				return; // we aldready have the value
			
			$nextoffset0 = $this->filesize;
			fseek($this->handle,0 , SEEK_END); //we go to end to find offset
			$nextoffset = ftell($this->handle);
			if ($nextoffset != $nextoffset0)
				throw new ErrorException('swTree:add nextoffset '.$nextoffset.' != '.$nextoffset0, 0, E_USER_ERROR, $this->path, $nextoffset);
			
			fseek($this->handle, $offset+5); // we set the next selector
			fwrite($this->handle,pack('N',$nextoffset));
			
			//write at the end
			fseek($this->handle, $nextoffset);
			$length = strlen($value);
			fwrite($this->handle,'V');
			fwrite($this->handle,pack('N',$length));
			fwrite($this->handle,pack('N',0));
			fwrite($this->handle,$value);
			$this->filesize =  ftell($this->handle);
		}
	}
	
	function set($key,$value)
	{
		if (! $this->handle) 
		{
			if (! $this->open(true)) return;
			if (! $this->handle) return;
		}
		$list = str_split($key);
		foreach($list as $k)
		{
			if(ord($k)) $keylist[]= $k;	//remove null character from text
		}
		$keylist[] = chr(0);
		$offset = 0;
		$fs0 = $this->filesize;
		fseek($this->handle, 0 , SEEK_END); // check length
		$fs = ftell($this->handle);
		if ($fs != $fs0)
				throw new ErrorException('swTree:set fs '.$fs.' != '.$fs0, 0, E_USER_ERROR, $this->path, $fs);
	
		if($fs>0)
			$reading = true;
		else
			$reading = false;
		foreach($keylist as $char)
		{
			if ($reading)
			{
				$test = $this->readNode($offset);
				while ($char != $test['selector'] && $test['else'] > 0) // loop until good selector
				{
					$offset = $test['else'];
					$test = $this->readNode($offset);
				}
				if ($char != $test['selector']) // no selector found, we need to add a new one
				{
					$else0 = $this->filesize;
					fseek($this->handle,0 , SEEK_END); // go to end
					$else = ftell($this->handle);
					if ($else0 != $else)
						throw new ErrorException('swTree:set else '.$else.' != '.$else0, 0, E_USER_ERROR, $this->path, $else);
	
					fseek($this->handle, $offset+6); //write offset to else selector
					fwrite($this->handle,pack('N',$else));
					
					//write at the end
					$offset = $else;
					$if = $offset + 10;
					fseek($this->handle, $offset); // we go to next selector and write it
					fwrite($this->handle,pack('aaNN','N',$char,$if,0));
					$this->filesize =  ftell($this->handle);
					
					$offset = $if;
					$reading = false; // we stopped reading tree, create new branches
				}
				else
					$offset = $test['if']; 
			}
			else
			{
				//write at the end
				$if = $offset + 10;
				fseek($this->handle, $offset); // we go to next selector and write it
				fwrite($this->handle,pack('aaNN','N',$char,$if,0));
				$this->filesize =  ftell($this->handle);
				$offset = $if;;
			}
		}
		if ($reading)
		{
			fseek($this->handle, $offset); // we check the current value
			$value = unpack('Nlength',fread($this->handle,4));
			$result = fread($this->handle,$value['length']);
			if ($result == $value) return; // value did not change, nothing to do
			
			$newoffset0 = $this->filesize;
			//write at the end
			
			fseek($this->handle,0 , SEEK_END); // value did change, we go to end to find offset
			$newoffset = ftell($this->handle);
			if ($newoffset != $newoffset0)
				throw new ErrorException('swTree:set newoffset '.$newoffset.' != '.$newoffset0, 0, E_USER_ERROR, $this->path, $newoffset);
	
			
			fseek($this->handle, $offset+1); // we change the value of the if selector
			fwrite($this->handle,pack('N',$newoffset));
			$this->filesize =  ftell($this->handle);
			$offset = $newoffset;
		}
		//write at the end
		fseek($this->handle, $offset);
		$length = strlen($value);
		fwrite($this->handle,'V');
		fwrite($this->handle,pack('N',$length));
		fwrite($this->handle,pack('N',0));
		fwrite($this->handle,$value);
		$this->filesize =  ftell($this->handle);
		
	}
	
	private function getOffset($key,$first=true)
	{
		if (! $this->handle) 
		{
			if (! $this->open()) return false;
		}
		if(!$this->length()) return false;
		$list = str_split($key);
		foreach($list as $k)
		{
			if(ord($k)) $keylist[]= $k;	//remove null character from text
		}
		$keylist[] = chr(0);
		$offset = 0;
		foreach($keylist as $char)
		{
			$test = $this->readNode($offset);
			while (ord($char) != ord($test['selector']) && $test['else'] > 0) // loop until good selector
			{
				$offset = $test['else'];
				$test = $this->readNode($offset);
			}
			if (ord($char) != ord($test['selector'])) { return 0; }// good selector not found
			$offset = $test['if']; // offset of good selector
		}
		if (!$first)
		{
			fseek($this->handle, $offset); 
			$test = unpack('a1type/Nlength/Nnext',fread($this->handle,9));
			
			while($test['next']>0)
			{
				$offset = $test['next'];
				fseek($this->handle, $test['next']); 
				$test = unpack('a1type/Nlength/Nnext',fread($this->handle,9));
			}
		}
		return $offset;
	}
	
	function get($value)
	{
		$offset = $this->getOffset($value);
		if (!$offset) return false;
		return $this->readValue($offset);
	}
	
	function length()
	{
		//return $this->filesize;
		
		
		fseek($this->handle,0 , SEEK_END); // value did change, we go to end to find offset
		$fs =  ftell($this->handle);
		if ($fs != $this->filesize)
						throw new ErrorException('swTree:length fs '.$fs.' != '.$this->filesize, 0, E_USER_ERROR, $this->path, $fs);
		return $fs;
		
	}
	
	function dump()
	{
		if (! $this->handle) 
		{
			if (! $this->open()) return;
		}
		$offset = 0;
		$length = $this->length();
		fseek($this->handle,$offset);
		$result = '<pre>';
		while (!feof($this->handle))
		{
			if ($offset == $length) return;
			$type = fread($this->handle,1);
			
			switch ($type)
			{
				case 'N': $test = $this->readNode($offset);
						  $result .= str_pad(dechex($offset),8,' ',STR_PAD_LEFT).' N '.str_pad($test['selector'],1).' '.str_pad(dechex($test['if']),8,' ',STR_PAD_LEFT).' '.str_pad(dechex($test['else']),8,' ',STR_PAD_LEFT).'<br>';
					      $offset +=10;
					      break;
				case 'V': $test = unpack('Nlength/Nnext',fread($this->handle,8));	
						  if ($test['length']>0)
								$value = fread($this->handle,$test['length']);
						  else
								$value = '';
					      $result .= str_pad(dechex($offset),8,' ',STR_PAD_LEFT).' V   '.str_pad(dechex(strlen($value)),8,' ',STR_PAD_LEFT).' '.str_pad(dechex($test['next']),8,' ',STR_PAD_LEFT).' "'.$value.'" <br>';
						  $offset +=strlen($value)+9;
						  break;
				
				default	: throw new ErrorException('swTree:dump unknown type', 0, E_USER_ERROR, $this->path, $offset);

			}
		}
		$result .='</pre>';
		return $result;
	}
	
	function match($operator='*',$filter='')
	{
		if (! $this->handle) 
		{
			if (! $this->open()) return;
		}
		$result = array();
		if (!$this->length()) return $result;
		$list = array();
		$todo = true;
		$list[] = array('key'=>'','offset'=>0);
		while (count($list)>0)
		{
			$list2 = array();
			foreach($list as $v)
			{
				$test = $this->readNode($v['offset']);
				unset($v2);
				if ($test['else']>0)
				{
					$v2 = array();
					$v2['key'] = $v['key'];
					$v2['offset'] = $test['else'];
				}
				
				if(ord($test['selector'])==0)
				{
					if (swFilterCompare($operator,$v['key'],$filter))
						$result[$v['key']] = $this->readValue($test['if']);
				}
				else
				{
					$v['key'] .= $test['selector'];
					$v['offset'] =  $test['if'];
					
					switch($operator)
					{
						// some cases we can already filter out
						case '=*' : $pfilter=substr($filter,0,strlen($v['key']));
									if (!strlen($pfilter) || substr($v['key'],0,strlen($pfilter)) == $pfilter) $list2[] = $v; break;
						case '~~' :
						case '~*' :	$pfilter=swNameURL(substr($filter,0,strlen($v['key'])));
									if (!strlen($pfilter) || substr(swNameURL($v['key']),0,strlen($pfilter)) == $pfilter) $list2[] = $v; break;
						case '>>' : 
						case '>>=': $pfilter=substr($filter,0,strlen($v['key']));
									if (!strlen($pfilter) || substr($v['key'],0,strlen($pfilter)) >= $pfilter) $list2[] = $v; break;
						case '<<' : 
						case '<<=': $pfilter=substr($filter,0,strlen($v['key']));
									if (!strlen($pfilter) || substr($v['key'],0,strlen($pfilter)) <= $pfilter) $list2[] = $v; break;
						default :   $list2[] = $v; break;

					}

				}
				if (isset($v2)) $list2[] = $v2;
					
			}
			$list = $list2;
		}
		return $result;
	}
	
}




function swGetLastTree()
{
	global $db;
	$bm = $db->treebitmap;
	for($i=$bm->length;$i>0;$i--)
	{
		if ($bm->getbit($i)) return $i;
	}
	return 0;
}

function swGetTree($key)
{
	global $swRoot;
	global $db;
	$file = $swRoot.'/site/tree/'.$key.'.txt';
	if (file_exists($file))
	{
		$tree = new swTree;
		$tree->path = $file;
		$tree->open();
		return $tree;
	}
}



function swIndexTree($numberofrevisions = 500, $refresh = false)
{
	echotime('tree start ');
	global $db;
	global $swRoot;
	global $swMaxSearchTime;
	if ($swMaxSearchTime<500) $swMaxSearchTime = 500;
	$db->init(); // provoke init
	$bitmap = $db->treebitmap;
	$ibitmap = $db->indexedbitmap;
	$cbitmap = $db->currentbitmap;
	
	if ($refresh)
		$bitmap = new swBitmap;
	if ($bitmap->length == 0)
		$refresh = true;
	
	if ($refresh)
		swClearTree();
	
	$lastrevision = $db->lastrevision;
	$lasttree= 0;
	$n=1;
	$trees = array();
	
	
	$tocheck = $bitmap->notop();
	$tocheck->redim($cbitmap->length,true);
	$tocheck = $tocheck->andop($cbitmap);
	$countbits = $tocheck->countbits();
	if ($countbits == 0)
	{
		echotime('tree complete '.$bitmap->countbits().'/'.$cbitmap->countbits());
		return;
	}

	
	
	global $swRoot;
	$trees['-url'] = new swTree('-url');
	$trees['-name'] = new swTree('-name');
	$trees['-word'] = new swTree('-word');
	$trees['-system'] = new swTree('-system');
	$trees['-field'] = new swTree('-field');
	
	
	
	
	$starttime = microtime(true);
	$c=0;
	echotime('tree loop ');
	$i = 1;
	$n = $tocheck->length;	
	$ic = 0;	
	while($i <= $n)
	{
		$nowtime = microtime(true);	
		$dur = sprintf("%04d",($nowtime-$starttime)*1000);
		if ($dur>$swMaxSearchTime && $i>10) break;
		
		$i++;
		if (!$tocheck->getbit($i)) continue;
		$ic++;
		
		//index;
		$w = new swRecord;
		$w->revision=$i;
		$w->error = '';
		$w->lookup();
		if ($w->error == '')
		{
			//echotime($w->revision.' '.$w->error);
		
			$url = swNameURL($w->name);
			$trees['-url']->add($url,$w->revision); $c++;
			$trees['-name']->add($w->name,$w->revision); $c++;
			if(strtolower(substr($url,0,7))=='system:')
			{
				// keep only most recent
				$test = $trees['-system']->get($url);
				if (!$test) $trees['-system']->set($url,$w->revision.'|'.$w->content);
				else
				{	
					$ts = explode('|',$test);
					if ($ts[0] < $w->revision)
						$trees['-system']->set($url,$w->revision.'|'.$w->content);
				}
				$c++;
			}
			$words = array_unique(explode('-',swNameURL($w->content)));
			foreach($words as $word)
			{
				if ($word)
				{
					$trees['-word']->add($word,$w->revision);
					$c++;
				}
			}
			foreach($w->internalfields as $k=>$v)
			{
				$trees['-field']->add($k,$w->revision);
				if (stristr($k,' ')) continue;
				$k = swNameURL($k);
				if(!isset($trees[$k]))
				{
					$trees[$k] = new swTree($k);
				}
				foreach($v as $onev)
				{
					$trees[$k]->add($onev,$w->revision);
					$c++;
				}
			}
		
        	$bitmap->setbit($i);
        }
		if ($ic>$numberofrevisions) break;

	}
	echotime('tree close ');
	foreach($trees as $t)
		$t->close();
	
	echotime('tree end '.$ic.'/'.$cbitmap->countbits().' files, '.count($trees). ' trees, '.$c.' leafs');

}

function swClearTree()
{
	 global $swRoot;
	 $path0 = $swRoot.'/site/tree/';

	 
	 $files = glob($path0.'/*.txt');
	   
	 foreach($files as $file)
	 {
	   	unlink($file);
	 }

}

function swTreeList()
{
	 global $swRoot;
	 $path0 = $swRoot.'/site/tree/';

	 
	 $files = glob($path0.'*.txt');
	  
	 $list = array();
	 foreach($files as $file)
	 {
	   $key = sprintf('%05d',filesize($file));
	   	$fn = str_replace($path0,'',$file);
	   	$fn = substr($fn,0,-4);
	   	$list[$key.' '.$fn] = $fn;
	 }
	 krsort($list);
	 return $list;
}


?>