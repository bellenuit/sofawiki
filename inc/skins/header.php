<?php include 'headerscript.php'; ?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="content-language" content="<?php echo $lang ?>'">
<base href="<?php echo $swBaseHrefFolder ?>">
<title><?php echo $swMainName.' - '.$swParsedName ?></title>
<meta name="title" content="<?php echo $swMainName.' - '.$swParsedName ?>">
<meta name="description" content="'<?php

	if (isset($wiki->internalfields['_description'])) 
	{
		$d = array_pop($wiki->internalfields['_description']);
	}
	else 
	{ 
		$rf = new swResumeFunction;
		$d = trim($rf->dowork(array(0,$name, 140, true)));
	}
	echo trim(str_replace('"',"'",$d));

?>'">
<link rel='stylesheet' href="inc/skins/default.css"/>
<?php echo @$skinstylesheet; ?>
<link rel="apple-touch-icon" sizes="180x180" href="site/skins/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="site/skins/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="site/skins/favicon-16x16.png">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

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
<style><?php echo $swParsedCSS; ?></style>
</head>
<body>
<div id="page">