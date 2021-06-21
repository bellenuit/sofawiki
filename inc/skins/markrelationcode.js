// marks Relation Code, to be used with mineditor
// recommended css

/*

code { 
	display:block;
	background-color: white;
	color: black; 
	padding: 8px; 
	margin-top: 8px;
	border: 1px solid #888;
	counter-reset: line;
	box-shadow: inset 2px 2px 4px lightgray;
 }

code p { 
	padding: 0px; 
	margin: 0px;
}

code p:before {
	content: counter(line);
	display: inline-block;
	font-size: 60%;
	padding: 0 .5em;
	margin-right: 1em;
	color: #888;
	counter-increment: line;
	width: 1.5em; 
}

code p.prefix1:before { margin-right: 3em; }
code p.prefix2:before { margin-right: 5em; }
code p.prefix3:before { margin-right: 7em; }
code p.prefix4:before { margin-right: 9em; }

code span.keyword { color: blue; }
code span.number { color: green; }
code span.comment { color: rgb(200,0,0);}
code span.quote { color: rgb(85, 26, 139); }
code span.print { color: gray; }


*/


function markRelationCode() 
{
		list = document.getElementsByTagName("code");

	for (i = 0; i < list.length; i++)
	{
		
		s0 = s = list[i].textContent;
		
		// encode print
		s = s.replace(/([\n|^]\' .*)([\n|$])/g,function (x,y,z) { return "@p"+btoa(encodeURIComponent(y))+"@"+z } );		
		
		// encode comments
		s = s.replace(/(\/\/.*)(\n|$])/g, function (x,y,z) { return "@c"+btoa(encodeURIComponent(y))+"@"+z } );

		// encode quotes. double quote will work (hack)
		s = s.replace(/".+?"/g, function (x) { return "@q"+btoa(encodeURIComponent(x))+"@" } );
		
		//numbers
		s = s.replace(/(\b\-?\d+\.?\d*?([Ee]\-?\d+)?\b)/g,"<span class='number'>$1</span>");
		
		//keywords start 
		
		s = s.replace(/(\n|\u200B|^)(assert all|assert exists|assert unique|assert columns)\b/g,"$1<span class='keyword'>$2</span>");
		
		s = s.replace(/(\n|\u200B|^)(end data|end function|end if|end program|end transaction|end while)\b/g,"$1<span class='keyword'>$2</span>");
			
		s = s.replace(/(\n|\u200B|^)(join|join natural|join left|join right|join outer|join leftsemi|join rightsemi|join leftanti|join rightanti)\b/g,"$1<span class='keyword'>$2</span>");
		
		s = s.replace(/(\n|\u200B|^)(read|read latin1|read macroman|read utf8|read windowslatin1)\b/g,"$1<span class='keyword'>$2</span>");	
		
		s = s.replace(/(\n|\u200B|^)(beep|compile|data|deserialize|difference|delegate|dup|echo|else|extend|format|function|if|import|include|init|input|insert|intersection|label|limit|order|parse|pivot|pop|program|print|project|project inline|relation|rename|run|select|serialize|set|stack|swap|template|transaction|union|update|while|write)/g,"$1<span class='keyword'>$2</span>");
		
		// wiki
		s = s.replace(/(\n|\u200B|^)(filter|virtual)/g,"$1<span class='keyword'>$2</span>");
		
		//keywords inline
		s = s.replace(/\b(where)\b/g,"<span class='keyword'>$1</span>");
		
		// comment recover
		s = s.replace(/(@c)([A-Za-z0-9=]*?)(@)/g, function (t,x,y,z) { return "<span class='comment'>"+decodeURIComponent(atob(y))+"</span>"; } );		

		
		// print recover 
		s = s.replace(/(@p)([A-Za-z0-9=]*?)(@)/g, function (t,x,y,z) { return "<span class='print'>"+decodeURIComponent(atob(y))+"</span>"; } );		
		
		// quote recover
		s = s.replace(/(@q)([A-Za-z0-9=]*?)(@)/g, function (t,x,y,z) { return "<span class='quote'>"+decodeURIComponent(atob(y))+"</span>"; } );	
		
		
		
		lines = s.split("\n");
		s="";
		keywordheader="<span class='keyword'>";
		c= lines.length;
		prefix = 0;
		for (j=0;j<c;j++)
		{			
			lines0 = lines[j];
			lines[j] = lines[j].replace(/\u200B/,"");  // detect at start of line
			
			if (lines[j].substring(0,(keywordheader+"data").length) == keywordheader+"data"
			|| lines[j].substring(0,(keywordheader+"if").length) == keywordheader+"if"
			|| lines[j].substring(0,(keywordheader+"while").length) == keywordheader+"while"
			|| lines[j].substring(0,(keywordheader+"function").length) == keywordheader+"function"
			|| lines[j].substring(0,(keywordheader+"program").length) == keywordheader+"program"
			|| lines[j].substring(0,(keywordheader+"transaction").length) == keywordheader+"transaction"
			|| lines[j].substring(0,(keywordheader+"aggregator").length) == keywordheader+"aggregator")
			{
				s += "<p class='prefix"+prefix+"'>"+lines[j]+"\n";
				prefix++;
			}
			else if(lines[j].substring(0,(keywordheader+"else").length) == keywordheader+"else")
			{
				prefix--;
				s += "<p class='prefix"+prefix+"'>"+lines[j]+"\n";
				prefix++;	
			}
			else if(lines[j].substring(0,(keywordheader+"end").length) == keywordheader+"end")
			{
				prefix--;
				s += "<p class='prefix"+prefix+"'>"+lines[j]+"\n";
			}
			else
			{				
				s += "<p class='prefix"+prefix+"'>"+lines0+"\n";
			}
		}
		
		// remove last \n
		s = s.substr(0,s.length-1);			
		
		list[i].innerHTML=s;	
		
				
			
		
	}
	
}

function miniEditor(editor, markCode)
{
	if (editor == null) return;
	editor.setAttribute("contentEditable", "true");
	editor.setAttribute("spellcheck", "false");
	editor.style.overflowWrap = "break-word";
    editor.style.overflowY = "auto";
    editor.style.resize = "vertical";
    editor.style.whiteSpace = "pre-wrap";
	editor.style.display = "block";

	let listeners = [];
	let history = [];
	let redohistory = [];
	let prev = editor.textContent;
    
    markCode(editor);  
    editor.style.display = 'visible';
    
    area = document.getElementById("shadoweditor");
	area.style.display = 'none';

        
    const on = (type, fn) => 
    { 
        listeners.push([type, fn]);
        editor.addEventListener(type, fn);
    };
    
    on("input",event => 
    { 
	    if (editor.textContent != prev)
	    {
			update();
	    }
    }); 
    
    on("copy", event =>
    {
	   var text = window.getSelection().toString().replace('\u200B', ''); // we must remove empty character
	   event.clipboardData.setData('text/plain', text);
	   event.preventDefault();
    });
    
    on("keydown", event => 
    {
	 	if (event.key === "Enter") // input does not detect newline
	 	{
		 	preventDefault(event);
		 	prev = editor.textContent;
		 	insert("\n\u200B"); // we must insert at least one character on the line 
		 	
	 	}
	 	if(isUndo(event))
	 	{
		 	if (history.length == 0) return;
		 	
		 	editor.textContent = history.pop();
		 	redohistory.push(prev);
		 	
		 	pf = longestCommonPrefix([editor.textContent,prev]);
		    p = pf.length;
		    d = editor.textContent.length - prev.length;
		    if (d > 0) p += d;
		    
		    markCode(editor);
		    setCaret(p); 

			prev = editor.textContent;	
		 	
	 	}
	 	if(isRedo(event))
	 	{
		 	if (redohistory.length == 0) return;
		 	
		 	editor.textContent = redohistory.pop();
		 	history.push(prev);
		 	
		 	pf = longestCommonPrefix([editor.textContent,prev]);
		    p = pf.length;
		    d = editor.textContent.length - prev.length;
		    if (d > 0) p += d;
		    
		    markCode(editor);
		    setCaret(p); 

			prev = editor.textContent;	
		 	
	 	}      
	});
	
	function insert(text) {
        text = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        document.execCommand("insertHTML", false, text); 	
    }

	function update()
    {
	    pf = longestCommonPrefix([editor.textContent,prev]);
	    p = pf.length;
	    d = editor.textContent.length - prev.length;
	    if (d > 0) p += d;
	    
	    markCode(editor);
	    setCaret(p); 
	    
	    history.push(prev);
	    prev = editor.textContent;
	    
	    shadoweditor = document.getElementById("shadoweditor");
	    if (editor != null) 
	    	shadoweditor.innerHTML = prev.replace('\u200B', '');
	    
	    
    }

	function setCaret(p)
	{
		const s = window.getSelection();
		const nodes = textNodesUnder(editor);
		var rest = p;
		nodes.forEach((node, index) =>
		{
			if (rest < 0) return;
			n = node.nodeValue.length;
			if (n >= rest)
			{
				s.setBaseAndExtent(node, rest, node, rest);
				rest = -1;
				return;
			}
			else
			{
				rest -= n;
			}			
		});
		if (rest<0) return;
		node = nodes.pop();
		if (n) 
		{
			n = node.nodeValue.length;
			s.setBaseAndExtent(node, n, node, n);
		}
	}

    function preventDefault(event) {
        event.preventDefault();
    }

    function isCtrl(event) {
        return event.metaKey || event.ctrlKey;
    }
	function isUndo(event) {
        return isCtrl(event) && !event.shiftKey && event.key === "z";
    }
    function isRedo(event) {
        return isCtrl(event) && event.shiftKey && event.key === "z";
	}
	
	function textNodesUnder(el)
	{
		var n, a=[], walk=document.createTreeWalker(el,NodeFilter.SHOW_TEXT,null,false);
		while(n=walk.nextNode()) a.push(n);
		return a;
	}

    
    function longestCommonPrefix(strs) {
    let longestPrefix = '';
    if (strs.length > 0) {
      longestPrefix = strs[0];
      for (let i = 1; i < strs.length; i++) {
        for (let j = 0; j < longestPrefix.length; j++) {
          if (strs[i][j] != longestPrefix[j]) {
            longestPrefix = longestPrefix.slice(0, j);
            break;
          }
        }
      }
    }
    return longestPrefix;
	};

   
}




