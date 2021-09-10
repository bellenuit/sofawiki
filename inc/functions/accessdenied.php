<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swAccessDenied extends swFunction
{

	function info()
	{
	 	return "() blocks not known user";
	}

	
	function dowork($args)
	{

		global $user;
		
		if (@$user->name == '')
		{
			swLogDeny($_SERVER['REMOTE_ADDR']);
		
			die('invalid acces '.$_SERVER['REMOTE_ADDR']);
		}
		
		return "access denied for not logged in users here";
		
	}

}

$swFunctions["accessdenied"] = new swAccessDenied;


?>