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
		$s0 = $s = $wiki->parsedContent;
		
		
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
				
		
		$opentag = strpos($s,"{{");
		$closetag = strpos($s,"}}",$opentag);
		if ($opentag === FALSE || $closetag === FALSE    // no tag
		|| $opentag > $closetag ) // no matching tag pair
		{
			// we can stop, but use again the fieldsparser, as content has changed.
			$wiki->parsedContent = $s;
			$fp = new swFieldsParser;
			$fp->dowork($wiki);
			//echotime('no tags');
			return;
		}
				
		// now we look for an inner pair
		// if there is an open tag before the next close tag, we must resolve that first
		
		$vstart = $opentag + strlen('{{');
		
		$i=0;
		while (strpos($s,"{{",$vstart) > 0 && strpos($s,"{{",$vstart) < $closetag)
		{
			$i++; if ($i>10) break;
			$vstart = strpos($s,"{{",$vstart)+ strlen('{{');
		}
		$vlength = $closetag - $vstart;
		
		$val0 = substr($s,$vstart,$vlength);
		
		
		$opentag2 = strpos($s,"{{",$vstart);
		
		// replace multiline templates with single line
		$val1 = str_replace(" \n| ","|",$val0);
		$val1 = str_replace(" \n|","|",$val1);
		$val1 = str_replace("\n| ","|",$val1);
		$val1 = str_replace("\n|","|",$val1);
		$val1 = str_replace("\n","",$val1);
		$val1 = trim($val1); //remove leading and trailing space
				
		$vals = explode('|',$val1);
		
		$val = $vals[0];
		$val = trim($val);
		
		//echotime('val '.$val);
		
		if (array_key_exists($val,$this->functions))
		{
			
			$f = $this->functions[$val];
			if ($wiki->wikinamespace()=='Template')
				$f->searcheverywhere = true;
			$c = $f->dowork($vals);
			$s = str_replace("{{".$val0."}}",$c,$s);
			$wiki->parsedContent = $s;
			if (strpos($s,'{{') !== FALSE)
				$this->dowork($wiki);
			//echotime('return '.$val.' ');
			return;
		}
		
		
		$fields = explode(":",$val);
		if (count($fields)>1 && !array_key_exists('*',$this->transcludenamespaces) 
		&& !array_key_exists($fields[0],$this->transcludenamespaces))
		{
			// not allowed, ignore it
			$s = str_replace("{{".$val0."}}",'',$s);
			
		    $wiki->parsedContent = $s;
			if (strpos($s,'{{') !== FALSE)
				$this->dowork($wiki);
			//echotime('return '.$val);
			return;
		}
		
		if (strpos($val,":")===0)
		{
			$val = substr($val,1);
		}
		elseif (strpos($val,":")>0)
		{
			// explicite namespace
		}
		else
		{
			$val = "Template:$val";
		}
		
		global $swSystemSiteValues;
		global $swSystemDefaults;
		
		$linkwiki = new swWiki();
		$linkwiki->name = $val;
		$linkwiki->lookupLocalname();
	
		if ($fields[0]=="System" && array_key_exists($fields[1],$swSystemSiteValues))
		{
			
			$c = $swSystemSiteValues[$fields[1]];
		}
		else
		{
			$linkwiki->lookup();   
			$c = '';
			if ($linkwiki->visible())
			{
				$c = $linkwiki->content;
				if ($fields[0]=="System")
				{
					$swSystemSiteValues[$fields[1]] = $c;
				}
			}
			elseif ($fields[0]=="System")
			{
				if (array_key_exists($fields[1],$swSystemDefaults))
				{
					$c = $swSystemDefaults[$fields[1]];
					$swSystemSiteValues[$fields[1]] = $c;
				}
				else
					$c = substr($fields[1],0,strpos($fields[1],"/"));
			}
		}
		
		
		for ($i = 1; $i< count($vals); $i++)
		{
			$c = str_replace("{{{".$i."}}}",$vals[$i],$c);
		}
			
		
		$s = str_replace("{{".$val0."}}",$c,$s);
		$wiki->parsedContent = $s;
		
		// do the other templates, but only if we changed (infinite loop)
		if ($s != $s0 )
		{
			$this->dowork($wiki);
		}
		
		// remove fields
		$fp = new swFieldsParser;
		$fp->dowork($wiki);
		
		//echotime('templates.php end');
		
	}

}

$swParsers["templates"] = new swTemplateParser;


?>