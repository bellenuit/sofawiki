<?php
	
/**
 *	Contains the swFunction parent class
 *  
 */


if (!defined("SOFAWIKI")) die("invalid acces");


/**
 *	Stub class for functions
 *  
 *  info() returns human visible information about the function
 *  arity() number of arguments. Only functions with arity > 0 can be used for calc and query.
 *  Use only consistent functions for query
 *  dowork($args) the actual code
 */

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

