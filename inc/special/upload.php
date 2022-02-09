<?php

if (!defined('SOFAWIKI')) die('invalid acces');


$swParsedName = 'Upload';
$swParsedContent = '<div id="editzone" class="editzone">
<div class="editheader">'.swSystemMessage("upload",$lang).'</div>
<form action="index.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="'.$swMaxFileSize.'" />
<input type="hidden" name="action" value="uploadfile" />
<input type="submit" value="Upload" />
<p>
<input type="file" name="uploadedfile" />
<p>Filename</p>
<input type="text" name="filename" value="" size="30" />
<p><input type="checkbox" name="deleteexisting" value="1" /> Delete existing file if duplicate
<p>Content</p> 
<textarea name="content" rows="5" cols="50"></textarea>
</form>
<div id="help">'.str_replace('{swMaxFileSize}', round($swMaxFileSize/1024/1024,0), swSystemMessage("upload-help",$lang)).'</div><!-- help -->	
</div>';


?>