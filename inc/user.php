<?php

if (!defined("SOFAWIKI")) die("invalid acces");


class swUser extends swRecord
{
	var $username;
	var $pass;
	var $ppass;
	var $ipuser;
	var $rights;
	var $session;
	
		
	function exists()
	{
        if (!$this->name) $this->name = "User:".$this->username;
        $this->lookup();
        return $this->visible();
	}
	
	function hasright($action,$name,$now='')
	{
		
		
		$name = swNameURL($name);
		$action = strtolower($action);
		
		global $swAllUserRights;
		
		$allrights = $swAllUserRights.$this->content;
		
		//echo "hasright $action,$name - $allrights<br>";
		
		$rightlist = swGetValue($allrights,'_'.$action,true);
		
		//print_r($rightlist);
		
		foreach($rightlist as $right)
		{
			if (stristr($right,'|'))
			{
				$fields = explode('|',$right);
				$right = $fields[0];
				$startdate = @$fields[1];
				$enddate = @$fields[2];
				if (!$now)
					$now = date('Y-m-d',time());
				if ($startdate != '' && $now < $startdate) continue;
				if ($enddate != '' && $now > $enddate) continue;
				
				echotime($right.$startdate.$enddate);
			}
			
			
			
			if ($name == '?') {return true;}
			
			if ($right == '*') { return true;}// power users
			
			$right = swNameURL($right); // * is not in nameurl
			if (($right == 'main' || $right == '') && !stristr($name,":")) {return true;}// main namespace
			
			if (stripos($name,$right) === 0 ) {  return true;}
			
		}
		if (function_exists('swInternalHasRightHook')) 
		{
			return swInternalHasRightHook($this, $action, $name);
		}
		else
			return false;
	}
		
	
	function nameshort()
	{	
        if (!$this->name) $this->name = "User:".$this->username;
		return str_replace("User:","",$this->name);
	}
	
	function validpassword()
	{
		// open password works, but avoid that hash of password works.
		//print_r($this);
		//print_r($_REQUEST);
		
		
		// masteruser
		if (isset($this->ppass) && $this->ppass != '')
			if ($this->ppass == $this->pass)
				return true;
		
		
		if ($this->ipuser)
			return true;
		
		$this->readHeader(); // force read original name
		
		// other users
		$pwfound = false;
		if (strlen($this->pass) < 30)
		{
			$key = $this->pass;
			//echo $key;
			if (stristr($this->content, "[[_pass::$key]]")) return true;
		}
		
		$kkey = $this->encryptpassword(); 
		if (stristr($this->content, "[[_pass::$kkey]]")) 
		{
			// token not used
			if (stristr($this->content, "[[_token::")) 
			{	
				$s = $this->content;
				$s = preg_replace("/\[\[\_token\:\:([^\]]*)\]\]/","",$s);
				$this->content = $s;
				$this->comment = 'token not used';
				$this->user = '';
				$this->insert();
				return true;		
			}		
			return true;
		}
		if ($kkey == $this->ppass) return true;
		
		
		// lost password
		if (stristr($this->content, "[[_newpass::$kkey]]")) 
		{
			$s = $this->content;
			$s = preg_replace("/\[\[\_pass\:\:([^\]]*)\]\]/","",$s);
			$s = preg_replace("/\[\[\_newpass\:\:([^\]]*)\]\]/","",$s);
			$s .= "[[_pass::$kkey]]";
			$this->content = $s;
			$this->comment = 'new password';
			$this->user = '';
			$this->insert();
			return true;
		
		}
		
				
	}
	
	
	
	function encryptpassword()
	{
		global $db;
		return md5(swNameURL($this->nameshort()).$this->pass.$db->salt);
	}
	
	function altusers()
	{
		$list = swGetValue($this->content,'_altuser',true);
		//print_r($list);
		return $list;
	}
	
	

}



?>