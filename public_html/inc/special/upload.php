<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Upload';
$swParsedContent = '<div id="editzone">
<form action="index.php" method="post" enctype="multipart/form-data">
<p><input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
<input type="hidden" name="action" value="uploadfile" />
<input type="file" name="uploadedfile" />
<br/>Filename: <input type="text" name="filename" value="" size="30" />
<br/><input type="checkbox" name="deleteexisting" value="1" /> Delete existing file if duplicate
<br/>Content: 
<br/><textarea name="content" rows="5" cols="50"></textarea>
<br/><input type="submit" value="Upload" />
</p></form>
</div>';


?>