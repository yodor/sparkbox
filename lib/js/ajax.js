if( typeof XMLHttpRequest == "undefined" )
XMLHttpRequest = function() {
	try { return new ActiveXObject("Msxml2.XMLHTTP.6.0") } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP.3.0") } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP") }     catch(e) {}
	try { return new ActiveXObject("Microsoft.XMLHTTP") }  catch(e) {}
	return false;
};



var req_arr = Array();

function ajaxPost( url, elmID, params) 
{

    var req = new XMLHttpRequest();
    if (!req)throw "Can not instantiate XMLHttpRequest";

    //params = "lorem=ipsum&name=binny";
    var loader_element = document.getElementById(elmID);
    showLoader(loader_element);

    req.open("POST", url, false);

    req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    req.setRequestHeader("Content-length", params.length);
    req.setRequestHeader("Connection", "close");
    req.send(params);

    if (req.readyState == 4 && req.status == 200) {

	if (req.responseText) {
	    var loader_element = document.getElementById(elmID);
	    if (loader_element){
		loader_element.innerHTML=req.responseText;
	    }
	    return req.responseText;
	}
    }
}

function ajax(url, elmID) 
{

    if (req_arr[elmID]) {
	var old_request = req_arr[elmID];
	old_request.abort();
    }

    var request = new XMLHttpRequest();
    req_arr[elmID]=request;

    if (!request) throw "Can not instantiate XMLHttpRequest";

    var loader_element = document.getElementById(elmID);
    showLoader(loader_element);

    request.open("GET", url, true);

    request.onreadystatechange = function() {
	if (request.readyState == 4 && request.status == 200) {

	    if (request.responseText) {
		var loader_element = document.getElementById(elmID);
		loader_element.innerHTML=request.responseText;
	    }

	}
	else {
	    showLoader(loader_element);
	}
    };
    request.send(null);

}

function ajaxSync(url, elmID) 
{

    var req = new XMLHttpRequest();
    if (!req)throw "Can not instantiate XMLHttpRequest";

    var loader_element = document.getElementById(elmID);
    showLoader(loader_element);

    req.open("GET", url, false);
    req.send(null);

    if (req.readyState == 4 && req.status == 200) {

	if (req.responseText) {
	    var loader_element = document.getElementById(elmID);
	    if (loader_element) {
		loader_element.innerHTML=req.responseText;

	    }
	    return req.responseText;
	}
    }
}



function processJSONResult(ret, skip_error)
{
  try {
    var result = JSON && JSON.parse(ret) || $.parseJSON(ret); //json_parse(ret);

    if (result.status != "OK") throw result;
    return result;

  }
  catch (err) {
    var err_type = err.name;

    if (err_type == "SyntaxError") {
	    err.message = "JSON Parser Error: " + err.message + "<BR>" + ret;
    }
    var emsg = (err.message ? err.message : err);
    if (!skip_error) {

	showAlert(emsg);

    }

  }
  return false;
}

function processResult(ret)
{
  if (ret.substring(0,1) != "1") {
      throw ret;
  }
}

function showLoader(elm)
{
    var html = "Loading ...";

    if (elm) {
	if (ajax_loader) html = ajax_loader;
	$(elm).html(html);
    }

}