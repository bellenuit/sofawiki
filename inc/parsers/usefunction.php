<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swUseFunctionParser extends swParser
{
	function info()
	{
	 	return "Handles keyword #USEFUNCTION to apply a function to the entire page";
	}

	
	
	function dowork(&$wiki)
	{
	
		global $swFunctions;
		
		$s = $wiki->parsedContent;
		$key = "#USEFUNCTION"; 
		
		
		if (substr($s,0,strlen($key))==$key)
		{
			
			//echo "usefunction";
			$pos = strpos($s," ") + strlen(" ");
			$pos2 = strpos($s."\n","\n",$pos);
			
			$template = trim(substr($s,$pos,$pos2-$pos));
			$s = substr($s,$pos2);
			$s = trim($s); 
			$vals =[$template,$s];
			
			// print_r($swFunctions);
						
			if (array_key_exists($template,$swFunctions)) // template is function
			{
					

					$f = $swFunctions[$template];
					$s = $f->dowork($vals);

					$wiki->parsedContent = "<nowiki>".$s."</nowiki>";

									
			}
			
		   
		}
		else
		{
			
		}
		
	}

}

$swParsers["usefunction"] = new swUseFunctionParser;


?>