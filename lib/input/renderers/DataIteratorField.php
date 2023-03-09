<?php
include_once("input/renderers/InputField.php");
include_once("components/renderers/items/DataIteratorItem.php");

abstract class DataIteratorField extends InputField
{

    protected $array_key_field_name = "";
    protected $array_key_model_id = false;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    protected function renderImpl()
    {

        if ($this->iterator instanceof IDataIterator) {
            $num = $this->iterator->exec();
        }

        $this->startRenderItems();
        $this->renderItems();
        $this->finishRenderItems();


    }

    protected function startRenderItems()
    {
        echo "<div class='FieldElements'>";
    }

    protected function finishRenderItems()
    {
        echo "</div>";
    }

    protected function renderItems()
    {

        $field_values = $this->input->getValue();
        $field_name = $this->input->getName();

        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }

        $index = 0;

        if ($this->iterator instanceof IDataIterator) {
            while ($data_row = $this->iterator->next()) {

                $id = $data_row[$this->iterator->key()];

                $item = $this->item;
                //$item = clone $this->item;

                $item->setID((int)$id);
                $item->setIndex($index);
                //sets the actual value of the item being rendered
                $item->setData($data_row);

                $isSelected = $this->isModelSelected();
                $item->setSelected($isSelected);

                $array_key_value = "";
                if ($isSelected) {
                    if ($this->array_key_model_id) {
                        //the corresponding ID of the value from transact-bean of DataInput
                        $modelID = $this->getModelValueID();
                        if ($modelID > -1) {
                            $array_key_value = "modelID:" . $modelID;
                        }
                    }
                }
                //override key
                if ($this->array_key_field_name && isset($data_row[$this->array_key_field_name])) {
                    $array_key_value = $data_row[$this->array_key_field_name];
                }
                $item->setName($field_name . "[".$array_key_value."]");

                $item->render();

                $index++;
            }
        }
    }

    /**
     * Search 'this' item value inside DataInput values.
     * @return bool
     */
    protected function isModelSelected(): bool
    {
        $field_values = $this->input->getValue();
        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }
        return (in_array($this->item->getValue(), $field_values));
    }

    /**
     * Return the key of the DataInput value.
     * Return -1 if 'this' item value is not inside 'this' DataInput values.
     * @return int
     */
    protected function getModelValueID() : int
    {
        $field_values = $this->input->getValue();
        if (!is_array($field_values)) return -1;
        $found = array_search($this->item->getValue(), $field_values);
        if ($found === FALSE) return -1;
        return $found;
    }

    /**
     * Set flag to enable using the modelID as array key. Actual value of the key will be prefixed with string - 'modelID:'
     * @param bool $mode
     */
    public function useArrayKeyModelID(bool $mode)
    {
        $this->array_key_model_id = $mode;
    }

    /**
     * Use iterator result value with key=$field_name as the array key
     * Overrides any other key naming like modelID
     * @param string $field_name
     */
    public function setArrayKeyFieldName(string $field_name)
    {
        $this->array_key_field_name = $field_name;
    }

}

?>