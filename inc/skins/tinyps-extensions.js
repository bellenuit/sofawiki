rpnOperators.autokern = function(context) {

	const code = `kernvalue neg 0 translate`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.charpath = function(context) {
    const [s] = context.pop("string");
    if (!context.graphics.current.length) {
       return context.error("nocurrentpoint");
    }
    if (!context.graphics.font) {
       return context.error("nocurrentfont");
    }
    if (!s) return context;
    const font = rpnFonts[context.graphics.font];
    if (!font) {
       return context.error("nocurrentfont");
    }
    const scale = context.graphics.size / font.head.unitsPerEm;
    var ps = " currentpoint currentpoint translate currentmatrix " + scale + " " + scale + " scale ";
    for (let i = 0; i < s.value.length; i++) {
        const c = s.value.charCodeAt(i);
        const gi = font.glyphIndex(c); 
        ps += font.glyphPath(gi);
        ps += font.glyphWidth(gi) + " 0 translate ";
    }
    ps += " setmatrix neg exch neg exch translate ";
    context = rpn(ps, context);
    return context;
};

rpnOperators.charprofile = function(context) {
    const [c] = context.pop("number");
    if (!context.graphics.font) {
       return context.error("nocurrentfont");
    }
    const fontres = rpnFonts[context.graphics.font];
    if (!fontres) {
       return context.error("nocurrentfont");
    }
    
    if (c < 65 || c > 122 ){
    context.stack.push(new rpnNumber(0));
    context.stack.push(new rpnNumber(0));
    context.stack.push(new rpnNumber(0));
    context.stack.push(new rpnNumber(0));
    context.stack.push(new rpnNumber(0));
    context.stack.push(new rpnNumber(0));    
    return context;
    }
    
    context2 = new rpnContext;
    context2.graphics.font = context.graphics.font;
    context2.graphics.size = context.graphics.size;   
    
    var ps = "";
    var gw = 0; 
    
    const type3mode = fontres.value && fontres.value.FontType.value == 3;

    
    const scale = type3mode ? 1 : fontres.head.unitsPerEm / 1000 ;
    if (type3mode) {
    	// context2.stack.push(fontres);
        // ps += " /currentfontdict exch def ";
/*         ps += " currentfontdict /FontMatrix get 0 get currentfontdict /FontMatrix get 3 get  scale "; */
/*         ps +=  context.graphics.size + " " + context.graphics.size + " scale  "; */
        ps += " 5 dict begin ";
        // ps += " /blabla { } def ";
        ps += " /fill {  } def ";
    	ps += " /stroke { } def ";
    	// BuildChar does not seem to work
        ps += " currentfontdict " + c.value + " currentfontdict begin "; 
        ps += " BuildChar " //  CharacterDefs charname get exec ";

        //ps += " /charname Encoding "+c.value+" get def ";
    	// ps += " CharacterDefs charname get exec";
    	ps += " end end";
    	let gn = fontres.value.Encoding.value[c.value].value;

        gw = fontres.value.Metrics.value[gn].value;
    } else {
        let gi = fontres.glyphIndex(c.value);
        ps += fontres.glyphPath(gi);
        gw = fontres.glyphWidth(gi);
    }
    context2.showmode = false;
    context2 = rpn(ps, context2);
//         postMessage(["status",context.id,"ps " + ps,null]);
    //postMessage(["status",context.id,"path " + JSON.stringify(context2.graphics.path),null]);
    // postMessage(["status",context.id,"stack " + JSON.stringify(context2.stack),null]); 
    //postMessage(["status",context.id,"dict " + JSON.stringify(context2.dict),null]); 
    var minx0 = gw/2;
    var maxx0 = gw/2;
    var minx1 = gw/2;
    var minx2 = gw/2;
    var maxx1 = gw/2;
    var maxx2 = gw/2;
    for (let subpath of context2.graphics.path)
    for (let seg of subpath) {
        if (seg.length) {

               let [x, y] = [seg[seg.length-2], Math.round(seg[seg.length-1]/scale/100)*100];
               if (y == 0 || y == 100 || y == 200) {
               		minx0 = Math.min(minx0, x);
                    maxx0 = Math.max(maxx0, x);
               }
               if (y == 300 || y == 400 || y == 500) {
               		minx1 = Math.min(minx1, x);
                    maxx1 = Math.max(maxx1, x);
               }
               if (y == 500 || y == 600 || y == 700) {
               		minx2 = Math.min(minx2, x);
                    maxx2 = Math.max(maxx2, x);
               }
           // if segment long, we add middle point 
              
              if (Math.abs(seg[seg.length-1]-seg[2])/scale > 500) {
              let [x, y] = [(seg[1]+seg[seg.length-2])/2, Math.round((seg[2]+seg[seg.length-1])/2/scale/100)*100];
               if (y == 0 || y == 100 || y == 200) {
               		minx0 = Math.min(minx0, x);
                    maxx0 = Math.max(maxx0, x);
               }
               if (y == 300 || y == 400 || y == 500) {
               		minx1 = Math.min(minx1, x);
                    maxx1 = Math.max(maxx1, x);
               }
               if (y == 500 || y == 600 || y == 700) {
               		minx2 = Math.min(minx2, x);
                    maxx2 = Math.max(maxx2, x);
               }
               }
           
           
        }
    }

    context.stack.push(new rpnNumber(Math.round(minx0)));
    context.stack.push(new rpnNumber(Math.round(minx1)));
    context.stack.push(new rpnNumber(Math.round(minx2)));
    context.stack.push(new rpnNumber(Math.round(gw - maxx0)));
    context.stack.push(new rpnNumber(Math.round(gw - maxx1)));
    context.stack.push(new rpnNumber(Math.round(gw - maxx2)));
    //context.stack.push(new rpnNumber(Math.round(gw/2)));
    
    return context;
};



rpnOperators.combsort = function(context) {
	const code = `10 dict begin /arr exch def
/d arr length 1 sub def
d d mul %max executions in bubble mode
{ /sorted 1 def
  /lastround d 1 eq def
  0 1 arr length d sub 1 sub { /i exch def
	 /v1 arr i get def
	 /v2 arr i d add get def
	 v1 v2 compare not { /sorted 0 def
		arr i v2 put
		arr i d add v1 put
	 } if
  } for
  /d d 1.3 div floor 1 max def
  lastround sorted mul { exit } if
} repeat
end`;
	context =  rpn(code, context);
	return context;
};


rpnOperators.compare = function(context) {
	const code = `le`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.concat = function(context) {

	const code = `
3 dict begin /b exch def /a exch def
/c a length b length add string def
c 0 a putinterval
c a length b putinterval
c end `;
	context =  rpn(code, context);
	return context;
};


rpnOperators.cshow = function(context) {
	const code = `
dup stringwidth pop 2 div neg 0 rmoveto show`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.countblanks = function(context) {
	const code = ` 10 dict begin /c 0 def { ( ) search { /c c 1 add def pop pop } { pop c end exit } ifelse } loop `;
	context =  rpn(code, context);
	return context;
};


rpnOperators.currencyformat = function(context) {
	const [r] = context.pop("number");
	if (!r) return context;
	const x = new Intl.NumberFormat('en-US',{maximumFractionDigits: 2, minimumFractionDigits: 2}).format(r.value).replaceAll(","," ").replace(/^-0$/,"0");
	context.stack.push(new rpnString(x,context.heap));
	return context;
};


rpnOperators.currentfontdict = function(context) {
    const fontres = rpnFonts[context.graphics.font];
    const type3mode = fontres.value && fontres.value.FontType.value == 3;
    if (! type3mode) {
    	context.stack.push(new rpnError("wrongfonttype"));
    	return context;
    }
    context.stack.push(fontres);
    return context;
};

rpnOperators.currentpath = function(context) {
	if (!context.graphics.current.length) {
	   context.stack.push(new rpnError("nocurrentpoint"));
       return context;
    }
    const p = new rpnArray([], context.heap);
    for (let subpath of context.graphics.path)
    for (let seg of subpath) {
    	let sp = new rpnArray([], context.heap);
        if (seg.length) {
        	sp.value.push(new rpnString(seg[0], context.heap));
        	for (i = 1; i < seg.length; i++)
        	   sp.value.push(new rpnNumber(seg[i]));        	
        }
        p.value.push(sp)
    }
    context.stack.push(p)
    return context;
}

rpnOperators.doughnutarc = function(context) {
	const code = ` 10 dict begin /a2 exch  def /a1 exch def /r2 exch def /r1 exch def /y exch def /x exch def
x a1 cos r1 mul add
y a1 sin r1 mul add moveto
x a1 cos r2 mul add
y a1 sin r2 mul add lineto
x y r2 a1 a2 arc
x a2 cos r1 mul add
y a2 sin r1 mul add lineto
x y r1 a2 a1 arc closepath
end `;
	context =  rpn(code, context);
	return context;
};


rpnOperators.findfontdict = function(context) {
    const [n] = context.pop("name");
    if (!n) return context;
    if (!rpnFonts[n.value]) {
        return context.error("notyp3font" );
    }
    context.stack.push(rpnFonts[n.value]);
    return context;
};

rpnOperators.imagedata = function (context) {
	const [id] = context.pop("string");	
	const imagedata = dataimages[id.value];
	
	context.stack.push(new rpnNumber(imagedata.width));
	context.stack.push(new rpnNumber(imagedata.height));
	
	const binary = imagedata.data;
	const arr = new Array(binary.length);
	for (var i = 0; i < binary.length; i++) {
		arr[i] = String.fromCharCode(binary[i])
	}

	const ar = new rpnString(arr.join(''),context.heap)
	ar.reference.inc();
	context.stack.push(ar);	

	
	return context;
}


rpnOperators.jshow = function(context) {
	const code = `10 dict begin /s exch def
s stringwidth pop sub s countblanks div 0 32 s widthshow end`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.jspace = function(context) {
	const code = `10 dict begin
/countblanks { 10 dict begin /c 0 def { ( ) search { /c c 1 add def pop pop } { pop c end exit } ifelse } loop } def
/s exch def s stringwidth pop sub s countblanks div end`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.kernvalue = function(context) {
	const font = rpnFonts[context.graphics.font];
	const type3mode = font.value && font.value.FontType.value == 3;
	const mindistance = (type3mode) ? 150 : font.head.unitsPerEm * 0.15;
	const code = `10 dict begin /c2 exch def /c1 exch def 
c1 charprofile /r3 exch def /r2 exch def /r1 exch def pop pop pop
c2 charprofile pop pop pop /l3 exch def /l2 exch def /l1 exch def
 r3 l3 add r2 l2 add min r1 l1 add min ${mindistance} sub 0 max end`;
	context =  rpn(code, context);
	return context;
};




rpnOperators.numberformat = function(context) {
	const [r] = context.pop("number");
	if (!r) return context;
	const x = new Intl.NumberFormat('en-US',{maximumFractionDigits: 2, minimumFractionDigits: 0}).format(r.value).replaceAll(","," ").replace(/^-0$/,"0");
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
/chartrect [ 0 0 640 640 16 div 9 mul ] def
/chartmargins [ 100 30 5 60 ] def
/xlimits [ 0 0.2 1 ] def
/ylimits [ 0 0.2 1 ] def
/data [ [(k) (v) (x) (y)] [ (foo) 0.5 0.4 0.3 ] [ (bar) 0.7 0.8 0.9] ] def 

/round1 { 1.5 div log 2 mul round 2 div dup dup floor sub 0.1 exch gt { 2.5  } { 10 } ifelse exch 1 sub floor 10 exch exp mul } def

% preparechart
/ymax 0 def
/alpha exch def /db exch def db length {
/data db alpha table def 
/ymin data 1 get alpha get def 
/ymax ymin def  
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
/xlimits [ 0 1 data length 1 sub ] def } if
/labellimit ylimits 1 get def

/xlog 0 def
/ylog 0 def


/square {
/h chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub def
chartmargins 2 chartrect 2 get chartmargins 0 get sub h sub put
} def

/chartproj { 
ylog { 10 ylimits 0 get exp max log  } if
ylimits 0 get sub ylimits 2 get ylimits 0 get sub div chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub mul chartrect 1 get add chartmargins 1 get add exch 
xlog { 10 xlimits 0 get exp max log  } if
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
{ 0.941 0.784 0 setrgbcolor fill } 
{ 0.75 setgray fill } 
{ 0.50 setgray fill } 
{ 0.25 setgray fill } 
{ 0.00 setgray fill } ] def 

/colors [ {}
{ 0.078 0.431 0.667 setrgbcolor  }
{ 0 0.510 0.353 setrgbcolor  }
{ 0.902 0.196 0.157 setrgbcolor  }
{ 0.941 0.549 0.157 setrgbcolor  }
{ 0.427 0.224 0.545 setrgbcolor  }
{ 0.941 0.784 0 setrgbcolor  } 
{ 0.75 setgray } 
{ 0.50 setgray } 
{ 0.25 setgray } 
{ 0.00 setgray } ] def 

/legendstyle [ {} {} {} {} {} {} {} {} {} {} {}] def

/textsizes { /titlesize exch def /bodysize exch def /labelsize exch def
/textfont (KugiRegular) def
textfont cvn bodysize selectfont
chartmargins 0 ymax numberformat stringwidth pop bodysize add put
chartmargins 1 bodysize 4 mul put 
chartmargins 2 bodysize 2 div put
chartmargins 3 titlesize 1.2 mul bodysize 2.2 mul add bodysize 2 mul add put
 } def
13 16 20 textsizes


/gridlinewidth 1.5 def
/axislinewidth 1.5 def
/borderlinewidth 1.5 def
/segmentborderlinewidth 1.5 def
/boldlinewidth 3 def

/gridcolor { 0.7 setgray } def
/axiscolor { 0 setgray } def
/bordercolor { 0 setgray } def
/labelcolor { 0 setgray } def
/invertedlabelcolor { 1 setgray } def
/labelformat { numberformat } def
/zeroticks 1 def 
/tickmark 0 def
1 setlinecap
1 setlinejoin

/dotsize 4 def

/xaxis { axiscolor axislinewidth setlinewidth  
xlimits 0 get xlog { 10 exch exp } if
0 chartproj moveto 
xlimits 2 get xlog { 10 exch exp } if
0 chartproj lineto stroke } def

/hxaxis { axiscolor axislinewidth setlinewidth xlimits 
xlimits 0 get 0 hchartproj moveto xlimits 2 get 0 hchartproj lineto stroke } def

/yaxis { axiscolor axislinewidth setlinewidth
0 ylimits 0 get ylog { 10 exch exp } if
chartproj moveto 0 ylimits 2 get ylog { 10 exch exp } if
chartproj lineto stroke } def

/hyaxis { axiscolor axislineswidth setlinewidth
xlimits 2 get ylimits 0 get hchartproj moveto xlimits 2 get ylimits 2 get hchartproj lineto stroke } def

/axis { xaxis yaxis } def
/haxis { hxaxis hyaxis } def

/border { bordercolor borderlinewidth setlinewidth 
xlimits 0 get ylimits 0 get chartproj moveto 
xlimits 2 get ylimits 0 get chartproj lineto
xlimits 2 get ylimits 2 get chartproj lineto
xlimits 0 get ylimits 2 get chartproj lineto closepath stroke
} def

/externalborder { bordercolor borderlinewidth setlinewidth 
chartrect 0 get chartrect 1 get moveto 
chartrect 2 get 0 rlineto
0 chartrect 3 get rlineto 
chartrect 2 get neg 0 rlineto closepath stroke
} def


/xticks { gridlinewidth setlinewidth textfont cvn bodysize selectfont
xlimits 0 get xlimits 1 get xlimits 2 get { /x exch def
xlog { /x x 10 exch exp def } if
x zeroticks or { labelcolor 
tickmark { x 0 ylimits 0 get max chartproj moveto 0 bodysize 4 div rlineto stroke } if
labelcolor x 0 ylimits 0 get max chartproj exch x labelformat stringwidth pop 2 div sub exch bodysize sub moveto x labelformat show} if
} for } def 


/yticks { gridlinewidth setlinewidth textfont cvn bodysize selectfont
ylimits 0 get ylimits 1 get ylimits 2 get {
/y exch def
ylog { /y y 10 exch exp def } if
y zeroticks or { labelcolor 
tickmark { 0 xlimits 0 get max y chartproj moveto bodysize 4 div 0 rlineto stroke } if
labelcolor 0 xlimits 0 get max y chartproj exch y labelformat stringwidth pop sub bodysize 2 div sub exch 0 sub moveto y labelformat show} if
} for } def

/ticks { xticks yticks } def

/hyticks { gridlinewidth setlinewidth textfont cvn bodysize selectfont
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
y { labelcolor 
tickmark { xlimits 2 get y hchartproj moveto 0 -5 rlineto stroke } if
labelcolor xlimits 2 get y hchartproj exch y labelformat stringwidth pop 2 div sub exch 20 sub moveto y labelformat show} if
} for } def

/xgrid { gridcolor gridlinewidth setlinewidth
xlimits 0 get xlimits 1 get xlimits 2 get { /x exch def
xlog { /x 10 x exp def } if
x zeroticks or { x ylimits 0 get ylog { 10 exch exp } if
chartproj moveto x ylimits 2 get ylog { 10 exch exp } if
chartproj lineto stroke
} if
} for } def 

/hxgrid { gridcolor gridlinewidth setlinewidth
xlimits 0 get xlimits 1 get xlimits 2 get { /x exch def
x zeroticks or { x ylimits 0 get hchartproj moveto x ylimits 2 get hchartproj lineto stroke
} if
} for } def 

/ygrid { gridcolor gridlinewidth setlinewidth
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
ylog { /y 10 y exp def } if
y zeroticks or { xlimits 0 get xlog { 10 exch exp } if
y chartproj moveto xlimits 2 get xlog { 10 exch exp } if
y chartproj lineto stroke
} if
} for } def

/hygrid { gridcolor gridlinewidth setlinewidth
ylimits 0 get ylimits 1 get ylimits 2 get { /y exch def
y zeroticks or { xlimits 0 get y hchartproj moveto xlimits 2 get y hchartproj lineto stroke
} if
} for } def
/grid { xgrid ygrid } def
/hgrid { hxgrid hygrid } def

/description { labelcolor textfont cvn bodysize selectfont
chartrect 0 get bodysize 2 div add
chartrect 1 get chartrect 3 get add chartmargins 3 get sub bodysize 2 mul add 
moveto show } def

/credits { labelcolor textfont cvn bodysize selectfont
chartrect 0 get bodysize 2 div add
chartrect 1 get chartmargins 1 get add bodysize 2.5 mul sub
moveto show } def

/xlabel { /s exch def labelcolor textfont cvn bodysize selectfont
xlimits 0 get xlimits 2 get add 2 div 0 chartproj 40 sub exch s stringwidth pop 2 div sub exch moveto s show } def

/ylabel { /s exch def labelcolor textfont cvn bodysize selectfont
20 chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub 2 div chartrect 0 get add chartmargins 1 get add moveto 90 rotate s stringwidth pop 2 div neg 0 rmoveto s show -90 rotate } def

/title { /s exch def gsave labelcolor textfont cvn titlesize selectfont
chartrect 0 get bodysize 2 div add 
chartrect 1 get chartrect 3 get add chartmargins 3 get sub bodysize 3.5 mul add
moveto s show grestore } def


/bar {  /b exch def 
gsave
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get def
/d 1 b length 1 add div def
/x row 1 sub i d mul add d 2 div add def
x ylimits 0 get 0 max chartproj moveto
x d add ylimits 0 get 0 max chartproj lineto 
x d add y chartproj lineto 
x y chartproj lineto 
closepath
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke 0 setgray
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore 
} def

/barvalues {  /b exch def 
gsave
textfont cvn labelsize selectfont
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get def
/d 1 b length 1 add div def
/x row 1 sub i d mul add d 2 div add def
x ylimits 0 get 0 max chartproj moveto
x d add ylimits 0 get 0 max chartproj lineto 
x d add y chartproj lineto 
x y chartproj lineto 
closepath
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray
y 0 gt { 
labelcolor
x d 2 div add y chartproj moveto 0 y round labelformat dup stringwidth pop 2 div neg 4 rmoveto show
} if
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore
} def

/stackedbar { /b exch def 
gsave
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
closepath
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore
} def

/hbar { /b exch def 
gsave
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get def
/d 1 b length 1 add div def
/x row 1 sub i d mul add d 2 div add def
x ylimits 0 get 0 max hchartproj moveto
x d add ylimits 0 get 0 max hchartproj lineto 
x d add y hchartproj lineto 
x y hchartproj lineto 
closepath 
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke   0 setgray
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore
} def

/hbarvalues { /b exch def 
gsave
textfont cvn labelsize selectfont
1 1 data length 1 sub { /row exch def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get def
/d 1 b length 1 add div def
/x row 1 sub i d mul add d 2 div add def
x ylimits 0 get 0 max hchartproj moveto
x d add ylimits 0 get 0 max hchartproj lineto 
x d add y hchartproj lineto 
x y hchartproj lineto 
closepath 
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray
y 0 gt { 
labelcolor
x d add y hchartproj moveto
bodysize 2 div
2 rmoveto
y round labelformat show
} if
legendstyle 
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore
} def

/hvotebar { /b exch def textfont cvn bodysize selectfont
gsave
/ylimits [ 0 20 100 ] def
/labellimit 9 def
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
x 0.5 add y0 hchartproj lineto 
x 0.5 add y hchartproj lineto 
x y hchartproj lineto 
closepath 
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray

y y0 sub labellimit gt  {
invertedlabelcolor
/s y y0 sub round cvs def
x 0.25 add y0 y add 2 div hchartproj moveto s stringwidth pop 2 div neg bodysize 2.4 div neg rmoveto
s show 
} if
/y0 y def
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore
} def



/doughnut { 20 dict begin
/b exch def textfont cvn labelsize selectfont
gsave
/ylimits [ 0 20 360 ] def

% margins not used
/xcenter chartrect 0 get chartrect 2 get 2 div add def
/ycenter chartrect 1 get chartrect 3 get 2 div add def
/d 1 data length 1 max div def

1 1 data length 1 sub { /row exch def 
/tot 0 def
0 1 b length 1 sub { /i exch def b i get /col exch def
/tot tot data row get col get add def
} for
/tot tot 360 div def
/y0 0 def

/r0 chartrect 2 get chartmargins 0 get sub chartmargins 2 get sub 
chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub min 2 div def
/r2 r0 d mul row 1 add mul def
/r1 r0 d mul row  mul def

0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get tot div y0 add def

xcenter ycenter r1 r2 y y0 doughnutarc 
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray

y y0 sub labellimit gt  {
invertedlabelcolor 
/s y y0 sub round cvs def

xcenter y y0 add 2 div cos r1 r2 add 2 div mul add 
ycenter y y0 add 2 div sin r1 r2 add 2 div mul add moveto
0 bodysize 2.4 div neg rmoveto
s cshow


} if
/y0 y def
legendstyle col { 0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath patterns col get exec } put 
} for 
} for 
grestore
end } def


/halfdoughnut { 20 dict begin
/b exch def textfont cvn bodysize selectfont
gsave
/ylimits [ 0 20 360 ] def

% margins not used
/xcenter chartrect 0 get chartrect 2 get 2 div add def
/ycenter chartrect 1 get chartmargins 1 get add def
/d 1 data length 1 max div def

1 1 data length 1 sub { /row exch def 
/tot 0 def
0 1 b length 1 sub { /i exch def b i get /col exch def
/tot tot data row get col get add def
} for
/tot tot 180 div def
/y0 0 def

/r0 chartrect 2 get chartmargins 0 get sub chartmargins 2 get sub 
chartrect 3 get chartmargins 1 get sub chartmargins 3 get sub min  def
/r2 r0 d mul row 1 add mul def
/r1 r0 d mul row  mul def

0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get tot div y0 add def

xcenter ycenter r1 r2 y y0 doughnutarc 
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray

0 setgray

y y0 sub labellimit gt {
invertedlabelcolor 
/s y y0 sub round cvs def

xcenter y y0 add 2 div cos r1 r2 add 2 div mul add 
ycenter y y0 add 2 div sin r1 r2 add 2 div mul add moveto
0 bodysize 2.4 div neg rmoveto
s cshow


} if


/y0 y def
legendstyle col { 0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath patterns col get exec } put 
} for 

0 setgray
xcenter r1 add  ycenter bodysize sub  moveto data row get 0 get  show


} for 
grestore
end } def




/hstackedbar { /b exch def textfont cvn bodysize selectfont
gsave
1 1 data length 1 sub { /row exch def 
/y0 0 def
0 1 b length 1 sub { /i exch def b i get /col exch def
/y data row get col get y0 add def
/d 1 def
/x row 0.75 sub def
x y0 hchartproj moveto
x 0.5 add  y0 hchartproj lineto 
x 0.5 add  y hchartproj lineto 
x y hchartproj lineto 
closepath
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray
/s y y0 sub round cvs def
y y0 sub labellimit gt {
invertedlabelcolor
x 0.25 add y0 y add 2 div hchartproj moveto s stringwidth pop 2 div neg bodysize 2.4 div neg rmoveto
s show 
} if
/y0 y def
legendstyle col { 
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath 
patterns col get exec } put 
} for 
} for 
grestore
} def




/line { 
{ /col exch def
gsave
boldlinewidth setlinewidth
/y data 1 get col get def
/x 0.5 def
x y chartproj moveto
2 1 data length 1 sub { /row exch def
/y data row get col get def
/x row 0.5 sub def
x y chartproj lineto 
legendstyle col { boldlinewidth setlinewidth
0 bodysize 3 div moveto bodysize 2 div bodysize 3 div lineto  
colors col get exec stroke } put 
} for 
colors col get exec stroke
} forall
grestore
} def



/spline { 
{ /col exch def
gsave
boldlinewidth setlinewidth
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
legendstyle col { boldlinewidth setlinewidth
0 bodysize 3 div moveto bodysize 2 div bodysize 3 div lineto  
colors col get exec stroke } put 
} for 
/y data data length 1 sub get col get def
/x data length 1 sub 0.5 sub def
x y chartproj 
x y chartproj curveto
colors col get exec stroke
} forall
grestore
} def

/linelabel { 10 dict begin
textfont cvn labelsize selectfont
dup length /cols exch def /collist cols array def 
/i 0 def
{ /col exch def
		collist i [ col 
			 data data length 1 sub get col get
			 data 0 get col get ] put
		/i i 1 add def
} forall
/compare { 1 get exch 1 get gt } def
collist quicksort
/ymin 0 def
collist { /c exch def
  colors c 0 get get exec
  data length 1 sub 0.5 sub
  c 1 get chartproj  
  ymin labelsize add max dup /ymin exch def
  moveto ( ) show c 2 get show   
} forall
grestore end
} def

/linevalue { 10 dict begin
textfont cvn labelsize selectfont
dup length /cols exch def /collist cols array def 
/i 0 def
{ /col exch def
		collist i [ col 
			 data data length 1 sub get col get
			 data 0 get col get ] put
		/i i 1 add def
} forall
/compare { 1 get exch 1 get gt } def
collist quicksort
/ymin 0 def
collist { /c exch def
  colors c 0 get get exec
  data length 1 sub 0.5 sub
  c 1 get chartproj  
  ymin labelsize add max dup /ymin exch def
  moveto ( ) show c 1 get labelformat show   
} forall
grestore end
} def

/linevaluelabel { 10 dict begin
textfont cvn labelsize selectfont
dup length /cols exch def /collist cols array def 
/i 0 def
{ /col exch def
		collist i [ col 
			 data data length 1 sub get col get
			 data 0 get col get ] put
		/i i 1 add def
} forall
/compare { 1 get exch 1 get gt } def
collist quicksort
/ymin 0 def
collist { /c exch def
  colors c 0 get get exec
  data length 1 sub 0.5 sub
  c 1 get chartproj  
  ymin labelsize add max dup /ymin exch def
  moveto ( ) show c 1 get labelformat show ( ) show c 2 get show   
} forall
grestore end
} def



/area { 
{ /col exch def
gsave
boldlinewidth setlinewidth
/y 0 def
/x 0 def
x y chartproj moveto
1 1 data length 1 sub { /row exch def
/y data row get col get def
/x row 0.5 sub def
x y chartproj lineto 
legendstyle col { boldlinewidth setlinewidth
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray
 } put 
} for 
/y 0 def
/x xlimits 2 get def 
x y chartproj lineto 
patterns col get exec stroke
} forall
grestore
} def

/stackedarea { /b exch def 
gsave
boldlinewidth setlinewidth
0 1 b length 1 sub { /i exch def b i get /col exch def
xlimits 0 get 0 max ylimits 0 get 0 max chartproj moveto 
1 1 data length 1 sub { /row exch def
/y0 ylimits 0 get 0 max 0 1 i 1 sub { /j exch def data row get b j get get add} for def
/y y0 data row get col get add def
/x row 0.5 sub def
x y0 chartproj lineto
legendstyle col { boldlinewidth setlinewidth
0 0 moveto 8 0 lineto 8 8 lineto 0 8 lineto closepath
} put 
} for 
xlimits 2 get ylimits 0 get 0 max chartproj lineto 
1 1 data length 1 sub  { /row exch data length sub neg def
/y0 ylimits 0 get 0 max 0 1 i 1 sub { /j exch def data row get b j get get add} for def
/y y0 data row get col get add def
/x row 0.5 sub def
x y chartproj lineto
} for 
closepath
gsave patterns col get exec grestore segmentborderlinewidth setlinewidth 1 setgray stroke  0 setgray
} for
grestore
} def

/dot {  
gsave
{ /col exch def
1 1 data length 1 sub { /row exch def
/y data row get col get def
/x row 0.5 sub def
newpath x y chartproj dotsize 0 360 arc colors col get exec fill
legendstyle col { newpath bodysize 3 div bodysize 3 div dotsize 0 360 arc 
colors col get exec fill } put 
} for 
} forall
grestore
} def

/xydot { /b exch def /xcol b 0 get def /ycol b 1 get def
gsave
1 1 data length 1 sub {/row exch def
/x data row get xcol get def
/y data row get ycol get def
newpath x y chartproj dotsize 0 360 arc colors xcol get exec fill
legendstyle xcol { newpath bodysize 3 div bodysize 3 div dotsize 0 360 arc 
colors col get exec fill } put 
} for  
grestore
} def

/labelxydot { /b exch def 
gsave
textfont cvn bodysize selectfont
/lcol b 0 get def /xcol b 1 get def /ycol b 2 get def
1 1 data length 1 sub {/row exch def
/x data row get xcol get def
/y data row get ycol get def
newpath x y chartproj 4 0 360 arc colors xcol get exec fill
x y chartproj exch 6 add exch 5 sub moveto data row get lcol get show
legendstyle xcol { newpath bodysize 3 div bodysize 3 div dotsize 0 360 arc 
colors col get exec fill } put 
} for  
grestore
} def

/bubbledot { /sc exch def /b exch def 
gsave
textfont cvn bodysize selectfont
/lcol b 0 get def /xcol b 1 get def /ycol b 2 get def /rcol b 3 get def
1 1 data length 1 sub { /row exch def
/x data row get xcol get def
/y data row get ycol get def
newpath x y chartproj data row get rcol get sqrt sc mul 0 360 arc colors xcol get 
exec fill
x y chartproj exch data row get rcol get sqrt sc mul add 2 add exch 5 sub moveto data row get lcol get show
legendstyle xcol { newpath bodysize 3 div bodysize 3 div dotsize 0 360 arc 
colors col get exec fill } put 
} for  
grestore
} def

/map { /p exch def /b exch def /lcol b 0 get def /pcol b 1 get def  /rcol b 2 get def
gsave
1 1 data length 1 sub { /row exch def
data row get pcol get cvx exec
data row get lcol get 
data row get rcol get p
} for  
grestore
} def

/plot { /fn exch def /p2 exch def /pstep exch def /p1 exch def 
p1 fn chartproj moveto
p1 pstep add pstep p2 { fn chartproj lineto } for 
} def

/bottomlegend { /b exch def
gsave
textfont cvn bodysize selectfont
chartrect 0 get bodysize 2 div add
chartrect 1 get chartmargins 1 get add bodysize 2.5 mul sub 
/y exch def /x exch def x y
b { /col exch def
gsave
x y translate
legendstyle col get exec
12 0 moveto
labelcolor data 0 get col get show
grestore
/x x data 0 get col get stringwidth pop add 20 add def 
} forall
grestore
} def

/toplegend { /b exch def /s exch def
gsave
labelcolor textfont cvn bodysize selectfont
chartrect 0 get bodysize 2 div add
chartrect 1 get chartrect 3 get add chartmargins 3 get sub bodysize 2 mul add
 /y exch def /x exch def x y
x y moveto s show
/x x s stringwidth pop add 12 add def 
b { /col exch def
gsave
x y translate
legendstyle col get exec
bodysize 4 div 3 mul 0 moveto
labelcolor data 0 get col get show
grestore
/x x data 0 get col get stringwidth pop add 24 add def 
} forall
grestore
} def

/category { 
gsave
labelcolor textfont cvn bodysize selectfont
1 1 data length 1 sub { /row exch def
row 0.5 sub ylimits 0 get 0 max chartproj 20 sub moveto data row get 0 get stringwidth pop 2 div neg 0 rmoveto data row get 0 get show
} for 
grestore
} def

/hcategory { 
gsave
labelcolor textfont cvn bodysize selectfont
1 1 data length 1 sub { /row exch def
row 0.5 sub ylimits 0 get 0 max hchartproj moveto
data row get 0 get stringwidth pop neg
bodysize 2 div sub 0 bodysize 2 div sub rmoveto 
data row get 0 get show
} for 
grestore
} def

/treemap {  15 dict begin /col exch def gsave
/flip 1 def
chartrect 0 get chartmargins 0 get add
chartrect 1 get chartmargins 1 get add translate
/scx chartrect 2 get chartmargins 2 get sub chartmargins 0 get sub def
/scy chartrect 3 get chartmargins 3 get sub chartmargins 1 get sub def
/x1 0 def
/x2 scx def
/y1 scy def
/y2 0 def
/vsum 0 def
1 1 data length 1 sub { /i exch def
/vsum vsum data i get col get add def
} for
/vrest vsum def
textfont cvn bodysize selectfont
boldlinewidth setlinewidth
1 1 data length 1 sub { /i exch def
  colors i get exec
  /v data i get col get def
  /vlabel vsum 100 eq { v cvs (%) concat } { v labelformat } ifelse def
  flip {
  /x2 x1 v vrest div scx x1 sub mul add def
  /y2 0 def
  x1 y1 moveto x2 y1 lineto x2 y2 lineto x1 y2 lineto
  gsave fill grestore invertedlabelcolor stroke
  y1 y2 sub bodysize 2.7 mul gt x2 x1 sub 5 sub
  data i get 0 get stringwidth pop 
  vlabel stringwidth pop max gt and {
  x1 y1 moveto 5 bodysize 1.3 mul neg rmoveto data i get 0 get show
  x1 y1 moveto 5 bodysize 2.5 mul neg rmoveto vlabel show
   } if
  /x1 x2 def
  /x2 scx def
  } {
  /y2 y1 v vrest div y1 mul sub def
  x1 y1 moveto x2 y1 lineto x2 y2 lineto x1 y2 lineto
  gsave fill grestore invertedlabelcolor stroke
  y1 y2 sub bodysize 2.7 mul gt x2 x1 sub 5 sub data i get 0 get stringwidth pop
  vlabel stringwidth pop max 
   gt and {
  x1 y1 moveto 5 bodysize 1.3 mul neg rmoveto data i get 0 get show
  x1 y1 moveto 5 bodysize 2.5 mul neg rmoveto vlabel show
  } if
  /y1 y2 def
  /y2 0 def
  } ifelse 
  /vrest vrest v sub def
  /flip 1 flip sub def
} for
grestore end  } def

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

rpnOperators.quicksort = function(context) {
	const code = `dup length 1 sub 0 exch quicksort0`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.quicksort0 = function(context) {
	const code = `10 dict begin /right exch def /left exch def /arr exch def
/pivot arr right get def
/i left def
left 1 right 1 sub { /j exch def
   /v arr j get def 
   v pivot compare {  
	  i j ne { 
	  /v2 arr i get def
	  arr i v put
	  arr j v2 put } if
	  /i i 1 add def
   } if
} for
  /v2 arr i get def
  arr i pivot put
  arr right v2 put
  left i 1 sub lt { arr left i 1 sub quicksort0 } if
  i 1 add right lt { arr i 1 add right quicksort0 } if
end`;
	context =  rpn(code, context);
	return context;
};


rpnOperators.rshow = function(context) {
	const code = `
dup stringwidth pop neg 0 rmoveto show`;
	context =  rpn(code, context);
	return context;
};

rpnOperators.sort = function(context) {
	const code = `10 dict begin /arr exch def
/c 0 def
0 1 arr length 2 sub { /i exch def
  /c c arr i get arr i 1 add get compare add def
} for
/c c arr length div def
c 0.25 gt c 0.75 lt and { arr quicksort } { arr combsort } ifelse
end`;
	context =  rpn(code, context);
	return context;
};


rpnOperators.table = function(context) {
	function parseNumber(s) {
	if (typeof s == 'unefined') return 0;
	if (s ==  '') return 0;
	if (s ==  '.') return 0;
	if (s == null) return 0;
	s = s.toString().replaceAll(/[^0-9-.]/g,"");
	return parseFloat(s);
}
	
	postMessage(["log",context.id,"table",null]);
	
	const [haslabel, tablename] = context.pop("number", "string");
	if (!tablename) return context; 
	data = rpnTables[tablename.value];	
	list = [];
	list.push('[');
	  
	   if (Array.isArray(data)) {
		   let first = data[0];
		   if (typeof first === 'object') {
			   let values = [];
			   columns = Object.keys(first);
			   list.push('[');
			   for(key of columns) {
				   // remove pretty format
				   list.push('(' + key.replace(/^"/,"").replace(/"$/,"").replace(/^'/,"").replace(/'$/,"").replace(/^__/,"").replace(/__$/,"") + ')');
			   }
			   list.push(']');
			   
			   for(row of data) {
				   list.push('[');
				   let fields = [];
				   var k = 0;
				   for(key of columns) {
				   	   let v = row[key] ?? '.';
					   if (k < haslabel.value) {
						   v = String(v);
						   v = v.replaceAll('&quote;','"'); // recover encoded quote
						   v = v.replaceAll(')',"\)"); // PostScript string delimiter
						   v = v.replaceAll('(',"\("); // PostScript string delimiter
						   list.push('(' + v + ')');
						   
					   }
						   
					   else {
						   list.push(parseNumber(v).toString());
					   }
						   
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
