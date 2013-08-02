<?php
include_once("lib/input/InputField.php");

interface ILabelRenderer
{

  public function renderLabel(InputField $field, $render_index=-1);



}
?>