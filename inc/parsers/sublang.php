<?php 

if (!defined("SOFAWIKI")) die("invalid acces");


class swSublangParser extends swParser
{
	
	function info()
	{
	 	return "Handles sublanguage include";
	}
	
	function dowork(&$wiki)
	{
		$s0 = $s = $wiki->parsedContent;
		
		// if language version, replace {{}} with non-language version of same page
		if (!$wiki->name) return '';
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
		
		$wiki->parsedContent = $s;		
		
	}

}

$swParsers["sublang"] = new swSublangParser;


