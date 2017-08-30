<?php

if (!defined("SOFAWIKI")) die("invalid acces");


class swRecord extends swPersistance
{
	var $revision;
	var $name;
	var $user;
	//var $lang;
	var $timestamp;
	var $status;
	var $content;
	var $comment;
	var $error;
	var $encoding;
	var $originalName;
	
	var $internalfields=array();
	
	function insert($silent=false)
	{
		
		$this->status = "ok";
		$this->writefile($silent); // Function writefile doesn't have a parameter in its declaration, is that correct?
	}
		
	function delete()
	{
		$this->status = "deleted";
		$this->content = "";
		
		$this->writefile();
		
		// delete current
		$path = $this->currentPath();
		unlink($path);
		echotime('unlink');
		
	}
	
	function protect()
	{
		if (!$this->status=="ok") {swException('Protection of not ok record'); $this->error = 'Protection of not ok record'; return;}
		$this->status = "protected";
		$this->writefile();
	}

	function unprotect()
	{
		$this->status = "ok";
		$this->writefile();
	}
	
	function lookupLocalname()
	{
		global $lang;
		if (!stristr($this->name,'/') && $lang)
		{
			// try to find local version
			$name0 = $this->name;
			$this->name .= '/'.$lang;
			//echotime($this->name);
			$this->lookupName();
			if (($this->persistance || $this->revision) && $this->status != 'deleted')
				return $this->name;
				
			// didn't work
			$this->name = $name0;
			
		}
		$this->lookupName();
		if ($this->persistance || $this->revision)
			return $this->name;

	}
	
	function lookupName()
	{
		global $db;
		
		$c = $this->currentPath();
		if (file_exists($c) && (!isset($_GET['refresh']) || !$_GET['refresh']))
		{
			$this->persistance = $c;
			$this->open();
			
			// bug character 146 not displayed in UTF 8
			$t146 = utf8_encode(chr(146));
			$this->name = str_replace($t146, "'", $this->name);
			$this->comment = str_replace($t146, "'", $this->comment);
			$this->content = str_replace($t146, "'", $this->content);
			// bug character 146 not displayed in UTF 8
			$t156 = utf8_encode(chr(156));
			$this->name = str_replace($t156, "oe", $this->name);
			$this->comment = str_replace($t156, "oe", $this->comment);
			$this->content = str_replace($t156, "oe", $this->content);
			
			// check bit for what reason ever it was not set.
			if ($this->revision > 0 && !$db->currentbitmap->getbit($this->revision))
			{
				$db->currentbitmap->setbit($this->revision);
			}
			
			if ($this->revision >0)
				return $this->name;
			//$this->persistance = '';
			return;
		}
		else
		{
			// check revisions but only if this is the global name
			// ressource limit else
			// some namespaces however are priority
			
			global $name, $swMainName;
			
			if ( stristr($this->name,$swMainName) || stristr($name,$this->name) 
			|| !stristr($this->name,':') || (!stristr($this->name,'/') &&   (stristr($this->name,'template') || stristr($this->name,'image')) ) )
			{
					$revs = swGetAllRevisionsFromName($this->name);
					sort($revs, SORT_NUMERIC); 
					$this->revision = array_pop($revs);
					echotime('revision '.$this->revision);
			}
			
		}
			
		
	}
	
