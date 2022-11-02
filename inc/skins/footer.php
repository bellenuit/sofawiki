<?php 
	
echo PHP_EOL.'</div><!-- page -->';
echo PHP_EOL.'</body>';


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
		t = s.substr(0);
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
			}, 2000);
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
</html>