<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swCalcfunction extends swFunction
{

	function info()
	{
	 	return "(term) makes RPN calculation";
	}

	
	function dowork($args)
	{

		// uses query.php
		
		$term = $args[1];
				
		$emptyarray = array();
		$result = swQueryTupleExpression($emptyarray, $term) ;
		
		if (is_array($result))
			$result = 'ERROR : '.array_pop($result); // error
		
		return $result;	
	}

}

$swFunctions["calc"] = new swCalcfunction;


?>