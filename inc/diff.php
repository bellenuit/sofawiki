<?php
	
/**
 *	Provides diff function in text and HTML mode.
 *
 *	Paul's Simple Diff Algorithm v 0.1
 *	© Paul Butler 2007 https://www.paulbutler.org/
 *	May be used and distributed under the zlib/libpng license.
 *	
 *	This code is intended for learning purposes; it was written with short
 *	code taking priority over performance. It could be used in a practical
 *	application, but there are a few ways it could be optimized.
 *	
 *	Given two arrays, the function diff will return an array of the changes.
 *	I won't describe the format of the array, but it will be obvious
 *	if you use print_r() on the result of a diff on some test data.
 *	
 */

if (!defined('SOFAWIKI')) die('invalid acces');

/** 
 *  Returns difference of 2 text
 *
 *  @param $old
 *  @param $new
 */
 
function swDiff($old, $new){
	$maxlen=0;
	foreach($old as $oindex => $ovalue)
	{
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex)
		{
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen)
			{
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}	
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		swDiff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		swDiff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

/**
  *	 Wraps difference of two texts in a pretty HTML code with ins and del tags
  *
  *  @param $old
  *  @param $new
  */

 
function swHtmlDiff($old, $new)
{
	$result = '';
	$diff = swDiff(explode(' ', $old), explode(' ', $new));
	foreach($diff as $k)
	{
		if(is_array($k)) 
		{
			$result .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
					   (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
		}
		else
		{
			$result .= $k . ' ';
		}
	}
	return $result;
}
 
?>