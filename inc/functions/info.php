<?php

if (!defined("SOFAWIKI")) die("invalid acces");

class swVersionFunction extends swFunction
{
	function info()
	{
	 	return "() Shows the Version of SofaWiki";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $swVersion;
	
		return $swVersion;
		
	}	
}

$swFunctions["version"] = new swVersionFunction;

class swCurrentDateFunction extends swFunction
{
	function info()
	{
	 	return "() Shows current date";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		
		return date("Y-m-d",time());
		
	}	
}

$swFunctions["currentdate"] = new swCurrentDateFunction;

class swCurrentUserFunction extends swFunction
{
	function info()
	{
	 	return "() Shows current user";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $user;
		return $user->nameshort();
		
	}	
}

$swFunctions["currentuser"] = new swCurrentUserFunction;


class swCurrentSkinFunction extends swFunction
{
	function info()
	{
	 	return "() Shows current skin";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $skin;
		return $skin;
		
	}	
}


$swFunctions["currentskin"] = new swCurrentSkinFunction;


class swCurrentNameFunction extends swFunction
{
	function info()
	{
	 	return "() Shows current page name";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		
		global $name;
		return $name;
		
	}	
}

$swFunctions["currentname"] = new swCurrentNameFunction;

class swCurrentURLFunction extends swFunction
{
	function info()
	{
	 	return "() Shows URL for the current page";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		
		global $swBaseHref;
		global $name;
		return "$swBaseHref?name=".swNameURL($name);
		
	}	
}

$swFunctions["currenturl"] = new swCurrentURLFunction;


class swCurrentLangFunction extends swFunction
{
	function info()
	{
	 	return "() Shows current language";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		
		global $lang;
		return $lang;
		
	}	
}

$swFunctions["currentlanguage"] = new swCurrentLangFunction;


class swCountPagesFunction extends swFunction
{
	function info()
	{
	 	return "() Shows number of pages";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $db;
		$currentbitmap = $db->currentbitmap;
		return $currentbitmap->countbits();
	}	
}

$swFunctions["countpages"] = new swCountPagesFunction;

class swCountRevisionsFunction extends swFunction
{
	function info()
	{
	 	return "() Shows the number of the revisions";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $db;
		global $db;
		$currentbitmap = $db->currentbitmap;
		return $currentbitmap->length-1;
	}	
}

$swFunctions["countrevisions"] = new swCountRevisionsFunction;



class swInstalledFunctionsFunction extends swFunction
{
	function info()
	{
	 	return "() Returns a list of all installed functions";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $swFunctions;
	
		$lines = array();
		foreach($swFunctions as $k=>$v)
		{
			$lines[$k] = "'''$k''' ".$v->info(); 
		}
		
		ksort($lines);
		return join("\n",$lines);
		
	}	


}

$swFunctions["functions"] = new swInstalledFunctionsFunction;

class swInstalledParsersFunction extends swFunction
{
	function info()
	{
	 	return "() Returns a list of all installed parsers";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $swParsers;
	
		$lines = array();
		foreach($swParsers as $k=>$v)
		{
			$lines[$k] = "'''$k''' ".$v->info(); 
		}
		
		return join("\n",$lines);
		
	}	
}

$swFunctions["parsers"] = new swInstalledParsersFunction;



class swInstalledSkinsFunction extends swFunction
{
	function info()
	{
	 	return "() Returns a list of all installed skins";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		global $swSkins;
	
		$lines = array();
		foreach($swSkins as $k=>$v)
		{
			$lines[$k] = "'''$k''' "; 
		}
		
		return join("\n",$lines);
		
	}	
}

$swFunctions["skins"] = new swInstalledSkinsFunction;

class swInstalledTemplatesFunction extends swFunction
{
	function info()
	{
	 	return "() Returns a list of all installed templates";
	}
	
	function arity()
	{
		return 0;
	}
	
	function dowork($args)
	{
		
		$revisions = swRelationToTable('filter _namespace "template", _name');
		
		
		$lines = array();
		foreach ($revisions as $row)
		{
			if (isset($row['_name']))
			{
				$name = $row['_name'];
				if (is_array($name))
					$name = array_pop($name);
					
		
				$templatename = substr($name,strlen('Template:'));
			
				$lines[$name] = "[[$name]]";
			}
		}
		
		return join("\n",$lines);
	}

}

$swFunctions["templates"] = new swInstalledTemplatesFunction;



?>
