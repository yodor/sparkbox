var curr_page = 1;
var curr_slot = 0;
var total_images = 0;
var image_slots = new Array();
var max_slots = 4;


function putImage(imageID, mID)
{

  var total_pages = totalPages();
  if (curr_page < total_pages) {
	  curr_page=total_pages;
	  redrawPage();
  }
	
  curr_slot++;

  if (curr_slot>max_slots) {
	if (curr_page+1>6) {
		showMaxPagesMessage();
		return;
	}
	clearSlots();
	curr_page++;
	
	curr_slot=1;
  }
  
  
  var d = document.getElementById("gallery_image_"+imageID);
  var p = document.getElementById("image_slot_"+curr_slot);
  var w =p.offsetWidth;
  var h =p.offsetHeight;
  p.innerHTML="<img src='"+d.src+"' width="+w+" height="+h+">";
  
  image_slots[total_images] = imageID;
  total_images++;

  updatePageNav();
}
function clearSlots()
{
  for (var a=1;a<=max_slots;a++){
	var d = document.getElementById("image_slot_"+a);
	d.innerHTML="";
  }
}
function totalPages()
{
    var total_pages = Math.ceil(total_images/max_slots);
	if (total_pages<1)total_pages=1;
	return total_pages;

}
function updatePageNav()
{
  var d = document.getElementById("page_nav");

  var html = "<table cellpadding=0 cellspacing=0 width=100%><tr>";
  var total_pages = totalPages();

  html+="<td align=right style='width:33%'>";
  if (curr_page > 1 && total_pages>1) {
	html+="<a class=pdf_page_nav_button href='javascript:prevPage()'>&laquo; </a> ";
  }
  html+="</td>";

  html+="<td align=center style='width:33%'>";
  html+= curr_page+" / "+total_pages;
  html+="</td>";

  html+="<td align=left style='width:33%'>";
  if (curr_page < total_pages) {
	html+="<a class=pdf_page_nav_button  href='javascript:nextPage()'> &raquo;</a> ";
  }
  html+="</td>";
  html+="</tr></table>";

  d.innerHTML=html;
  
  
}

function clearSlot(slot_pos)
{
  var idx = (curr_page * max_slots) - max_slots + slot_pos - 1;

  if (idx >= image_slots.length) {
	  curr_slot=0;
	  return;
  }

  var total_pages = totalPages();

  image_slots.splice(idx,1);
  total_images--;

  if ( totalPages() < total_pages ) {
	prevPage();
	return;
  }
  
  redrawPage();
  updatePageNav();
}

function redrawPage()
{
 
  clearSlots();

  for (var a=1;a<=max_slots;a++){
	var idx = (curr_page * max_slots) - max_slots + a - 1;
	
	if (idx == image_slots.length) {
		break;
	}

	curr_slot=a;

	var elm = image_slots[idx];
 
	var c = document.getElementById("gallery_image_"+elm);

	var d = document.getElementById("image_slot_"+a);
	var w = d.offsetWidth;
	var h = d.offsetHeight;
	d.innerHTML="<img src='"+c.src+"' width="+w+" height="+h+">";

  }
}
function prevPage()
{
  curr_page--;
  if (curr_page<1)curr_page=1;
  redrawPage();
  updatePageNav();
}
function nextPage()
{
  var total_pages = Math.ceil(total_images/max_slots);
  curr_page++;
  if (curr_page>total_pages) curr_page=total_pages;
  redrawPage();
  updatePageNav();
}
function idxAsString()
{
  var ret="";
  for (var a=0;a<image_slots.length;a++){
	  ret+=image_slots[a]+";";
  }
  return ret;
}
function clearPDF()
{
  curr_page = 1;
  curr_slot = 0;
  total_images = 0;
  image_slots = new Array();
  max_slots = 4;
  redrawPage();
  updatePageNav();
}