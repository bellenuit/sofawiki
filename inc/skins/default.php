<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<?php
echo '<meta http-equiv="content-language" content="'.$lang.'">';
echo '<base href="'.$swBaseHrefFolder.'">';
	
$t = $swMainName;
if (trim($swParsedName) != '') 
	if (stristr($swParsedName, $swMainName))
		$t = $swParsedName;
	else
		$t .= ' - '.$swParsedName;
$t = trim(str_replace('"',"'",$t));
if (isset($wiki->internalfields['_description'])) $d = array_pop($wiki->internalfields['_description']);
else { $rf = new swResumeFunction; $d = trim($rf->dowork(array(0,$name, 140, true)));}
$d = trim(str_replace('"',"'",$d));

echo '<meta name="title" content="'. $t. '">
<meta name="description" content="'.$d.'">'.PHP_EOL;
?>
<title><?php echo $t ?></title>
<link rel='stylesheet' href="inc/skins/default.css"/>
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
<style><?php echo $swParsedCSS ?></style>
</head>
<body>

<div id='header'>
<?php
	echo swSystemMessage("SkinHeader",$lang, true);
?>
</div>






<div id='langmenu'>
<?php 
	foreach($swLangMenus as $item) {echo $item." " ; } 
?>
</div>


<div id='menu'>
<?php 
	echo $swHomeMenu. "<br/>"; 
	echo swSystemMessage("SkinMenu",$lang, true). "<br/>\r\n";
	echo $swSearchMenu; 
	
	
	if ($username != "")
		echo "<div id='editmenu'>\r\n";
	else
		echo "<div id='editmenu0'>\r\n";
		
	foreach($swEditMenus as $item) {echo $item."<br/>\r\n"; }
	echo "<br/>";
	foreach($swLoginMenus as $item) {echo $item."<br/>\r\n" ; }
	echo "<span class='error'>$swError</span>\r\n";
	if ($user->hasright('modify','*')) echo "<br><span class='debug'>$swDebug</span>\r\n";
?>
</div><!-- editmenu -->
</div><!-- menu -->


<div id='title'>
<h1><?php echo "$swParsedName" ?></h1>
</div><!-- title -->

<div id='content'><?php echo "

$swParsedContent
" ?>

<div id="info">
<?php echo "$swFooter"; echo swSystemMessage("SkinFooter",$lang, true);?>
</div>

</div><!-- content -->



</body>
</html>