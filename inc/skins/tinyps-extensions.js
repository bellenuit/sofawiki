
rpnOperators.numberformat = function(context) {
    const [r] = context.pop("number");
    if (!r) return context;
    const x = new Intl.NumberFormat('en-US',{maximumFractionDigits: 2}).format(r.value).replaceAll(","," ");
    context.stack.push(new rpnString(x,context.heap));
    return context;
};

rpnOperators.patternfill = function(context) {
    const code = `
10 dict begin /p exch def
gsave
[ 1 0 0 1 0 0 ] setmatrix
pathbbox
/t exch def /r exch def /b exch def /l exch def
l b translate 
clip
r l sub t b sub p
grestore end newpath
`;
    context =  rpn(code, context);
    return context;
};


rpnOperators.preparechart = function(context) {
    const code = `
/TGL017 findfont 16 scalefont setfont
/chartrect [ 0 0 576 576 16 div 9 mul ] def
/chartmargins [ 100 80 5 60 ] def
/xlimits [ 0 0.2 1 ] def
/ylimits [ 0 0.2 1 ] def
/data [ [(k) (v) (x) (y)] [ (foo) 0.5 0.4 0.3 ] [ (bar) 0.7 0.8 0.9] ] def 

/round1 { log 0.5 sub round 10 exch exp } def

% preparechart
/alpha exch def /db exch def db length {
/data db alpha table def data 
/ymin data 1 get alpha get def 
/ymax ymin def ymax 
alpha 1 data 0 get length 1 sub { /col exch def
1 1 data length 1 sub { /row exch def
/y data row get col get def
/ymax y ymax max def
/ymin y ymin min def
} for
} for 
/ystep ymax ymin sub round1 def
ystep 0 eq { /ystep 1 def } if
/ymin ymin ystep div 0.5 sub round ystep mul def
/ymax ymax ystep div 0.5 add round ystep mul def
ymax ymin div 2 gt { /ymin 0 def } if
/ylimits [ ymin ystep ymax ] def 
/xlimits [ 0 1 data length 1 sub ] def
chartmargins 0 ymax numberformat stringwidth pop 10 add put } if


/square {
/h chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub def
chartmargins 2 chartrect 2 get chartmargins 0 get sub h sub put
} def

/chartproj { ylimits 0 get sub ylimits 2 get ylimits 0 get sub div chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub mul chartrect 1 get add chartmargins 1 get add exch 
xlimits 0 get sub xlimits 2 get xlimits 0 get sub div chartrect 2 get chartmargins 0 get sub chartmargins 2 get sub mul chartrect 0 get add chartmargins 0 get add exch } def

/hchartproj { ylimits 0 get sub ylimits 2 get ylimits 0 get sub div chartrect 2 get chartmargins 0 get sub chartmargins 2 get sub mul chartrect 0 get add chartmargins 0 get add exch 
xlimits 2 get exch sub xlimits 2 get xlimits 0 get sub div chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub mul chartrect 1 get add chartmargins 1 get add exch   exch } def

preparepatterns

/patterns [ {}
{ 0.078 0.431 0.667 setrgbcolor fill }
{ 0 0.510 0.353 setrgbcolor fill }
{ 0.902 0.196 0.157 setrgbcolor fill }
{ 0.941 0.549 0.157 setrgbcolor fill }
{ 0.427 0.224 0.545 setrgbcolor fill }
{ 0.941 0.784 0 setrgbcolor fill } ] def 

/colors [ {}
{ 0.078 0.431 0.667 setrgbcolor  }
{ 0 0.510 0.353 setrgbcolor  }
{ 0.902 0.196 0.157 setrgbcolor  }
{ 0.941 0.549 0.157 setrgbcolor  }
{ 0.427 0.224 0.545 setrgbcolor  }
{ 0.941 0.784 0 setrgbcolor  } ] def 

/legendstyle [ {} {} {} {} {} {} {} ] def

/xaxis { 0 setgray 1 setlinewidth xlimits 
xlimits 0 get 0 chartproj moveto xlimits 2 get 0 chartproj lineto stroke } def
/hxaxis { 0 setgray 1 setlinewidth xlimits 
xlimits 0 get 0 hchartproj moveto xlimits 2 get 0 hchartproj lineto stroke } def

/yaxis { 0 setgray 1 setlinewidth
0 ylimits 0 get chartproj moveto 0 ylimits 2 get chartproj lineto stroke } def
/hyaxis { 0 setgray 1 setlinewidth
xlimits 2 get ylimits 0 get hchartproj moveto xlimits 2 get ylimits 2 get hchartproj lineto stroke } def

/axis { xaxis yaxis } def
/haxis { hxaxis hyaxis } def

/border { 0 setgray 1 setlinewidth 
xlimits 0 get ylimits 0 get chartproj moveto 
xlimits 2 get ylimits 0 get chartproj lineto
xlimits 2 get ylimits 2 get chartproj lineto
xlimits 0 get ylimits 2 get chartproj lineto closepath stroke
} def

/xticks { 0 setgray 0.5 setlinewidth
xlimits 0 get xlimits 1 get xlimits 2 get { /x exch def
x { x 0 ylimits 0 get max chartproj moveto 0 -5 rlineto stroke
x 0 ylimits 0 get max chartproj exch x numberformat stringwidth pop 2 div sub exch 20 sub moveto x numberformat show} if
} for } def 
/yticks { 0 setgray 0.5 setlinewidth
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
y { 0 xlimits 0 get max y chartproj moveto -5 0 rlineto stroke
0 xlimits 0 get max y chartproj exch y numberformat stringwidth pop sub 7 sub exch 5 sub moveto y numberformat show} if
} for } def
/ticks { xticks yticks } def
/hyticks { 0 setgray 0.5 setlinewidth
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
y { xlimits 2 get y hchartproj moveto 0 -5 rlineto stroke
xlimits 2 get y hchartproj exch y numberformat stringwidth pop 2 div sub exch 20 sub moveto y numberformat show} if
} for } def

/xgrid { 0.5 setlinewidth
xlimits 0 get xlimits 1 get xlimits 2 get { /x exch def
x { x ylimits 0 get chartproj moveto x ylimits 2 get chartproj lineto stroke
} if
} for } def 
/hxgrid { 0.5 setlinewidth
xlimits 0 get xlimits 1 get xlimits 2 get { /x exch def
x { x ylimits 0 get hchartproj moveto x ylimits 2 get hchartproj lineto stroke
} if
} for } def 
/ygrid { 0.5 setlinewidth
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
y { xlimits 0 get y chartproj moveto xlimits 2 get y chartproj lineto stroke
} if
} for } def
/hygrid { 0.5 setlinewidth
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
y { xlimits 0 get y hchartproj moveto xlimits 2 get y hchartproj lineto stroke
} if
} for } def
/grid { xgrid ygrid } def
/hgrid { hxgrid hygrid } def

/description { 0 chartrect 3 get chartmargins 3 get sub 20 add moveto show } def
/credits { chartrect 0 get chartrect 1 get chartmargins 1 get add 40 sub moveto show } def
/xlabel { /s exch def xlimits 0 get xlimits 2 get add 2 div 0 chartproj 40 sub exch s stringwidth pop 2 div sub exch moveto s show } def
/ylabel { /s exch def 20 chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub 2 div chartrect 0 get add chartmargins 1 get add moveto 90 rotate s stringwidth pop 2 div neg 0 rmoveto s show -90 rotate } def
/title { /s exch def gsave 
/TGL017 findfont 20 scalefont setfont 
0 chartrect 3 get chartmargins 3 get sub 40 add moveto s show grestore } def




/bar {  /b exch def 
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get def
/d 1 b length 1 add div def
/x row 1 sub i d mul add d 2 div add def
x ylimits 0 get 0 max chartproj moveto
x d add ylimits 0 get 0 max chartproj lineto 
x d add y chartproj lineto 
x y chartproj lineto 
closepath patterns col get exec 0 setgray
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
} def

/stackedbar { /b exch def 
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y0 ylimits 0 get 0 max 0 1 i 1 sub { /j exch def data row get b j get get add} for def
/y y0 data row get col get add def
/d 1 1 1 add div def
/x row 1 sub d 2 div add def
x y0 chartproj moveto
x d add y0 chartproj lineto 
x d add y chartproj lineto 
x y chartproj lineto 
closepath patterns col get exec 0 setgray
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
} def

/hbar { /b exch def 
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get def
/d 1 b length 1 add div def
/x row 1 sub i d mul add d 2 div add def
x ylimits 0 get 0 max hchartproj moveto
x d add ylimits 0 get 0 max hchartproj lineto 
x d add y hchartproj lineto 
x y hchartproj lineto 
closepath patterns col get exec 0 setgray
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
} def
/hvotebar { /b exch def 
/ylimits [ 0 20 100 ] def
1 1 data length 1 sub { /row exch def 
/tot 0 def
0 1 b length 1 sub { /i exch def b i get /col exch def
/tot tot data row get col get add def
} for
/tot tot 100 div def
/y0 0 def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get tot div y0 add def
/d 1 def
/x row 0.75 sub def
x y0 hchartproj moveto
x 0.5 add  y0 hchartproj lineto 
x 0.5 add  y hchartproj lineto 
x y hchartproj lineto 
closepath patterns col get exec 0 setgray
/s y y0 sub round cvs def
x y0 y add 2 div hchartproj moveto y y0 s stringwidth pop 2 div neg -36 rmoveto s show
/y0 y def
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
} def

/line { 3 setlinewidth
{ /col exch def
/y data 1 get col get def
/x 0.5 def
x y chartproj moveto
2 1 data length 1 sub { /row exch def
/y data row get col get def
/x row 0.5 sub def
x y chartproj lineto 
legendstyle col { 3 setlinewidth
0 5 moveto 8 5 lineto  
colors col get exec stroke } put 
} for 
colors col get exec stroke
} forall
0 setgray
} def

/spline { 3 setlinewidth
{ /col exch def
/y data 1 get col get def
/x 0.5 def
x y chartproj moveto
x y chartproj 
2 1 data length 2 sub { /row exch def
/y data row get col get def
/y0 data row 1 sub get col get def
/y2 data row 1 add get col get def
/x row 0.5 sub def
x 0.33 sub y y2 y0 sub 6 div sub chartproj
x y chartproj curveto 
x 0.33 add y y2 y0 sub 6 div add chartproj
legendstyle col { 3 setlinewidth
0 5 moveto 8 5 lineto  
colors col get exec stroke } put 
} for 
/y data data length 1 sub get col get def
/x data length 1 sub 0.5 sub def
x y chartproj 
x y chartproj curveto
colors col get exec stroke
} forall
0 setgray
} def


/area { 3 setlinewidth
{ /col exch def
/y 0 def
/x 0 def
x y chartproj moveto
1 1 data length 1 sub { /row exch def
/y data row get col get def
/x row 0.5 sub def
x y chartproj lineto 
legendstyle col { 3 setlinewidth
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath
patterns col get exec stroke } put 
} for 
/y 0 def
/x xlimits 2 get def 
x y chartproj lineto 
patterns col get exec stroke
} forall
0 setgray
} def

/stackedarea { /b exch def 3 setlinewidth
0 1 b length 1 sub { /i exch def b i get /col exch def
xlimits 0 get 0 max ylimits 0 get 0 max chartproj moveto 
1 1 data length 1 sub { /row exch def
/y0 ylimits 0 get 0 max 0 1 i 1 sub { /j exch def data row get b j get get add} for def
/y y0 data row get col get add def
/x row 0.5 sub def
x y0 chartproj lineto
legendstyle col { 3 setlinewidth
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath
patterns col get exec stroke } put 
} for 
xlimits 2 get ylimits 0 get 0 max chartproj lineto 
1 1 data length 1 sub  { /row exch data length sub neg def
/y0 ylimits 0 get 0 max 0 1 i 1 sub { /j exch def data row get b j get get add} for def
/y y0 data row get col get add def
/x row 0.5 sub def
x y chartproj lineto
} for 
closepath
patterns col get exec stroke
} for
0 setgray
} def

/dot {  
{ /col exch def
1 1 data length 1 sub { /row exch def
/y data row get col get def
/x row 0.5 sub def
x y chartproj 4 0 360 arc colors col get exec fill
legendstyle col { 4 5 4 0 360 arc 
colors col get exec fill } put 
} for 
} forall
0 setgray
} def

/xydot { /b exch def /xcol b 0 get def /ycol b 1 get def
1 1 data length 1 sub {/row exch def
/x data row get xcol get def
/y data row get ycol get def
x y chartproj 4 0 360 arc colors xcol get exec fill
legendstyle xcol { 4 5 4 0 360 arc 
colors col get exec fill } put 
} for  
0 setgray
} def

/labelxydot { /b exch def /lcol b 0 get def /xcol b 1 get def /ycol b 2 get def
1 1 data length 1 sub {/row exch def
/x data row get xcol get def
/y data row get ycol get def
x y chartproj 4 0 360 arc colors xcol get exec fill
x y chartproj exch 6 add exch 5 sub moveto data row get lcol get show
legendstyle xcol { 4 5 4 0 360 arc 
colors col get exec fill } put 
} for  
0 setgray
} def

/bubbledot { /sc exch def /b exch def /lcol b 0 get def /xcol b 1 get def /ycol b 2 get def /rcol b 3 get def
1 1 data length 1 sub { /row exch def
/x data row get xcol get def
/y data row get ycol get def
x y chartproj data row get rcol get sqrt sc mul 0 360 arc colors xcol get 
exec fill
x y chartproj exch data row get rcol get sqrt sc mul add 2 add exch 5 sub moveto data row get lcol get show
legendstyle xcol { 4 5 4 0 360 arc 
colors col get exec fill } put 
} for  
0 setgray
} def

/map { /p exch def /b exch def /lcol b 0 get def /pcol b 1 get def  /rcol b 2 get def
1 1 data length 1 sub { /row exch def
data row get pcol get cvx exec
data row get lcol get 
data row get rcol get p
} for  
0 setgray
} def

/plot { /fn exch def /p2 exch def /pstep exch def /p1 exch def 
p1 fn chartproj moveto
p1 pstep add pstep p2 { fn chartproj lineto } for 
0 setgray 
} def

/bottomlegend { /b exch def
0 chartmargins 1 get 60 sub /y exch def /x exch def x y
b { /col exch def
gsave
x y translate
legendstyle col get exec
12 0 moveto
0 setgray data 0 get col get show
grestore
/x x data 0 get col get stringwidth pop add 20 add def 
} forall
} def

/toplegend { /b exch def /s exch def
0 chartrect 3 get chartmargins 3 get sub 20 add /y exch def /x exch def x y
x y moveto s show
/x x s stringwidth pop add 12 add def 
b { /col exch def
gsave
x y translate
legendstyle col get exec
12 0 moveto
0 setgray data 0 get col get show
grestore
/x x data 0 get col get stringwidth pop add 24 add def 
} forall
} def

/category { 0 setgray
1 1 data length 1 sub { /row exch def
row 0.5 sub 0 chartproj 20 sub moveto data row get 0 get stringwidth pop 2 div neg 0 rmoveto data row get 0 get show
} for 
} def

/hcategory { 0 setgray
1 1 data length 1 sub { /row exch def
row 0.5 sub 0 hchartproj 8 sub moveto data row get 0 get stringwidth pop neg 8 sub 0 rmoveto data row get 0 get show
} for 
} def

`;
    context =  rpn(code, context);
    return context;
};


