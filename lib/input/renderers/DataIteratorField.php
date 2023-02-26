<?php
include_once("input/renderers/InputField.php");
include_once("components/renderers/items/DataIteratorItem.php");

abstract class DataIteratorField extends InputField
{

    protected $array_key_field = "";

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

                $array_key_value = "";
                if ($this->array_key_field && isset($data_row[$this->array_key_field])) {
                    $array_key_value = $data_row[$this->array_key_field];
                }
                $item->setName($field_name . "[".$array_key_value."]");
                $item->setIndex($index);

                $item->setData($data_row);

                $item->setSelected($this->isModelSelected());
                $item->render();

                $index++;
            }
        }
    }

    /**
     * Iterator values construct the array
     * Checkboxes post values directly
     * Search inside values if iterator value is found
     * @param DataInput $input
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

    public function setArrayKeyDataField(string $field_name)
    {
        $this->array_key_field = $field_name;
    }
}

?>