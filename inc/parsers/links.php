<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swLinksParser extends swParser
{
	var $ignorecategories;
	var $ignorelanguagelinks;
	
	function info()
	{
	 	return "Handles internal and external links";
	}

	function dowork(&$wiki)
	{
		
		$s = $wiki->parsedContent;
		global $user;
		global $lang;
		
		 // must start with whitespace
		  $s = " ".$s;

		 // external links without markup must not start with [ ' " first special case first line
		 $s = preg_replace('@\n(https?://[-A-Za-z0-9.?&=%/+;,:#_\(\)]+)@', ' <br><a href="$1" target="_blank">$1</a> ', $s);
		 $s = preg_replace('@[^[\[\'\"](https?://[-A-Za-z0-9.?&=%/+;,:#_\(\)]+)@', ' <a href="$1" target="_blank">$1</a> ', $s);
		 
		 // external links with markup
		 $s = preg_replace('@\[(https?://[-A-Za-z0-9.?&=%/+;,#_\(\)]+)]@', ' <a href="$1" target="_blank">$1</a> ', $s);

		 // external links with markup and alternate text
		 $s = preg_replace('@\[(https?://[-A-Za-z0-9.?&=%/+;,#_\(\)]+) (.*?)\]@', ' <a href="$1" target="_blank">$2</a> ', $s); 

		 // mail links without markup must have space before
		  $s = preg_replace('/\s([-a-zA-Z0-9_.]+@[-a-zA-Z0-9_.]+)/', ' <a href="mailto:$1" target="_blank">$1</a> ', $s);
		 
		 // mail links with markup
		 $s = preg_replace('/\[mailto:([-a-zA-Z0-9_.]+@[-a-zA-Z0-9_.]+)\]/', ' <a href="mailto:$1" target="_blank">$1</a> ', $s);

		 // mail links with markup and alternate text
		  $s = preg_replace('/\[mailto:([-a-zA-Z0-9_.]+@[-a-zA-Z0-9_.]+) (.*?)\]/', ' <a href="mailto:$1" target="_blank">$2</a> ', $s);


 		 $s = substr($s,1);	

		// internal links
		preg_match_all("@\[\[([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER);
		
		$categories = array();
		
		foreach ($matches as $v)
		{
			
			$val = $v[1]; // link
			
			// ignore fields
			if (stristr($val,"::")) continue; // internal variables
			
			
			// handle the rest of the links
			if ($v[2] == "|") // pipe
			{
				if ($v[3] != "")
					$label = $v[3];
				else
				{	// pipe trick
					$label = preg_replace("@(.*):+(.*)@", "$2", $val);  // remove namespace
					$label = preg_replace("@(.*)\(+(.*)@", "$1", $label); // remove label
				}
			}
			else
			{
				$label = $val;
				if (substr($label,0,1) == ":") $label = substr($label,1);
			}
			
			$val0 = $val;
			
			$linkwiki = new swWiki();
			
			if (substr($val,0,8) == "Special:")
			{
				
				$linkwiki->name = $val;
				$s = str_replace($v[0], "<a href='".$linkwiki->link('','')."'>$label</a>",$s);
				continue;
			}
			elseif (substr($val,0,9) == "Category:")
			{
				$linkwiki->name = $val;
				$linkwiki->lookupLocalName();
				$linkwiki->lookup(true);
				$v2 = $linkwiki->getdisplayname();
				if (substr($v2,0,9) == "Category:")
					$v2 = substr($v2,9);
				
				$linkwiki = new swWiki();
				$linkwiki->name = $val;
				$linkwiki->lookup();
				if ($linkwiki->visible())
				{
						if ($user->hasright('view', $linkwiki->name))
							$v = "<a href='".$linkwiki->link("",$lang)."'>$v2</a>";
						else
							$v = $v2;
				}
				else
				{
						// show only invalid if right to modify
						
						if ($user->hasright('modify', $linkwiki->name))
							$v = "<a href='".$linkwiki->link("",$lang)."' class='invalid'>$v2</a>";
						else
							$v = $v2;
				}
				$categories[] = "<li>$v</li>";
				$wiki->internalcategories[] = $v;  
				$s = str_replace("[[".$val."]]","",$s);
				
				
			}
			else
			{ 
				
				if (!$this->ignorecategories)
				{
						if (substr($val,0,10) == ":Category:") 
							{ 
								$val = substr($val,1); 
								$linkwiki->name = $val;
								$linkwiki->lookupLocalName();
								$linkwiki->lookup(true);
								$v2 = $linkwiki->getdisplayname();
								if (substr($v2,0,9) == "Category:")
								$v2 = substr($v2,9);
								
								if ($v[2] == "|")
									$label = $v2;
								
							}
				}
					
					
					
						
					$languagefound = false;
					
					
					
					// catch interlanguage links
					global $swLanguages;
					foreach ($swLanguages as $l)
					{
						
						$test = substr($val,0,strlen($l)+1);
						// echo "lang $l $test";
						if ($test=="$l:")
						{
							//echo "lang";
							$val = substr($val,3);
							$wiki->interlanguageLinks[$l] = $val;
							$languagefound = true;
							global $swLangMenus, $lang;
							$w2 = new swWiki;
							$w2->name = $val;
							
							if (!$this->ignorelanguagelinks)
							{
								global $swLangURL;
								if ($swLangURL)
									$swLangMenus[$l] = '<a href="'.$w2->link('view',$l).'">'.swSystemMessage($l,$lang).'</a>';

								else	
									$swLangMenus[$l] = "<a href='".$w2->link("view")."&amp;lang=$l'>".swSystemMessage($l,$lang)."</a>";
							}
	
							
						}
						
					
					}
				
				
				if ($languagefound) 
				{
					$s = str_replace($v[0], "",$s);
					continue;
				}

				
				// add a hook here for custom handler
				
				if (function_exists('swInternalLinkHook')) {
  					$hooklink = swInternalLinkHook($val);  					
  					if ($hooklink)
  					{
  						$s = str_replace($v[0], '<a href="'.$hooklink.'" target="_blank">'.$label.'</a>',$s);
  						continue;
  					}
				}
				
				
				
				if (true)
				{
					// now check if not deleted
					$linkwiki->name = $val;
					
					$linkwiki->lookup();
					
					if ($linkwiki->visible())
					{
						if ($user->hasright('view', $linkwiki->name))
							$s = str_replace($v[0], "<a href='".$linkwiki->link("",$lang)."'>$label</a>",$s);
						else
							$s = str_replace($v[0], $label, $s);
					}
					else
					{
						// maybe there is a local version
						$linkwiki->lookupLocalName();
						if ($linkwiki->visible())
						{
							$linkwiki->name = $val;
							if ($user->hasright('view', $linkwiki->name))
								$s = str_replace($v[0], "<a href='".$linkwiki->link("",$lang)."'>$label</a>",$s);
							else
								$s = str_replace($v[0], $label, $s);
						}
						else
						{
						
						
							if ($user->hasright('modify', $wiki->name))
								$s = str_replace($v[0], "<a href='".$linkwiki->link("",$lang)."' class='invalid'>$label </a>",$s);
							else
								$s = str_replace($v[0], $label,$s);
						}
						
					}
				
				}
				elseif($val=="")
				{
					$s = str_replace($v[0], $label,$s);
				}
				else
				{
					$linkwiki = new swWiki();
					$linkwiki->name = $val;
					
					if ($user->hasright('modify', $wiki->name))
							$s = str_replace($v[0], "<a href='".$linkwiki->link("",$lang)."' class='invalid'>$label</a>",$s);
						else
							$s = str_replace($v[0], $label ,$s);
				}
				
				$wiki->internalLinks[] = $val0;
			}
		}


		if (count($categories) > 0)
		{
			$s .= "<div class='categories' id='categories'><ul>".join("",$categories)."</ul></div>";
		}
		

		$wiki->parsedContent = $s;
		
		
	}

}

$swParsers["links"] = new swLinksParser;


?>