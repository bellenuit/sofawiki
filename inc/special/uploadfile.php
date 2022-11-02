<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Uploading File';
$swParsedContent = '';


$file = $_FILES['uploadedfile'];
$filename = trim($_POST['filename']);
$deleteexisting = false;
if (isset($_POST['deleteexisting'])) $deleteexisting = true;

$filename =  swHandleUploadFile($file, $filename, $content, $deleteexisting);

$swParsedContent .=  '<p><a href="index.php?name=image:'.$filename.'">Image:'.$filename.'</a>';
$swParsedContent .=  '<p><img src="site/files/'.$filename.'" style="max-width:100%">';




?>