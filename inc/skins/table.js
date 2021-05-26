/* 
	Sortable and searchable table for Relation (print grid)
	
	Original source for sort
	https://stackoverflow.com/questions/14267781/sorting-html-table-with-javascript
	
	Original source for search
	https://stackoverflow.com/questions/51187477/how-to-filter-a-html-table-using-simple-javascript
	Modification: Use regex search and renamed tablefilter, added it to make it work for multiple tables in a page (input has id inputNNN and table has id tableNNN)
	
	Needs CSS (in default.css)
	
	.sortable th
{
    cursor: pointer;
}

input.sortable
{
	width: calc(100% - 7px);
	border: 1px solid black;
	padding-left: 5px;
	padding-right: 0px;
	padding-top: 3px;
	padding-bottom: 3px;
}

	and table must be preceded by
	
	'<nowiki><div><input type="text" id="input'.$id.'" class="sortable" onkeyup="tablefilter('.$id.')" placeholder="Filter..." title="Type in a name"></div></nowiki>'
	
	must have an id
	
	{| class="print sortable" maxgrid="'.$limit.'" id="table'.$id.'"
	
	and table.js must be inserted after the table to make it work.
	
*/


var getCellValue = function(tr, idx){ return tr.children[idx].innerText || tr.children[idx].textContent; }

var comparer = function(idx, asc) { return function(a, b) { return function(v1, v2) {
        return v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2);
    }(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));
}};

// do the work...
Array.prototype.slice.call(document.querySelectorAll('th')).forEach(function(th) { th.addEventListener('click', function() { 
        var table = th.parentNode
        while(table.tagName.toUpperCase() != 'TABLE') table = table.parentNode;
        Array.prototype.slice.call(table.querySelectorAll('tr:nth-child(n+2)'))
            .sort(comparer(Array.prototype.slice.call(th.parentNode.children).indexOf(th), this.asc = !this.asc))
            .forEach(function(tr) { table.appendChild(tr) });
    })
});


function tablefilter(id) {
  var input, filter, table, tr, td, cell, i, j;
  theid = id.toString();
  // console.log(theid);
  input = document.getElementById('input'+theid);
  //filter = input.value.toUpperCase();
  filter = input.value;
  table = document.getElementById('table'+theid);
  maxgrid = table.getAttribute('maxgrid');
  
  tr = table.getElementsByTagName("tr");
  
  try {
 	 regexfiler = new RegExp(filter, "i");
  }
  catch
  {
	 return;
  }
  
  console.log(maxgrid);
  
  visiblerows = 0;
  
  for (i = 0; i < tr.length; i++) {
    // Hide the row initially.
    tr[i].style.display = "none";
  
    td = tr[i].getElementsByTagName("td");
    for (var j = 0; j < td.length; j++) {
      cell = tr[i].getElementsByTagName("td")[j];
      if (cell) {
        // if (cell.innerHTML.toUpperCase().indexOf(filter) > -1) {
	    if (cell.innerHTML.match(regexfiler) && (maxgrid == '' || visiblerows <= maxgrid) ) {
          tr[i].style.display = "";
          visiblerows++;
          break;
        } 
      }
    }
  }
}


function setChangeListener (div, listener) {

    div.addEventListener("blur", listener);
    div.addEventListener("keyup", listener);
    div.addEventListener("paste", listener);
    div.addEventListener("copy", listener);
    div.addEventListener("cut", listener);
    div.addEventListener("delete", listener);
    div.addEventListener("mouseup", listener);

}

Array.prototype.slice.call(document.querySelectorAll('td')).forEach(function(td) { td.addEventListener('blur', 
function() { 
        var separator = ', '
        var table = td.parentNode
        while(table.tagName.toUpperCase() != 'TABLE') table = table.parentNode;
        var rows = table.querySelectorAll('tr');
        var csv = [];
        for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        for (var j = 0; j < cols.length; j++) {
            // Clean innertext to remove multiple spaces and jumpline (break csv)
            var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ')
            // Escape double-quote with double-double-quote (see https://stackoverflow.com/questions/17808511/properly-escape-a-double-quote-in-csv)
            
            // Push escaped string
            data = data.replace(/"/g, '""');
            
            if(i==0)
            {
            	row.push(data);
            	
            }
            else
            	
            	row.push('"' + data + '"');
		}
		if(i==0)
            {
            	csv.push("relation "+row.join(separator));  
            	csv.push("data");
            	
            }
            else
				csv.push(row.join(separator));  
		}
		
		csv.push("end data");
		
		
		
		theid = table.getAttribute('id').replace("table","");
		form = document.getElementById("form"+theid);
		file = form.getAttribute("file");
		
		csv.push("write "+'"' + file+'"' );
		
		var csv_string = csv.join('\n');
		// console.log(csv_string);
		
		textarea = form.querySelector('textarea');		
		textarea.innerHTML = csv_string;
		submit = form.querySelector('input');
		submit.disabled = false;
		
		
		
         
    })
});

