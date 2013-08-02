<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/ICellRenderer.php");


class TableCellRenderer extends Component implements ICellRenderer
{

  protected $tooltip_field = "";
  protected $action = false;

  protected $value_attributes = array();
  
  public function __construct()
  {
	  parent::__construct();

  }
  public function startRender()
  {

	  $all_attribs = $this->prepareAttributes();
	  echo "<td $all_attribs >";
  }
  public function setAction(Action $a)
  {
	  $this->action = $a;

  }
  public function finishRender()
  {
	  echo "</td>";
  }

  public function renderImpl()
  {

  }
  public function addValueAttribute($field_name)
  {
      $this->value_attributes[]=$field_name;
      
  }
  
  protected function processAttributes($row, TableColumn $tc)
  {
      $this->setAttribute("column", $tc->getFieldName());
      foreach ($this->value_attributes as $idx=>$field) {
	if (isset($row[$field])) {
	  $this->setAttribute($field,$row[$field]);
	}
      }
  }
  
  public function renderCell($row, TableColumn $tc)
  {
	  $this->processAttributes($row, $tc);
	  
	  if (isset($row[$this->tooltip_field])) {
		  $this->setTooltipText($row[$this->tooltip_field]);
	  }

	  $this->startRender();
	  if ($tc->getView()->getIterator()->getBean()) {

			trbean($row[$tc->getView()->getIterator()->getPrKey()],  $tc->getFieldName(), $row, $tc->getView()->getIterator()->getBean());

	  }

	  echo "<span>".$row[$tc->getFieldName()]."</span>";


	  $this->finishRender();
  }
  public function setTooltipFromField($field_name)
  {
	  $this->tooltip_field = $field_name;
  }

}

?>