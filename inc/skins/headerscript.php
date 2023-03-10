<?php if (isset($_REQUEST['ajax']))
{
	if ($swOvertime) echo '1'; else echo '0';
	
	echo $swParsedContent;
		
	exit;

}
?>