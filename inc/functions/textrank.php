<?php

if (!defined("SOFAWIKI")) die("invalid acces");

/* TextRank paper from Rada Mihalcea and Paul Tarau */


class swTextRankNameFunction extends swFunction
{
	var $searcheverywhere = false;
	function info()
	{
	 	return "(name, length) Returns the most significant sentences of an article";
	}

	
	function dowork($args)
	{
		
		$s = $args[1];	
		if (isset($args[2])) $length = floatval($args[2]); else $length = 255;	
		if (isset($args[3])) $raw = $args[3]; else $raw = 0;
		
		global $swTranscludeNamespaces;
		$transcludenamespaces = array_flip($swTranscludeNamespaces);

		if (!$this->searcheverywhere && stristr($s,':') 
		&& !array_key_exists('*',$transcludenamespaces))
		{
			$fields = explode(':',$s);
			if (!array_key_exists($fields[0],$transcludenamespaces))
			return '';  // access error
		}
		
		
		$wiki = new swWiki;
		$wiki->name = $s;
		$wiki->revision = NULL;
		$wiki->lookup();
		$wiki->parsers = array();
		
		$s = $wiki->content;
		
		$tr = new swTextRankFunction;
		$args[1] = $s;
		return $tr->dowork($args);
	}

}


class swTextRankFunction extends swFunction
{
	var $searcheverywhere = false;
	function info()
	{
	 	return "(s, length) Returns the most significant sentences of a text";
	}

	
	function dowork($args)
	{
		
		$s = $args[1]; 
		$length = 255;
		if (isset($args[2]) && floatval($args[2])) $length = floatval($args[2]);

		
// add cache here as the function is expensive

// tokenize text into sentences. Separators .?! ignore whitespace and lines without separator

	    $list = preg_split('/([\.?!\n\r])[\s\n\r]/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
	    
	   
	    
	    $sentences = array();
	    for($i=0;$i<count($list);$i+=2)
	    {
	        $t['text'] = $list[$i].@$list[$i+1]; // last separator does not exist
	        $test = preg_split('/[\n\r]/',$t['text']);
	    	if (count($test)>1) $t['text'] = array_pop($test); // ignore lines without character
	    	$t['length'] = strlen($t['text']);
	    	$t['words'] = array_flip(preg_split('/\W/',$list[$i],-1,PREG_SPLIT_NO_EMPTY));
	    	$t['score'] = $t['length']; // random
	    	$t['offset'] = $i;
	    	if (count($t['words'])) $sentences []= $t;
	    	
	    }
	    unset($list);
// make wordlist for each sentence
// define edges for the graph
		for($i=0;$i<count($sentences);$i++) 
		{
			$wt = 0;
			for($j=0;$j<count($sentences);$j++)
			{
				if ($i==$j) continue;
				$w = 0;
				foreach($sentences[$i]['words'] as $w)
					if(isset($sentences[$j]['words'][$w]))
						$w++;
				
				$sentences[$i]['graph'][$j] = $w / log( $sentences[$i]['length'] * $sentences[$j]['length']);
				$wt += $w;
			}
			$sentences[$i]['weights'] = $wt;
		}

		// return '';
	
		
// rank iterate threshold
		$threshold = 0.0001; $e = 1;
		$dump = 0.85;
		$k = 0;
		while ($e > $threshold && $k < 1000 ) 
		{
			$e = 0;
			for($i=0;$i<count($sentences);$i++) 
			{
				$c = 0;
				for($j=0;$j<count($sentences);$j++) 
				{
					if ($i==$j) continue;
					$c += $sentences[$i]['graph'][$j] * $sentences[$j]['score'];
				}
				if ($c) $c /= $sentences[$i]['weights'];
				$c = (1-$dump)+$dump*$c;
			
				$e += abs($sentences[$i]['score']-$c);
				$sentences[$i]['score'] = $c;
			}
			$k++;			
		} 
		
	
// take as much as it takes into length

		//foreach($sentences as $k=>$v) echo $v['score'].' ';
		//secho '<p>';
		
		usort($sentences,function ($a, $b) { return (floatval($a['score'])-floatval($b['score']) < 0); } );
		
		//foreach($sentences as $k=>$v) echo $v['score'].' ';
		
		//print_r($sentences);
		            
    	$result = array();
    	$c = 0;
    	foreach($sentences as $k=>$v)
    	{
    		if ($c > $length/2 && $c+$v['length']>$length)  break; 
    		$result[] = $v;
    		$c += $v['length'];
    	}
    	
    	//print_r($result);
    	
		unset($sentences);

// output chronologically
		usort($result, function ($a, $b) { return (floatval($a['offset'])-floatval($b['offset'])>0); } );
		
		//foreach($result as $k=>$v) echo $v['offset'].' ';
		
		
		
		$s = '';
		foreach($result as $v)
		{
			$s .= $v['text'].' ';
		}
		//echo $s;
		
		return $s;
		
		
	}

}



$swFunctions["textrank"] = new swTextRankFunction;
$swFunctions["textrankname"] = new swTextRankNameFunction;


?>