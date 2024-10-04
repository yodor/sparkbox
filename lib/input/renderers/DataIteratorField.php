<?php
include_once("input/renderers/InputField.php");
include_once("components/renderers/items/DataIteratorItem.php");

abstract class DataIteratorField extends InputField
{

    protected $array_key_field_name = "";
    protected $array_key_model_id = false;

    protected Container $elements;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->elements = new Container(false);
        $this->elements->setComponentClass("FieldElements");

        $this->items->append($this->elements);

        $this->elements->items()->append(new ClosureComponent($this->renderItems(...), false));
    }


    protected function renderItems() : void
    {

        if (!$this->iterator instanceof IDataIterator) {
            throw new Exception("IDataIterator not set");
        }
        if (!$this->item instanceof DataIteratorItem) {
            throw new Exception("DataIteratorItem not set");
        }

        $field_values = $this->dataInput->getValue();

        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }

        $position = 0;

        $num = $this->iterator->exec();

        //ArrayField appends [] to the dataInput
        $this->item->setName($this->dataInput->getName());

        while ($data = $this->iterator->next()) {

            $id = 0;
            if (isset($data[$this->iterator->key()])) {
                $id = $data[$this->iterator->key()];
            }

            $this->item->setID((int)$id);

            $this->item->setPosition($position);

            //sets value and label
            $this->item->setData($data);

            $this->item->setSelected($this->isModelSelected((string)$this->item->getValue()));

            $array_key_value = $position;
            if ($this->item->isSelected()) {
                if ($this->array_key_model_id) {
                    //the corresponding ID of the value from transact-bean of DataInput
                    $modelID = $this->getModelValueID();
                    if ($modelID > -1) {
                        $array_key_value = "modelID:" . $modelID;
                    }
                }
            }

            //override key
            if ($this->array_key_field_name && isset($data[$this->array_key_field_name])) {
                $array_key_value = $data[$this->array_key_field_name];
            }


            $this->item->setKey($array_key_value);

            $this->item->render();

            $position++;
        }

    }

    /**
     * Search 'this' item value inside DataInput values.
     * @return bool
     */
    protected function isModelSelected(string $item_value): bool
    {

        $field_values = $this->dataInput->getValue();

        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }
        return in_array($item_value, $field_values);
    }

    /**
     * Return the key of the DataInput value.
     * Return -1 if 'this' item value is not inside 'this' DataInput values.
     * @return int
     */
    protected function getModelValueID() : int|string
    {
        $field_values = $this->dataInput->getValue();
        if (!is_array($field_values)) return -1;
        $found = array_search($this->item->getValue(), $field_values);
        if ($found === FALSE) return -1;
        return $found;
    }

    /**
     * Set flag to enable using the modelID as array key. Actual value of the key will be prefixed with string - 'modelID:'
     * @param bool $mode
     */
    public function useArrayKeyModelID(bool $mode) : void
    {
        $this->array_key_model_id = $mode;
    }

    /**
     * Use iterator result value with key=$field_name as the array key
     * Overrides any other key naming like modelID
     * @param string $field_name
     */
    public function setArrayKeyFieldName(string $field_name) : void
    {
        $this->array_key_field_name = $field_name;
    }

}

?>
