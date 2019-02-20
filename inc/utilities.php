<?php 

// This file must be text encoding UTF8 no BOM not to get problems with cookies

if (!defined('SOFAWIKI')) die('invalid acces');


function swSimpleSanitize($s)
{
	// filters out XSS input to be used on variables that should not habe html code or exec code
	$s = str_replace("<","",$s);
	$s = str_replace(">","",$s);
	return $s;
}

function swLengthSort($a,$b)
{
	$sa = strlen($a);
	$sb = strlen($b);
	if ($sa == $sb)
		return ($a > $b);
	else
		return ($sa < $sb); // longer first
}

function swUnescape($s)
{
	  	// escape characters
  		$s = str_replace('<colon>',':',$s);
  		$s = str_replace('<leftsquare>','[',$s);
  		$s = str_replace('<rightsquare>',']',$s);
  		$s = str_replace('<leftcurly>','{',$s);
  		$s = str_replace('<rightcurly>','}',$s);
  		$s = str_replace('<pipe>','|',$s);
  		$s = str_replace('<space>',' ',$s);
  		$s = str_replace('<lt>','&lt;',$s);
  		$s = str_replace('<gt>','&gt;',$s);
  		$s = str_replace('<null>','',$s);
  		$s = str_replace('<backslash>','\\',$s);
  		//$s = str_replace("<gt>",">",$s); 
  		//$s = str_replace("<lt>","<",$s);//security problem
  		return $s;
}


function swGetArrayValue($array,$key,$default='')
{
	if (array_key_exists($key,$array))
		return $array[$key];
	else
		return $default;
}


function swQuerySplit($s)
{
	// split on space, but preserve [[field::value space]]
	return explode(' ',$s);
}

function swStrReplace($pattern, $replace, $s)
{
	// use lowercase, uppercase and titlecase
	$patterns = array();
	$patterns[] = $pattern;
	$patterns[] = strtoupper($pattern);
	$patterns[] = strtolower($pattern);
	$patterns[] = strtoupper(substr($pattern,0,1)).substr($pattern,1);

	foreach ($patterns as $p)
	{
		$r = str_replace($pattern,$p,$replace);
		$s = str_replace($p,$r,$s);
	}
	return $s;	
}

function swGetValue($s, $key, $getArray = false)
{
	// equivalent to fields parser
	
	$pattern = '[['.$key.'::';
	$results = array();
	$result = "";
	$pos = true;
	while ($pos !== FALSE)
	{
		$pos = strpos($s, $pattern);
		if ($pos !== FALSE)
		{
			$pos0 = $pos + strlen($pattern);
			$pos2 = strpos($s,']]',$pos0);
			if ($pos2 !== FALSE)
			{
				$result = substr($s,$pos0, $pos2-$pos0);
				$rs =explode('::',$result);
				foreach($rs as $r)
				{
					if (!$getArray) return $r;
					$results[] = $r;
				}
				
			}
		}
		$s = substr($s,$pos+1); 
		
	}
	if (!$getArray)
	{
		if (count($results)>0)
			return $results[0];
		else
			return '';
	}
	return $results;
	
}


