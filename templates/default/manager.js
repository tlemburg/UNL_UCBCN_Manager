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


addLoadEvent(function() {
	updateRow();
	if(document.getElementById('unl_ucbcn_event')){
	requiredField();
	hideField();
	}
});

function getElementsByClassName(oElm, strTagName, strClassName){
    var arrElements = (strTagName == "*" && oElm.all)? oElm.all : oElm.getElementsByTagName(strTagName);
    var arrReturnElements = new Array();
    strClassName = strClassName.replace(/\-/g, "\\-");
    var oRegExp = new RegExp("(^|\\s)" + strClassName + "(\\s|$)");
    var oElement;
    for(var i=0; i<arrElements.length; i++){
        oElement = arrElements[i];      
        if(oRegExp.test(oElement.className)){
            arrReturnElements.push(oElement);
        }
    }
    return (arrReturnElements);
}

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

function requiredField(){
	var fieldset = document.getElementsByTagName('fieldset');
	var lastrequired = getElementsByClassName(document, "span", "required");
	
	//alert(lastrequired.length);
	lastrequired[lastrequired.length - 1].id = 'lastfieldset';
	
	for(i=0; i<fieldset.length; i++){
		//var divrequired = getElementsByClassName(fieldset[i], "div", "reqnote");
		var spanrequired = getElementsByClassName(fieldset[i], "span", "required");
		if (spanrequired.length > 0){
			spanrequired[0].parentNode.nextSibling.childNodes[0].style.background = '#f8e6e9';
		}	
	}	
}

function hideField(){
	var id = document.getElementById('optionaldetailsheader');
	var formContainer = getElementsByClassName(id, "div", "formcontent");
	createButton('Click to add additional details', id, formHide, 'formShow')
	formContainer[0].style.display='none';
  	
  	//fix some layout problem at the same time
  	var eventType = document.getElementById('eventtypeheader');
  	eventType.getElementsByTagName('label')[0].style.display = 'none';
  	var eventLoc = document.getElementById('eventlocationheader');
  	eventLoc.getElementsByTagName('label')[0].style.display = 'none';
  	var eventNewLoc = document.getElementById('__reverseLink_eventdatetime_event_idlocation_id_1__subForm__div');
  	eventNewLoc.className = 'newlocation';
}

function formHide(){
	var id = document.getElementById('optionaldetailsheader');
	var formContainer = getElementsByClassName(id, "div", "formcontent");
	formContainer[0].style.display=(formContainer[0].style.display=="block")?"none":"block";
	var linkId = document.getElementById('formShow');
	linkId.childNodes[0].nodeValue = (linkId.childNodes[0].nodeValue=="Hide Form")?"Click to add additional details":"Hide Form";
	return false;
}

function createButton(linktext, attachE, actionFunc, idN){
	var morelink = document.createElement("a");
	morelink.style.display = 'inline';
	var text = document.createTextNode(linktext);
	morelink.id=idN;
	morelink.href = '#';
	morelink.onclick = actionFunc;
	morelink.appendChild(text);
	attachE.appendChild(morelink);
}