<?php if (isset($_REQUEST['ajax']))
{
	if (isset($swOvertime) && $swOvertime)
		echo '1';
		else
		echo '0';
	echo $swParsedContent;
		
	return;

}
?><!DOCTYPE HTML>
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
<link rel='stylesheet' href="inc/skins/editzone.css"/>
<style><?php echo $swParsedCSS ?></style>
<script src="inc/skins/editzone.js"></script>
<script>window.onload = colorCodeInit </script>
</head>
<body>

<div id='header'>
<?php
	echo swSystemMessage("skin-header",$lang, true);
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
	echo swSystemMessage("skin-menu",$lang, true). "<br/>\r\n";
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

<div id='content'><div id='parsedcontent'><?php echo "

$swParsedContent
" ?>

</div><div id="info">
<?php echo "$swFooter"; echo swSystemMessage("skin-footer",$lang, true);?>
</div>

</div><!-- content -->

<?php 

if (isset($swOvertime) && $swOvertime && count($_POST) == 0)
{

echo '
<script>
var xmlhttp=new XMLHttpRequest();
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
    	s = xmlhttp.responseText;
		overtime = s.substr(0,1);
		t = s.substr(1);
		document.getElementById("parsedcontent").innerHTML=t;
		if (overtime=="1")
		{
			setTimeout(function()
			{	
				var u = document.URL;
				if (u.indexOf("?") == -1) u = u+"?ajax=1"; else u = u+"&ajax=1";
				xmlhttp.open("GET",u,true);
				xmlhttp.send();
				document.title = document.title+"-";
				document.getElementById("searchovertime").innerHTML +="...";
			}, 3000);
		}
		else
			document.title = document.title+".";
    }
  }
setTimeout(function()
{
	var u = document.URL;
	if (u.indexOf("?") == -1) u = u+"?ajax=1"; else u = u+"&ajax=1";
	xmlhttp.open("GET",u,true);
	xmlhttp.send();
	document.title = document.title+"-"
	document.getElementById("searchovertime").innerHTML +="...";
}, 3000);
</script>';
}

?>
</body>
</html>