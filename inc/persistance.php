<?php 

// This file must be text encoding UTF8 no BOM not to get problems with cookies

if (!defined('SOFAWIKI')) die('invalid acces');


#[AllowDynamicProperties]
class swPersistance 
{ 
    var $persistance;
     
    function open() 
    { 
        $vars = array();
        if (file_exists($this->persistance))
        {
       	 	$s=file_get_contents($this->persistance); 
       	 	$vars = @unserialize($s);
       	 	if ($vars === FALSE) unlink($this->persistance); // file is corrupt.
       		if (!$vars) 
       		{
       			echotime('Could not open file '.$this->persistance.' for reading.'); 
       		}
        }

        if (!$vars)  return false;
        if (!is_array($vars))  return false;
        foreach($vars as $key=>$val) 
        {            
			if ($key != 'persistance')
			$this->{$key} =$vars[$key];
        } 
        return true;
    }
       
    function save() 
    { 
        swSemaphoreSignal($this->persistance);
        $s = serialize(get_object_vars($this));
        unlink($this->persistance);
        if($f = @fopen($this->persistance,'w')) 
        { 
            @flock($f, LOCK_EX);
            @fwrite($f,$s); 
            @flock($f, LOCK_UN);
            @fclose($f); 
            
        }  
        else
        {
	        echotime('Could not open file '.$this->persistance.' for writing.'); 
	    }
        swSemaphoreRelease($this->persistance);
        
    } 
    
    function openString($s)
    {
       	$vars = @unserialize($s);
        if (!$vars)  return false;
        if (!is_array($vars))  return false;
        foreach($vars as $key=>$val) 
        {            
			if ($key != 'persistance')
			$this->{$key} =$vars[$key];
        } 
        return true;
    }
    
    function saveString()
    {
	    return serialize(get_object_vars($this));
    }
    

} 

