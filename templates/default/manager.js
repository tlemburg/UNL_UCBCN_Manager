function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}

addLoadEvent(updateRow);

function showHide(e)
{
   document.getElementById(e).style.display=(document.getElementById(e).style.display=="block")?"none":"block";
   return false;
}

function checknegate(id){
	checkevent(id);
}

function highlightLine(l,id) {
	animation(l,id);	
	checkevent(id);
}

function animation(l,id){
	var TRrow = "row" + id;
	if(!l.className){
	Spry.Effect.Highlight(TRrow,{duration:400,from:'#ffffff',to:'#ffffcc',restoreColor:'#ffffcc',toggle: true});
	}
	else{
	Spry.Effect.Highlight(TRrow,{duration:400,from:'#e8f5fa',to:'#ffffcc',restoreColor:'#ffffcc',toggle: true});
	} 
}

function checkevent(id) {
	 checkSet = eval("document.formlist.event" + id);
	 checkSet.checked = !checkSet.checked
}

function updateRow(){
	var rowT = document.getElementsByTagName('tr');
	for (i=0; i< rowT.length; i++)
		{
			if(rowT[i].className == 'updated'){
				if(rowT[i].className == 'alt'){
				Spry.Effect.Highlight(rowT[i],{duration:2000,from:'#FAFAB7',to:'#e8f5fa',toggle: false});
				}
				else{
				Spry.Effect.Highlight(rowT[i],{duration:2000,from:'#FAFAB7',to:'#ffffff',toggle: false});					
				}
			}
		}	

} 
