<?php

if (!defined('SOFAWIKI')) die('invalid acces');

$swParsedName = 'Special:Tiny PS';

$swMaxOverallSearchTime *=3;  

$q = swGetArrayValue($_REQUEST,'q');
$submitrefresh = swGetArrayValue($_REQUEST,'submitrefresh');
$submkitwikitext = swGetArrayValue($_REQUEST,'submitwikitext');
$submitexecute = swGetArrayValue($_REQUEST,'submitexecute');
if ($submitrefresh)
	$swDebugRefresh = true;

$swParsedContent = '<nowiki>
<div id="editzone" class="editzone specialrelation">
<div class="editheader">Tiny PS</div>
<form onsubmit = "runPS(); return false">
<input type="submit" name="submit" value="Run ^R" accesskey="r"/>
<input type="hidden" name="name" value="special:relation" />
<textarea id="shadoweditor" name="q" rows=8>100 100 moveto 200 100 lineto 150 166 lineto closepath fill showpage</textarea>
</form>
</div><!-- editzone -->
<tiny-ps id="ps" width="640" height="360" format="svg,svgurl,canvasurl" oversampling="4"></tiny-ps>
<script src="inc/skins/tinyps120.js"></script>
<script src="inc/skins/tinyps-extensions.js"></script>
<script>rpnFontURLs = '.json_encode($rpnFontURLs).';</script>
<script>
function runPS() {
	const node = document.getElementById("ps");
	const textarea = document.getElementById("shadoweditor");

	node.innerHTML = textarea.value;
}

</script>
</nowiki>
';

	$alines = array('relation',$q);
	$dtb = new swRelationfunction;
	
	/* echo "<pre>";
	print_r($alines);
	echo "</pre>"; */

		
	$s = $dtb->dowork($alines);
	
	// remove nowiki
	//$swParsedContent = str_replace('<nowiki>','',$swParsedContent);
	//$swParsedContent = str_replace('</nowiki>','',$swParsedContent);
	//$swParsedContent = swUnescape($swParsedContent);

//$query = str_replace("\n","<br>",$query);

$swMaxOverallSearchTime /=3;  

if (trim($s)=="" && !stristr($q,'print') && (stristr($q,'relation') || stristr($q,'read') || stristr($q,'filter') || stristr($q,'import') || stristr($q,'virtual')))
$s = "''Did you forget '''''print'''''?''";


if ($submkitwikitext)
{
	$swParseSpecial = false;
	$swParsedContent .= '<textarea rows=16 cols=180 style="width:100%">'.$s.'</textarea>';
}
else
{
	$swParseSpecial = true;
	$swParsedContent .= $s;

}






?>