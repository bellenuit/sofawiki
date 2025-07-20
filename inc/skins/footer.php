<?php 
	
echo PHP_EOL.'</div><!-- page -->';

include 'menu.php';
echo PHP_EOL.'</body>';


if (isset($swOvertime) && $swOvertime && count($_POST) == 0) include 'footerscript.php';

?>
</html>