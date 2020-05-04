<?php
include_once("lib/input/renderers/DataSourceField.php");
include_once("lib/input/renderers/DataSourceItem.php");
include_once("class/beans/ClassAttributesBean.php");

class SourceAttributeItem extends DataSourceItem
{

    //TODO: add methods to set 'caID' string and attribute_unit
    public function renderImpl()
    {
        echo "<label class='SourceAttributeName' data='attribute_name'>" . $this->label . "</label>";

        echo "<input class='SourceAttributeValue' data='attribute_value' type='text' value='{$this->value}' name='{$this->name}'>";

        echo "<input data='foreign_key' type='hidden' name='fk_{$this->name}' value='caID:{$this->id}'>";

        echo "<label class='SourceAttributeUnit' data='attribute_unit'>" . $this->data_row["attribute_unit"] . "</label>";
    }

}

class SourceRelatedField extends DataSourceField
{

    public function __construct()
    {
        parent::__construct();
        $this->setItemRenderer(new SourceAttributeItem());

        //       RequestController::addAjaxHandler(new SourceRelatedFieldAjaxHandler());

    }

    public function setIterator(IDataIterator $query)
    {
        parent::setIterator($query);
        $this->addClassName(get_class($query));
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "css/SourceRelatedField.css";
        return $arr;
    }

    protected function renderItems()
    {


        if ( $this->iterator->count() < 1) {
            echo tr("No optional attributes");
            return;
        }

        $this->list_key = $this->field->getName();

        parent::renderItems();
    }
}

?>
