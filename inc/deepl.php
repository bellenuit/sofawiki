<?php

// Support for Deepl API Created 1.8.2019
// simple, no error handling, no tag handling

$swTranslateLanguages = array('de','en','es','fr','it');

function swTranslate($text,$source,$target)
{
	
	global $swDeeplKey;
	global $swDeeplFree;
	
		
	// echo "hallo";
	
	if (!isset($swDeeplKey)) return 'Error: DeepL key missing';
	
	$ch = curl_init();

	if (isset($swDeeplFree) && $swDeeplFree)
		curl_setopt($ch, CURLOPT_URL, 'https://api-free.deepl.com/v2/translate');
	else
		curl_setopt($ch, CURLOPT_URL, 'https://api.deepl.com/v2/translate');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string 
	
	$encoded = '';
	$encoded .= 'auth_key='.urlencode($swDeeplKey).'&';
	$encoded .= 'text='.urlencode($text).'&';
	$encoded .= 'source_lang='.$source.'&';
	$encoded .= 'target_lang='.$target;
	
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $encoded);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$result = curl_exec($ch);
	
	//print_r($ch);
	
	//echo "::";
	
	curl_close($ch);
	
	//print_r($result);
	
	
	
	$fields = json_decode($result, true);
	
	// print_r($fields);
	
	if (!is_array($fields)) return 'Error no JSON';
		
	if (array_key_exists('translations',$fields))
	{
		$fields = array_pop($fields['translations']);
		if (array_key_exists('text',$fields))
			return $fields['text'];
	}
	elseif (array_key_exists('message',$fields))
		return 'Error: ' .$fields['message'];
	else
		return 'Error';
	
}
	
?>