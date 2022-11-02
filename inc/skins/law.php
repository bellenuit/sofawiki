<?php 
$skinstylesheet = '<link rel="stylesheet" href="inc/skins/law.css"/>';
include 'header.php';
?>


<div id='langmenu'>
<?php 
?>
</div>


<div id='menu'>
<?php 
	echo $swHomeMenu. "<br/>"; 
	echo swSystemMessage("skin-menu",$lang, true). "<br/>\r\n";
	echo $swSearchMenu; 
	
	
	if ($username != "")
		echo "<div id='editmenu'>\r\n";
	else
		echo "<div id='editmenu0'>\r\n";
		
	foreach($swLoginMenus as $item) {echo $item."<br/>\r\n" ; }
	echo "<span class='error'>$swError</span>\r\n";
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



<?php 
include 'footer.php';