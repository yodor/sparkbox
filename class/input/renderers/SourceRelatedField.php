<?php
include_once("lib/input/renderers/DataSourceField.php");
include_once("lib/input/renderers/DataSourceItem.php");
include_once("class/beans/ClassAttributesBean.php");

class SourceAttributeItem extends DataSourceItem
{

    public function renderImpl()
    {

        echo "<label class='SourceAttributeName' data='attribute_name'>" . $this->label . "</label>";

        echo "<input class='SourceAttributeValue' data='attribute_value' type='text' value='{$this->value}' name='{$this->name}[]'>";

        echo "<input data='foreign_key' type='hidden' name='fk_{$this->name}[]' value='caID:{$this->id}'>";

        echo "<label class='SourceAttributeUnit' data='attribute_unit'>" . $this->data_row["attribute_unit"] . "</label>";
    }

}


class SourceRelatedField extends DataSourceField
{

    public function __construct()
    {
        parent::__construct();
        $this->setItemRenderer(new SourceAttributeItem());
    }

    //    public function setSource(IDataBean $source)
    //    {
    //        parent::setIterator($source);
    //        $this->addClassName(get_class($source));
    //    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "css/SourceRelatedField.css";
        return $arr;
    }

    public function renderControls()
    {

    }

    public function renderElementSource()
    {

    }

    public function renderArrayContents()
    {

    }

    public function renderImpl()
    {

        $num = $this->iterator->exec();

        if ($num < 1) {
            echo "Selected source does not provide optional attributes";
            return;
        }

        $this->startRenderItems();

        $this->renderItems();

        $this->finishRenderItems();

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

            // 		$id = $data_row[$this->getSource()->getPrKey()];
            $id = $data_row[$prkey];

            $value = isset($data_row[$field_name]) ? $data_row[$field_name] : "";
            $label = $data_row[$this->list_label];


            $item = clone $this->item;
            $item->setID($id);
            $item->setValue($value);
            $item->setLabel($label);
            $item->setName($field_name);
            $item->setIndex($index);

            $item->setDataRow($data_row);

            $item->render();

            $index++;
        }
    }
}

?>
