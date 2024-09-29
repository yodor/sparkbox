<?php
include_once("components/Container.php");
include_once("utils/IDataResultProcessor.php");

abstract class DataIteratorItem extends Container implements IDataResultProcessor
{
    //use value of data array key '$value_key' to construct the value of this item
    protected string $value_key = "";

    //use value of data array key '$label_key' to construct the label of this item
    protected string $label_key = "";

    protected array $data = array();

    protected string $label = "";

    protected $value = null;

    protected int $id = -1;

    //render html attributes from data_row
    protected array $data_attributes = array();

    protected bool $selected = false;

    protected bool $checked = false;

    protected int $position = 0;


    public function __construct(bool $chained_component_class = true)
    {
        parent::__construct($chained_component_class);
    }

    protected function resetData()
    {
        $this->value = "";
        $this->label = "";

    }

    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    public function getPosition() : int
    {
        return $this->position;
    }

    /**
     * Create attributes from the iterator data passed
     * Ex: $name = "title", $field = "product_name" => title = '$data["product_name"]' ;
     * Ex: $name = "title", $field = "" => title = '$data["title"]'
     * @param string $name Set html attribute '$name' using value from data result row key '$name'
     * @param string $field Override data result row key to '$field'
     */
    public function addDataAttribute(string $name, string $field = "") : void
    {
        if (!$field) $field = $name;
        $this->data_attributes[$name] = $field;
    }

    public function getDataAttributes(): array
    {
        return $this->data_attributes;
    }

    public function setLabel(string $label) : void
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setID(int $id) : void
    {
        $this->id = $id;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function setValueKey(string $field) : void
    {
        $this->value_key = $field;
    }

    public function getValueKey(): string
    {
        return $this->value_key;
    }

    public function setLabelKey(string $field) : void
    {
        $this->label_key = $field;
    }

    public function getLabelKey(): string
    {
        return $this->label_key;
    }

    public function setValue($value) : void
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        if ($this->index > -1) {
            $this->setAttribute("index", $this->index);
        }
    }

    public function setSelected(bool $mode) : void
    {
        $this->selected = $mode;
    }

    public function isSelected() : bool
    {
        return $this->selected;
    }

    public function setChecked(bool $mode) : void
    {
        $this->checked = $mode;
    }

    public function isChecked() : bool
    {
        return $this->checked;
    }

    public function getDataValue(string $key) : string
    {
        return $this->data[$key];
    }

    public function setData(array $data) : void
    {
        $this->data = $data;
        //This class is reused during sequential render calls. Clear all state variables below
        $this->value = $data[$this->value_key] ?? "";
        $this->label = $data[$this->label_key] ?? "";

        foreach ($this->data_attributes as $attributeName => $fieldName) {
            if (isset($this->data[$fieldName])) {
                $this->setAttribute($attributeName, $this->data[$fieldName]);
            }
            else {
                $this->removeAttribute($attributeName);
            }
        }

    }

    public function renderSeparator(int $idx_curr, int $items_total)
    {

    }

}

?>
