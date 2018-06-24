<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swFunction
{
	
		
	function info()
	{
		// stub
		return "generic";
	}
	
	function arity()
	{
		// stub 
		// only functions with arity >= 0 can be used inside calc function
		// must be sure that no external information is used - consistency filter function
		return -1;
	}
	
	function dowork($args)
	{
		// stub
		return " generic";
	}
}

?>