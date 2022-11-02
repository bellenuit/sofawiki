<?php if (isset($_REQUEST['ajax']))
{
	echo $swParsedContent;
		
	exit;

}
?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="content-language" content="<?php echo $lang ?>'">
<base href="<?php echo $swBaseHrefFolder ?>">
<title><?php echo $swMainName.' - '.$swParsedName ?></title>
<meta name="title" content="<?php echo $swMainName.' - '.$swParsedName ?>">
<meta name="description" content="'<?php

if (isset($wiki->internalfields['_description'])) 
{
	$d = array_pop($wiki->internalfields['_description']);
}
else 
{ 
	$rf = new swResumeFunction;
	$d = trim($rf->dowork(array(0,$name, 140, true)));
}
echo trim(str_replace('"',"'",$d));

?>'">
<link rel='stylesheet' href="inc/skins/default.css"/>
<?php echo @$skinstylesheet; ?>
<link rel="apple-touch-icon" sizes="180x180" href="site/skins/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="site/skins/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="site/skins/favicon-16x16.png">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<?php
if ($action=='view')
{
	foreach($swLanguages as $l)
	{		
		if ($l!=$lang)
		{
			if ($swLangURL)
			{
				if (isset($wiki->interlanguageLinks[$l]))
					echo '<link rel="alternate" hreflang="'.$l.'" href="'.$l.'/'.swNameURL($wiki->interlanguageLinks[$l]).'">'.PHP_EOL;
				else
					echo '<link rel="alternate" hreflang="'.$l.'" href="'.$wiki->link('view',$l).'">'.PHP_EOL;
			}
			else
			{
				if (isset($wiki->interlanguageLinks[$l]))
					echo '<link rel="alternate" hreflang="'.$l.'" href="'.swNameURL($wiki->interlanguageLinks[$l]).'">'.PHP_EOL;
				else
					echo '<link rel="alternate" hreflang="'.$l.'" href="'.swNameURL($wiki->name).'&lang='.$l.'">'.PHP_EOL;
			}
		}
		else
		{
			if ($swLangURL)
				echo '<link rel="canonical" hreflang="'.$l.'" href="'.$swBaseHrefFolder.$wiki->link('view',$l).'">'.PHP_EOL;
		}
		
	}
}

?>
<style><?php

echo $swParsedCSS;

?></style>
</head>
<?php 
echo PHP_EOL.'<body>';

$barHeader = '<div class="dropdown">';
$barHeaderDesktop = '<div class="dropdown desktop">';
$barHeaderHeader = '<a class="dropdown-item-header" onclick="showAppMenu(this.parentNode); event.stopPropagation();">';
$itemHeader = '<div class="dropdown-item">';
$itemSilentHeader = '<div class="dropdown-item-silent">';
$groupHeader = '<div class="dropdown-content">';
$barHeaderFooter = '</a>'.PHP_EOL;
$barFooter = $itemFooter = $groupFooter = '</div>'.PHP_EOL;

if (count($swEditMenus) || true)
{
	$sep = ''; //'&nbsp;&nbsp;&nbsp;';
	
	echo '<div id="appmenu">';
	echo '<div id="mobilemenu" class="dropdownmobilemenu">';
	echo $barHeaderHeader.'≡'.$barHeaderFooter;
	echo $groupHeader;
	echo $itemHeader.$swHomeMenu.$itemFooter; 
	foreach($swLangMenus as $item) {echo $itemHeader.$item.$itemFooter;} 
	$sms = swSystemMessage("skin-menu",$lang, true);
	$sms = str_replace('<p>','',str_replace('<br>','',$sms));
	$sm = explode(PHP_EOL,$sms);
	foreach($sm as $item) {echo $itemHeader.$item.$itemFooter;} 
	foreach($swLoginMenus as $item) 
	{
		if ($item == $username)
		{
			echo $itemSilentHeader.$item.$itemFooter;
		}
		else
		{
			echo $itemHeader.$item.$itemFooter;
		}
	}
	echo $groupFooter;
	echo $barFooter;

	$mainmenus = array();
					
	foreach($swEditMenus as $key=>$item)
	{
		if (stristr($key,'-')) // submenu
		{
			$fields = explode('-',$key);
			if (!array_key_exists($fields[0], $mainmenus))
			{
				$mt = swSystemMessage($fields[0],$lang);
				$mainmenus[$fields[0]] = $barHeader.$barHeaderHeader.$mt.$barHeaderFooter.$groupHeader; 
			}
			
			$mainmenus[$fields[0]] .= $itemHeader.$item.$itemFooter;
		}
		else
		{
			 // $mainmenus[$key] = $barHeader.$itemHeader.$item.$itemFooter.$groupHeader; 
		}
	}
	
	if (isset($swUserDebug) && $swUserDebug && $user->hasright('modify','*')) 
	{
		$mainmenus['debug'] = $barHeaderDesktop.$barHeaderHeader.'Debug'.$barHeaderFooter.$groupHeader; 
		$mainmenus['debug'] .= $itemSilentHeader.$swDebug.$itemFooter;
		 
	}
	if (count($swLangMenus))
	{
		$mainmenus['lang']  = $barHeaderDesktop.$barHeaderHeader.swSystemMessage($lang,$lang).$barHeaderFooter.$groupHeader;
		foreach($swLangMenus as $item) {$mainmenus['lang'] .= $itemHeader.$item.$itemFooter;}
	}

	if ($username)
		$mainmenus['login'] = $barHeaderDesktop.$barHeaderHeader.'User:'.$username.$barHeaderFooter.$groupHeader;
	else
		$mainmenus['login'] = $barHeaderDesktop.$barHeaderHeader.swSystemMessage('login',$lang).$barHeaderFooter.$groupHeader;
	 	

	foreach($swLoginMenus as $item)
	{
		if ($item == $username) continue;
		$mainmenus['login'] .= $itemHeader.$item.$itemFooter;
	}
	
	$mainmenus['search']  = $barHeader.'<div>'.$swSingleSearchMenu;
	
		
	foreach($mainmenus as $item) {echo $item.$groupFooter.$barFooter.PHP_EOL ; }
	

	if (trim($swStatus) || trim($swError))
	{
		echo PHP_EOL.'<div class="dropdown"><div class="dropdown-item-status">';
		echo trim($swStatus);
		if (trim($swError)) 
		{
			if (trim($swStatus)) echo ' ';
			echo PHP_EOL.'<span class="error">'.trim($swError).'</span>';
		}
		echo PHP_EOL.'</div></div>';
	}
	
	
	echo '
<script>
function showAppMenu(parent)
{
	hideAppMenu();
	// set this menu						
	children = parent.childNodes;
	for (var i = 0; i < children.length; i++)
	{
		if (children[i].className == "dropdown-content") children[i].style.display = "block";
	}			
}

function hideAppMenu()
{
	collection = document.getElementsByClassName("dropdown-content");
	for (var i = 0; i < collection.length; i++)
	{
		collection[i].style.display = "none";
	}
}

window.onclick = hideAppMenu;
</script>';

	
	echo $barFooter;
	echo $barFooter;
	
}



	



echo PHP_EOL.'<div id="page">';