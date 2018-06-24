<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo swSystemMessage("sitename",$lang);  echo " ".$swParsedName ?></title>
<link rel='stylesheet' href="inc/skins/zeitung.css"/>
<style><?php echo $swParsedCSS ?></style>
</head>
<body>
<div id='body'>

<div id='header'>
<?php
echo swSystemMessage("skin-header",$lang, true);
?>
</div>


<!--
<div id='search'>
<?php echo $swSearchMenu; ?>
</div>
-->

<div id='menu'><small>
<?php
	echo $swHomeMenu. "<br/>"; 
	echo swSystemMessage("skin-menu",$lang, true). "<br/><br/>";
	if (count($swEditMenus)>1)
		foreach($swEditMenus as $item) {echo $item."<br/>"; }
	echo " <span class='error'>$swError</span>"; 
?>
</small></div>
<div id='content'>
<?php echo "<h1>$swParsedName</h1>" ?>

<div id='parsedContent'>
<?php echo "
$swParsedContent
" 
?>
</div>
</div>

<div id='footer'>
<?php
	echo swSystemMessage("impressum",$lang,true);
	echo "<br/><small>"; 
	foreach($swLoginMenus as $item) {echo $item." " ; } ; 
	echo swSystemMessage("skin-footer",$lang, true);
	echo "</small>";  
?>
</div>

</div> <!--body-->
</body>
</html>