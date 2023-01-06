<?php

if (!defined('SOFAWIKI')) die('invalid acces');


$q = @$_REQUEST['q'];

if(isset($_REQUEST['refresh'])) { $swDebugRefresh  = true;}

$query = 'filter _namespace "rest", _name, id, operationid, namespace, description, parameters, parameterdescriptions, examples, code
select _namespace == "rest"
project drop _namespace';

if ($swSimpleURL)
{
	$swAPIBasePath = $swBaseHrefFolder.'rest/api';  
	$linkpath = $swBaseHrefFolder;
}
else
{
	$swAPIBasePath = $swBaseHrefFolder.'index.php?name=rest/api';  
	$linkpath = $swBaseHrefFolder.'index.php?name=';
}



$list = swRelationToTable($query);

//print_r($list);

// prebuild queries

$list[] = array(
	'_name' => '',
	'id' => '/pages',
	'operationid' => '/pages',
	'namespace' => 'main',
	'description' => 'List of pages',
	'parameters' => '',
	'parameterdescriptions' => '',
	'parameterexamples' => '',
	'code' => 'filter _namespace "main", _name
extend link = "'.$linkpath.'".urltext(_name)
rename _name name
project name, link
order name');

/* $list[] = array(
	'id' => '/foo',
	'namespace' => 'main',
	'operationId' => '/foo',
	'description' => 'Sample list',
	'parameters' => '',
	'parameterdescriptions' => '',
	'examples' => '',
	'code' => 'relation foo, bar
insert 2,"::3"
insert 2,"::3::4"
insert 2,"::"'); */


$list[] = array(
	'_name' => '',
	'id' => '/images',
	'operationid' => '/images',
	'namespace' => 'main',
	'description' => 'List of images',
	'parameters' => '',
	'parameterdescriptions' => '',
	'parameterexamples' => '',
	'code' => 'filter _namespace "image", _name
extend link = "'.$linkpath.'".urltext(_name)
rename _name name
project name, link
order name');


$list[] = array(
	'_name' => '',
	'id' => '/images/{id}/{version}',
	'namespace' => 'main',
	'operationid' => '/images/.+?/.+',
	'description' => 'Resized version of image',
	'parameters' => 'id::version',
	'parameterdescriptions' => 'Filename of image::version string w|w-h|w-h-crop',
	'parameterexamples' => 'minette.jpg::240-160-auto',
	'code' => 'set parameters = replace(path,"/images/","")
set id = regexreplace(parameters,"/.+$","")
set path = regexreplace(parameters,"^.+/","")
filter _namespace "image", _name id
select _name == "Image:".id
update _name = replace(_name, "Image:","")
extend p = path'); 

$list2 = array();
foreach($list as $v)
{
	$list2[$v['id']] = $v;
}
$list = $list2;

krsort($list);

$found = false;
foreach($list as $v)
{
	$k = $v['id'];
	$k2 = str_replace('/','\/',@$v['operationid']);
	if (preg_match('/'.$k2.'/',$q) && $user->hasright('view',@$v['namespace'].':')) // silently deny acess
	{
		$found = true;
				
		$v2 = 'set path = "'.$q.'" '.PHP_EOL.'set basepath = "'.$swAPIBasePath.'" '.PHP_EOL.'set baselink = "'.$linkpath.'" '.PHP_EOL.$v['code'];
		$result = swRelationToTable($v2);
		
		//print_r($result);
		
		if ($swOvertime)
		{
			header('Content-Type: application/json; charset=utf-8'); 
			echo json_encode(array('error'=>'Timeout. Try again after some seconds'));
			exit();
		}
		
		//echo $v2;
		// special case thumbnails
		if ($k == '/images/{id}/{version}')
		{
			if (!count($result)) { header('HTTP/1.0 404 Not Found'); echo 'HTTP/1.0 404 Not Found'; exit();}
									
			$imagename = $result[0]['_name'];
			
			$token = @$_GET['token'];
			$test = md5($imagename.date('Ymd',time()));
			if ($test != $token) { header('HTTP/1.0 403 Not Found'); echo 'HTTP/1.0 403 Forbidden'; exit();}

			$p = $result[0]['p'];
			
			$plist = explode('-',$p);
			if (isset($plist[2])) $crop = $plist[2]; else $crop = '';
			if (isset($plist[1])) $desth = $plist[1]; else $desth = '';
			if (isset($plist[0])) $destw = $plist[0]; else $$destw = '';
						
			$path = swImageDownscale($imagename, $destw, $desth, $crop);
			$filename = str_replace('/site/cache/','',$path);

			if(!$path) 
			{
				$path = '/site/files/'.$imagename;
				$filename = $imagename;
			}
			switch(substr($imagename,-4))
			{
				case '.jpg' : header ('Content-Type: image/jpg'); break;
				case 'jpeg' : header ('Content-Type: image/jpg'); break;
				case '.png' : header ('Content-Type: image/png'); break;
				case '.gif' : header ('Content-Type: image/gif'); break;
			}					
				
			header('Content-Disposition: inline; filename="'.$filename.'"');
			readfile($swRoot.'/'.$path);
			exit();
		}
			
		// explode fields with multiple values	
		$result2 = array();
		foreach($result as $row)
		{
			$row2 = array();
			foreach($row as $key=>$value)
			{
				if (substr($key,-7)=='_concat')
				{
					if ($value)
						$row2[substr($key,0,-7)] = explode('::',$value);
					else
						$row2[substr($key,0,-7)] = array();
				}
				else
				{
					$row2[$key] = $value;
				}
			}
			$result2[] = $row2;
		}		
		
		$json = json_encode($result2);
		break;
	}
}
if (!$found)
{
	if ($q)
	{
		{ header('HTTP/1.0 403 Not Found'); echo 'HTTP/1.0 403 Forbidden '.$q; exit();}
	}
	
	
	$result = array();
	$result['openapi'] = '3.1.0';	
	$result['info']['title'] = $swMainName.' REST API';
	$result['info']['description'] = 'Sofawiki implementation of action rest';
	$result['info']['version'] = $swVersion;
	
	$result['servers'][]['url'] = $swBaseHrefFolder.'rest/api';
	
	
	foreach($list as $v)
	{
		
		$k = $v['id'];
		
		if (!$user->hasright('view',$v['namespace'].':')) continue;
		
		
		$result['paths'][$k]['get']['operationId'] = $v['operationid'];
		$result['paths'][$k]['get']['description'] = $v['description'];
		
		if ($v['parameters'])
		{
			$ps = explode('::',$v['parameters']);
			$ds = explode('::',@$v['parameterdescriptions']);
			$es = explode('::',@$v['parameterexamples']);
			$c = count($ps);
			$pi = 0;
			for($i=0;$i<$c;$i++)
			{
				$plist = array();
				$plist['name'] = $ps[$i];
				$plist['in'] = 'path';
				$plist['required'] = true;
				$plist['description'] = $ds[$i];
				$plist['schema']['type'] = 'string';
				$plist['example'] = $es[$i];
				$result['paths'][$k]['get']['parameters'] []= $plist;
				
			}
		}
				
	}
	
	$json = json_encode($result);
}

if (isset($_REQUEST['q']))
{

	header('Content-Type: application/json; charset=utf-8');
	echo $json;
	$endtime = microtime(true);

	if ($endtime<$swStartTime) $endtime = $swStartTime;
	$usedtime = sprintf('%04d',($endtime-$swStartTime)*1000);

	swLog($username,$name,$action,$q,$lang,$referer,$usedtime,$swError,'','','');
	exit();
}

//  normal special page
$query = '';
$swParsedName = 'Special:REST';
$swParsedContent .= 'The REST API access point is '.$swAPIBasePath.' '.PHP_EOL;  

$swParsedContent .= 'Add paths with pages in the Rest namespace.'.PHP_EOL;
$swParsedContent .= 'The pages must contain the fields id, operationId, namespace, description, parameters, parameterdescription, parameterexamples, code.'.PHP_EOL;
$swParsedContent .= '===Installed Paths (order of evaluation)==='.PHP_EOL;

foreach($list as $v)
{
	$swParsedContent .= '* <nowiki><a href="'.$swAPIBasePath.$v['id'].'" target="_blank">'.$v['id'].'</a>';
	
	$swParsedContent .= '<br><i>'.$v['description'];
	if ($v['_name'])
		$swParsedContent .= '<nowiki> <a href="index.php?action=edit&name='.$v['_name'].'" target="_blank">...</a>';
	$swParsedContent .= '</i></nowiki>';
	$swParsedContent .= PHP_EOL;
}




$swParseSpecial = true;


?>