function selectAllOptions(cbo_id)
{
  var selObj = document.getElementById(cbo_id);
  for (var i=0; i<selObj.options.length; i++) {
    selObj.options[i].selected = true;
  }
	return true;
}
function transfer(src_id, dst_id)
{
    //var option_indexes = new Array();
    var source_select = document.getElementById(src_id);
    var target_select = document.getElementById(dst_id);

//  alert("test");
    var idx=0;

    while (idx<source_select.options.length && source_select.options.length >0)
    {
        var opt = source_select.options[idx];
        if (opt.selected){
            try {
                target_select.add(opt, null); // standards compliant; doesn't work in IE
            }
            catch(ex) {
                target_select.add(opt); // IE only
            }
        }
        else {
            idx++;
        }

    }

}