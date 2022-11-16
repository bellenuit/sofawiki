<?php 

if (!defined("SOFAWIKI")) die("invalid acces");


class swTemplateParser extends swParser
{

	var $functions;
	var $transcludenamespaces;
	var $parentwiki;
	
	function info()
	{
	 	return "Handles pages transclusion, functions and templates";
	}
	
	function dowork(&$wiki)
	{
		$s = $wiki->parsedContent;
		
		
				
		global $swFunctions;
		$this->functions = $swFunctions;
		global $swTranscludeNamespaces;
		$this->transcludenamespaces = array_flip($swTranscludeNamespaces);
		
		
		$s0 = '';
		
		// simple check unbalanced curly brackets against error 503 when PRCE looks too far
		$matches1 = explode('{{',$s);
		$matches2 = explode('}}',$s);
		if (count($matches1) != count($matches2)) { global $swError; $swError = 'Unbalanced curly brackets'; return; };
		
		// single { and } are now valid inside templates
		
		while ($s0 != $s) // start to first }}
		{
			
			
			
			$s0 = $s;
			
			$endpos = strpos($s,'}}');  //echo $endpos;
			if ($endpos === FALSE ) continue;
			
			$startpos = strrpos(substr($s,0,$endpos),'{{'); //echo $startpos;
			if ($startpos === FALSE ) continue;
			
			$val0 = substr($s,$startpos+2,$endpos-$startpos-2);
			$valheader = substr($s,0,$startpos);
			$valfooter = substr($s,$endpos+2);
			
			
			
			if ($val0) // last {{ to end
			{
				
				
				
				// should not have {{ nor }}
				
				if (stristr($val0,"{{") || stristr($val0,"}}") ) { $wiki->parsedContent = $val0; return; } 
				
								
				$val0protected = preg_replace("@\[\[([^\]\|]*)([\|]?)(.*?)\]\]@",'[[$1&pipeprotected;$3]]',$val0);
				$verylongvals = explode("|",$val0protected);
				
				$vals = array();
				
				
			
			
				foreach($verylongvals as $v)
				{
					$v = str_replace('&pipeprotected;','|',$v);
					
					if (substr($v,0,2)== "::")
					{
						$v = substr($v,2); 
						//evaluate style inside template
						
						$p = new swStyleParser;
						$p->domakepretty = false;
						$w2 = new swWiki;
						$w2->parsedContent = $v;
						$p->doWork($w2);
						$v = $w2->parsedContent;
					}
					else
						$v = trim($v);
					$vals[] = $v;
				}
				
				// print_r($vals);
				
				$vheader = trim($vals[0]);
				
				// echotime('template '.$vheader);
				
				
				if (array_key_exists($vheader,$this->functions)) // template is function
				{
					$f = $this->functions[$vheader];
					$c = $f->dowork($vals);
									
				}
				elseif(substr($vheader,0,1) == ':') // transclude verbatim from main namespace
				{
					$vheader = substr($vheader,1);
					if (strpos($vheader,':')>0) //not allow, just ignore it
						$s = str_replace("{{".$val0."}}",'',$s);
					else
					{
						$linkwiki = new swWiki();
						$linkwiki->name = $vheader;
						$linkwiki->lookupLocalname();
						$linkwiki->lookup();
						$c = $linkwiki->content;
					}
					
				}
				elseif (strpos($vheader,':')>0) // // transclude verbatim not main namespace
				{
					$ns = strtolower(substr($vheader,0,strpos($vheader,':')));
					$nf = substr($vheader,strpos($vheader,':')+1);
					if ($ns != 'system' && !array_key_exists('*',$this->transcludenamespaces) &&
						 !array_key_exists($ns,$this->transcludenamespaces)) //not allow, just ignore it
							$c = '';
					elseif ($ns == 'system')
					{
						global $lang;
						$c = swSystemMessage($nf,$lang);
					}
					else
					{
						$linkwiki = new swWiki();
						$linkwiki->name = $vheader;
						$linkwiki->lookupLocalname();
						$linkwiki->lookup();
						$c = $linkwiki->content;
						
					}
				}
				else // true template
				{
					$linkwiki = new swWiki();
					$linkwiki->name = 'Template:'.$vheader;
					$linkwiki->lookupLocalname();
					$linkwiki->lookup();
					$c = $linkwiki->content;
					
					for ($i = 1; $i< count($vals); $i++)
					{
						$c = str_replace("{{{".$i."}}}",$vals[$i],$c);
						$c = preg_replace("/\{\{\{".$i."\?([^}]*)}}}/",$vals[$i],$c);
					}
					// remove missing parameters
					// $c = preg_replace("/\{\{\{(\d+)}}}/","&#123;&#123;&#123;$1&#125;&#125;&#125;",$c);
					$c = preg_replace("/\{\{\{(\d+)}}}/",'',$c);
				
				}
				
				// if (!strstr($s,'{{'.$val0.'}}')) echo "something went wrong<p>{{".$val0."}}<p>$s";
				
				
				
				$s = $valheader.$c.$valfooter;
				
			
			}
			
			  
		}
				
		$wiki->parsedContent = $s;
		
		
	}

}

$swParsers["templates"] = new swTemplateParser;


?>