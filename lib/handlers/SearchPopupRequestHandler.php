<?php
include_once("lib/handlers/RequestHandler.php");


abstract class SearchPopupRequestHandler extends RequestHandler
{
  
  public function __construct()
  {
	  parent::__construct("search_result_popup");
  }

  protected function parseParams() 
  {
	    $db = DBDriver::get();

	  if (!isset($_REQUEST["field_id"])) throw new Exception("field_id not passed");
	  $this->field_id = $_REQUEST["field_id"];

	  if (!isset($_REQUEST["field_name"])) throw new Exception("field_name not passed");
	  $this->field_name = $_REQUEST["field_name"];

	  if (!isset($_REQUEST["srch_crit"])) throw new Exception("srch_crit not passed");
	  $this->srch_crit = $db->escapeString($_REQUEST["srch_crit"]);

	  
  }

  protected function process() 
  {
	  $success=false;

	 
	  $db = DBDriver::get();

	  try {
		  $field_id = $this->field_id;
		  $field_name = $this->field_name;
		  
		  
		  echo "<div style='background:white;border:1px solid black;padding:5px;'>";
		  $srch = $this->srch_crit;

		  $attr= " style='cursor:pointer;border-bottom:1px dotted gray;clear:both;padding-top:5px;padding-bottom:5px;' onClick='javascript:search_result_clicked(this,\"$field_name\",\"$field_id\");' ";
		  $fname = "draw_search_results_$field_name";
		  $this->$fname($srch, $db, $attr);
		  echo "</div>";

		  
		  $success=true;
	  }
	  catch (Exception $e) {
			
			throw $e;
	  }
	  return $success;

  }
  protected function dumpJS()
  {
	parent::dumpJS();
?>
<script language=javascript>
function processSearchPopup(txt, field_name, e)
{
  if (!e) var e = window.event;
  var ascii = e.which;

  if ( ascii == 27 || ascii == 9 ) {
	closePopup("popup_search_result_holder",true);
	return;
  }
  if ( ascii<32|| ascii>122 ) return;

  showPopup("popup_search_result_holder",txt.id);

var add_filter="";
 if (txt.form.seller_id.value && txt.form.buyer_id.value) {
	add_filter="&seller_id="+txt.form.seller_id.value+"&buyer_id="+txt.form.buyer_id.value;
 }

  ajax("?ajax=1&cmd=search_result_popup&srch_crit="+txt.value+"&field_name="+field_name+"&field_id="+txt.id+add_filter, "popup_search_result_holder"); 
}

//control first goes here after clicking the result popup
function search_result_clicked(dv, field_name, field_id)
{
  
  var result_split = field_id.split("_");
  var render_index=-1;
  if ( (result_split.length)-1 > -1 ) {
	  render_index = result_split[result_split.length-1];
  }
  

  if(typeof window["search_result_clicked_"+field_name] == 'function') { 
	window["search_result_clicked_"+field_name](dv, field_name, field_id, render_index);
  }
  else {
	alert("undefined function: " + "search_result_clicked_"+field_name);
  }
  
  closePopup("popup_search_result_holder",true);
}
</script>
<?php
  }

}
?>