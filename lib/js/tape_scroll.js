var step=10;
var tm = 15;
//width + border + margin
// var item_total_width = 120 + 20;difined in draw routines
var is_scrolling=false;
var cont=0;
//dir_right=-1;
//dir_left=1;
var f1=false;
var need_clone=false;

function setNeedClone(mode)
{
	need_clone=mode;
}
function startScroll(dir,cnt)
{
	if (is_scrolling)return;
	is_scrolling=true;
	cont=cnt;
	setupScroll(dir,cnt);
}

function setupScroll(dir)
{
	var d = document.getElementById("tape");
	var l = d.lastChild;
	var f = d.firstChild;

	if (dir==1){
		
		if (need_clone){
			f1 = l.cloneNode(true);
			d.appendChild(f1);
			
		}
		else {
			d.removeChild(l);
		}
		d.insertBefore(l,f);
// 		d.style.marginLeft= "-"+item_total_width+"px";


		moveTape(0-item_total_width,dir);

		

	}
	else {

		//viewport width == num items width
		if (need_clone){
			f1 = f.cloneNode(true);
			d.appendChild(f1);
		}

		moveTape(0,dir);
	}
}
function moveTape(curr_pos, dir)
{
	if (!is_scrolling)return;

	var d = document.getElementById("tape");
	var st = curr_pos;
	st+=step;
	d.style.marginLeft = (st*dir)+"px";

	//width=120;+ 2+2 border + 8 padding
	if ( (st>item_total_width && dir==-1) || (st>0 && dir==1) ){
		moveFinished(dir);
	}
	else {
		setTimeout("moveTape("+st+","+dir+")",tm);
	}
}
function moveFinished(dir)
{
	var d = document.getElementById("tape");
	//right scroll
	if (dir==-1) {
		var f = d.firstChild;
		d.removeChild(f);
		d.appendChild(f);
		
	}
	d.style.marginLeft="0px";

	//remove the cloned node
	if (f1) {
		d.removeChild(f1);
		f1=false;
	}
	
	if (cont) {
		setupScroll(dir);
	}
	else {
		is_scrolling=false;
	}
	
}
function stopScroll()
{
	cont=false;
}