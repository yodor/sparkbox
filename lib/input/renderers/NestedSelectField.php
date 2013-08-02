<?php
include_once("lib/input/renderers/SelectField.php");
include_once("lib/beans/NestedSetBean.php");

class NestedSelectField extends SelectField 
{


  public $rootParent = 0;
  public $node_order = "";

  
  public function __construct()
  {
      parent::__construct();

  }

  public function renderImpl()
  {

      if (!$this->data_bean instanceof NestedSetBean) {
	  throw new Exception("NestedSetBean required as data source for this field");
      }

      parent::renderImpl();
  }
  
  protected function renderItems()
  {
      $this->listChildsSelect($this->rootParent, 0);
  }


  private function listChildsSelect($parentID, $level)
  {

      $clevel = $level;
      $clevel++;

      $cnselect = $this->data_bean->childNodes($parentID);

      if ($this->node_order) {
	$cnselect->order_by = " {$this->node_order} ";
      }
      else {
	$cnselect->order_by = " {$this->list_label} ";
      }

      $sql = $cnselect->getSQL();

      $total = -1;

      $itr = $this->data_bean->createIterator($sql, $total);

      if ($total<1)return;

      $prkey = $this->data_bean->getPrKey();

      $margin = 2 * ($level+1) ;
      
      $field_values = $this->field->getValue();
      $field_name = $this->field->getName();
      
      while ($this->data_bean->fetchNext($data_row, $itr)) {

	  $id = $data_row[$prkey];

	  trbean($id, $this->list_label, $data_row, $this->data_bean);
	  
	  $value = $data_row[$this->list_key];
	  $label = $data_row[$this->list_label];

	  $label = implode('',array_fill(0, $margin, '&nbsp;')).$label;
	  
	  $selected = $this->isModelSelected($value, $field_values);

	  $item = clone $this->item;
	  $item->setID($id);
	  $item->setValue($value);

	  $item->setLabel($label);
	  $item->setName($field_name."[]");
	  $item->setIndex($level);
	  $item->setSelected($selected);
	  
	  $item->render();

	  $this->listChildsSelect($id, $clevel);
      }
  }

}
?>