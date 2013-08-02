<?php
include_once ("lib/components/Component.php");
include_once ("lib/components/renderers/ICellRenderer.php");
include_once ("lib/components/TableColumn.php");

class TableHeaderCellRenderer extends Component implements ICellRenderer
{
  protected $is_sortable = false;

  protected $tooltip_field = "";
  protected $tooltip_text = "";


  public function __construct($is_sortable=true)
  {
	  parent::__construct();

	  $this->is_sortable = $is_sortable;


  }
  public function startRender()
  {
	  $all_attribs = $this->prepareAttributes();
	  echo "<th $all_attribs>";
  }

  public function finishRender()
  {
	  echo "</th>";
  }
	protected function processAttributes($row, TableColumn $tc)
  {
			$this->setAttribute("column", $tc->getFieldName());
  }

  public function isSortable()
  {
	  return ($this->is_sortable>0);
  }
  public function setSortable($mode)
  {
	  $this->is_sortable = ($mode>0);
  }
  protected function renderImpl()
  {

  }

  public function renderCell($row, TableColumn $tc) {

		$this->processAttributes($row, $tc);

	  $this->startRender();

	  if ($this->isSortable()) {
			  $qry = $_GET;

			  $odir="ASC";
			  if (isset($qry["orderdir"]) && ( isset($qry["orderby"]) && strcmp( $qry["orderby"],$tc->getFieldName() )==0 ) ){
				  if (strcmp($qry["orderdir"],"ASC")==0){
					  $odir="DESC";
				  }
				  else {
					  $odir="ASC";
				  }
			  }
			  $qry["orderby"]=$tc->getFieldName();
			  $qry["orderdir"]=$odir;
			  $qrystr = queryString($qry);

// 	&darr;

			  echo "<a href='$qrystr'>".tr($tc->getLabel())."</a>";
$arr = "&darr;";
if (isset($_GET["orderdir"]) && strcmp($_GET["orderdir"],"DESC")==0) {
  $arr="&uarr;";
}
if (isset($_GET["orderby"]) && strcmp($_GET["orderby"],$tc->getFieldName())==0) {
  echo "<span style='font-weight:bold;margin-left:5px;font-size:14px;'>$arr</span>";
}
	  }
	  else {
			  echo "<span>".tr($tc->getLabel())."</span>";
	  }

	  $this->finishRender();
  }

  public function setTooltipFromField($field_name)
  {
	  $this->tooltip_field = $field_name;
  }
}

?>