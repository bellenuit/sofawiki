<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swResumeFunction extends swFunction
{
	var $searcheverywhere = false;
	function info()
	{
	 	return "(name, length, raw) Returns the first words of an article";
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
		$ip = new swImagesParser;
		$ip->ignorelinks = true;
		$lp = new swLinksParser;
		$lp->ignorecategories = true;  
		$lp->ignorelanguagelinks = true;
		$wiki->parsers['tidy'] = $ip;
		$wiki->parsers['image'] = $ip;
		$wiki->parsers['link'] = $lp;
		$wiki->parsers['nowiki'] = new swNoWikiParser;
		$s = $wiki->parse();
		
		//remove links
		$s = preg_replace('#<a (.*?)>(.*?)</a>#','$2',$s);


		if (strlen($s)<$length) return $s;
		
		
		$p = strpos($s."\n","\n",2);
		
		$s = substr($s,0,$p);
		
		if ($p<$length) return $s;
		
		$list = explode(". ",$s);
	
		$t = '';
		
		foreach ($list as $elem)
		{
			if (strlen($t)>$length) return $t.'.';
			
			if ($t != "")
			{
				$test = $t.'. '.$elem;
				if (strlen($test)<=$length*1.5)
					$t .= '. '.$elem;
				else
					return $t.".";
				
			}
			else
			{
				if (strlen($elem)>$length*1.5)
					return substr($elem,0,$length)."...";
				else
					$t = $elem;
			}
		}
		return $t;
	}

}

$swFunctions["resume"] = new swResumeFunction;


?>