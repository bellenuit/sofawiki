<?php 
	
/**
 *	Provides functions to connect to DeepL translation service.
 *
 *  Define your API key in $swDeeplKey or $swDeeplFree (if you use the free API)
 *  Supported languages: de, en, es, fr, it
 *  Basic. Tags and errors are not handled
 */

$swTranslateLanguages = array('de','en','es','fr','it');

/**
 * Translates some text from a source to a target language
 *
 * @param $text
 * @param $source language iso2 code
 * @param $target language iso2 code
 */
	

function swTranslate($text,$source,$target)
{
	global $swDeeplKey;
	global $swDeeplFree;
	
	if (!isset($swDeeplKey)) return 'Error: DeepL key missing';
	
	$ch = curl_init();

	$server = 'https://api.deepl.com/v2/translate';
	if (isset($swDeeplFree) && $swDeeplFree) $server = 'https://api-free.deepl.com/v2/translate';
	curl_setopt($ch, CURLOPT_URL, $server);
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
	
	echo $target;
		
	curl_close($ch);
	
	$fields = json_decode($result, true);
	
	if (!is_array($fields)) return 'Error no JSON';
		
	if (array_key_exists('translations',$fields))
	{
		$fields = array_pop($fields['translations']);
		if (array_key_exists('text',$fields)) return $fields['text'];
	}
	elseif (array_key_exists('message',$fields))
	{
		return 'Error: ' .$fields['message'];
	}
	else
	{
		return 'Error';
	}
	
}

/**
 * Returns character count and character limit of the DeepL translation service.
 */

function swTranslateUsage()
{
	
	global $swDeeplKey;
	global $swDeeplFree;
		
	if (!isset($swDeeplKey)) return '-';
	
	$ch = curl_init();

	$server = 'https://api.deepl.com/v2/translate';
	if (isset($swDeeplFree) && $swDeeplFree) $server = 'https://api-free.deepl.com/v2/translate';
	curl_setopt($ch, CURLOPT_URL, $server);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string 
	
	$encoded = '';
	$encoded .= 'auth_key='.urlencode($swDeeplKey).'&';
	
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $encoded);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$result = curl_exec($ch);
	
	
	curl_close($ch);
		
	$fields = json_decode($result, true);
		
	if (!is_array($fields)) return 'Error no JSON';
		
	if (array_key_exists('character_count',$fields))
	{
		$character_count = $fields['character_count'];
		
		if (array_key_exists('character_limit',$fields))
		{
			$character_limit = $fields['character_limit'];
			
			return $character_count.'/'.$character_limit;
		}
		
	}
		
	
	
}
	
?>