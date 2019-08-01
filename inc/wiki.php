<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swWiki extends swRecord
{
	
	var $originalName;
	var $parsedContent;
	var $internalLinks=array();
	var $interlanguageLinks=array();
	var $internalcategories=array();
	var $parsers=array();
	
	
	function parse()
	{
		
		global $lang;
		$this->originalName = $this->name;
		$this->parsedContent = $this->content;
		$this->displayname = $this->localname($lang);
		
		// do not parse special namespaces
		switch ($this->wikinamespace())
		{
			case 'Logs':
			case 'User':     
			case 'Template': $s = $this->content;
							 $s = str_replace("&", "&amp;",$s);
							 $s = str_replace("<", "&lt;",$s);
							 $s = str_replace(">", "&gt;",$s);
							 $this->parsedContent = "<pre>$s</pre>";
							 break;
			
			default:  $docache = false;
					  foreach ($this->parsers as $parser)
								{	
										
										if (is_a($parser,'swCacheParser'))
										{
											$r = $parser->dowork($this); 
											if ($r==2) break;
											if ($r==1) $docache = true; 
											continue;
										}
										
										//echo  $this->parsedContent;
										//print_r($parser); 
										
										// get all <nowiki> tags and replace with magic word
										$s = $this->parsedContent;
										preg_match_all("/<nowiki>(.*)<\/nowiki>/Us", $s, $matches, PREG_SET_ORDER);
										
										$nowikis = array();
										$i = 0;
										foreach ($matches as $v)
										{
											$i++;
											$k = "(NOWIKI$i)"; 
											$nowikis[$k] = $v[0];
											$s = str_replace($v[0],$k,$s);
										}
										
										
										
										
										$this->parsedContent = $s;
										
										
										$parser->dowork($this); 
										
										// put nowiki tags back
										
										$s = $this->parsedContent;
										foreach($nowikis as $k=>$v)
										{
											
											$s = str_replace($k,$v,$s);
										
										}
										$this->parsedContent = $s;
										
										
								}
								
								// clean nowiki
								$s = $this->parsedContent;
								$s = str_replace("<nowiki>","",$s);
								$s = str_replace("</nowiki>","",$s);	
								$this->parsedContent = $s;
								
								if ($docache)
								{
									$parser=new swPutCacheParser;
									$parser->dowork($this); 
									
								}
								
		}
		
		
		
		//print_r($this->interlanguageLinks);
		
		
		return $this->parsedContent;
	}
	
	
	function nameshortwithoutlanguage()
	{	
		$i=strpos($this->name,":");
		if ($i>-1)
		{	
			$s= substr($this->name,0,$i+1);
		}
		else
			$s= $this->name;
		$i=strpos($s,"/");
		if ($i>-1)
		{	
			$s= substr($this->name,0,$i);
		}
		else
			$s= $this->name;
				
		return $s;
		
	}
	
	function namewithoutlanguage()
	{	
		
		$s= $this->name;
		$i=strpos($s,"/");
		if ($i>-1)
		{	
			$s= substr($this->name,0,$i);
		}
		else
			$s= $this->name;
				
		return $s;
		
	}

	function localname($lang)
	{
		$key="namespace".swNameURL($this->wikinamespace());
		$ns = swSystemMessage($key,$lang);
		if ($ns == $key)  // not found
			$ns = $this->wikinamespace();
		$ns = strtoupper(substr($ns,0,1)).substr($ns,1);
		if ($ns)
		{
			$s = $ns.":".$this->nameshort();
		}
		else
			$s = $this->nameshort();
	
		if (substr($s,-3,1) == "/")
		{
			$s = substr($s,0,-3);
		}
		return $s;
	}
	
	function getdisplayname()
	{
	
		$key = "#DISPLAYNAME"; 
		$s = $this->content;
		
		if (substr($s,0,strlen($key))==$key)
		{
			
			if ($p=strpos($s,"\n"))
			{
				
			}
			else
			{
				$p = strlen($s);
			}
			
			
			return substr($s,strlen($key),$p-strlen($key));
		 }
		 else
		 
		 	return $this->name;
	
	}

	
	function link($action, $subpagelang="")
	{

		global $swLanguages;
		if (count($swLanguages)<2)
			$subpagelang="";
		
		$url = swNameURL($this->name);

		
		if ($subpagelang=="--")
		{
			if (substr($url,-3,1)=="/")
			$url = substr($url,0,-3);
		}
		
		if ($action != '')
			$result = "index.php?action=$action&amp;name=$url";
		else
			$result = "index.php?name=$url";
	
		if ($action == 'editview') // force index syntax and not use $swSimpleURL
			$result = "index.php?name=$url";
			
		if ($subpagelang && $subpagelang!= "--")
		{
		if (substr($result,-3,1) == "/")
			$result = substr($result,0,-3)."/".$subpagelang;
		else
			$result = $result."/".$subpagelang;
		}
		
		// 
		global $swSimpleURL;
		global $swLangURL;
		global $swMainName; 
		if ($swSimpleURL && ($action =="" || $action =="view") && substr($this->name,0,1) != "." && substr($this->name,-4) != ".txt")
		{
			$result = swNameURL($this->name);
			if (!$result) 
				$result = $swMainName;			
			if ($swLangURL)
				$result = $subpagelang.'/'.$result;
			if (stristr($this->name,":"))
				$result = './'.$result; // force relative link
				
		}
		
		
		
		return $result;
	
	}
	
	function articlelink($action, $subpagelang="")
	{
		$w2 = new swWiki;
		$w2->name = $this->simplename();
		return $w2->link($action,$subpagelang);
	}
	
	
	function contentclean()
	{
		$s = $this->content;
		$s = htmlspecialchars($s);
		$s = str_replace("&#039;","'",$s);
		$s = str_replace("&amp;#039;","'",$s);
		return $s;
	}
	
}



?>