	function readHeader()
	{
		if (!$this->revision) return false;
		$file = swGetPath($this->revision);
		if(!file_exists($file)) return false;
		if (phpversion()>"5.0.0")
			$s = file_get_contents($file, NULL, NULL, 0, 1000);  // enough for header
		else
			$s = file_get_contents($file);
		$this->revision = swGetValue($s,"_revision");
		$this->name = swGetValue($s,"_name");
		$this->user = swGetValue($s,"_user");
		$this->timestamp = swGetValue($s,"_timestamp");
		$this->status = swGetValue($s,"_status");
		$this->comment = swGetValue($s,"_comment");
		$this->encoding = swGetValue($s,"_encoding");
		
		if (strlen($s) <= 512)
		{
			$pos = strpos($s,"[[_ ]]");
			$this->content = substr($s,$pos+strlen("[[_ ]]"));
		}

		return $s;
	}
	
	
	function lookup()
	{
		
		global $db;
		global $lang;
		$this->error = '';
		
		
		if ($this->persistance && $this->revision) // allready open
		{
			if (!preg_match('//u', $this->name)) // check valid utf8 string
			$this->name =  swNameURL($this->name);
			return;
		}
		
		if ($this->persistance)
		{
			$this->open();
			
			// check bit for what reason ever it was not set.
			if ($this->revision > 0 && !$db->currentbitmap->getbit($this->revision))
			{
				$db->currentbitmap->setbit($this->revision);
			}
			
			if (!preg_match('//u', $this->name)) // check valid utf8 string
			$this->name =  swNameURL($this->name);

						
			return;
			
		}
		elseif ($this->revision)
		{
			// we have a serialized version that is faster to read
			$s = '';
			global $swShortIndex;
			if ($swShortIndex && $db->shortbitmap->getbit($this->revision))
			{
				@fseek($swShortIndex, 512*($this->revision-1));
				$s = @fread($swShortIndex,512);
			}
			
			$hasshort = false;
			if (strstr($s,'[[_ ]]'))
			{
				$hasshort = true;
				$s = rtrim($s);
				
			}
			else
			{
				
				$file = swGetPath($this->revision,true);
				if (file_exists($file))
				{
					$this->persistance = $file;
					$this->open();
					$this->error = '';
					return;
				}
				
				echotime('long '.$this->revision);
				$file = swGetPath($this->revision);
				
				if (!file_exists($file)) 
				{
					$pathlist = explode('/',$file);
					$fname = array_pop($pathlist);
					$this->error ='missing '.$fname;  
					return;
				}
				if ($this->name)
						echotime('read '.$this->name);
				$s = swFileGet($file);
			}
			
			
			
			
			$this->error = ''; 
			
			$this->revision = swGetValue($s,"_revision");
			$this->name = swGetValue($s,"_name");
			$this->user = swGetValue($s,"_user");
			$this->timestamp = swGetValue($s,"_timestamp");
			$this->status = swGetValue($s,"_status");
			$this->comment = swGetValue($s,"_comment");
			$this->encoding = swGetValue($s,"_encoding");
			
			$pos = strpos($s,"[[_ ]]");
			$this->content = substr($s,$pos+strlen("[[_ ]]"));
			
			
			
			
			if ($this->encoding != "UTF8")
			{
				// most "latin1" encoding is actuallay windows cp1252
				
				$this->name = cp1252_to_utf8($this->name);
				$this->comment = cp1252_to_utf8($this->comment);
				$this->content = cp1252_to_utf8($this->content);
				
				
				// use the following if it was really latin1
				/*
				$this->name = utf8_encode($this->name);
				$this->comment = utf8_encode($this->comment);
				$this->content = utf8_encode($this->content);
				*/
				
			}
			
			// bug character 146 not displayed in UTF 8
			$t146 = utf8_encode(chr(146));
			$this->name = str_replace($t146, "'", $this->name);
			$this->comment = str_replace($t146, "'", $this->comment);
			$this->content = str_replace($t146, "'", $this->content);
			// bug character 146 not displayed in UTF 8
			$t156 = utf8_encode(chr(156));
			$this->name = str_replace($t156, "oe", $this->name);
			$this->comment = str_replace($t156, "oe", $this->comment);
			$this->content = str_replace($t156, "oe", $this->content);
						
			if (!preg_match('//u', $this->name))// check valid utf8 string
			{
				$this->name =  swNameURL($this->name);
				echotime($s);
			}

			
			
			$this->internalfields = swGetAllFields($this->content, true);
			
			//echotime($this->content);
			
			if ($hasshort) return;
			
			$db->updateIndexes($this->revision);
			if ($db->currentbitmap->getbit($this->revision))
				$this->writecurrent();
			return;
		}
		else
		{
			$this->lookupName();
			if ($this->persistance)
			{
				$this->lookup();
				return;
			}
			elseif ($this->revision)
			{
				$this->lookup();
				
				//create missing current
				$this->writecurrent();
				return;
			}
			else
			{
				$this->content = '';
				$this->error ='No record with this name'; 
				/*
				$this->revision = 0;
				$this->persistance = $this->currentPath();
				$this->save();
				echotime('zero '.$this->name);
				*/
				return;
			}
			
		}
		
		$this->error ='No name, no revision'; 
		
	}
	
	function writecurrent()
	{
		global $db;
		swSemaphoreSignal();
		
		// do not write invalid revisions with missing files
		if ($this->status != '')
		{
			$db->updateIndexes($this->revision);
			
			$file = $this->currentPath();
			$rec2 = new swRecord;
			if (file_exists($file))
			{
				$rec2->persistance = $file;
				$rec2->open();
			}
			if ($rec2->revision < $this->revision) // does not already exist
			{
				// must be first to unset old revision if there is.
				echotime('writec '. $this->name.' '.$this->revision);
				$this->internalfields = swGetAllFields($this->content);
				$this->persistance = $this->currentPath();
				$this->save();
				swFileGet($this->currentPath()); // force cache
			}
			$s = $this->source();
			global $swRamdiskPath;
			if (strlen($s) <= 512 && $swRamdiskPath == '')
			{
				echotime('writeshort '. $this->name.' '.$this->revision);
				global $swRoot;
				$s = substr($s,0,512);
				$s = str_pad($s,512,' ');
				$path = $swRoot.'/site/indexes/short.txt';
				$fpt = fopen($path,'c');
				@fseek($fpt, 512*($this->revision-1));
				@fwrite($fpt, $s);
				@fclose($fpt);
				$db->shortbitmap->setbit($this->revision);
			}
			else
			{
				$cp = swGetPath($this->revision,true);
				$this->persistance = $cp;
				$this->save();
				swFileGet($cp); // force cache
			}
			
			
		}
		swSemaphoreRelease();
	}
	
