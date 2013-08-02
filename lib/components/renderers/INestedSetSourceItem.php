<?php
interface INestedSetSourceItem
{
  public function setDataRow($data_row);
  public function getDataRow();
  
  public function setID($id);
  public function getID();
  
  public function setLabel($label);
  public function getLabel();
  
  public function setSelected($mode);
  public function isSelected();

}

?>