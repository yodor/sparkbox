<?php
include_once("lib/input/renderers/InputField.php");
include_once("lib/input/renderers/IDataSourceItem.php");

abstract class DataSourceField extends InputField
{

    /**
     * @var SQLQuery|null
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function setItemRenderer(IDataSourceItem $cmp)
    {
        $this->item = $cmp;
    }

    public function getItemRenderer() : IDataSourceItem
    {
        return $this->item;
    }

    public function renderImpl()
    {

        $this->iterator->exec();

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

        $field_values = $this->field->getValue();
        $field_name = $this->field->getName();

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

            $item = clone $this->item;
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