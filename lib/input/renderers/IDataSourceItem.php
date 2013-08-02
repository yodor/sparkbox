<?php


interface IDataSourceItem
{
  public function setDataRow($data_row);
  public function setID($id);
  public function setValue($value);
  public function setLabel($label);
  public function setName($name);
  public function setSelected($mode);
  public function isSelected();
  
}

?>