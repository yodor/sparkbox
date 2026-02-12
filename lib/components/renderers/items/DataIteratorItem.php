<?php
include_once("components/Container.php");
include_once("utils/IDataResultProcessor.php");

/**
 * Used to render sequential data values.
 * Created once used many times with different data.
 */
abstract class DataIteratorItem extends Container implements IDataResultProcessor
{
    //use value of data array key '$value_key' to construct the value of this item
    protected string $value_key = "";

    //use value of data array key '$label_key' to construct the label of this item
    protected string $label_key = "";

    protected array $data = array();

    protected string $label = "";

    protected mixed $value = null;

    protected int $id = -1;

    //render html attributes from data_row
    protected array $data_attributes = array();

    protected bool $selected = false;

    protected bool $checked = false;

    protected int $position = -1;

    /**
     * Model index key value
     * @var string|null
     */
    protected ?string $key = null;

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("DataIteratorItem");
    }

    /**
     * Sequential position during iteration
     * @param int $position
     * @return void
     */
    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    public function getPosition() : int
    {
        return $this->position;
    }


    /**
     * Set the array key value ie name[$key] will be output as html attribute name
     * @param string|null $key
     * @return void
     */
    public function setKey(?string $key) : void
    {
        $this->key = $key;
    }

    /**
     * Get the array key value
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
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

    /**
     * Primary Key value from the result row
     * @param int $id
     * @return void
     */
    public function setID(int $id) : void
    {
        $this->id = $id;
    }

    public function getID(): int
    {
        return $this->id;
    }

    /**
     * Data key value to use as value of this item during setData
     * @param string $field
     * @return void
     */
    public function setValueKey(string $field) : void
    {
        $this->value_key = $field;
    }

    /**
     * Get data key in use for value
     * @return string
     */
    public function getValueKey(): string
    {
        return $this->value_key;
    }

    /**
     * Data key value to use as label of this item during setData
     * @param string $field
     * @return void
     */
    public function setLabelKey(string $field) : void
    {
        $this->label_key = $field;
    }

    /**
     * Get data key in use for label
     * @return string
     */
    public function getLabelKey(): string
    {
        return $this->label_key;
    }

    public function setValue(mixed $value) : void
    {
        $this->value = $value;
    }

    public function getValue() : mixed
    {
        return $this->value;
    }

    /**
     * Return string to be used as name attribute of this item
     * Default is to use "$this->name"."[$this->key]"
     * Ex name='input1' key='1' => "input1[1]"
     * If key is null just $this->getName() is returned
     * @return string
     */
    protected function createInputName() : string
    {
        $name = $this->getName();
        if (!is_null($this->key)) {
            $name = $name."[".$this->key."]";
        }
        return $name;
    }

    /**
     * Assign attributes pos. key, name to the Component passed in $input
     * Uses createInputName to set the actual name attribute
     * Called from processAttributes
     * Fields with complex elements ie CheckItem, RadioItem override getInput() and return the actual input item
     * Component $input might be 'this' if getInput() is not reimplemented
     * @param Component $input
     * @return void
     */
    protected function assignInputAttributes(Component $input) : void
    {
        if ($this->position>-1) {
            $input->setAttribute("pos", $this->position);
        }

        if (!is_null($this->key)) {
            $input->setAttribute("key", $this->key);
        }
        else {
            $input->removeAttribute("key");
        }

        $name = $this->createInputName();

        //overwrite the name - actual attribute will be set during processAttributes of $input
        $input->setName($name);

    }

    /**
     * Return '$this' for passing to assignInputAttributes
     * Fields with complex elements ie CheckItem, RadioItem override this method and return the actual input component here
     * @return Component
     */
    public function getInput() : Component
    {
        return $this;
    }

    /**
     * Item renderers are reused for each iterator step so clear values
     * Uses assignInputAttributes(getInput())
     * @return void
     */
    protected function processAttributes(): void
    {
        $this->assignInputAttributes($this->getInput());

        //set actual name attribute using $this->name
        //parent::processAttributes();
        if ($this->name) {
            $this->setAttribute("name", $this->name);
        }
        else {
            $this->removeAttribute("name");
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

    public function getData() : array
    {
        return $this->data;
    }

    public function setData(array $data) : void
    {
        $this->data = $data;
        //This class is reused during sequential render calls. Clear all state variables below
        $this->value = $data[$this->value_key] ?? "";
        $this->label = $data[$this->label_key] ?? "";

        foreach ($this->data_attributes as $attributeName => $fieldName) {
            if (isset($data[$fieldName])) {
                $this->setAttribute($attributeName, $data[$fieldName]);
            }
            else {
                $this->removeAttribute($attributeName);
            }
        }
    }

}