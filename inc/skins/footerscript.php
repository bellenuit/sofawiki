<script>
var xmlhttp=new XMLHttpRequest();
xmlhttp.onreadystatechange=function()
  {
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
    {
    	s = xmlhttp.responseText;
		s = s.trim();
		overtime = s.substr(0,1);
		t = s.substr(1);
		/* document.getElementById("parsedcontent").innerHTML=t;*/
		setInnerHTML(document.getElementById("parsedcontent"),t);
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

/* https://stackoverflow.com/questions/2592092/executing-script-elements-inserted-with-innerhtml */
var setInnerHTML = function(elm, html) {
  elm.innerHTML = html;
  Array.from(elm.querySelectorAll("script")).forEach( oldScript => {
    const newScript = document.createElement("script");
    Array.from(oldScript.attributes)
      .forEach( attr => newScript.setAttribute(attr.name, attr.value) );
    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
    oldScript.parentNode.replaceChild(newScript, oldScript);
  });
}
</script>