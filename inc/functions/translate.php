<?php

if (!defined("SOFAWIKI")) die("invalid acces");

include_once $swRoot.'/inc/deepl.php';



class swTranslateFunction extends swFunction
{

	function info()
	{
	 	return "(text, source, target) translate text using Deepl";
	}

	function arity()
	{
		return 3;
	}
	
	function dowork($args)
	{
		global $swRoot;
		
		$text = $args[1];
		$source = $args[2];
		$target = $args[3];
		
		//print_r($args);
		
		// use cache
		
		$cachefile = $swRoot.'/site/cache/'.md5('translate'.$text.$source.$target).'txt';
		if (file_exists($cachefile))
			$result = file_get_contents($cachefile);
		else
			$result = '';
		
		if ($result) return $result;
		
		echotime('new translation');
		
		$result = swTranslate($text,$source,$target);		
		file_put_contents($cachefile, $result);
		return $result;
	}

}

$swFunctions["translate"] = new swTranslateFunction;


?>