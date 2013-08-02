<?php
include_once("lib/input/renderers/InputRenderer.php");

abstract class InputTagRenderer extends InputRenderer
{

  public function __construct()
  {
      parent::__construct();

  }

  public function renderField(InputField $field, $render_index=-1)
  {

    $field_value = mysql_real_unescape_string($field->getValue());
    
    $this->setFieldAttribute("value", $field_value);
    $this->setFieldAttribute("name", $field->getName());
    
    
    parent::renderField($field, $render_index);
    
  }

  public function renderImpl()
  {
      $field_attr = $this->prepareFieldAttributes();
      
      echo "<input $field_attr>";

  }



}
?>