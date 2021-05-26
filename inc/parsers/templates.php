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
		
		
		// if language version, replace {{}} with non-language version of same page
		if (stristr($wiki->name,'/') && stristr($s, '{{}}'))
		{
			$myname2 = substr($wiki->name,0,-3);
			{
				$wiki2 = new swWiki;
				$wiki2->name = $myname2;
				$wiki2->lookup();
				
				if (trim($wiki2->content) == '')
					$s = str_replace('{{}}'."\n",'',$s);
				else
				{
					$s = str_replace('{{}}',$wiki2->content,$s);
					foreach ($wiki2->internalfields as $k=>$v)
					{
						foreach ($v as $item)
						{
								$wiki->internalfields[$k][] = $item;
						}
					}
				}
			}
		}
		
		$s0 = '';
		
		// simple check unbalanced curly brackets against error 503 when PRCE looks too far
		$matches1 = explode('{{',$s);
		$matches2 = explode('}}',$s);
		if (count($matches1) != count($matches2)) { global $swError; $swError = 'Unbalanced curly brackets'; return; };
		
		
		// search for most inner {{ }} pair, but without {{{ }}} therefore we search a letter before
		while (preg_match("@[^\{]\{\{([^\{])*?\}\}@"," ".$s,$matches) && $s0 != $s)
		{
			$s0 = $s;
			$val0 = substr($matches[0],3,-2);
			
			// we must protect pipe characters inside [[ ]] because they are parsed later in links.php
			
			
			
			
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
					//$v = trim(str_replace("\n","",$v));
					
					
				
				
				$vals[] = $v;
			}
			
			
			$vheader = trim($vals[0]);
			
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
				// remove optional parameters
				for ($i = 1; $i<10; $i++)
				{
					$c = preg_replace("/\{\{\{".$i."\?([^}]*)}}}/","$1",$c);
				}
				
			}
				
			$s = str_replace("{{".$val0."}}",$c,$s);   
		}
				
		$wiki->parsedContent = $s;
		
		
	}

}

$swParsers["templates"] = new swTemplateParser;


?>