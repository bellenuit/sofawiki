<?php
	
/**
 *	Provides wrappers for some text functions (systemMessage, diff, nowiki).
 */

if (!defined('SOFAWIKI')) die('invalid acces');

/**
 *	Returns system message.
 *
 *  @param $msg
 *  @param $lang
 *  @param $styled
 */

class swSystemMessageFunction extends swFunction
{
	function info()
	{
	 	return "(msg, lang) Returns System Message";
	}
	
	function dowork($args)
	{
		
		$msg = @$args[1];
		$lang = @$args[2];
		$styled = @$args[3];
		
		return swSystemMessage($msg,$lang,$styled);
		
	}	
}

$swFunctions["systemmessage"] = new swSystemMessageFunction;


/**
 *	Returns HTML Diff of two texts.
 *
 *  @param $old
 *  @param $nes
 */

class swDiffFunction extends swFunction
{
	function info()
	{
	 	return "(old, new) returns Diff as HTML";
	}
	
	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		//print_r($args);
		
		$old = @$args[1];
		$new = @$args[2];
	
		
		$result =  swHtmlDiff($old,$new);
		
		//echo $result;
		
		return $result;
	
		
	}	
}

$swFunctions["diff"] = new swDiffFunction;

/**
 *	Returns text with nowiki tag
 *
 *  One some context, the function is easier than the tag.
 *
 *  @param $text
 */


class swNowikifunction extends swFunction
{
	function info()
	{
	 	return "(text) envelopes text in nowiki tags";
	}
	
	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		
		//print_r($args);
		
		$text = @$args[1];
		$result =  '<nowiki>'.$text.'</nowiki>';
		
		//echo $result;
		
		return $result;
	
		
	}	
}

$swFunctions["nowiki"] = new swNowikifunction;

class swEncryptPasswordFuntion extends swFunction
{
	function info()
	{
	 	return "(name,pass) encrypts password";
	}
	
	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		
		
		
		$myname = @$args[1];
		$mypass = @$args[2];
		
		$myuser = new swUser;
		$myuser->pass=$mypass;
		$myuser->username=swNameURL($myname);
		$passencrypted = $myuser->encryptpassword();
		
		
		return $passencrypted;
	
		
	}	
}

$swFunctions["encryptpassword"] = new swEncryptPasswordFuntion;





?>