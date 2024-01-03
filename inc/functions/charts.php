<?php

if (!defined("SOFAWIKI")) die("invalid acces");


function swChartJS($labels, $categories, $columns, $type, $options)
{ 
	if (!count($labels)) return '';

	if (count($labels) != count($columns)) return 'swChart arity error (labels '.count($labels).', columns '.count($columns).')';
	
	$colors = array('blue','green','red','orange','violet','yellow','black');
	$tensions = array(0.3);
	if ($type == "radar") $tensions = array(0);
	$steps = array(0);
	$points = array(0);
	if ($type == "scatter") $points = array(1);
	$lines = array(1);
	$fills = array(0);
	$dashes = array(0);
	$stacks = array(0);
	$charttypes = array('bar');
	$gaps = false;
	$rough = false;
	$width = '100%';
	$opts = array();
	$legend = 'top';
	$aspectratio = 1.76;
	$title = '';
	$haslabels = false;
	$labelset = array();
	$haspng = false;

	
	$low = '';
	$high = '';
	
	$arguments = explode(' ',$options);
	
	$state = '';
	
	foreach($arguments as $a)
	{
		switch ($a)
		{
			case '-aspectratio': $state = 'aspectratio'; break;
			case '-title': $state = 'title'; break;
			case '-labels': $haslabels = true; $state = 'labels'; break;
			case '-colors': $state = 'colors'; break;
			case '-width': $state = 'width'; break;
			case '-tensions': $state = 'tensions'; break;
			case '-fills': $state = 'fills'; break;
			case '-steps': $state = 'steps'; break;
			case '-dashes': $state = 'dashes'; break;
			case '-points': $state = 'points'; break;
			case '-lines': $state = 'lines'; break;
			case '-types': $state = 'types'; break;
			case '-low': $state = 'low'; break;
			case '-high': $state = 'high'; break;
			case '-legend': $state = 'legend'; break;
			case '-gap': $gaps = true ; $state = ''; break;
			case '-png': $haspng = true ; $state = ''; break;
			case '-rough': 	$rough = true; $state = ''; break;
			case '-stacks': $opts['scales']['xAxes']['stacked'] = $opts['scales']['yAxes']['stacked'] = true; $state = 'stacks'; break;
			
			case '': break;
				
			default:
				
			switch ($state)
			{
				case 'aspectratio': $aspectratio = floatval($a); $state = ''; break;
				case 'title': $title = str_replace('_',' ',$a); $state = ''; break;
				case 'labels': $labelset = explode(',',$a); $state = ''; break;
				case 'colors': $colors = explode(',',$a); $state = ''; break;
				case 'tensions': $tensions = explode(',',$a); $state = ''; break;
				case 'steps': $steps = explode(',',$a); $state = ''; break;
				case 'dashes': $dashes = explode(',',$a); $state = ''; break;
				case 'lines': $lines = explode(',',$a); $state = ''; break;
				case 'points': $points = explode(',',$a); $state = ''; break;
				case 'fills': $fills = explode(',',$a); $state = ''; break;
				case 'stacks': $stacks = explode(',',$a); $state = ''; break;
				case 'types': $charttypes = explode(',',$a); $state = ''; break;
				case 'width': $width = $a; $state = ''; break;
				case 'legend': $legend = $a; $state = ''; break;
				case 'low': $low = $a; $state = ''; break;
				case 'high': $high = $a; $state = ''; break;	
				
				default : // ignore;				
			}
		}
	}
	
	
	if ($low) 
	{
		$opts['scales']['y']['suggestedMin'] = $low;
		$opts['scales']['y']['min'] = $low;
	}
	if ($high) 
	{
		$opts['ticks']['suggestedMax'] = $high;
		$opts['ticks']['max'] = $high;
	}
	
	if ($aspectratio)
	{
		$opts['aspectRatio'] = $aspectratio;
	}

	
	
	$datasets = array();
	
	for($i=0;$i<count($labels);$i++)
	{
		$set = array();
		$set['label'] = str_replace('"','',$labels[$i]);
		
		if (isset($opts['scales']['xAxes']['stacked']) && $opts['scales']['xAxes']['stacked'])
		$set['stack'] = $stacks[min($i,count($stacks)-1)];
		
		
		$set['borderColor'] = $colors[min($i,count($colors)-1)];
		$set['backgroundColor'] = $colors[min($i,count($colors)-1)];
		
		if ($type == 'doughnut' | $type == 'pie' )
		{
			$set['borderColor'] = $colors;
			$set['backgroundColor'] = $colors;
		}
		
		
			
		
		if ($fills[min($i,count($fills)-1)])
		{
			$set['fill'] = true;
			$c = alphahex($set['borderColor'],$fills[min($i,count($fills)-1)]);
			$set['backgroundColor'] = $c;
		}
		else
			$set['fill'] = false;

		
		$set['tension'] = $tensions[min($i,count($tensions)-1)];
		
		if ($steps[min($i,count($steps)-1)])
		{
			$set['steppedLine'] = true;
			unset($set['tension']);
		}
		
		if (!$points[min($i,count($points)-1)])
			$set['pointRadius'] = 0;
			
		if (!$lines[min($i,count($lines)-1)])
			$set['showLine'] = false;
	
		if ($dashes[min($i,count($dashes)-1)])
			$set['borderDash'] = array($dashes[min($i,count($dashes)-1)],$dashes[min($i,count($dashes)-1)]);

		$set['spanGaps'] = !$gaps;
		
		if ($rough)
		{
			$set['borderWidth'] = 2;
			$set['rough']['roughness'] = 0.7;
			$set['rough']['bowing'] = 1;
			$set['rough']['fillStyle'] = 'hachure';
			$set['rough']['fillWeight'] = 0.5;
			$set['rough']['hachureAngle'] = -41;
			$set['rough']['hachureGap'] = 4;
			$set['rough']['curveStepCount'] = '9';
			$set['rough']['simplification'] = 0;
			
		}
		
		if ($haslabels && $labelset[$i])
		{
			$set['datalabels']['align'] = 'top';
			$set['datalabels']['anchor'] = 'end';
		}
		else
		{
			$set['datalabels']['display'] = false;
		}

		
		if ($type == 'mixed')
		{
			$set['type'] = $charttypes[min($i,count($charttypes)-1)];
		}

		$set['data'] = $columns[$i];
		
		$datasets[] = $set;
	}
	
	
	$id = md5(rand());
	$result = '<nowiki><script src="inc/skins/chart.min.js"></script><div class="linechart" style="width:'.$width.';"><canvas class="linechart" id="'.$id.'" style=" max-width:700px;"></canvas></div></nowiki>';
	
	if ($rough)
	{
		$result .= '<nowiki><script src="inc/skins/rough.js"></script>';
		$result .= '<nowiki><script src="inc/skins/chartjs-plugin-rough.min.js"></script></nowiki>';
		$result .= '<nowiki><script>Chart.defaults.global.defaultFontFamily = "Comic Sans MS";
Chart.defaults.global.defaultFontSize = 14;</script></nowiki>';		
	}
	
	// put in globals, as json does not seem to work
	if (!$legend == 'none')
		$result .= '<nowiki><script>Chart.defaults.global.legend.display = false; </script></nowiki>';
	else
		$result .= '<nowiki><script>Chart.defaults.global.legend.position = "'.$legend.'"; </script></nowiki>';
	
		
	
	if ($type === 'mixed' ) $type = 'bar';
	
	
	$json = array();
	$json['type'] = $type;
	$json['data']['labels'] = $categories;
	$json['data']['datasets'] = $datasets;
	$json['data']['options'] = $opts;
	$jsonp = array();
	if ($rough) $jsonp []= 'ChartRough';
	if ($haslabels) $jsonp []= 'ChartDataLabels';
	if ($title) $jsonp []= '{ title: { display: true,  text: "'.$title.'"  } }';
	
	//print_r($jsonp);
	$json['plugins'] = '['.join(",",$jsonp).']'; // constants cannot be json_encoded

	
	$json = '{ type: '.json_encode($json['type']).', data: '.json_encode($json['data']).', plugins:'.$json['plugins'].'}';
	
	$result .= '<nowiki><script>
	c = document.getElementById("'.$id.'");
	c.height = c.width / '.$aspectratio.';
	ch = new Chart("'.$id.'", '.$json.' ); </script></nowiki>'.PHP_EOL;
	
	if ($haspng)
	$result .= '<nowiki><script>
	setTimeout(function() {
	a = document.createElement("a"); 
	a.setAttribute("href",c.toDataURL("image/png",1));
	a.setAttribute("target","_blank");
	a.innerHTML = "PNG";
	c.parentNode.appendChild(a);},1000); 
	</script></nowiki>'.PHP_EOL;
	
	
	

	
	
	return $result;
}

class swMixedChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Mixed Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'mixed', $options);

	}

}
$swFunctions["mixedchart"] = new swMixedChart;

class swLineChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Line Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'line', $options);

	}

}

$swFunctions["linechart"] = new swLineChart;

class swBarchart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Bar Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'bar', $options);

	}

}

$swFunctions["barchart"] = new swBarchart;

class swHorizontalBarchart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Horizontal Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'horizontalBar', $options);

	}

}

$swFunctions["horizontalbarchart"] = new swHorizontalBarchart;


class swDoughnutChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Doughnut Chart";
	}

	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'doughnut', $options);

	}

}

$swFunctions["doughnutchart"] = new swDoughnutChart; 


class swPieChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Pie Chart";
	}

	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'pie', $options);

	}

}

$swFunctions["piechart"] = new swPieChart; 



class swRadarChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Radar Chart";
	}

	function arity()
	{
		return 1;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels = explode(',',array_shift($lines));
		$arity = count($labels);
		$columns = array();
		for($i=0;$i<$arity;$i++)
		{
			$columns[$i] = array();	
		}
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=0;$i<$arity;$i++)
			{
				$columns[$i][] = $fields[$i];
			}
		}
		$categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		array_shift($labels);
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'radar', $options);

	}


}


$swFunctions["radarchart"] = new swRadarChart; 

class SwScatterChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Scatter Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels0 = explode(',',array_shift($lines));
		$labels = array();
		$arity = count($labels0);
		$columns = array();
		for($i=1;$i<$arity;$i+=2)
		{
			$columns[($i-1)/2] = array();
			$labels[]=$labels0[$i];
		}
		
		
				
		$k = 0;
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=1;$i<$arity;$i+=2)
			{
				$columns[($i-1)/2][$k]['x'] = $fields[$i];
				$columns[($i-1)/2][$k]['y'] = $fields[$i+1];
			}
			$k++;

		}
		// $categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		$categories = array(); // not used
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'scatter', $options);

	}

}

$swFunctions["scatterchart"] = new swScatterChart; 

class swBubbleChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Bubble Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
		$relation = @$args[1];
		if (!$relation) return '';
		$lines = explode(PHP_EOL,$relation);
		$labels0 = explode(',',array_shift($lines));
		$labels = array();
		$arity = count($labels0);
		$columns = array();
		for($i=1;$i<$arity;$i+=3)
		{
			$columns[($i-1)/3] = array();
			$labels[]=$labels0[$i];
		}
		
		
				
		$k = 0;
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=1;$i<$arity;$i+=3)
			{
				$columns[($i-1)/3][$k]['x'] = $fields[$i];
				$columns[($i-1)/3][$k]['y'] = $fields[$i+1];
				$columns[($i-1)/3][$k]['r'] = $fields[$i+2];
			}
			$k++;

		}
		// $categories = str_replace('"','',array_shift($columns));  // str_replace works in arrays
		$categories = array(); // not used
		
		$options = @$args[2];
		
		return swChartJS($labels, $categories, $columns, 'bubble', $options);

	}

}

