<?php

if (!defined("SOFAWIKI")) die("invalid acces");



class swWiki extends swRecord
{
	
	var $originalName;
	var $parsedContent;
	var $displayName;
	var $internalLinks=array();
	var $interlanguageLinks=array();
	var $internalcategories=array();
	var $parsers=array();
	
	
	function parse()
	{
		
		global $lang;
		$this->originalName = $this->name;
		$this->parsedContent = $this->content; 
		$this->displayName = $this->localname($lang);
		
		echotime('parse '.$this->name);
		
		
		// do not parse special namespaces
		switch ($this->wikinamespace())
		{
			case 'Logs':
			case 'Rest':
			case 'User':     
			case 'Template': $s = $this->content;
							 $s = str_replace("&", "&amp;",$s);
							 $s = str_replace("<", "&lt;",$s);
							 $s = str_replace(">", "&gt;",$s);
							 $this->parsedContent = "<pre>$s</pre>";
							 break;
			
			default:  		$docache = false; 
							$nowikis = array();
							foreach ($this->parsers as $key=>$parser)
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
								
								if (strstr($s,'<nowiki>'))
								{
									
									echotime('parse nowiki');
									
									
									$offset = 0;
									
									$pos = strpos($s,'<nowiki>',$offset);
									
									$lines = array();
									
									
									// offset-----pos<nowiki>----pos2</nowiki>
									
									while (true)
									{
										$pos2 = strpos($s,'</nowiki>',$pos);
										if ($pos !== false && $pos2 !== false)
										{
											// before match
											$lines[] = substr($s,$offset,$pos-$offset);
											$v = substr($s,$pos+strlen('<nowiki>'),$pos2-$pos-strlen('<nowiki>'));
											$k = 'NOWIKI('.md5($v).')'; 
											$nowikis[$k] = $v;
											// match
											$lines[] = $k;
											$offset = $pos2+strlen('</nowiki>');
											$pos = strpos($s,'<nowiki>',$offset);
										}
										else
										{
											$lines[] = substr($s,$offset);
											break;
										}
										
									} 
									
									
									if (count($lines)) $s = join('',$lines);
								
								}
								
								// print_r($lines);
								
								$this->parsedContent = $s;
								
								echotime('parse '.$key);
								$parser->dowork($this); 
								//echotime('parse done');
								
								//echo $key.' '.strlen($s);
								
								 //echo '<p>'.$key.': '.$this->parsedContent ;
									
							}
							
							
							
							// put nowiki tags back
							
							$s = $this->parsedContent; 
							
							if (strstr($s,'NOWIKI('))
							{
								$lines = [];
								$offset = 0;
								preg_match_all("/NOWIKI\([0-9a-f]{32}\)/Us", $s, $matches, PREG_SET_ORDER);
								//print_r($matches);
								foreach ($matches as $v)
								{
									$p = strpos($s,$v[0],$offset);
									$lines[] = substr($s,$offset,$p-$offset);
									$lines[] = $nowikis[$v[0]];
									$offset = $p+40;
								}
								
								$lines[] = substr($s,$offset);
								
								$s = join('',$lines);
							}
							
							//echo $s;
							
							/*
							foreach($nowikis as $k=>$v)
							{
								$s = str_replace($k,$v,$s); // lent!
							}
							*/
							$this->parsedContent = $s;
							
							// clean nowiki
							$s = $this->parsedContent;
							//$s = str_replace("<nowiki>","",$s);
							//$s = str_replace("</nowiki>","",$s);	
							$this->parsedContent = $s;
							
							if ($docache)
							{
								$parser=new swPutCacheParser;
								$parser->dowork($this); 
								
							}
								
		}
		
		
		
		echotime('parse end');
		
		
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
	
	function language()
	{	
		
		$s= $this->name;
		$i=strpos($s,"/");
		if ($i>-1)
		{	
			$s= substr($this->name,$i+1);
		}
		else
			$s= '--';
				
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
			$result = swNameURL($this->namewithoutlanguage());
			if (!$result) 
				$result = swNameURL($swMainName);			
			if ($swLangURL)
			{
				if ($subpagelang && $subpagelang != '--')
				{
					$result = $subpagelang.'/'.swNameURL($this->namewithoutlanguage()); // don't double language
				}
				else
				{
					$result = swNameURL($this->namewithoutlanguage()); // don't double language
				}
			}
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