function parseFile(file, name="", prefix="", comment="") {
    var theFile    = file;
    var fileSize   = file.size;
    var fileName   = file.name
    var serverName = name;
    var prefix     = prefix;
    var theComment = comment;
    var theUploadTime = 0;
    var chunkSize  = 1024 * 1024; // bytes
    var offset     = 0;
    var self       = this; // we need a reference to the current object
    var chunkReaderBlock = null;
    
    var joblist    = {};
    var jobarray   = []; 
    var inverselist = {}; 
    var uploadlist = {};
    var starttime  = 0;
    var lastseconds = 999999999;
    var uploadedchunks = 0;
    var previouschunks = 0;
    var errorlevel = 0;
    
    
    
/**
 * 		Reads a chunk to be anlayzed as MD5 in buildChunkList
 *
 *      Async
 */
	 

    readFile = function(_offset, length, _file) {
        var r = new FileReader();
        var blob = _file.slice(_offset, length + _offset);
        key = _offset
        r.onload = buildChunkList;
        r.readAsBinaryString(blob);
        
    }



/**
 * 		Calculates MD5 of chunk pour constituer la liste et quand il finit, passe au checkChunks
 *
 */


    var buildChunkList = function(evt) {
        if (evt.target.error == null) {
            
            data = evt.target.result;
            key = md5(data);
            
            joblist[offset] = key;
            uploadlist[offset] = key;
            inverselist[key] = offset;
            jobarray.push(key);
           
            showProgress(uploadlist)
            done = Object.keys(joblist).length 
            done = done
            total = Math.ceil(theFile.size / chunkSize)
            showStatus("Reading " + done + " MB  / " + total + " MB <p><progress value="+(done*0.1)+" max="+total+">" )
            
            offset += chunkSize
            
        } else {
            showConsole("Read error: " + evt.target.error);
            return;
        }
        if (offset >= fileSize) {
            showProgress(uploadlist);
            showConsole("Done reading file " + Object.keys(joblist).length + " " + file.name )
            showStatus("Done reading <p><progress value="+(total*0.1)+" max="+total+">")   
           
            const d = new Date();
			starttime = d.getTime();
            checkChunks() 
            
            return;
        }

        // of to the next chunk
        readFile(offset, chunkSize, file);
    }

/**
 * 		Checks if all files of the joblist are on the server, if yes, removes them from the uploadList
 *
 *      Async
 */

	async function checkChunks() {
	    let formData = new FormData();
		checkchunks = jobarray.join(",");
		formData.append("checkchunks",checkchunks);

		formData.append("filename", file.name);        
	    fetch("index.php?action=uploadbigfile", { method: "POST",  body: formData }) 
		.then((response) =>
		{
		   if (response.ok)
		   {
		   		response.text().then ((text) =>
		   		{
	    	
		   			showStatus("Check server <p><progress value="+(total*0.1)+" max="+total+"> ");
	    	
			    	if (text) {
				    	try { 
					    	list = JSON.parse(text)
					    	
					    	for (const elem of list) {
					    		offset1 = inverselist[elem];
								delete(uploadlist[offset1]);
								previouschunks = previouschunks + 1;
								showProgress(uploadlist);
							}
					    	
					   	}
				    	catch(error) { alert(text); }
				    }
		    
					uploadJob();
					
				});
			}
			else
			{
				// there may have been a timeout
				// we try again to contact the server, but let some time (the server may be busy)
				showStatus("Recontacting server")
				setTimeout(function() { checkChunks(); } , 10000);
			}
				
	    })
	    .catch(err =>  { showStatus(err); setTimeout(function() { checkChunks(); } , 10000) } );
	}




/**
 * 		Uploads one chunk from the uploadlist to the server, et quand il a tout fait, passe au ComposeChunks
 *
 *      Async
 */
    
        
    async function uploadJob(top=false) {
	    
		offsets = Object.keys(uploadlist)
		if (!offsets.length)
		{
		    // we have uploaded all files and can go to the next step
		    done = 0;
		    total = Object.keys(joblist).length;
		    showStatus("Composing ...<p><progress value="+(done*0.1+0.9*total)+" max="+total+">"); 
		    composeChunks()  
		    return
		}
		
		// take the first element to upload
		offset1 = offsets[0];		
		offset1 = parseInt(offset1); // nasty error if string		
		key = uploadlist[offset1];
			
		var blob = file.slice(offset1, chunkSize + offset1);
			
		let formData = new FormData();           
		formData.append("uploadedfile", blob, key);
		fetch("index.php?action=uploadbigfile", { method: "POST",  body: formData }) 
		.then((response) =>
		{
		   if (response.ok)
		   {
		   		response.text().then ((text) =>
		   		{

				 	// file has been uploaded, we can remove it from the liste
				 	// (if not, we just redo the job
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
				
					// we create a waiting clock but wait 10 samples
					const d = new Date();
					let currenttime = d.getTime();
					clock = "..."
					uploadedchunks = uploadedchunks + 1
					if (uploadedchunks > 10) clock = getClock(currenttime,starttime,done-previouschunks,todo)
								
					// retain as field for creation of image page
					theUploadTime = Math.round((currenttime - starttime) / 1000.0)
					
					showStatus("Uploading " + done + " MB / " + total + " MB. Estimated time " + clock + unicodePixels(key)+"<p><progress value="+(done*0.8+0.1*total)+" max="+total+"> " )
				    setTimeout(function() { uploadJob(); } ,50)
			    
			    });
			}
			else
			{
				// there may have been a timeout
				// we try again to contact the server, but let some time (the server may be busy)
				showStatus("Recontacting server")
				setTimeout(function() { uploadJob(); } , 10000);
			}
		})
		.catch(err =>  { showStatus(err); setTimeout(function() { uploadJob(); } , 10000) } );
	}
   
/**
 * 		Sends a list to ther server to concatenate the chunks to the file. 
 *
 *      Async
 */


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
		formData.append("uploadtime", theUploadTime); 
		formData.append("start", start);          
	    fetch("index.php?action=uploadbigfile", { method: "POST",  body: formData })  
		.then((response) =>
		{
		   if (response.ok)
		   {
		   		response.text().then ((text) =>
		   		{
			    	if (text.substr(0,2) == 'ok')
			    	{
			    	
				    	showConsole(text);
				    	showStatus(text);
				    }
				    else if (text.substr(0,5) == 'limit')
					{
						done = text.substr(6)
						
						total = Object.keys(joblist).length;
						showConsole("Composing " + done + " MB / " + total + " MB <p><progress value="+(done*0.1+0.9*total)+" max="+total+">" );
						showStatus("Composing " + done + " MB / " + total + " MB <p><progress value="+(done*0.1+0.9*total)+" max="+total+">"); 
						composeChunks(text.substr(6));
					}
					else if (text.substr(0,19) == 'compose error chunk')
					{
						// something was not yet there, try again
						errorlevel = errorlevel + 1;
						
						if (errorlevel < 10)
						{
							setTimeout(function() { checkChunks(); } ,250) 
						}
				    }
				    else
				    {
					    showStatus(text);
				    }
			    
			    });
			}
			else
			{
				// there may have been a timeout
				// we try again to contact the server, but let some time (the server may be busy)
				showStatus("Recontacting server")
				setTimeout(function() { composeChunks(); } , 10000);
			}
		})
		.catch(err =>  { showStatus(err); setTimeout(function() { composeChunks(); } , 10000) } );
	}

	
	
    // now lets start the read with the first block
    readFile(offset, chunkSize, file);
        
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

function getClock(current, start, done, todo)
{
	// calculate milliseconds per megabyte (chunk  = 1 MB) based on done uploads
	mspermb = (current - start) / done
	mstodo = todo * mspermb
	seconds = Math.round(mstodo / 1000.0)
		
	// caclulate minutes and hours
	minutes = Math.floor(seconds / 60)
	
	seconds = seconds - minutes * 60
	seconds = "0" + seconds
	seconds = seconds.substr(-2)
	
	hours = Math.floor(minutes / 60)

	minutes = minutes - hours * 60
	minutes = "0" + minutes
	minutes = minutes.substr(-2)
	
	hours = "0" + hours
	hours = hours.substr(-2)
	
	clock = hours + ":" + minutes + ":" + seconds
	return clock

}

window.onerror = function myErrorHandler(errorMsg, url, lineNumber)
{
    showStatus("Error occured: " + errorMsg + "<br>" + lineNumber);//or any message
    return false;
}