$swFunctions["bubblechart"] = new swBubbleChart; 



class swMatrixChart extends swFunction
{

	function info()
	{
	 	return "(relation, options) creates a Matrix Chart";
	}

	function arity()
	{
		return 2;
	}
	
	function dowork($args)
	{
	
				
		$relation = @$args[1];
		if (!$relation) return '';
		
		
		$colors = array('gray','blue','green','red','orange','violet','yellow','black');
		
		$arguments = explode(' ',@$args[2]);
		$state = '';
		foreach($arguments as $a)
		{
			switch ($a)
			{
				case '-colors': $state = 'colors'; break;
				case '': break;
				
				default:
				
				switch ($state)
				{
					case 'colors': $colors = explode(',',$a); break;

					
				}
				
			}
		}
		
		
		$lines = explode(PHP_EOL,$relation);
		$header = explode(',',array_shift($lines));
		$arity = count($header);
	
		for($i=0;$i<$arity;$i++)
		{
			$column[$i] = array();	
		}
		
		$k = 0;
		
		$labelx = array();
		$labely = array();
		
		$allvalues = array();
		$values = array();
				
		foreach($lines as $line)
		{
			if (!$line) continue;
			$fields = explode(',',$line);
			for($i=1;$i<$arity;$i+=3)
			{
				$labelx[]=str_replace('"','',$fields[$i]); 
				$labely[]=str_replace('"','',$fields[$i+1]);
				
				
				$values[str_replace('"','',$fields[$i])][str_replace('"','',$fields[$i+1])] = 	$fields[$i+2];	
				$allvalues[] = $fields[$i+2];
				
			}
			
			
			
			
			$k++;
		}
		
		sort($allvalues,SORT_NUMERIC);
		
		$vmin = array_shift($allvalues);
		$vmax = array_pop($allvalues);
		
		
		
		$labelx = array_unique($labelx);
		$labely = array_unique($labely);
		
		$color1 = array_shift($colors);
		$color2 = array_shift($colors);
		
		if (substr($color1,0,1) != "#")
			$color1 = color_name_to_hex($color1);
			
		if (substr($color2,0,1) != "#")
			$color2 = color_name_to_hex($color2);

						
		$c1 = hex2rgb($color1);
		$c2 = hex2rgb($color2);

		
		$result = '<nowiki><table class="matrixchart">';
		
		$result .= '<tr><td></td>';

		foreach($labelx as $x)
		{
			$result .= '<td class="matrixchartcolumnlabel">'.$x.'</td>';
		}
		$result .= '</tr>';

		
		foreach($labely as $y)
		{
			$result .= '<tr>';
			
			$result .= '<td class="matrixchartrowlabel" >'.$y.'</td>';
			
			foreach($labelx as $x)
			{
				$c = 'red';
				$v = @$values[$x][$y];
				$v = sprintf("%02d",$v);
				
				if ($vmax != $vmin)
					$vcol = ($v - $vmin)/($vmax -$vmin);
				else
					$vcol = 0;
				
				
				$cmix = array();
				
				for($i=0;$i<3;$i++)
				{
					$cmix[$i] = $vcol*$c1[$i] + (1-$vcol)*$c2[$i];
				}
				
				//print_r($cmix);
				
				$c = sprintf("#%02x%02x%02x", $cmix[0], $cmix[1], $cmix[2]);
				
				// echo $c;
				
				$result .= '<td class="matrixchartcell" style="background-color:'.$c.'">'.$v.'</td>';
			}
			
			$result .= '</tr>';
		}
		$result .= '</table></nowiki>';
		return $result;
		
		
	}

}

$swFunctions["matrixchart"] = new swMatrixChart; 

function alphahex($hex,$a)
{
	if (substr($hex,0,1) != "#")
		$hex = color_name_to_hex($hex);		
	$c = hex2rgb($hex);	
	$c = sprintf("#%02x%02x%02x%02x", $c[0], $c[1], $c[2], $a*255);
	return $c;
	
}

