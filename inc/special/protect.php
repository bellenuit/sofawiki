<?php

if (!defined('SOFAWIKI')) die('invalid access');

if ($action == 'protect' && $user->hasright('protect', $wiki->namewithoutlanguage()))
{
	$name = $wiki->namewithoutlanguage();
	$wiki->name = $name;
	$wiki->lookup();
	$wiki->user = $user->name;
	$wiki->protect();
	$swParsedName = $wiki->name;
	$swStatus = 'Protected '.$wiki->name;
	
	foreach($swLanguages as $ln)
	{
		$wiki2 = new swWiki;
		$wiki2->name = $name.'/'.$ln;
		$wiki2->user = $user->name;
		$wiki2->lookup();
		if ($wiki2->status == 'ok') 
		{
			$wiki2->protect();
			$swStatus.=' /'.$ln;
		}
	}
}
elseif ($action == 'unprotect' && $user->hasright('protect', $wiki->namewithoutlanguage()))
{
	$name = $wiki->namewithoutlanguage();
	$wiki->name = $name;
	$wiki->lookup();
	$wiki->user = $user->name;
	$wiki->unprotect();
	$swParsedName = $wiki->name;
	$swStatus = 'Unprotected '.$wiki->name;
	
	foreach($swLanguages as $ln)
	{
		$wiki2 = new swWiki;
		$wiki2->name = $name.'/'.$ln;
		$wiki2->user = $user->name;
		$wiki2->lookup();
		if ($wiki2->status == 'protected') 
		{
			$wiki2->unprotect();
			$swStatus.=' /'.$ln;
		}
	}
}		
	

?>