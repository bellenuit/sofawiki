<?php

if (!defined("SOFAWIKI")) die("invalid acces");


$swFamilyNameDict = NULL;


class swFamilyNameFunction extends swFunction
{

	function info()
	{
	 	return "(name) defines family name for albphabetical order, using excptions";
	}

	
	function dowork($args)
	{

		if (isset($args[1])) $n = $args[1]; else $n = '';	
		
		// check if there is familyname field in page
		
		global $swFamilyNameDict ;
		if (!is_array($swFamilyNameDict)) 
		{
			$q = 'SELECT _name, familyname WHERE familyname';
			$revisions = swFilter($q,'*','query','current');
			foreach($revisions as $tuple)
			{
				$swFamilyNameDict[$tuple['_name']] = $tuple['familyname'];
			}
		}
		
		if (isset($swFamilyNameDict[$n])) return swNameURL($swFamilyNameDict[$n]);
		
		if (stristr($n,' '))
		{
			$fields = explode(' ',$n,3);
			if (count($fields) > 2 && strlen($fields[1]) == 1) // detect one letter middle name 
				$s = $fields[2].' '.$fields[0].' '.$fields[1];
			elseif (count($fields) > 2 && strlen($fields[1]) == 2 && substr($fields[1],1)=='.') // detect one letter middle name 
				$s = $fields[2].' '.$fields[0].' '.$fields[1];
			elseif (count($fields) > 2)
				$s = $fields[1].' '.$fields[2].' '.$fields[0];
			else
				$s = $fields[1].' '.$fields[0];
		}
		else
			$s = $n;
		
		return swNameURL($s);
		
		
	}

}

$swFunctions["familyname"] = new swFamilyNameFunction;

$swFamilyNameDict = NULL;

function swGetFamilyName($n)
{
	$f = new swFamilyNameFunction;
	$args = array('',$n);
	return $f->dowork($args);
}



?>