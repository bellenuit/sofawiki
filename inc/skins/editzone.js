
function colorCode() {
  var ta  = document.getElementById("editzonesource")
  var tc  = document.getElementById("editzonecolor")
  
  if (ta.scrollHeight > ta.clientHeight) {
  	ta.style.height = ta.scrollHeight + 30 + "px"
  	tc.style.height = ta.style.height
  	ta.parentElement.style.height = ta.style.height
  }
  	// hack get save width no different wrap does not yet work
//   while( ta.scrollHeight > tc.scrollHeight)
  	tc.style.width = (ta.clientWidth - 10) + "px";
  
  var s = ta.value
  
  
  function replaceTemplate(s){
  	var start = s.lastIndexOf("{{")
  	if (start == -1) return s
  	var stop = s.substring(start).indexOf("}}")
  	if (stop == -1) return s
  	var c = s.substring(start+2, start+stop)
  	.replace(/^\|(\s?)/gm,"<mark class='parsed'>&#124;$1</mark>")
    .replace(/(\s?)\|(\s?)/gm,"<mark class='parsed'>$1&#124;$2</mark>")
  	
  	s = s.substring(0, start)
  	+ "<mark class='parsed'>&#124;&#124;</mark>"
  	+ c
  	+ "<mark class='parsed'>&#126;&#126;</mark>"
  	+ s.substring(start+stop+2)
  	return replaceTemplate(s)
  }
  
  function replaceTable2(s) {
  	
  	
  	return s
    .replace(/^{\|(.*)$/g, function(){ 
  		
  		var c = arguments[1]
  		return "<mark class='parsed'>{|"+c+"</mark>"})
  }
  
  function replaceTable(s){
  	var match = s.match(/^\{\|/m)
	var start = s.indexOf(match[0])
	alert(start)
	if (start == -1) return s
  	var match = s.substring(start).match(/^\|\}/gm)
  	var stop = s.indexOf(match[0])
  	if (stop == -1) return s
  	var c = s.substring(start+2, start+stop)
  	.replace(/^\|([+-]?\s?)/gm,"<mark class='parsed'>&#124;$1</mark>")
    .replace(/^\!(\s?)/gm,"<mark class='parsed'>!$1</mark>")
    .replace(" || ","<mark class='parsed'> &#125;&#125; </mark>")
    .replace(" !! ","<mark class='parsed'>&nbsp;!!&nbsp;</mark>")
  	
  	s = s.substring(0, start)
  	+ "<mark class='parsed'>&#124;&#124;</mark>"
  	+ c
  	+ "<mark class='parsed'>&#126;&#126;</mark>"
  	+ replaceTable(s.substring(start+stop+2))
  	return s
  }
  
   s = s
  // Return at the end
        .replace(/\n$/g, "\n\n")
  // preserve nowwiki, will be replaced at the end
        .replace(/<nowiki>(.*?)<\/nowiki>/gm,         
                 function(){ return '<nowiki>'+encodeURI(arguments[1])+'</nowiki>'; } )
  // directives
        .replace(/^#CACHE (\d+)\n/,"<mark class='parsed'>#CACHE $1</mark>\n")
        .replace(/^#DISPLAYNAME (.*?)\n/,"<mark class='parsed'>#DISPLAYNAME $1</mark>\n")
        .replace(/^#REDIRECT \[\[(.*?)\]\]\n/,"<mark class='parsed'>#REDIRECT &#91;&#91;$1&#93;&#93;</mark>\n")
        
        
 // allowed tags
	var tags = ["b","i","u","s","sup","sub","tt","code","span","br","hr","small","big","div"]
	tags = ["b"]
	for (var  i in tags) {
	var t = tags[i]
	var r = "<"+t+"(.*?)>(.*?)</"+t+">"
	var rx = new RegExp(r,"gm")
// 	alert(rx)
		s = s.replace(rx,"<mark class='parsed'>&lt;"+t+"$1></mark>$2<mark class='parsed'&lt;/t><mark>")
	r = "<"+t+"(.*?)>"
	rx = new RegExp(r,"gm")
// 	alert(rx)
 		s = s.replace(rx,"<mark class='parsed'>&lt;"+t+"$1></mark>")
	}
	
	
//  not allowed tags
		s = s
// 		.replace(/<(?!nowiki|mark)(.+)>/gm,"<mark class='error'><&lt;$1>")
// 		.replace(/<\/(?!nowiki|mark)(.+)>/gm,"<mark class='error'><&lt;/$1>")
		
		.replace(/</gm,"&lt;")
		.replace(/&lt;nowiki>/gm,"<nowiki>")
		.replace(/&lt;\/nowiki>/gm,"</nowiki>")
		.replace(/&lt;mark/gm,"<mark")
		.replace(/&lt;\/mark/gm,"</mark")
		
		s = s
        .replace(/^====([^=]*)====$/gm, "<mark class='parsed'>&#61;&#61;&#61;&#61;</mark>$1<mark class='parsed'>&#61;&#61;&#61;&#61;</mark>")
  		.replace(/^===([^=]*)===$/gm, "<mark class='parsed'>&#61;&#61;&#61;</mark>$1<mark class='parsed'>&#61;&#61;&#61;</mark>")
  		.replace(/^==([^=]*)==$/gm, "<mark class='parsed'>&#61;&#61;</mark>$1<mark class='parsed'>&#61;&#61;</mark>")
  		.replace(/(={3,5})/gm, "<mark class='error'>$1</mark>")
  		
        .replace(/\[\[(.*?)\]\]/gm, "<mark class='parsed'>&#91;&#91;</mark>$1<mark class='parsed'>&#93;&#93;</mark>")
        .replace(/\[\[/gm, "<mark class='error'>&#91;&#91;</mark>")
        .replace(/\]\]/gm, "<mark class='error'>&#93;&#93;</mark>")
        
        .replace(/\[(https?:\/\/)(.*?)\]/gm, "<mark class='parsed'>&#91;</mark>$1<!-- -->$2<mark class='parsed'>&#93;</mark>")
        
        s = replaceTemplate(s)
        s = s
        .replace(/\{\{/gm,"<mark class='error'>&#123;&#123;</mark>")
        .replace(/\}\}/gm,"<mark class='error'>&#125;&#125;</mark>")
        .replace(/^(\*{1,3})/gm, "<mark class='parsed'>$1</mark>")
        .replace(/^(\#{1,3})/gm, "<mark class='parsed'>$1</mark>")
        
// table
// 		.replaceTable(s)  
/*
  		.replace(/^{\|(.*)$/gm, function(){ 
  		
  		var c = arguments[1]
  		return "<mark class='parsed'>{|"+c+"</mark>"})
  		
  	   .replace(/^\|-(.*)$/gm, "<mark class='parsed'>|-$1</mark>")
  	   .replace(/^\| (.*?) \|\| /gm, "| $1<mark class='parsed'> &#124;&#124; </mark>")
  	   .replace(/^\| (.*)$/gm, "<mark class='parsed'>| </mark>$1")
  	   .replace(/^\|\}(.*)$/gm, "<mark class='parsed'>|}$1</mark>")
  	   */
  	   
  	   .replace(/(https?:\/\/)([\da-z\.-]+)\.([a-z\.]{2,6})(\/[\S]*)*/gm, "<mark class='parsed'>$1$2.$3$4</mark>")
  	   
  		.replace(/'''''(.*?)'''''/gm, "<mark class='parsed'>&#39;&#39;&#39;&#39;&#39</mark>$1<mark class='parsed'>&#39;&#39;&#39;&#39;&#39;</mark>")
  		.replace(/'''(.*?)'''/gm, "<mark class='parsed'>&#39;&#39;&#39;</mark>$1<mark class='parsed'>&#39;&#39;&#39;</mark>")
        .replace(/''(.*?)''/gm, "<mark class='parsed'>&#39;&#39;</mark>$1<mark class='parsed'>&#39;&#39;</mark>")
        .replace(/('{2,5})/gm, "<mark class='error'>$1</mark>")
        
       .replace(/<nowiki>(.*?)<\/nowiki>/gm,         
                 function(){ return '<mark  class="parsed">&lt;nowiki>'+decodeURI(arguments[1])+'&lt;/nowiki></mark>'; } ) 
        
  tc.innerHTML = s ;
}

window.onload = colorCode;