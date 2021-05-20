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
  
  for (i = 1; i < tr.length; i++) {
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

