<?php
include_once("lib/input/InputField.php");

interface IFieldRenderer
{
  public function setField(InputField $field);
  public function getField();
  public function renderValue(InputField $field);
  public function renderField(InputField $field);

}
?>