<?php
include_once("input/renderers/InputField.php");
include_once("input/renderers/DataSourceItem.php");

abstract class DataSourceField extends InputField
{

    /**
     * @var DataSourceItem
     */
    protected $item;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
    }

    public function setItemRenderer(DataSourceItem $cmp)
    {
        $this->item = $cmp;
    }

    public function getItemRenderer() : DataSourceItem
    {
        return $this->item;
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

        $prkey = $this->iterator->key();

        $index = 0;

        while ($data_row = $this->iterator->next()) {

            $id = $data_row[$prkey];

            $value = isset($data_row[$this->list_key]) ? $data_row[$this->list_key] : "";

            $label = $data_row[$this->list_label];

            $selected = $this->isModelSelected($value, $field_values);

            $item = $this->item;
            //$item = clone $this->item;
            $item->setID($id);
            $item->setValue($value);
            $item->setLabel($label);
            $item->setName($field_name . "[]");
            $item->setIndex($index);
            $item->setSelected($selected);
            $item->setDataRow($data_row);

            $item->render();

            $index++;
        }
    }

    /**
     * Iterator values construct the array
     * Checkboxes post values directly
     * Search inside values if iterator value is found
     * @param $value
     * @param $field_values
     * @return bool
     */
    protected function isModelSelected($value, $field_values)
    {
        return (in_array($value, $field_values));
    }


}

?>