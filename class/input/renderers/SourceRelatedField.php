<?php
include_once("lib/input/renderers/DataSourceField.php");
include_once("lib/input/renderers/DataSourceItem.php");
include_once("class/beans/ClassAttributesBean.php");

class SourceAttributeItem extends DataSourceItem 
{

    public function renderImpl()
    {

		echo "<label class='SourceAttributeName' data='attribute_name'>".$this->label."</label>";
		
		echo "<input class='SourceAttributeValue' data='attribute_value' type='text' value='{$this->value}' name='{$this->name}[]'>";
		
		echo "<input data='foreign_key' type='hidden' name='fk_{$this->name}[]' value='caID:{$this->id}'>";

		echo "<label class='SourceAttributeUnit' data='attribute_unit'>".$this->data_row["attribute_unit"]."</label>";
    }

}


class SourceRelatedField extends DataSourceField implements IArrayFieldRenderer, IHeadRenderer
{

  public function __construct()
  {
      parent::__construct();
      $this->setItemRenderer(new SourceAttributeItem());
      
//       RequestController::addAjaxHandler(new SourceRelatedFieldAjaxHandler());
      
       
  }
  public function setSource(IDataBean $source)
  {
	  parent::setSource($source);
	  $this->addClassName(get_class($source));
  }
  public function renderStyle()
  {
      echo "<link rel='stylesheet' href='".SITE_ROOT."css/SourceRelatedField.css' type='text/css' >";
      echo "\n";
  }
  public function getHeadClass()
  {
      return "SourceRelatedField";
  }
  public function renderScript()
  {
    
  }

  public function renderControls()
  {

  }
  public function renderElementSource()
  {

  }
  public function renderArrayContents()
  {

  }
  
  public function renderImpl()
  {

        
      if ($this->data_bean instanceof IDataBean) {

	  $source_fields = $this->data_bean->getFields();

	  if (!in_array($this->list_key, $source_fields)) throw new Exception("List Key '{$this->list_key}' not found in data source fields");
	  
// 	  if (!in_array($this->list_label, $source_fields)) throw new Exception("List Label '{$this->list_label}' not found in data source fields");

	  $num = $this->data_bean->startIterator($this->data_filter, $this->data_fields);
//   echo $this->data_bean->getLastIteratorSQL();
  
	  if ($num<1) {
	    echo "Selected source does not provide optional attributes";
	    return;
	  }
	  $this->startRenderItems();
	  
	  $this->renderItems();
	  
	  $this->finishRenderItems();
      }

  }


  protected function renderItems()
  {
      
      $field_values = $this->field->getValue();
      $field_name = $this->field->getName();

      if (!is_array($field_values)) {
		$field_values = array($field_values);
      }
      
      $prkey = $this->data_bean->getPrKey();
      $index = 0;

      while ($this->data_bean->fetchNext($data_row))
      {

// 		$id = $data_row[$this->getSource()->getPrKey()];
		$id = $data_row[$prkey];

		$value = isset($data_row[$field_name]) ? $data_row[$field_name] : "";
		$label = $data_row[$this->list_label];

		
		$item = clone $this->item;
		$item->setID($id);
		$item->setValue($value);
		$item->setLabel($label);
		$item->setName($field_name);
		$item->setIndex($index);

		$item->setDataRow($data_row);
		
		$item->render();
	  
		$index++;
      }
  }
}
?>
