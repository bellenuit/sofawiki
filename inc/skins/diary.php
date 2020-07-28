<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<base href="<?php echo $swBaseHrefFolder ?>">
<title><?php echo $swParsedName ?></title>
<link rel='stylesheet' href="inc/skins/diary.css"/>
<style><?php echo $swParsedCSS ?></style>
</head>
<body>

<div id='header'>
<?php
	echo swSystemMessage("skin-header",$lang, true);
?>
</div>






<div id='langmenu'>
<?php 
	// foreach($swLangMenus as $item) {echo $item." " ; } 
?>
</div>


<div id='menu'>
<?php 
	// echo $swHomeMenu. "<br/>"; 
	echo swSystemMessage("skin-menu",$lang, true). "<br/>\r\n";
	echo $swSearchMenu; 
	
	
	if ($username != "")
		echo "<div id='editmenu'>\r\n";
	else
		echo "<div id='editmenu0'>\r\n";
		
	if ($username != '')
		foreach($swEditMenus as $item) {echo $item."<br/>\r\n"; }
	echo "<br/>";
	foreach($swLoginMenus as $item) {echo $item."<br/>\r\n" ; }
	echo "<span class='error'>$swError</span>\r\n";
	// if ($user->hasright('modify','*')) echo "<br><span class='debug'>$swDebug</span>\r\n";
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
<?php echo "$swFooter"; echo swSystemMessage("skin-footer",$lang, true);?>
</div>

</div><!-- content -->



</body>
</html>