function swGetAllFields($s,$allowinternalvariables=false)
{
	
		preg_match_all("@\[\[([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER);
		
		$result=array();
		
		foreach ($matches as $v)
		{
			
			$val = $v[1]; // link
			//echotime('field0 '.$val);
			// handle fields
			
			if (!$allowinternalvariables)
				if (substr($val,0,1)=='_' && substr($val,0,strlen('_description')) != '_description')
				continue;
			
			
			//echotime('field '.$val);
			if ($delim = stripos($val,'::'))	// we show only values		
			{ 
				$val = $v[1].$v[2].$v[3]; // we use everything 
				
				$fieldstring = substr($val,$delim+2);  
				$key = substr($val,0,$delim);
				
				$fields = explode('::',$fieldstring);
				
				$t = '';
				foreach ($fields as $f)
				{
					$result[$key][]=$f;
				}

				
			}
			elseif(substr(strtolower($val),0,strlen('category:')) == 'category:')
			{
				$result['_category'][]=substr($v[1],strlen('category:'));
			}
			else
			{
				$result['_link'][]=$v[1];
			}
			
		}
		
		preg_match_all("@\{\{(.*?)\}\}@", $s, $matches, PREG_SET_ORDER);
		foreach ($matches as $v)
		{
			if (substr($v[1],0,1) != '{') // not use args
			{
				$fields = explode('|',$v[1]);
				$result['_template'][]=$fields[0];
			}
			
		}
		return $result;

}

function swReplaceFields($s,$fields,$options)
{
	$fields0 = swGetAllFields($s);
	
	// options not implemented. default is replace existing fields, or create it if it exists
	
	
	
	if (stristr($options,'REMOVE_OLD'))
	{
		foreach($fields0 as $key=>$value)
		{
			if (substr($key,0,1) != '_')
				$s = preg_replace('@\[\['.$key.'::([^\]\|]*)\]\]@','[[::]]',$s);
		}
		
	}
	
	

	
	foreach($fields as $key=>$value)
	{
		// replace existing fields, with one or more instance
		$s = preg_replace('@\[\['.$key.'::([^\]\|]*)\]\]@','[[::]]',$s);
		// add new field
		if (is_array($value))
			$s .= PHP_EOL.'[['.$key.'::'.join('::',$value).']]';
		else
			$s .= PHP_EOL.'[['.$key.'::'.$value.']]';
	}
	
	
	// remove field lines that are empty;
	$s = str_replace("[[::]]\n\r",'',$s);
	$s = str_replace("[[::]]\r\n",'',$s);	
	$s = str_replace("[[::]]\n",'',$s);	
	$s = str_replace("[[::]]\r",'',$s);
	$s = str_replace("[[::]]",'',$s);
		
	return $s;
	
}


function swGetAllLinks($s)
{

	preg_match_all("@\[\[([^\]\|]*)([\|]?)(.*?)\]\]@", $s, $matches, PREG_SET_ORDER);
	foreach ($matches as $v)
	{	
		$result[] = $v[1]; 
	}
	return $result;
	
}



function swValidate($v,$invalidcharacters)
{
	$l = strlen($invalidcharacters);
	$i=0;
	for ($i==0;$i<$l;$i++)
	{
		if (strstr($v,substr($invalidcharacters,$i,1))) return false;
	}
	return true;
}


function swNameURL($name)
{
	if (!is_string($name))
	{	
		if (is_array($name))
		{
			debug_print_backtrace();
			exit;
		}
		$name = strval($name);
	}
	
	$s = $name;
	
	$s = swUnescape($s);
	
	// replace spaces by hyphen 
	$s = str_replace(' ','-',$s);
	$s = str_replace('_','-',$s);
	// replace special characters
	$s = str_replace('´','-',$s);
	$s = str_replace('"','-',$s);
	$s = str_replace('&#039;','-',$s);
	$s = str_replace('&amp;','-',$s);
//	$s = str_replace('&quot;','-',$s);
	$s = str_replace("'",'-',$s);
	// replace german umlauts
	$s = str_replace('ä','ae',$s);
	$s = str_replace('æ','ae',$s);
	$s = str_replace('ö','oe',$s);
	$s = str_replace('ø','oe',$s);
	$s = str_replace('œ','oe',$s);
	$s = str_replace('ü','ue',$s);
	$s = str_replace('Ä','Ae',$s);
	$s = str_replace('Ö','Oe',$s);
	$s = str_replace('Ü','Ue',$s);
	
	// replace special characters allowed
	$s = str_replace('?','-',$s);
	$s = str_replace('!','-',$s);
	$s = str_replace('.','-',$s); 
	$s = str_replace('«','-',$s);
	$s = str_replace('»','-',$s);
	$s = str_replace('=','-',$s);
	
	// replace not allowed
	$s = str_replace('>','-',$s); //new
	$s = str_replace('<','-',$s); //new
	$s = str_replace('[','-',$s); //new
	$s = str_replace(']','-',$s); //new
	$s = str_replace('^','-',$s); //new
	$s = str_replace('~','-',$s); //new
	$s = str_replace('::','-',$s); //new
	

	$swNameURLstrtable = array('à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
	'ç'=>'c',
	'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
	'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
	'ñ'=>'n',
	'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
	'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y',
	'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
	'Ç'=>'C',
	'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
	'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
	'Ñ'=>'N',
	'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
	'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y');

	
	$s = strtr($s,$swNameURLstrtable);
	
	// now replace anything else left with hyphen
	
	$s = preg_replace("@[^-a-zA-Z0-9/:.-_]@","-",$s);
	
    // Capitalize First letter
	$s = strtoupper(substr($s,0,1)).substr($s,1);
	// Capitalize First letter if there is a Name Space
	if (stristr($s,':'))
	{
		$p = strpos($s,':');
		$s = substr($s,0,$p+1).strtoupper(substr($s,$p+1,1)).substr($s,$p+2);
	}
	
	$s = strtolower($s);
	
	// Remove double --
	while (stristr($s,'--'))
		$s = str_replace('--','-',$s);

		
	if ($s == "-") return $s;
	// remove trailing -
	while (substr($s,-1)=="-")
		$s = substr($s,0,-1);
	
	// remove trailing also on languages subpages
	while (stristr($s,'-/'))
		$s = str_replace('-/','/',$s);
		
	return $s;
	
}


function swFileGetContents($url)
{
       	$c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_URL, $url);
        $contents = curl_exec($c);
        
        //print_r($c);
        

        if ($contents)
        {
        	curl_close($c);
        	return $contents;
        }
        else 
        {
            echotime('swFileGetContents error: '.curl_error($c));
            curl_close($c);
            return false;
         }
}

