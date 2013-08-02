<?php
include_once ("lib/components/Component.php");
include_once ("lib/components/renderers/ICellRenderer.php");
include_once ("lib/components/TableColumn.php");

class CheckboxFieldCellRenderer extends TableCellRenderer implements ICellRenderer
{
  protected $value_field = "";

  public function __construct($value_field="")
  {
	  parent::__construct();

	  $this->value_field = $value_field;
	  
  }

  public function renderCell($row, TableColumn $tc) {
	  $this->startRender();
	  $field_key = $tc->getFieldName();
	  
	  $value = json_string($row[$field_key]);
	  if ($this->value_field) {
		$value = json_string($row[$this->value_field]);
	  }

	  echo "<input type=checkbox name='select_{$field_key}[]'  value=$value>";
	  echo "<BR>";
	  echo $row[$field_key];

	  
	  $this->finishRender();
  }
}

?>
