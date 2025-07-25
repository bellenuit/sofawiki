<?php 

// This file must be text encoding UTF8 no BOM not to get problems with cookies

if (!defined('SOFAWIKI')) die('invalid acces');


function array_clone($arr) 
{ 
  if (is_array($arr)) 
	return array_slice($arr, 0, null, true);
  else return array();
 }
 
function p_open($flag) {
    global $p_times;
    if (null === $p_times)
        $p_times = [];
    if (! array_key_exists($flag, $p_times))
        $p_times[$flag] = [ 'total' => 0, 'open' => 0 ];
    $p_times[$flag]['open'] = microtime(true);
}

function p_close($flag)
{
    global $p_times;
    if (isset($p_times[$flag]['open'])) {
        $p_times[$flag]['total'] += (microtime(true) - $p_times[$flag]['open']);
        unset($p_times[$flag]['open']);
    }
}

function p_dump()
{
    global $p_times;
    $dump = [];
    $sum  = 0;
    if(!isset($p_times)) return $dump;
    foreach ($p_times as $flag => $info) {
        $dump[$flag]['elapsed'] = $info['total'];
        $sum += $info['total'];
    }
    foreach ($dump as $flag => $info) {
        $dump[$flag]['percent'] = $dump[$flag]['elapsed']/max($sum,1);
    }
    return $dump;
}



function swSimpleSanitize($s)
{
	$s = preg_replace('/\x00|<[^>]*>?/', '', $s);
	$s = preg_replace('/"/', '&#34;', $s);
    // $s = preg_replace(["'", '"'], ['&#39;', '&#34;'], $s); single quote is not a good idea, single quote is allowed in names
    return $s;
	// https://stackoverflow.com/questions/69207368/constant-filter-sanitize-string-is-deprecated
	// return filter_var($s, FILTER_SANITIZE_FULL_SPECIAL_CHARS); // FILTER_SANITIZE_STRING is depreciated
}


function swHTMLSanitize($s)
{
	// cleans database output to HTML stream
	if ($s == '') return $s;
	$s = str_replace('<','&lt;',$s);
	$s = str_replace('>','&gt;',$s);
	return $s;
}



function swEscape($s)
{
	  	// wikitext relevant characters must be protected when dealing with external data (filter, import)
	  	
	  	// escape characters
  		$s = str_replace(':','<colon>',$s);
  		$s = str_replace('[','<leftsquare>',$s);
  		$s = str_replace(']','<rightsquare>',$s);
  		$s = str_replace('{','<leftcurly>',$s);
  		$s = str_replace('}','<rightcurly>',$s);
  		$s = str_replace('|','<pipe>',$s);
  		//$s = str_replace(' ','<space>',$s);
  		$s = str_replace('&lt;','<lt>',$s);
  		$s = str_replace('&gt;','<gt>',$s);
  		$s = str_replace('\\','<backslash>',$s);
  		
  		return $s;
}

function swFileGetContents($url,$file)
{
       	$c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 0); 
		curl_setopt($c, CURLOPT_TIMEOUT, 0);
        $contents = curl_exec($c);
        
        if ($contents && strlen($contents))
        {
        	curl_close($c);
        	if (isset($file)) file_put_contents($file,$contents);
        	return $contents;
        }
        else 
        {
            echotime('swFileGetContents error: '.curl_error($c));
            curl_close($c);
            return false;
         }
}

function swFileStreamLineGenerator($file, $encoding = 'utf-8')
{
	$handle = fopen($file, "r");
	if ($handle) 
	{
		while (($line = fgets($handle)) !== false) 
		{
			switch($encoding)
			{
				case 'macroman': $line = iconv('macintosh', 'UTF-8', $line); break;
				case 'windowslatin1': $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252', $line); break;
				case 'latin1': $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1'); break;
				default: break;
			}
			yield $line;
		}
		
		
		fclose($handle);
	}
		
}


function swFlattenArray($arr)
{
	$result = array();
	
	if(!is_array($arr)) return $result;
	
	foreach($arr as $k=>$v)
	{
		if (is_array($v))
		{
			$arr2 = swFlattenArray($v);
			foreach($arr2 as $k2=>$v2)
			{
				$result[$k.'/'.$k2] = $v2;
			}	
		}
		else
		{
			$result[$k] = $v;
		}
	}
	
	return $result;
	
}


