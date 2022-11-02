<?php

$wiki->name = $name;
$wiki->name = $wiki->namewithoutlanguage();
$wiki->lookup();

if (!$wiki->revision || strtolower(substr($wiki->name,0,6)) != 'image:')
{
	echo 'no image file '.$wiki->name;
	exit;
}
$filename = substr($wiki->name,6);
$filepath = $swRoot.'/site/files/'.$filename;
$mime_type = mime_content_type($filepath);
header('Content-type: '.$mime_type);
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.filesize($filepath));
readfile($filepath);

exit;