function blendhex($hex1,$hex2,$a)
{
	if (substr($hex1,0,1) != "#")
		$hex1 = color_name_to_hex($hex1);		
	if (substr($hex2,0,1) != "#")
		$hex2 = color_name_to_hex($hex2);

	$c1 = hex2rgb($hex1);
	$c2 = hex2rgb($hex2);
	
	
	$cmix = array();
	for($i=0;$i<3;$i++)
		$cmix[$i] = $a*$c1[$i] + (1-$a)*$c2[$i];
	$c = sprintf("#%02x%02x%02x", $cmix[0], $cmix[1], $cmix[2]);
	return $c;	
	 
}

function hex2rgb($hex) {
	$hex = str_replace("#", "", $hex);
	if(strlen($hex) == 3) {
		$r = hexdec(substr($hex,0,1).substr($hex,0,1));
		$g = hexdec(substr($hex,1,1).substr($hex,1,1));
		$b = hexdec(substr($hex,2,1).substr($hex,2,1));
	} 
	else {
		$r = hexdec(substr($hex,0,2));
		$g = hexdec(substr($hex,2,2));
		$b = hexdec(substr($hex,4,2));
	}
	$rgb = array($r, $g, $b);
	return $rgb;
}

// https://stackoverflow.com/questions/2553566/how-to-convert-a-string-color-to-its-hex-code-or-rgb-value

