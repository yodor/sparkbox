<?php
include_once("lib/components/Component.php");
include_once("lib/input/renderers/IDataSourceItem.php");

abstract class DataSourceItem extends Component implements IDataSourceItem
{
    protected $data_row = array();
    protected $index = -1;
    protected $label = "";
    protected $value = "";
    protected $id = "";
    protected $name = "";
    protected $key_name = "";
    //render html attributes from data_row
    protected $data_row_attributes = array();
    protected $user_attributes = "";
    
    public function addDataRowAttribute($name)
    {
        $this->data_row_attributes[] = $name;
    }
    public function getDataRowAttributes()
    {
        return $this->data_row_attributes;
    }
    public function setUserAttributes($attr_text)
    {
        $this->user_attributes = $attr_text;
    }
    public function setIndex($index)
    {
	$this->index = $index;
    }
    public function setLabel($label)
    {
	$this->label = $label;
    }
    public function setDataRow($data_row)
    {
	$this->data_row = $data_row;
	foreach($this->data_row_attributes as $idx=>$name) {
            if (isset($this->data_row[$name])) {
                $this->setAttribute($name, $this->data_row[$name]);
            }
        }
    }
    public function setID($id)
    {
	//source model id
	$this->id = $id;
    }
    public function setKeyName($key_name)
    {
	$this->key_name = $key_name;
    }
    public function setValue($value)
    {
	$this->value = $value;
    }
    public function setName($name)
    {
	$this->name = $name;
    }
    
    public function __construct()
    {
	parent::__construct();
	$this->component_class="";
    }
    
    public function startRender()
    {
      //row index in the data source values
      echo "<div class='".get_class($this)."' ";
      if ($this->index>-1) {
	echo " index='{$this->index}' ";
      }
      echo ">";

    }
    public function finishRender()
    {
      echo "</div>";
    }

    public function setSelected($mode)
    {
      $this->selected = ($mode) ? TRUE : FALSE;
    }
    public function isSelected()
    {
	return $this->selected;
    }
}

?>
