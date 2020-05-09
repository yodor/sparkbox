<?php
include_once("input/renderers/InputField.php");
include_once("input/renderers/DataIteratorItem.php");

abstract class DataIteratorField extends InputField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    public function renderImpl()
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

            $item->setID($id);
            $item->setName($field_name . "[]");
            $item->setIndex($index);

            $item->setData($data_row, $this->input);

            $item->render();

            $index++;
        }
    }

//    /**
//     * Iterator values construct the array
//     * Checkboxes post values directly
//     * Search inside values if iterator value is found
//     * @param $value
//     * @param $field_values
//     * @return bool
//     */
//    protected function isModelSelected($value, $field_values)
//    {
//        return (in_array($value, $field_values));
//    }


}

?>