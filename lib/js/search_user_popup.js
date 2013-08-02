

function processSearchPopup(e, elm)
{

  var popup = $("#search_popup");

  if (!e) var e = window.event;
  var ascii = e.which;

  if ( ascii == 27 || ascii == 9 ) {
	hideSearchPopup();
	return;
  }

  if ( ascii<32 || ascii>122 ) return;

  var crit = elm.value;

  if (crit.length<1)return;



  if( popup.css("display")!="block") {

	var pos = $(elm).offset();
	popup.css("display", "block");
	popup.css("position", "absolute");
	popup.css("left",pos.left);
	popup.css("top", pos.top + $(elm).innerHeight() + 2);
	popup.css("z-index", 2);

  }

  popup.html(" ");

  var ajaxsrc = "?ajax=1&cmd=member_search&crit="+crit;
  var result = false;
  if (result = processJSONResult(ajaxSync(ajaxsrc), true)) {
	  if (result.results.length>0) {
		  for (var a=0;a<result.results.length;a++) {
			  var res = result.results[a];
			  var upID = res.upID;
			  if (upID<1)upID=-1;
			  var html = createResultElement( res.userID,  upID, res.name);
			  popup.append(html);


		  }
	  }
	  else {
		popup.append("Nothing found");
	  }
  }

}



function createResultElement(userID, upID, member_name)
{

  var name = "<div>"+member_name+"</div>";
  var img_tag = "<div><img src='"+SITE_ROOT+"storage.php?cmd=image_thumb&size=16&height=-1&class=UserPhotosBean&id="+upID+"'></div>";

  var html = "<div class=result_row userID='"+userID+"' onClick='javascript:resultClicked(this)'>";
  html+=img_tag+name;
  html+="<div class='remove_result'></div>";
  html+="</div>";

  return html;
}


function hideSearchPopup()
{
  $("#search_popup").css("display", "none");
  $("#search_popup").html(" ");

  $("#member_search").attr("value", "");
}

$(document).click(function(e) {
    // Check for left button
    if (e.button == 0) {
        hideSearchPopup();
    }
});