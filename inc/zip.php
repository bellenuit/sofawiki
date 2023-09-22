<?php


/* 

Lazy Zip Archive is for Zip files with a lot of changes. 
It keeps second small journal file for recent changes and updates only when journal is bigger than 1MB.
The classs is incomplet only functions used in Trigram are actually implemented

*/ 

class swZipArchive
{
	var $zip;
	var $journal;
	var $numFiles;
	var $files;
	
	function open ($filename, $flags=0)
	{
		$this->zip = new ZipArchive;
		$this->journal = new ZipArchive;
		
		$this->zip->open($filename, $flags);
		$this->journal->open($filename.'.journal.zip', $flags);
		
		$this->numFiles = $this->zip->numFiles;
				
		return true;
	}
	
	
	function close()
	{
		$jn = $this->journal->filename;
		if (file_exists($jn))
		{
			$this->journal->close();
		
			if (rand(0,100)>90)
			{
				echotime('compact zip');
				$this->journal->open($jn);
				for( $i = 0; $i < $this->journal->numFiles; $i++ )
				{
					$stat = $this->journal->statIndex($i);
					$fn = $stat['name'];
					$s = $this->journal->getFromName($fn);
					$this->zip->addFromString($fn,$s);
				}
				$this->journal->close();
				unlink($jn);
			}
		}
		$this->zip->close();
	}
	
	
	function addFromString($fn,$s)
	{
		// if it exists, add to the journal, if it's new, add to main file (to keep num files)
		$i = $this->zip->locateName($fn);
		if ($i === FALSE)
		{
			return $this->zip->addFromString($fn,$s);
		}
		else
		{
			return $this->journal->addFromString($fn,$s);
		}
	}
	
	function getFromName($fn)
	{
		$jn = $this->journal->filename;
		if (file_exists($jn))
		{
			$i = $this->journal->locateName($fn);
			if ($i === FALSE)
				$s = $this->zip->getFromName($fn);
			else
				$s = $this->journal->getFromName($fn);
		}
		else
			$s = $this->zip->getFromName($fn);
		

		return $s;
	}
	
	function statIndex($n)
	{
		return $this->zip->statIndex($n);
	}
	
}


/*

For PHP 5.2 the built in ZipArchive is used.
For older versions, we use a wrapper class.


*/


if (PHP_VERSION < '5.2.0' || !class_exists("ZipArchive"))
{

	include_once $swRoot.'/inc/zip4.php'; 

	class ZipArchive
	{
		var $zipfile;
		var $filename;
		
		
		const CREATE = 1;
		const OVERWRITE = 2;
		const EXCL = 4;
		const CHECKCONS = 8;
		
		function open($filename, $flags)
		{
			switch ($flags)
			{
				case ZIPARCHIVE::CREATE:
						$this->zipfile = new zipfile;
						$this->filename = $filename;
						return true;
				
				case ZIPARCHIVE::OVERWRITE:
				case ZIPARCHIVE::EXCL:
				case ZIPARCHIVE::CHECKCONS:
			}
			
		}
		
		function addEmptyDir($dirname)
		{
			if (isset($this->zipfile))
			{
				$this->zipfile->add_dir($dirname);
				return true;
			}
			
			return true;
		}
		
		function addFile($filename, $fn) 
		{
			if (isset($this->zipfile))
			{
				if (file_exists($filename))
				{
					$data = file_get_contents($filename);
					$this->zipfile->add_file($data, $fn); 
					return true;
				}
			}
		}
		
		function close() 
		{
			if (isset($this->zipfile))
			{
				$file = fopen($this->filename, "wb");
				$s = $this->zipfile->file();
				$out = fwrite($file, $s);
				fclose($file);
			}
			return true;
		}
		
	
	}
	
	

}

function unzip($file,$destination)
{
	
	$zip = @zip_open($file);
	if (is_resource($zip) || is_array($zip)) // polyfill
	{
	  
	  while ($zip_entry = @zip_read($zip)) 
	  {
		$name = @zip_entry_name($zip_entry);
		if(strpos($name, '.'))
		{
			if (@zip_entry_open($zip, $zip_entry, 'r')) 
			{
			  $fp = fopen($destination.'/'.@zip_entry_name($zip_entry), 'w');
			  $buf = @zip_entry_read($zip_entry, @zip_entry_filesize($zip_entry));
			  fwrite($fp,$buf);
			  @zip_entry_close($zip_entry);
			  fclose($fp);
			}
		}
		else
			@mkdir($destination.'/'.$name);
	  }
	  @zip_close($zip);
	}
}	

if (!function_exists('zip_open'))
{
	function ShellFix($s)
	{
	  return "'".str_replace("'", "'\''", $s)."'";
	}
	
	function zip_open($s)
	{
	  $fp = @fopen($s, 'rb');
	  if(!$fp) return false;
	  
	  $lines = Array();
	  $cmd = 'unzip -v '.shellfix($s);
	  exec($cmd, $lines);
	  
	  
	  
	  $contents = Array();
	  $ok=false;
	  foreach($lines as $line)  
	  {
	    if($line[0]=='-') { $ok=!$ok; continue; }
	    if(!$ok) continue;
	    
	    $length = (int)$line;
	    $fn = trim(substr($line,58));
	    
	    $contents[] = Array('name' => $fn, 'length' => $length);
	  }
	  
	  return
	    Array('fp'       => $fp,  
	          'name'     => $s,
	          'contents' => $contents,
	          'pointer'  => -1);
	}                           
	function zip_read(&$fp)
	{
	  if(!$fp) return false; 
	  
	  $next = $fp['pointer'] + 1;
	  if($next >= count($fp['contents'])) return false;
	 
	  $fp['pointer'] = $next;
	  return $fp['contents'][$next];
	}
	function zip_entry_name(&$res)
	{
	  if(!$res) return false;
	  return $res['name'];
	}                           
	function zip_entry_filesize(&$res)
	{
	  if(!$res) return false;
	  return $res['length'];
	}
	function zip_entry_open(&$fp, &$res)
	{
	  if(!$res) return false;
	
	  $cmd = 'unzip -p '.shellfix($fp['name']).' '.shellfix($res['name']);
	  
	  $res['fp'] = popen($cmd, 'r');
	  return !!$res['fp'];   
	}
	function zip_entry_read(&$res, $nbytes)
	{
	  return fread($res['fp'], $nbytes);
	}
	function zip_entry_close(&$res)
	{
	  fclose($res['fp']);
	  unset($res['fp']);
	}
	function zip_close(&$fp)
	{
	  fclose($fp['fp']);
	}


}


?>