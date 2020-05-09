<?php
include_once("components/Component.php");

abstract class DataIteratorItem extends Component
{
    //use value of data array key '$value_key' to construct the value of this item
    protected $value_key;

    //use value of data array key '$label_key' to construct the label of this item
    protected $label_key;

    protected $data = array();
    protected $index = -1;

    protected $label = "";
    protected $value = "";

    protected $id = "";
    protected $name = "";
    protected $key_name = "";

    //render html attributes from data_row
    protected $data_attributes = array();

    protected $user_attributes = "";

    protected $selected = FALSE;

    public function addDataAttribute($name)
    {
        $this->data_attributes[] = $name;
    }

    public function getDataAttributes()
    {
        return $this->data_attributes;
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
    public function getLabel() : string
    {
        return $this->label;
    }

    public function setID($id)
    {
        //source model id
        $this->id = $id;
    }

    public function setValueKey(string $field)
    {
        $this->value_key = $field;
    }

    public function getValueKey(): string
    {
        return $this->value_key;
    }

    public function setLabelKey(string $field)
    {
        $this->label_key = $field;
    }

    public function getLabelKey() : string
    {
        return $this->label_key;
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
        //$this->component_class = "";
    }

    public function startRender()
    {
        //row index in the data source values
        echo "<div class='" . get_class($this) . "' ";
        if ($this->index > -1) {
            echo " index='{$this->index}' ";
        }
        echo ">";

    }

    public function finishRender()
    {
        echo "</div>";
    }

    public function setSelected(bool $mode)
    {
        $this->selected = $mode;
    }

    public function isSelected()
    {
        return $this->selected;
    }

    public function setData(array $data, DataInput $input)
    {
        $this->data = $data;
        foreach ($this->data_attributes as $idx => $name) {
            if (isset($this->data[$name])) {
                $this->setAttribute($name, $this->data[$name]);
            }
        }

        $this->value = isset($data[$this->value_key]) ? $data[$this->value_key] : "";
        $this->label = $data[$this->label_key];

        $this->selected = $this->isModelSelected($input);
    }

    /**
     * Iterator values construct the array
     * Checkboxes post values directly
     * Search inside values if iterator value is found
     * @param $value
     * @param $field_values
     * @return bool
     */
    protected function isModelSelected(DataInput $input) : bool
    {
        $field_values = $input->getValue();
        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }
        return (in_array($this->value, $field_values));
    }
}

?>
