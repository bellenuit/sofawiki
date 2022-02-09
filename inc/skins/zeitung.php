<?php 
$skinstylesheet = '<link rel="stylesheet" href="inc/skins/zeitung.css"/>';
include 'header.php';
?>


<div id='header'>
<?php
echo swSystemMessage("skin-header",$lang, true);
?>
</div>


<div id='menu'>
<?php
	echo "<p>".$swHomeMenu. "<br/>"; 
	echo swSystemMessage("skin-menu",$lang, true). "<br/><br/>";
	echo " <span class='error'>$swError</span>"; 
?>
</div>
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
	echo "<br/>"; 
	foreach($swLoginMenus as $item) {echo $item." " ; } ; 
	echo swSystemMessage("skin-footer",$lang, true);
	echo "";  
?>
</div>

<?php 
include 'footer.php';