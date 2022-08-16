<?php 
	$skinstylesheet = '<link rel="stylesheet" href="inc/skins/wiki.css"/>';
include 'header.php';

echo PHP_EOL.'<div id="header">';
echo PHP_EOL.swSystemMessage("skin-header",$lang, true);

echo PHP_EOL.'</div>';

//echo PHP_EOL.'<div id="langmenu">';
//foreach($swLangMenus as $item) {echo PHP_EOL.$item.' ' ; } 
//echo PHP_EOL.'</div>';

echo PHP_EOL.'<div id="menu">';

if ($swStatus) echo PHP_EOL.'<p><span class="ok">'.$swStatus.'</span></p>';

if ($swError) echo PHP_EOL.'<p><span class="error">'.$swError.'</span></p>';


echo PHP_EOL.$swHomeMenu. "<br/>"; 
echo PHP_EOL.swSystemMessage("skin-menu",$lang, true). "<br/>\r\n";

foreach($swLoginMenus as $item) 
{

	echo PHP_EOL.$item."<br/>"; ;
}

echo PHP_EOL.'<br/><br/><br/>';

// echo PHP_EOL.$swSearchMenu; 




echo PHP_EOL.'</div><!-- menu -->';

echo PHP_EOL.'<div id="mobilemenu"><p>';

if ($swStatus) echo PHP_EOL.'<span class="ok">'.$swStatus.'</span> ';

if ($swError) echo PHP_EOL.'<span class="error">'.$swError.'</span> ';


echo PHP_EOL.$swHomeMenu. ' '; 
echo PHP_EOL.str_replace('<p>',' ',str_replace('<br>',' ',swSystemMessage("skin-menu",$lang, true))). " ";

foreach($swLoginMenus as $item) 
{
	if ($item == $username)
	{
		echo PHP_EOL.$item;
	}
	else
	{
		echo PHP_EOL.$item;
	}
}


echo PHP_EOL.'<p>'.$swSearchMenu; 
	


echo PHP_EOL.'</div><!-- mobilemenu -->';


echo PHP_EOL.'<div id="main">';
echo PHP_EOL.'<div id="content">';
echo PHP_EOL.'<div id="titl">';
echo PHP_EOL.'<h1>'.$swParsedName.'</h1>';
echo PHP_EOL.'</div><!-- title -->';
echo PHP_EOL.$swParsedContent;
echo PHP_EOL.'</div><!-- content -->';
echo PHP_EOL.'<div id="info">';
echo $swFooter; 
echo swSystemMessage("skin-footer",$lang, true);
echo PHP_EOL.'</div>';
echo PHP_EOL.'</div><!-- main -->';


include 'footer.php';