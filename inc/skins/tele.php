<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<base href="<?php echo $swBaseHrefFolder ?>">
<title><?php echo swSystemMessage("sitename",$lang);  echo " ".$swParsedName ?></title>
<link rel='stylesheet' href="inc/skins/tele.css"/>
<style><?php echo $swParsedCSS ?></style>
</head>
<body>

<div id='body'>

<!--
<div id='header'>
<?php
echo swSystemMessage("SkinHeader",$lang);
?>
</div>
-->

<!--
<div id='search'>
<?php echo $swSearchMenu; ?>
</div>
-->

<div id='logo'>
<a href="index.php">
<!-- <img src="site/files/tele.gif">-->
</a>
</div>

<div id='header'>
<div id='header-elem'>

<?php



$ishome = (($name=="Home" || $name="") && ($action=="view" || $action==""));

$s = swSystemMessage("skin-header",$lang, true);

if ($ishome)
{
	echo "<h2>$s</h2>";
}
else
{
	echo "<p>$s</p>";

}
?>
</div>
</div>


<div id='menu'><small>
<?php 

foreach($swLangMenus as $item) {echo $item."<br/>" ; }
echo "<br/>";
	
if (!$ishome)
{
	echo $swHomeMenu. "<br/>"; 
	
	
}
echo swSystemMessage("skin-menu",$lang, true). "<br/><br/>";

if (count($swEditMenus)>1)
	foreach($swEditMenus as $item) {echo $item."<br/>"; }



echo " <span class='error'>$swError</span>"; ?>
</small></div>


<div id='content'>

<?php if (!$ishome) echo "<h2>$swParsedName</h2>" ?>

<div id='parsedContent'><?php echo "

$swParsedContent
" ?>

</div>

<div id='footer2'>

</div>


</div>

<div id='footer'><small>
<?php if ($ishome || true) 
{ echo swSystemMessage("impressum",$lang,true);
echo "<br/>"; 
foreach($swLoginMenus as $item) {echo $item." " ; } ; echo "</small>"; }  

if (!$ishome && false) {
echo swSystemMessage("impressum-small",$lang,true);
echo "<br/><small>"; 
foreach($swLoginMenus as $item) {echo $item." " ; } ; echo "</small>"; } 

echo swSystemMessage("skin-footer",$lang, true); ?>
</div>

</div> <!--body-->



</body>
</html>