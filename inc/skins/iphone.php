<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo $swParsedName ?></title>
<link rel='stylesheet' href='inc/skins/iphone.css'/>
<style><?php echo $swParsedCSS ?></style>
<meta name='viewport' content='width=320; initial-scale=1.0; maximum-scale=2.0; user-scalable=1;' />
</head>
<body>


<div id='header'>
<?php
echo swSystemMessage("skin-header",$lang, true);
?>
</div>


<div class='menu'>
<?php 
echo "<div class='menuitem'>".$swHomeMenu."</div>\n"; 
foreach($swLoginMenus as $item) { echo "<div class='menuitem'>".$item."</div> \n"; }
echo "<span class='error'>$swError</span>\n"; ?>
</div>


<?php

if (count($swEditMenus)>0) 
{
	echo "<div class='menu'>";
	foreach($swEditMenus as $item) 
		{echo "<div class='menuitem'>".$item."</div> \n"; }
	echo "</div>";
}
?>



<div id='content'>

<h1><?php echo "$swParsedName" ?></h1>

<div id='parsedContent'><?php echo "

$swParsedContent
" ?>

</div><!-- parsedContent -->
</div><!-- Content -->

<div id='search'>
<?php echo $swSearchMenu; ?>
</div>

<div id='menubottom'>
<?php


foreach($swLangMenus as $item) {echo "<div class='menuitem'>".$item."</div> \n"; }

echo "<div class='menuitem'>".$swHomeMenu."</div>\n"; 
echo "<div class='menuitem'>".swSystemMessage("skin-menu",$lang, true)."</div>";


?>
</div>




<div id="info">
<?php echo "$swFooter" ?>
</div>

</body>
</html>