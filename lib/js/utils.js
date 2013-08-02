var console=console||{"log":function(){}};

Function.prototype.bind = function(scope)
{
  var _function = this;

  return function() {
    return _function.apply(scope, arguments);
  }
}

if (typeof String.prototype.startsWith != 'function') {
  
  String.prototype.startsWith = function (prefix){
    return this.indexOf(prefix) == 0;
  };
}
if (typeof String.prototype.endsWith != 'function') {
  String.prototype.endsWith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
  };
}

function escapeHtml(unsafe) {
  return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}


function checkTextLength(elm)
{

  var d = document.getElementById("chars_remaining");
  if (!d)return;

  var remain = 160 - elm.value.length;
  if (remain < 0) {
	  remain = 0;
	  elm.value = elm.value.substring(0,160);
  }

  d.innerHTML = "" + (remain) + "";
  return true;
}

function ltrim(str) 
{
	for(var k = 0; k < str.length && isWhitespace(str.charAt(k)); k++);
	return str.substring(k, str.length);
}
function rtrim(str) 
{
	for(var j=str.length-1; j>=0 && isWhitespace(str.charAt(j)) ; j--) ;
	return str.substring(0,j+1);
}
function trim(str) 
{
	return ltrim(rtrim(str));
}
function isWhitespace(charToCheck) 
{
	var whitespaceChars = " \t\n\r\f";
	return (whitespaceChars.indexOf(charToCheck) != -1);
}

function setWindowCentered(d, visibility)
{
  if (!visibility) visibility = "visible";
  
  var scr_scroll = getScrollXY();
  var scr_size = getWindowSize();


  d.style.visibility=visibility;
  
  d.style.left =  (scr_scroll[0] + ((scr_size[0] - d.clientWidth)/2)) + "px";
  d.style.top =  ( ((scr_size[1] - d.clientHeight)/2)) + "px";
  
}

function disableEnterKey(e)
{
    var key;
    if(window.event)
	key = window.event.keyCode;     //IE
    else
	key = e.which;     //firefox
    if(key == 13)
	return false;
    else
	return true;
}

function disableSelection(target)
{
  if (typeof target.onselectstart!="undefined") {
      //IE route
      target.onselectstart=function(){return false};
  }
  else if (typeof target.style.MozUserSelect!="undefined")  {
      //Firefox route
      target.style.MozUserSelect="none";
  }
  else {
      //All other route (ie: Opera)
      target.onmousedown=function(){return false};
  }
	  
  target.style.cursor = "default";
}

function getWindowSize() {
  var myWidth = 0, myHeight = 0;
  if( typeof( window.innerWidth ) == 'number' ) {
    //Non-IE
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in 'standards compliant mode'
    myWidth = document.documentElement.clientWidth;
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  }
  //window.alert( 'Width = ' + myWidth );
  //window.alert( 'Height = ' + myHeight );
	return [ myWidth, myHeight ];
}
function getScrollXY() {
  var scrOfX = 0, scrOfY = 0;
  if( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return [ scrOfX, scrOfY ];
}



function showDebug(txt)
{
  
  var d = document.getElementById("debug");
  if (!d)return;
  d.innerHTML=d.innerHTML+"<BR>"+txt;
  d.scrollTop += d.offsetHeight;
}


function isEven(val)
{
	if ( val%2 == 0 ) {
		return true;
	}
	else {
		return false;
	}
}


function isDirty(form) {
  var ret = false;

    for (var i=0; i<form.elements.length; i++) {
        var field = form.elements[i];

        switch (field.type) {
            case "select-multiple":
            case "select-one":

                var options = field.options;
                for (var j=0; j<options.length; j++) {
                    if(options[j].selected != options[j].defaultSelected && options[j].value != -1) {

						ret= true;
					}
                }
                break;
            case "text":
            case "file":
            case "password":
                if (field.value != field.defaultValue) ret= true;
                break;
			case "hidden":
// 			    alert(field.value + " : " + field.defaultValue);
				if (field.value != field.defaultValue) ret=true;
				break;
            case "checkbox":
            case "radio":
                if (field.checked != field.defaultChecked) ret= true;
                break;
        }
        if (ret) {

		  break;

		}
    }

    return ret;
}

function formtostr(frm)
{
    var params = "";


    for (var a=0;a< frm.elements.length; a++)
    {
	var curr = frm.elements[a];
	if (curr.name.length>0){

	    var val = curr.name + "=" + encodeURIComponent(curr.value) + "&";
	    if (curr.type == "radio") {
		      if (!curr.checked) continue;

	    }
	    if (curr.type == "checkbox") {
		    if (!curr.checked) continue;
	    }
	    params+= val;
	}
    }
    return params;
}


function addButtonEvent(btn, func)
{
  var old = btn.onclick;
  
  console.log(old);
  
  if (old != 'function') {
    btn.onclick = func;
    
  } else {
    btn.onclick = function() {
      if (old) {
        old();
      }
      func();
    }
  }
}

function addLoadEvent(func) 
{
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

function showExceptonDetails(lnk) 
{
  showAlert($(lnk).next().html());
}


function validateEmail(fld) 
{
    var error="";
	fld.value = trim(fld.value);
    var tfld = fld.value; // value of field with whitespace trimmed off
    
    var emailFilter = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;


   
    if (fld.value == "") {
      //  fld.style.color = '#FFAFAF';
        error = "You didn't enter an email address.\n";
		
    } else if (!emailFilter.test(tfld)) {              //test email for illegal 
        error = "Please enter a valid email address.\n";
    } 
    return error;
}