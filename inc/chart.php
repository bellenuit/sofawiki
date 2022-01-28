<?php

/**
 *	Provides a SVG chart function for swQuery.
 *
 *  Obsolete. Use inc/functions/charts.php
 *  Uses chartist.js
 */
 
 /**
 *	Creates Javascript code to render an SVG on the page.
 *
 *  @param $rows values in swQuery format
 *  @param $options First word: BAR, LINE, PIE. Can have json style options in the following 
 */

function swChart($rows,$options)
{
	
	$charttype = 'LINE';
	// first word of options is chart type. rest is json
	$fields = explode(' ',trim($options));
	if (count($fields)>0)
	{
		$charttype = array_shift($fields);
	}
	$options = join(' ',$fields);
	
	$chartid = 'chart'.rand(0,999999);
	
	$result = '<nowiki>';
	$result .= '<link rel="stylesheet" href="inc/skins/chartist.css">';
	$result .= '<link rel="stylesheet" href="inc/skins/chartist.wiki.css">';
	if (file_exists('site/skins/chartist.css'))
	{
		$result .= '<link rel="stylesheet" href="site/skins/chartist.wiki.css">';
	}
	$result .= '<script src="inc/skins/chartist.min.js"></script>';
	$result .= '<div class="ct-chart ct-minor-seventh" id="'.$chartid.'"></div>';
	$result .= '<script>';
	$result .= 'var data = {';

	$labels = array();
	$values = array();
			
    foreach($rows as $row)
    {
	    $n = count($row);
		   
		$labels[] = "'".array_shift($row)."'";
		
		for ($i=1;$i<$n;$i++) $values[$i][] = array_shift($row);	    	    
    }
    
	$result .= 'labels: ['.join(',',$labels).'],';
	if ($charttype=="PIE")
	{
		$result .= 'series: ';
		$v = array_shift($values);
		$result .= '['.join(',',$v).']';
		$result .= '}; ';		
	}
	else
	{
		$result .= 'series: [';
		foreach($values as $v) $result .= '['.join(',',$v).'],';
		$result = substr($result,0,-1); // cut last ,
		$result .= ']}; ';
	}	
	$result .= 'var options = {'.$options.'}; ';
	
	switch($charttype)
	{
		case 'BAR':  $result .= 'new Chartist.Bar("#'.$chartid.'", data, options); '; break;
		case 'LINE': $result .= 'new Chartist.Line("#'.$chartid.'", data, options); '; break;
		case 'PIE': $result .= 'new Chartist.Pie("#'.$chartid.'", data, options); '; break;		
	}
	
	$result .= '</script>';
    $result .= '</nowiki>';
	return $result;
	
}
	
?>