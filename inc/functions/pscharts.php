<?php

if (!defined("SOFAWIKI")) die("invalid acces");


class psChart extends swFunction
{
	function info()
	{
	 	return "(relation, options) creates a PostScript Bar Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		
		$csv = @$args[1];
		$list = explode("\n",$csv);
		$header = $list[0];
		$header = str_replace('"','__',$header); // hack: will be removed
		
		$temp = tempnam(sys_get_temp_dir(), 'MyFileName'.rand(0,1000));
		file_put_contents($temp,$csv);
		
		$r  = new swRelation($header);
		$r->setCSV($temp);
		$t = $r->getTab();
		
		$j = $r->getJSON();
		
		$script = '<script>rpnExtensions =  `r = '.$j.'; rpnTables = {}; rpnTables["_relation"] = r["relation"]`</script>';
						
		return $script."{{tiny-ps|
(_relation) 1 preparechart
".@$args[2]."
showpage
}}";
		
	}
}

$swFunctions["pschart"] = new psChart;