	function source()
	{
		return "[[_revision::$this->revision]]"
		. "\n[[_name::$this->name]]"
		. "\n[[_user::$this->user]]"
		. "\n[[_timestamp::$this->timestamp]]"
		. "\n[[_status::$this->status]]"
		. "\n[[_comment::$this->comment]]"
		. "\n[[_encoding::$this->encoding]]"
		. "\n[[_ ]]$this->content";
	}
	

	function writefile()
	{
		
		global $db;
		$db->init();
		echotime('writefile '. $this->name);
		swSemaphoreSignal();
		echotime('oldrev '. $this->revision);
		if ($this->revision>0)
			$db->currentbitmap->unsetbit($this->revision);
		
		echotime('write '. $this->name);
		
		$this->revision = $db->GetLastRevisionFolderItem()+1;
		
		//never overwrite an existing file!
		while ($this->revision<1 || file_exists(swGetPath($this->revision)))
		{	
			$this->revision++;
		}
		
		$this->timestamp = date("Y-m-d H:i:s",time());
		$this->encoding = "UTF8";
		
		$t = $this->source();
		
		$file = swGetPath($this->revision);
		if ($handle = fopen($file, 'w')) { fwrite($handle, $t); fclose($handle); }
		else { swException('Write error revision $this->revision'); $this->error = 'Write error revision $this->revision';  return; }
				
		
		echotime('newrev '. $this->revision);
		
		$this->internalfields = swGetAllFields($this->content);
		$this->writecurrent();	
		swSemaphoreRelease();

	}
	
	
	
	function history()
	{
		$list = swGetAllRevisionsFromName($this->name);
		$records = array();
		foreach ($list as $item)
		{
			$record = new swRecord;
			$record->revision = $item;
			$records[] = $record;
		}
		return $records;
	}
	
	function md5Name()
	{
		return md5('name='.swNameURL($this->name));
	}
	
	function currentPath()
	{
		global $swRoot;
		
		$file = "$swRoot/site/current/".$this->md5Name().".txt";
		
		return $file;
	}
	
	
	function wikinamespace()
	{
		$i=strpos($this->name,":");
		if ($i>-1)
		{	
			return substr($this->name,0,$i);
		}
		else
			return "";
	}

	function nameshort()
	{	
		$i=strpos($this->name,":");
		if ($i>-1)
		{	
			$n= substr($this->name,$i+1);
		}
		else
			$n= $this->name;
		
		// clean
		if (stristr($n,"_") && $this->status == "")
		{
			return str_replace("_", " ", $n);
		}
		return $n;
		
	}
	
	function simplenamespace()
	{
		return $this->wikinamespace();
	}

	

	function simplename()
	{
		if (!$this->simplenamespace()) return $this->nameshort(); // Main name space
		return $this->simplenamespace().":".$this->nameshort();
	}
	
	
	function getFields()
	{	
		$list = swGetAllLinks($this->content);
		return $list;
	}
	
	function visible()
	{
		if ($this->status == "ok") return true;
		if ($this->status == "protected") return true;
		return false;
	}


} 

$path = $swRoot.'/site/indexes/short.txt';
if (file_exists($path))
	$swShortIndex = fopen($path,'r');


/* This structure encodes the difference between ISO-8859-1 and Windows-1252,
   as a map from the UTF-8 encoding of some ISO-8859-1 control characters to
   the UTF-8 encoding of the non-control characters that Windows-1252 places
   at the equivalent code points. */

$cp1252_map = array(
    "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
    "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
    "\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
    "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
    "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
    "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
    "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
    "\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
    "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
    "\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
    "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
    "\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
    "\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
    "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
    "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
    "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
    "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
    "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
    "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
    "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */

    "\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
    "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
    "\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
    "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
    "\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
    "\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
    "\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
);

function cp1252_to_utf8($str) {
        global $cp1252_map; 
        return  strtr(utf8_encode($str), $cp1252_map);
}



?>
