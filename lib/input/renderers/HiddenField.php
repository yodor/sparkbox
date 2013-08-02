<?php
include_once("lib/input/renderers/InputRenderer.php");

class HiddenField extends InputRenderer {

  public function __construct()
  {
	  parent::__construct();
	  $this->attributes["type"]="hidden";

  }


  public function renderImpl()
  {
	
	$field_value = $this->field->getValue();

	$field_value=htmlentities(mysql_real_unescape_string($field_value),ENT_QUOTES,"UTF-8");

	$this->attributes["value"] = $field_value;
	$this->attributes["name"] = $this->field->getName();

	$attr = $this->prepareAttributes();

	echo "<input $attr >";
  }

}
?>