// field writer uses rows separated by [[]] and having only one value by field

function swRowToString($arr)
{
	$result = '';
	foreach($arr as $primary=>$list)
	{
		//unescape tags
		$result .= '[[_primary::'.$primary.']]'.PHP_EOL;
		foreach($list as $key=>$value)
		{
			$value = str_replace(']','<rightsqare>',$value);
			$value = str_replace('[','<leftsquare>',$value);
			$value = str_replace('::','<colon><colon>',$value);
		
			$result .= '[['.$key.'::'.$value.']]'.PHP_EOL;
		}
		$result .= '[[]]'.PHP_EOL;
	}
	
	return $result;
}

function swWriteRow($handle, $arr)
{
	fwrite($handle,swRowToString($arr));
}

function swReadField($handle)
{
	$result = array();
	$state = 'S';
	while(!feof($handle))
	{
		$c = fread($handle,1);
		
		switch($state)
		{
			case 'S':	$key = ''; $value = '';
						switch($c)
						{ 	case '[': $state = 'o'; break;
							default: ;
						} break;
			case 'o':	switch($c)
						{ 	case '[': $state = 'k'; break;
							default: $state = 'S';
						} break;
			case 'k':	switch($c)
						{ 	case ':': $state = 'h'; break;
							case ']': $state = 'f'; break;
							default:  $key .= $c;
						} break;
			case 'h':	switch($c)
						{ 	case ':': $state = 'v'; break;
							case ']': $state = 'S'; break;
							default:  $state = 'S';
						} break;
			case 'v':	switch($c)
						{ 	case ':': $state = 'vh'; $value .= $c; break;
							case ']': $state = 'c'; break;
							default:  $value .= $c;
						} break;
			case 'vh':	switch($c)
						{ 	case ':': $state = 'S'; break; // we do not want multiple values
							case ']': $state = 'S'; break;
							default:  $state = 'v'; $value .= $c;
						} break;
			case 'c':	switch($c)
						{ 	case ']': $result[$key] = $value; $state = 'S';
							default:  $state = 'S';
						} break;
			case 'f':	switch($c)
						{ 	case ']': return $result;
							default:  $state = 'S';
						} break;
			}
	}
	return $result;


}


?>
