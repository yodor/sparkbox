<?php
include_once("lib/input/renderers/InputField.php");
include_once("lib/input/renderers/IDataSourceItem.php");

abstract class DataSourceField extends InputField
{

    public function __construct()
    {
        parent::__construct();


    }

    public function setItemRenderer(IDataSourceItem $cmp)
    {
        $this->item = $cmp;
    }

    public function getItemRenderer()
    {
        return $this->item;
    }

    public function renderImpl()
    {

        if ($this->data_bean instanceof IDataBean) {

            $source_fields = $this->data_bean->fields();

            if (!in_array($this->list_key, $source_fields)) throw new Exception("List Key '{$this->list_key}' not found in data source fields");
            if (!in_array($this->list_label, $source_fields)) throw new Exception("List Label '{$this->list_label}' not found in data source fields");

            $this->data_bean->startIterator($this->data_filter, $this->data_fields);

            $this->startRenderItems();

            $this->renderItems();

            $this->finishRenderItems();
        }

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

        $prkey = $this->data_bean->key();
        $index = 0;

        while ($this->data_bean->fetchNext($data_row)) {

            $id = $data_row[$prkey];

            $value = $data_row[$this->list_key];
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

    protected function isModelSelected($value, $field_values)
    {
        return (in_array($value, $field_values));
    }


}

?>