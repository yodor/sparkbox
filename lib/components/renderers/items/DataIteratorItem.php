<?php
include_once("components/Component.php");
include_once("utils/IDataResultProcessor.php");

abstract class DataIteratorItem extends Component implements IDataResultProcessor
{
    //use value of data array key '$value_key' to construct the value of this item
    protected $value_key;

    //use value of data array key '$label_key' to construct the label of this item
    protected $label_key;

    protected $data = array();

    protected $label = "";
    protected $value = "";

    protected $id = -1;

    //render html attributes from data_row
    protected $data_attributes = array();

    protected $selected = FALSE;

    protected $position = 0;


    public function setPosition(int $position)
    {
        $this->position = $position;
    }

    public function getPosition(int $position) : int
    {
        return $this->position;
    }

    /**
     * During setData set the attribute '$name' to the value of $row[$field]
     * @param string $name
     * @param string $field
     */
    public function addDataAttribute(string $name, string $field = "")
    {
        if (!$field) $field = $name;
        $this->data_attributes[$name] = $field;
    }

    public function getDataAttributes(): array
    {
        return $this->data_attributes;
    }

    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setID(int $id)
    {
        $this->id = $id;
    }

    public function getID(): int
    {
        return $this->id;
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

    public function getLabelKey(): string
    {
        return $this->label_key;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __construct()
    {
        parent::__construct();
        //$this->component_class = "";
    }

    protected function resetData()
    {
        $this->value = "";
        $this->label = "";

    }

    protected function processAttributes()
    {
        parent::processAttributes();

        if ($this->index > -1) {
            $this->setAttribute("index", $this->index);
        }
    }

    public function setSelected(bool $mode)
    {
        $this->selected = $mode;
    }

    public function isSelected()
    {
        return $this->selected;
    }

    public function getDataValue(string $key) : string
    {
        return $this->data[$key];
    }

    public function setData(array &$data)
    {
        $this->data = $data;
        //This class is reused during sequential render calls. Clear all state variables below


        foreach ($this->data_attributes as $attributeName => $fieldName) {
            if (isset($this->data[$fieldName])) {
                $this->setAttribute($attributeName, $this->data[$fieldName]);
            }
            else {
                $this->clearAttribute($attributeName);
            }
        }

        if ($this->value_key) {
            if (isset($data[$this->value_key])) {
                $this->value = isset($data[$this->value_key]) ? $data[$this->value_key] : "";
            }
            else {
                $this->value = "";
            }
        }

        if ($this->label_key) {
            if (isset($data[$this->label_key])) {
                $this->label = $data[$this->label_key];
            }
            else {
                $this->label = "";
            }
        }

    }

    public function renderSeparator(int $idx_curr, int $items_total)
    {

    }

}

?>