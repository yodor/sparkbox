<?php
include_once("input/renderers/InputField.php");
include_once("components/renderers/items/DataIteratorItem.php");

abstract class DataIteratorField extends InputField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    protected function renderImpl()
    {

        $num = $this->iterator->exec();

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

        while ($data_row = $this->iterator->next()) {

            $id = $data_row[$this->iterator->key()];

            $item = $this->item;
            //$item = clone $this->item;

            $item->setID((int)$id);
            $item->setName($field_name . "[]");
            $item->setIndex($index);

            $item->setData($data_row);

            $item->setSelected($this->isModelSelected());
            $item->render();

            $index++;
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

}

?>