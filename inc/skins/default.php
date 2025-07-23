<?php 
$skinstylesheet = ''; // default.css is always loaded. add the specific stylesheet	
include 'header.php';
echo PHP_EOL.'<div id="header">';
echo PHP_EOL.swSystemMessage("skin-header",$lang, true);
echo PHP_EOL.'</div><!-- header -->';
echo PHP_EOL.'<div id="menu">';

echo PHP_EOL.$swHomeMenu. '<br/>'; 
foreach($swLangMenus as $item) {echo PHP_EOL.$item.'<br/>'; } 
echo '<br/>'.PHP_EOL; 
if (!$username) foreach($swLoginMenus as $item) {echo PHP_EOL.$item.'<br/>'; }
echo PHP_EOL.swSystemMessage("skin-menu",$lang, true). '<br/>';
echo PHP_EOL.$swSearchMenu; 
echo PHP_EOL.'</div><!-- menu -->';

if (!count($swEditMenus))
{
	echo PHP_EOL.'<div id="mobileappmenu">';
	echo PHP_EOL.'<div id="mobilemenu" class="dropdownmobilemenu">';
	if(isset($barHeaderHeader)) echo PHP_EOL.$barHeaderHeader.'≡'.$barHeaderFooter;
	if(isset($groupHeader)) echo PHP_EOL.$groupHeader;
	if(isset($temHeader)) echo PHP_EOL.$itemHeader.$swHomeMenu.$itemFooter; 
	foreach($swLangMenus as $item) 
	{
		 if(isset($temHeader) && isset($itemFooter)) echo PHP_EOL.$itemHeader.$item.$itemFooter;	    
	} 
	$sms = swSystemMessage("skin-menu",$lang, true);
	$sms = str_replace('<p>','',str_replace('<br>','',$sms));
	$sm = explode(PHP_EOL,$sms);
	foreach($sm as $item)
	{
		if(isset($temHeader) && isset($itemFooter)) echo PHP_EOL.$itemHeader.$item.$itemFooter;
	} 
	foreach($swLoginMenus as $item) 
	{
		if ($item == $username)
		{
			if(isset($itemSilentHeader) && isset($itemFooter))  echo PHP_EOL.$itemSilentHeader.$item.$itemFooter;
		}
		else
		{
			if(isset($temHeader) && isset($itemFooter))  echo PHP_EOL.$itemHeader.$item.$itemFooter;
		}
	}
	if(isset($groupFooter)) echo PHP_EOL.$groupFooter;
	if(isset($barFooter)) echo PHP_EOL.$barFooter;
	if(isset($barHeader) && isset($swSingleSearchMenu) && isset($barFooter)) echo PHP_EOL.$barHeader.$swSingleSearchMenu.$barFooter;
	echo PHP_EOL.'</div><!-- mobilemenu -->';
	echo PHP_EOL.'</div><!-- mobileappmenu -->';
}
echo PHP_EOL.'<div id="content">';
echo PHP_EOL.'<div id="title">';
echo PHP_EOL.'<h1>'.$swParsedName.'</h1>';
echo PHP_EOL.'<div id="parsedcontent">';
echo $swParsedContent;
echo PHP_EOL.'</div><!-- parsedcontent -->';
echo PHP_EOL.'<div id="info">';
echo PHP_EOL.$swFooter; 
echo PHP_EOL.swSystemMessage('skin-footer"',$lang, true);	
echo PHP_EOL.'</div><!-- info -->';
echo PHP_EOL.'</div><!-- content -->';




include 'footer.php';