function color_name_to_hex($color_name)
{
    // standard 147 HTML color names
    $colors  =  array(
        'aliceblue'=>'F0F8FF',
        'antiquewhite'=>'FAEBD7',
        'aqua'=>'00FFFF',
        'aquamarine'=>'7FFFD4',
        'azure'=>'F0FFFF',
        'beige'=>'F5F5DC',
        'bisque'=>'FFE4C4',
        'black'=>'000000',
        'blanchedalmond '=>'FFEBCD',
        'blue'=>'0000FF',
        'blueviolet'=>'8A2BE2',
        'brown'=>'A52A2A',
        'burlywood'=>'DEB887',
        'cadetblue'=>'5F9EA0',
        'chartreuse'=>'7FFF00',
        'chocolate'=>'D2691E',
        'coral'=>'FF7F50',
        'cornflowerblue'=>'6495ED',
        'cornsilk'=>'FFF8DC',
        'crimson'=>'DC143C',
        'cyan'=>'00FFFF',
        'darkblue'=>'00008B',
        'darkcyan'=>'008B8B',
        'darkgoldenrod'=>'B8860B',
        'darkgray'=>'A9A9A9',
        'darkgreen'=>'006400',
        'darkgrey'=>'A9A9A9',
        'darkkhaki'=>'BDB76B',
        'darkmagenta'=>'8B008B',
        'darkolivegreen'=>'556B2F',
        'darkorange'=>'FF8C00',
        'darkorchid'=>'9932CC',
        'darkred'=>'8B0000',
        'darksalmon'=>'E9967A',
        'darkseagreen'=>'8FBC8F',
        'darkslateblue'=>'483D8B',
        'darkslategray'=>'2F4F4F',
        'darkslategrey'=>'2F4F4F',
        'darkturquoise'=>'00CED1',
        'darkviolet'=>'9400D3',
        'deeppink'=>'FF1493',
        'deepskyblue'=>'00BFFF',
        'dimgray'=>'696969',
        'dimgrey'=>'696969',
        'dodgerblue'=>'1E90FF',
        'firebrick'=>'B22222',
        'floralwhite'=>'FFFAF0',
        'forestgreen'=>'228B22',
        'fuchsia'=>'FF00FF',
        'gainsboro'=>'DCDCDC',
        'ghostwhite'=>'F8F8FF',
        'gold'=>'FFD700',
        'goldenrod'=>'DAA520',
        'gray'=>'808080',
        'green'=>'008000',
        'greenyellow'=>'ADFF2F',
        'grey'=>'808080',
        'honeydew'=>'F0FFF0',
        'hotpink'=>'FF69B4',
        'indianred'=>'CD5C5C',
        'indigo'=>'4B0082',
        'ivory'=>'FFFFF0',
        'khaki'=>'F0E68C',
        'lavender'=>'E6E6FA',
        'lavenderblush'=>'FFF0F5',
        'lawngreen'=>'7CFC00',
        'lemonchiffon'=>'FFFACD',
        'lightblue'=>'ADD8E6',
        'lightcoral'=>'F08080',
        'lightcyan'=>'E0FFFF',
        'lightgoldenrodyellow'=>'FAFAD2',
        'lightgray'=>'D3D3D3',
        'lightgreen'=>'90EE90',
        'lightgrey'=>'D3D3D3',
        'lightpink'=>'FFB6C1',
        'lightsalmon'=>'FFA07A',
        'lightseagreen'=>'20B2AA',
        'lightskyblue'=>'87CEFA',
        'lightslategray'=>'778899',
        'lightslategrey'=>'778899',
        'lightsteelblue'=>'B0C4DE',
        'lightyellow'=>'FFFFE0',
        'lime'=>'00FF00',
        'limegreen'=>'32CD32',
        'linen'=>'FAF0E6',
        'magenta'=>'FF00FF',
        'maroon'=>'800000',
        'mediumaquamarine'=>'66CDAA',
        'mediumblue'=>'0000CD',
        'mediumorchid'=>'BA55D3',
        'mediumpurple'=>'9370D0',
        'mediumseagreen'=>'3CB371',
        'mediumslateblue'=>'7B68EE',
        'mediumspringgreen'=>'00FA9A',
        'mediumturquoise'=>'48D1CC',
        'mediumvioletred'=>'C71585',
        'midnightblue'=>'191970',
        'mintcream'=>'F5FFFA',
        'mistyrose'=>'FFE4E1',
        'moccasin'=>'FFE4B5',
        'navajowhite'=>'FFDEAD',
        'navy'=>'000080',
        'oldlace'=>'FDF5E6',
        'olive'=>'808000',
        'olivedrab'=>'6B8E23',
        'orange'=>'FFA500',
        'orangered'=>'FF4500',
        'orchid'=>'DA70D6',
        'palegoldenrod'=>'EEE8AA',
        'palegreen'=>'98FB98',
        'paleturquoise'=>'AFEEEE',
        'palevioletred'=>'DB7093',
        'papayawhip'=>'FFEFD5',
        'peachpuff'=>'FFDAB9',
        'peru'=>'CD853F',
        'pink'=>'FFC0CB',
        'plum'=>'DDA0DD',
        'powderblue'=>'B0E0E6',
        'purple'=>'800080',
        'red'=>'FF0000',
        'rosybrown'=>'BC8F8F',
        'royalblue'=>'4169E1',
        'saddlebrown'=>'8B4513',
        'salmon'=>'FA8072',
        'sandybrown'=>'F4A460',
        'seagreen'=>'2E8B57',
        'seashell'=>'FFF5EE',
        'sienna'=>'A0522D',
        'silver'=>'C0C0C0',
        'skyblue'=>'87CEEB',
        'slateblue'=>'6A5ACD',
        'slategray'=>'708090',
        'slategrey'=>'708090',
        'snow'=>'FFFAFA',
        'springgreen'=>'00FF7F',
        'steelblue'=>'4682B4',
        'tan'=>'D2B48C',
        'teal'=>'008080',
        'thistle'=>'D8BFD8',
        'tomato'=>'FF6347',
        'turquoise'=>'40E0D0',
        'violet'=>'EE82EE',
        'wheat'=>'F5DEB3',
        'white'=>'FFFFFF',
        'whitesmoke'=>'F5F5F5',
        'yellow'=>'FFFF00',
        'yellowgreen'=>'9ACD32');

    $color_name = strtolower($color_name);
    if (isset($colors[$color_name]))
    {
        return ('#' . $colors[$color_name]);
    }
    else
    {
        return ($color_name);
    }
}



?>