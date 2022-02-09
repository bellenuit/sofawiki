function parseFile(file, name="", prefix="", comment="") {
    var theFile    = file;
    var fileSize   = file.size;
    var fileName   = file.name
    var serverName = name;
    var prefix     = prefix;
    var theComment = comment;
    var chunkSize  = 1024 * 1024; // bytes
    var offset     = 0;
    var self       = this; // we need a reference to the current object
    var chunkReaderBlock = null;
    
    var joblist    = {};
    var jobarray   = []; 
    var inverselist = {}; 
    var uploadlist = {};
    var starttime  = 0;
    var errorlevel = 0;
    const d = new Date();
    starttime = d.getTime();

    var readEventHandler = function(evt) {
        if (evt.target.error == null) {
            
            data = evt.target.result;
            key = md5(data);
            
            joblist[offset] = key;
            uploadlist[offset] = key;
            inverselist[key] = offset;
            jobarray.push(key);
           
            showProgress(uploadlist)
            showStatus("Reading " + Object.keys(joblist).length + " MB ")
            
            offset += 1024 * 1024
            
        } else {
            showConsole("Read error: " + evt.target.error);
            return;
        }
        if (offset >= fileSize) {
            showProgress(uploadlist);
            showConsole("Done reading file " + Object.keys(joblist).length + " " + file.name)
            showStatus("Done reading")   
           
            // uploadJob();
            checkChunks() 
            
            return;
        }

        // of to the next chunk
        chunkReaderBlock(offset, 1024 * 1024, file);
    }
    
    

    chunkReaderBlock = function(_offset, length, _file) {
        var r = new FileReader();
        var blob = _file.slice(_offset, length + _offset);
        key = _offset
        r.onload = readEventHandler;
        r.readAsBinaryString(blob);
    }
    
        
    async function uploadJob() {
	    
    offsets = Object.keys(uploadlist)
    if (!offsets.length)
    {
	    done = 0;
	    total = Object.keys(joblist).length;
	    showStatus("Composing " + done + " MB / " + total + " MB <p><progress value="+done+" max="+total+">"); 
	    composeChunks()
	    
	    return
	}
	
	// showConsole(JSON.stringify(offsets));
	
	offset1 = offsets[0];
	key = uploadlist[offset1];
	
	
	
	// showConsole(offset1 + ": " + key + " " + 1024 * 1024);
	
	offset1 = parseInt(offset1); // nasty error if string
	
	var blob = file.slice(offset1, 1024 * 1024 + offset1);
	
	x =   blob.size
	y =   offset1 	
	// showConsole("x "+x);
	// showConsole("y "+x);
	
    let formData = new FormData();           
    formData.append("uploadedfile", blob, key);
    response = await fetch("index.php?action=uploadbigfile", {
      method: "POST", 
      body: formData
    });    
    response.text().then(function(text) { 
	    
	   //  showConsole(text); 
	    
	 	if (text.indexOf("upload ok") > -1)
	    {
		    key = text.substr(10)
		    offset1 = inverselist[key];
		    delete(uploadlist[offset1]);
		}
		
		showProgress(uploadlist); 
		total = Object.keys(joblist).length;
		todo = Object.keys(uploadlist).length;
		done = total - todo
		
		const d = new Date();
		let currenttime = d.getTime();
		
		mspermb = (currenttime - starttime) / done
		mstodo = todo * mspermb
		seconds = Math.round(mstodo / 1000.0)
		
		minutes = Math.floor(seconds / 60)
		seconds = seconds - minutes * 60
		seconds = "0" + seconds
		seconds = seconds.substr(0,2)
		
		clock = minutes + ":" + seconds 
		
		showStatus("Uploading " + done + " MB / " + total + " MB "+unicodePixels(key)+"<p><progress value="+done+" max="+total+"> " + clock )
	    setTimeout(function() { uploadJob(); } ,50) });
	}
   

	async function composeChunks(start=0) {
	    let formData = new FormData();
	    composechunks = jobarray.join(",");
		formData.append("composechunks",composechunks);
		name = fileName
		if (serverName) {
			name = serverName
			if (getExtension(serverName) != getExtension(fileName)) name = name + "." + getExtension(fileName)
		}
		formData.append("filename", prefix+name); 
		formData.append("comment", theComment); 
		formData.append("start", start);          
	    response = await fetch("index.php?action=uploadbigfile", {
	      method: "POST", 
	      body: formData
	    });    
	    node = document.getElementById("status")
	    response.text().then(function(text)  {  
	    	
	    	showStatus(text);
	    	
	    	if (text.substr(0,2) == 'ok')
	    	{
	    	
		    	showConsole(text);
		    	showStatus(text);
		    }
		    else if (text.substr(0,5) == 'limit')
			{
				done = text.substr(6)
				total = Object.keys(joblist).length;
				showConsole("Composing " + done + " MB / " + total + " MB <p><progress value="+done+" max="+total+">" );
				showStatus("Composing " + done + " MB / " + total + " MB <p><progress value="+done+" max="+total+">"); 
				composeChunks(text.substr(6));
			}
			else if (text.substr(0,19) == 'compose error chunk')
			{
				// something was not yet there, try again
				errorlevel = errorlevel + 1;
				
				if (errorlevel < 10)
				{
					setTimeout(function() { chechChunks(); } ,250) 
				}
		    }
	    });
	}


	async function checkChunks() {
	    let formData = new FormData();
		checkchunks = jobarray.join(",");
		formData.append("checkchunks",checkchunks);

		formData.append("filename", file.name);        
	    response = await fetch("index.php?action=uploadbigfile", {
	      method: "POST", 
	      body: formData
	    });    
	    node = document.getElementById("status")
	    response.text().then(function(text)  { 
	    	
	    	showStatus("Check chunks");
	    	
	    	if (text) {
		    	try { 
			    	list = JSON.parse(text)
			    	
			    	for (const elem of list) {
			    		offset1 = inverselist[elem];
						delete(uploadlist[offset1]);
						showProgress(uploadlist);
					}
			    	
			   	}
		    	catch(error) { alert(text); }
		    }
		    
		    uploadJob();
	    });
	}
	
	
    // now lets start the read with the first block
    chunkReaderBlock(offset, 1024 * 1024, file);
    
}


function showProgress(list)
{
	node = document.getElementById("progress")
	node.innerHTML = JSON.stringify(list,null,4)
}

function showConsole(msg)
{
	node = document.getElementById("console")
	node.innerHTML = node.innerHTML + "\r\n" + msg
}

function showStatus(msg)
{
	node = document.getElementById("status")
	node.innerHTML = msg
}

function unicodePixels(msg)
{
	// 8 pixel values
	pixels = {}
	pixels["0"] = "\u2596"; pixels["1"] = "\u2597"; pixels["2"] = "\u2584"; pixels["3"] = "\u2598"; 
	pixels["4"] = "\u258C"; pixels["5"] = "\u259A"; pixels["6"] = "\u2599"; pixels["7"] = "\u259D";
	pixels["8"] = "\u259E"; pixels["9"] = "\u2590"; pixels["a"] = "\u259F"; pixels["b"] = "\u2580"; 
	pixels["c"] = "\u259B"; pixels["d"] = "\u259C"; pixels["e"] = "\u259F"; pixels["f"] = "\u2588"; 
	
	s = "";
	for (var i = 0; i < msg.length; i++ )
	{
		s = s + pixels[msg[i]];
		if (!((i+1) % 8)) s = s + "<br>"
	} 
	
	return "<pre>"+s+"</pre>"
}

function getExtension(filename)
{
  return filename.split(".").pop();
}