function swGetAllFields($s,$allowinternalvariables=false)
{
	
// removed regex by state machine, as regex lead to 503 errors on complex pages

	$result=array();
	
	$state = 'start';
	
	$slist = array();
	$clist = array();
	
	for($i=0;$i < strlen($s); $i++)
	{
		$ch = $s[$i];
		
		switch($state)
		{
			case 'start':	switch($ch)
							{
								case '[' : $state = 'sleft'; break;
								case '{' : $state = 'cleft'; break;
								default: ;
							}; break;
			case 'sleft':	switch($ch)
							{
								case '[' : $state = 'square'; $start=$i+1; break;
								default: $state = 'start' ;
							}; break;
			case 'square':	switch($ch)
							{
								case ']' : $state = 'sright'; $end=$i; break;
								default: $state = 'square' ;
							}; break;
			case 'sright':	switch($ch)
							{
								case ']' : $slist[]=substr($s,$start,$end-$start); $state = 'start'; break;
								default: $state = 'square' ;
							}; break;
			case 'cleft':	switch($ch)
							{
								case '{' : $state = 'curly'; $start=$i+1; break;
								default: $state = 'start' ;
							}; break;
			case 'curly':	switch($ch)
							{
								case '}' : $state = 'cright'; $end=$i; break;
								default: $state = 'curly' ;
							}; break;
			case 'cright':	switch($ch)
							{
								case '}' : $clist[]=substr($s,$start,$end-$start); $state = 'start'; break;
								default: $state = 'curly' ;
							}; break;
		}
	}
	$result = array();
// 	echotime('slist'.print_r($slist,true));
	foreach($slist as $elem)
	{
		if ($elem == '') continue;

// 		real fields
		$fields = explode('::',$elem);
		if (count($fields)>1)
		{
			for($i = 1; $i<count($fields); $i++)
			{
				$result[$fields[0]][] = $fields[$i];
			}
		}
//      categories
		elseif(strtolower(substr($elem,0,strlen('category:'))) == 'category:')
		{
			$result['_category'][] = substr($elem,strlen('category:'));
		}
// 		internal links
		else
		{
// 			puts entire link. should we parse for | ?
			$fields = explode('|',$elem);
			$result['_link'][] = $fields[0];
		}

	}
	foreach($clist as $elem)
	{
		$fields = explode('|', $elem);
		$result['_template'][] = $fields[0];
	}
	//echotime('fields'.print_r($result,true));
	return $result;
	
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


function swGetArrayValue($array,$key,$default='')
{
	if (array_key_exists($key,$array))
		return $array[$key];
	else
		return $default;
}

function swGetValue($s, $key, $getArray = false)
{
	// equivalent to fields parser
		
	// reject if invalid key
	if (!preg_match('@[A-Za-z_][^\n\][|#<>{},:+\/]*@',$key))
	
// [A-Za-z_] First letter of field name must be a latin letter or underscore
// [^\s\][|#<>{},:+\/]*? The following character can be all except newline, braquets, pipes, tag, curly, comma and colon

	{
		// echo $key;
		
		if ($getArray)
			return array();
		else
			return '';
	}
	
	$result=array();
	
	$key = preg_quote($key);
	preg_match_all('@\[\['.$key.'::((?:.|\n)*?)\]\]@', $s, $matches, PREG_SET_ORDER);

// \[\[ two opening square braquets
// [^\s\][|#<>{},:+\/]*? The following character can be all except newline, braquets, pipes, tag, curly, comma and colon
// :: The field separator
// ((?:.|\n)*?) anything, but lazy, the first single character is not captured (?:)
// \]\]: Closing brackets

	// print_r($matches);
	
	foreach ($matches as $v)
	{
		
		$value = $v[1];
				
		$values = explode('::',$value);
		
		foreach($values as $v)
		{
			if (!$getArray) return $v;
			
			$result[]=$v;
		}	
		
	}
	if (!$getArray) return '';
	return $result;
	
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

	
	foreach($swNameURLstrtable as $k=>$v)
	{
		$s = str_replace($k, $v, $s);
	}
	
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

function swNumberformat($d,$f)
{		
	
	if (substr($f,-1,1)=='s') 
	{
		// we must handle unicode manually
		preg_match("/%(-)?(\d+)(.\d+)?s/",$f,$matches);
		
		//print_r($matches);
		
		$cmax = substr(@$matches[3],1);  
		
		if ($cmax) $d = mb_substr($d,0,$cmax,'UTF-8');
		
		$c = @$matches[2];
		$pad = str_repeat(' ',max(0,abs($c) - mb_strlen($d,'UTF-8')));
		
		if (@$matches[1] == '-') return $d.$pad;
		return $pad.$d;
	}
	elseif (substr($f,-1,1)=='w')  // wrap to multiple lines
	{
		// we must handle unicode manually
		preg_match("/%(-)?(\d+)(.\d+)?w/",$f,$matches);
		
		$cmax = substr(@$matches[3],1); 
		$c = @$matches[2]; 

		$lines = array();
		$line = '';
		$d = preg_replace('~\R~u', PHP_EOL, $d); // unify line endings
		foreach(explode(' ',$d) as $word)
		{
			if ($line) $test = $line.' '.$word;
			else $test = $word;
			
			if (mb_strlen($test,'UTF-8') <= $cmax)
			{
				$line = $test;
			}
			else
			{
				if ($line)
				{
					$lines2 = explode(PHP_EOL,$line);  // can have PHP_EOL in d
					foreach($lines2 as $line2)
					{					
						$pad = str_repeat(' ',max(0,abs($cmax) - mb_strlen($line2,'UTF-8')));
						if (@$matches[1] == '-') 
								$lines[] = $line2.$pad; 
						else
								$lines[] = $pad.$line2; 
					}
					
					$line = '';
				}
				$rest = $word;
				while (mb_strlen($rest,'UTF-8') > $cmax)
				{
					$lines[] = mb_substr($word,0,$cmax,'UTF-8');
					$rest = mb_substr($word,$cmax,NULL,'UTF-8');
					$rest = '';
				}
				$line = $rest;	
			}
		}
		if ($line || !count($lines))
		{
			$lines2 = explode(PHP_EOL,$line);  // can have PHP_EOL in d
			foreach($lines2 as $line2)
			{					
				$pad = str_repeat(' ',max(0,abs($cmax) - mb_strlen($line2,'UTF-8')));
				if (@$matches[1] == '-') 
						$lines[] = $line2.$pad; 
				else
						$lines[] = $pad.$line2; 
			}


		}
		return join(PHP_EOL,$lines);

	}
	elseif ($d === '∞' || $d === '-∞' || $d === '⦵')
	{
		return $d;
	}
	else
	{
		switch(substr($f,-1,1))
		{
			case 'n' :  $f = substr($f,0,-1).'f';
						$s = sprintf($f,floatval($d));
						$sign='';
						if (substr($s,0,1)=='-')
						{
							$s = substr($s,1);
							$sign='-';
						}
						if (stristr($s,'.'))
						{
							$prefix = substr($s,0,strpos($s,'.'));
							$postfix = substr($s,strpos($s,'.'));
						}
						else
						{
							$prefix = $s;
							$postfix = '';
						}
						
						$prefix = strrev($prefix);
						$prefix = chunk_split($prefix,3,' ');
						$prefix = trim(strrev($prefix));
						return $sign.$prefix.$postfix;
			case 'N' :	$f = substr($f,0,-1).'f';
						$s = sprintf($f,floatval($d));
						$sign='';
						if (substr($s,0,1)=='-')
						{
							$s = substr($s,1);
							$sign="-";
						}
						if (stristr($s,'.'))
						{
							$prefix = substr($s,0,strpos($s,'.'));
							$postfix = substr($s,strpos($s,'.'));
						}
						else
						{
							$prefix = $s;
							$postfix = '';
						}
						
						$prefix = strrev($prefix);
						$prefix = chunk_split($prefix,3,"'");
						$prefix = trim(strrev($prefix));
						$s = $sign.$prefix.$postfix;
						return str_replace("-'","-",$s); // strange bug
			case 'p' :	return sprintf(substr($f,0,-1).'f',floatval($d)*100).'%';
			case 'P' :	return sprintf(substr($f,0,-1).'f',floatval($d)*100).' %';
			default  :	return sprintf($f,floatval($d)); 
											
		}
	
	}
	
}



function swQuerySplit($s)
{
	// split on space, but preserve [[field::value space]]
	return explode(' ',$s);
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

function swReplaceFields($s,$fields,$options='')
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
		{
			
			
			
			$s .= PHP_EOL.'[['.$key;
			if (!count($value)) $s .= '::';
			foreach($value as $v)
			{
				$s.= '::'.swEscape($v);
			}
			$s .= ']]';
		}
		elseif($value == '⦵') 
			; // do nothing
		else
			$s .= PHP_EOL.'[['.$key.'::'.swEscape($value).']]';
	}
	
	
	// remove field lines that are empty;
	$s = str_replace("[[::]]\n\r",'',$s);
	$s = str_replace("[[::]]\r\n",'',$s);	
	$s = str_replace("[[::]]\n",'',$s);	
	$s = str_replace("[[::]]\r",'',$s);
	$s = str_replace("[[::]]",'',$s);
		
	return $s;
	
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

function swString2File($s)
{
	$tmpfname = tempnam('/tmp', '');
	file_put_contents($tmpfname,$s);		
	return $tmpfname;
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

function swUnescape($s)
{
	  	
	  	if (!$s) return $s;
	  	
	  	if ($s && strpos($s, '<')===false) return $s;
	  	
	  	// used in expressions and on final renderings
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
  		$s = str_replace('<_>','',$s);
  		//$s = str_replace("<gt>",">",$s); 
  		//$s = str_replace("<lt>","<",$s);//security problem
  		return $s;
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


function swWriteRow($handle, $arr)
{
	fwrite($handle,swRowToString($arr));
}






