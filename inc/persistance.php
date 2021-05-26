<?php 

// This file must be text encoding UTF8 no BOM not to get problems with cookies

if (!defined('SOFAWIKI')) die('invalid acces');


class swPersistance 
{ 
    var $persistance;
    function saveString()
    {
	    return serialize(get_object_vars($this));
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
     
        
    /**********************/ 
    function save() 
    { 
        swSemaphoreSignal();
        $s = serialize(get_object_vars($this));
        swUnlink($this->persistance);
        if($f = @fopen($this->persistance,"w")) 
        { 
            @flock($f, LOCK_EX);
            @fwrite($f,$s); 
            @flock($f, LOCK_UN);
            @fclose($f); 
            
        }  
        else echotime("Could not open file ".$this->persistance." for writing, at Persistant::save"); 
        swSemaphoreRelease();
        
    } 
    /**********************/ 
    function open() 
    { 
        $vars = array();
        if (file_exists($this->persistance))
        {
       	 	$s=swFileGet($this->persistance); 
       	 	$vars = @unserialize($s);
       	 	if ($vars === FALSE) unlink($this->persistance); // file is corrupt.
       		if (!$vars) 
       		{
       			echotime("Could not open file ".$this->persistance." for reading, at Persistant::open"); 
       		}
       		
       		 	
        }
        //echotime(print_r($vars,true));
        if (!$vars)  return false;
        if (!is_array($vars))  return false;
        foreach($vars as $key=>$val) 
        {            
			if ($key != 'persistance')
			$this->{$key} =$vars[$key];
        } 
        return true;
    } 
    /**********************/ 
} 

?>