rpnOperators.preparepatterns = function(context) {
    const code = `
/hpat { /h exch def /w exch def
0 6 h { newpath 0 exch moveto w 0 rlineto stroke } for 
} def

/dhpat { /h exch def /w exch def
0 3 h { newpath 0 exch moveto w 0 rlineto stroke } for 
} def

/vpat { /h exch def /w exch def
0 6 w { newpath 0 moveto 0 h rlineto stroke } for 
} def

/dvpat { /h exch def /w exch def
0 3 w { newpath 0 moveto 0 h rlineto stroke } for 
} def

/apat { /h exch def /w exch def /m w h max def
0 6 1.41 mul w { newpath 0 moveto m m rlineto stroke } for 
0 6 1.41 mul  h { newpath 0 exch moveto m m rlineto stroke } for 
} def

/dapat { /h exch def /w exch def /m w h max def
0 3 1.41 mul w { newpath 0 moveto m m rlineto stroke } for 
0 3 1.41 mul h { newpath 0 exch moveto m m rlineto stroke } for 
} def

/dpat { /h exch def /w exch def /m w h max def
0 6 1.41 mul w { newpath m moveto m m neg rlineto stroke } for 
0 6 1.41 mul h { newpath 0 exch moveto m m neg rlineto stroke } for 
} def

/ddpat { /h exch def /w exch def /m w h max def
0 3 1.41 mul w { newpath m moveto m m neg rlineto stroke } for 
0 3 1.41 mul h { newpath 0 exch moveto m m neg rlineto stroke } for 
} def

/ppat { /h exch def /w exch def
0 6 h { /hi exch def 0 6 w { /wi exch def newpath wi hi 1 0 360 arc fill } for } for 
} def

/cpat { 2 copy hpat vpat } def
/acpat { 2 copy apat dpat } def

/raster { /perc exch def /h exch def /w exch def
/r perc 100 div 36 mul 3.14159 div sqrt def
0 6 h { /hi exch def 0 6 w { /wi exch def newpath wi hi r 0 360 arc fill } for } for 
} def
`;
    context =  rpn(code, context);
    return context;
};



rpnOperators.table = function(context) {
    const [haslabel, tablename] = context.pop("number", "string");
    if (!tablename) return context; console.log("table " +tablename.value )
	results = rpnTables[tablename.value];	
	list = [];
	list.push('[');
	elem = results;
	console.log(rpnTables);
	  
	   if (Array.isArray(elem)) {
		   let first = elem[0];
		   if (typeof first === 'object') {
			   let cols = Object.keys(first);
			   let values = [];
			   
			   list.push('[');
			   for(key in first) {
				   list.push('(' + key + ')');
			   }
			   list.push(']');
			   
			   for(row of elem) {
				   list.push('[');
				   let fields = [];
				   var k = 0;
				   for(key in row) {
					   if (k < haslabel.value)
						   list.push('(' + row[key] + ')');
					   else
					       if (row[key]) list.push(row[key]); else list.push('0');
			           k++;
				   }
				   list.push(']');
				   
			   }
		   }
	   } else {
		   context.error("missingtable");
	   }
	   
	
	list.push(']');
	const s = list.join(" ");
	console.log(s.slice(0,140));
	context = rpn(s, context);
    return context;
};
