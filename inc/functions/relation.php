<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swRelationfunction extends swFunction
{

	function info()
	{
	 	return "(source) executes Relation code";
	}
	
	
	function dowork($args)
	{
		
		array_shift($args);
		
		if ($args[0] == '') array_shift($args);
		
		$term = join('|',$args);
		
		//echo $term;
		
		//echo '<pre>'.$term.'</pre>';
		
		//$term = str_replace('\n',PHP_EOL,$term);
		
		$e = new swRelationLineHandler;
		$result = $e->run($term);
						
		return $result;	
	}

}

$swFunctions["relation"] = new swRelationfunction;


?>